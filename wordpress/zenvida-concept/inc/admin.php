<?php
/**
 * Admin for non-technical editors:
 *  - Ritual details form (photos from the media library, price rows, category, visibility)
 *  - Ritual list with photo, show/hide switch and drag-and-drop order
 *  - Area / Need fields (colour, photo, long text, order)
 *  - A simpler admin: tidy menu, quick guide on the dashboard, only useful blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ assets */

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = get_current_screen();
	$ours   = ( $screen && ( in_array( $screen->post_type, array( 'ritual', 'zv_popup', 'zv_event' ), true ) || in_array( $screen->taxonomy, array( 'area', 'need' ), true ) ) )
		|| false !== strpos( $hook, 'zv-settings' ) || 'index.php' === $hook;
	if ( ! $ours ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );
	$ver = fn( $f ) => ZV_VERSION . '.' . filemtime( get_template_directory() . $f );
	wp_enqueue_style( 'zv-admin', get_template_directory_uri() . '/assets/css/admin.css', array(), $ver( '/assets/css/admin.css' ) );
	wp_enqueue_script( 'zv-admin', get_template_directory_uri() . '/assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), $ver( '/assets/js/admin.js' ), true );
	wp_localize_script( 'zv-admin', 'ZV_ADMIN', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'zv_admin' ),
	) );
} );

/** Photo field: preview + "Choose photo" / "Remove". Stores the image URL. */
function zv_media_field( $name, $value, $label, $help = '' ) {
	$value = (string) $value;
	?>
	<div class="zv-media" data-zv-media>
		<span class="zv-label"><?php echo esc_html( $label ); ?></span>
		<div class="zv-media-preview"<?php echo $value ? '' : ' hidden'; ?>><img src="<?php echo esc_url( $value ); ?>" alt=""></div>
		<div class="zv-media-empty"<?php echo $value ? ' hidden' : ''; ?>>No photo yet</div>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<p>
			<button type="button" class="button button-primary" data-zv-pick><?php echo $value ? 'Change photo' : 'Choose photo'; ?></button>
			<button type="button" class="button-link zv-remove" data-zv-clear<?php echo $value ? '' : ' hidden'; ?>>Remove</button>
		</p>
		<?php if ( $help ) : ?><p class="description"><?php echo esc_html( $help ); ?></p><?php endif; ?>
	</div>
	<?php
}

/**
 * May a meta box save? Only when its fields were part of the submitted form, and either the
 * box's own nonce or WordPress's post nonce (kept fresh by the editor) is valid. A long-open
 * edit screen therefore still saves prices, photos and dates instead of silently dropping them.
 */
function zv_box_can_save( $post_id, $field, $action ) {
	if ( ! isset( $_POST[ $field ] ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) { // phpcs:ignore
		return false;
	}
	$own  = wp_verify_nonce( sanitize_key( $_POST[ $field ] ), $action ); // phpcs:ignore
	$core = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'update-post_' . $post_id ); // phpcs:ignore
	return (bool) ( $own || $core );
}

/* ------------------------------------------------------ ritual details form */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'zv_details', 'Ritual details — price, photos, category', 'zv_details_box', 'ritual', 'normal', 'high' );
} );

/** Popularity presets shown as plain words. */
function zv_pop_levels() {
	return array( 2 => 'Low', 5 => 'Normal', 7 => 'Popular', 10 => 'Best seller' );
}

function zv_details_box( $post ) {
	wp_nonce_field( 'zv_details', 'zv_details_nonce' );
	$m      = fn( $k ) => get_post_meta( $post->ID, $k, true );
	$opts   = get_post_meta( $post->ID, 'zv_options', true ) ? zv_parse_options( $m( 'zv_options' ) ) : array( array( 60, null ) );
	$hl     = array_filter( array_map( 'trim', explode( "\n", (string) $m( 'zv_highlights' ) ) ) );
	$hl     = $hl ? array_map( fn( $l ) => array_pad( array_map( 'trim', explode( '|', $l, 2 ) ), 2, '' ), $hl ) : array( array( '', '' ) );
	$area   = get_the_terms( $post, 'area' );
	$area   = ( $area && ! is_wp_error( $area ) ) ? $area[0]->term_id : 0;
	if ( ! $area && 'auto-draft' === $post->post_status ) {
		$first = zv_terms_ordered( 'area' );
		$area  = $first ? $first[0]->term_id : 0; // New ritual: preselect the first area.
	}
	$needs  = wp_get_object_terms( $post->ID, 'need', array( 'fields' => 'ids' ) );
	$needs  = is_wp_error( $needs ) ? array() : $needs;
	$pop    = (int) $m( 'zv_popularity' );
	$levels = zv_pop_levels();
	$near   = array_reduce( array_keys( $levels ), fn( $c, $l ) => null === $c || abs( $l - $pop ) < abs( $c - $pop ) ? $l : $c );
	$lead   = $m( 'zv_lead' ) ?: $post->post_excerpt;
	?>
	<div class="zv-box">
		<p class="zv-name"><label class="zv-label" for="zv_title_sync">Ritual name</label>
			<input type="text" id="zv_title_sync" class="widefat" value="<?php echo esc_attr( 'auto-draft' === $post->post_status ? '' : $post->post_title ); ?>" placeholder="e.g. Hot Stone Foot Ritual" autocomplete="off"></p>
		<div class="zv-visible">
			<label class="zv-switch"><input type="checkbox" name="zv_visible" value="1" <?php checked( ! $m( 'zv_hidden' ) ); ?>><span></span></label>
			<div><b>Show on the website</b><br><span class="description">Switch off to hide this ritual everywhere (rituals page, homepage, its own page). You can switch it back on any time.</span></div>
		</div>

		<h3>1. Price &amp; duration</h3>
		<table class="zv-rows" data-zv-repeat>
			<thead><tr><th>Minutes</th><th>Price (€)</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $opts as $o ) : ?>
				<tr data-zv-row>
					<td data-label="Minutes"><input type="number" min="5" step="5" name="zv_opt_min[]" value="<?php echo esc_attr( $o[0] ); ?>"></td>
					<td data-label="Price (€)"><input type="number" min="0" step="1" name="zv_opt_price[]" value="<?php echo esc_attr( $o[1] ?? '' ); ?>" placeholder="At studio"></td>
					<td><button type="button" class="button-link zv-remove" data-zv-del>Remove</button></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" data-zv-add>+ Add another duration</button> <span class="description">Leave the price empty to show "Price at studio".</span></p>
		<p class="zv-inline">
			<label>Deposit paid online <span class="zv-euro"><input type="number" min="0" name="zv_deposit" value="<?php echo esc_attr( $m( 'zv_deposit' ) ?: 25 ); ?>"></span></label>
			<label><input type="checkbox" name="zv_couples" value="1" <?php checked( (bool) $m( 'zv_couples' ) ); ?>> Price is for two people (couples ritual)</label>
			<label><input type="checkbox" name="zv_baths" value="1" <?php checked( (bool) $m( 'zv_baths' ) ); ?>> Offer foot-bath upgrades when booking</label>
		</p>

		<h3>2. Photos</h3>
		<div class="zv-photos">
			<?php
			zv_media_field( 'zv_image', $m( 'zv_image' ), 'Main photo', 'Used on this page, on the rituals page and on the homepage.' );
			zv_media_field( 'zv_gallery_1', $m( 'zv_gallery_1' ), 'Extra photo 1 (optional)' );
			zv_media_field( 'zv_gallery_2', $m( 'zv_gallery_2' ), 'Extra photo 2 (optional)' );
			?>
		</div>

		<h3>3. Texts</h3>
		<p><label class="zv-label" for="zv_lead">Intro under the title</label>
			<textarea id="zv_lead" name="zv_lead" rows="3" class="widefat"><?php echo esc_textarea( $lead ); ?></textarea></p>
		<p><label class="zv-label" for="zv_desc">Short description on the rituals page (one sentence)</label>
			<textarea id="zv_desc" name="zv_desc" rows="2" class="widefat"><?php echo esc_textarea( $m( 'zv_desc' ) ); ?></textarea></p>
		<span class="zv-label">Highlights (the short list under the intro)</span>
		<table class="zv-rows wide" data-zv-repeat>
			<thead><tr><th>Bold part</th><th>Rest of the line</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( $hl as $h ) : ?>
				<tr data-zv-row>
					<td data-label="Bold part"><input type="text" name="zv_hl_title[]" value="<?php echo esc_attr( $h[0] ); ?>" placeholder="e.g. Hot stones"></td>
					<td data-label="Rest of the line"><input type="text" name="zv_hl_text[]" value="<?php echo esc_attr( $h[1] ); ?>" placeholder="e.g. warm basalt along the arches"></td>
					<td><button type="button" class="button-link zv-remove" data-zv-del>Remove</button></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" data-zv-add>+ Add highlight</button></p>

		<h3>4. Category &amp; labels</h3>
		<p class="zv-inline">
			<label>Area
				<select name="zv_area">
					<?php foreach ( zv_terms_ordered( 'area' ) as $t ) : ?>
						<option value="<?php echo (int) $t->term_id; ?>" <?php selected( $area, $t->term_id ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>How popular is it?
				<select name="zv_popularity">
					<?php foreach ( $levels as $v => $l ) : ?>
						<option value="<?php echo (int) $v; ?>" <?php selected( $near, $v ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<p class="description">Popular rituals are listed first in "Recommended" and on the homepage.</p>
		<span class="zv-label">Good for (the "how do you feel" filters)</span>
		<p class="zv-checks">
			<?php foreach ( zv_terms_ordered( 'need' ) as $t ) : ?>
				<label><input type="checkbox" name="zv_needs[]" value="<?php echo (int) $t->term_id; ?>" <?php checked( in_array( $t->term_id, $needs, true ) ); ?>> <?php echo esc_html( $t->name ); ?></label>
			<?php endforeach; ?>
		</p>
		<p class="zv-checks">
			<label><input type="checkbox" name="zv_signature" value="1" <?php checked( (bool) $m( 'zv_signature' ) ); ?>> <b>Signature</b> — badge + shown on the homepage</label>
			<label><input type="checkbox" name="zv_seasonal" value="1" <?php checked( (bool) $m( 'zv_seasonal' ) ); ?>> <b>Seasonal</b> — badge</label>
		</p>
		<p class="description">The sections further down this page (steps, FAQ, visit…) are edited in the editor above: click a text to change it, use + to add a question.</p>
	</div>
	<?php
}

add_action( 'save_post_ritual', function ( $post_id ) {
	if ( ! zv_box_can_save( $post_id, 'zv_details_nonce', 'zv_details' ) ) {
		return;
	}
	$in = wp_unslash( $_POST );

	// Durations & prices → "60:80, 90:110"
	$pairs = array();
	foreach ( (array) ( $in['zv_opt_min'] ?? array() ) as $i => $min ) {
		$min = absint( $min );
		if ( $min ) {
			$price   = trim( (string) ( $in['zv_opt_price'][ $i ] ?? '' ) );
			$pairs[] = $min . ':' . ( '' === $price ? '' : absint( $price ) );
		}
	}
	update_post_meta( $post_id, 'zv_options', implode( ', ', $pairs ) );

	// Highlights → "Title | text" lines
	$lines = array();
	foreach ( (array) ( $in['zv_hl_title'] ?? array() ) as $i => $t ) {
		$t = sanitize_text_field( $t );
		$x = sanitize_text_field( $in['zv_hl_text'][ $i ] ?? '' );
		if ( '' !== $t || '' !== $x ) {
			$lines[] = $t . ' | ' . $x;
		}
	}
	update_post_meta( $post_id, 'zv_highlights', implode( "\n", $lines ) );

	update_post_meta( $post_id, 'zv_desc', sanitize_textarea_field( $in['zv_desc'] ?? '' ) );
	update_post_meta( $post_id, 'zv_lead', sanitize_textarea_field( $in['zv_lead'] ?? '' ) );
	foreach ( array( 'zv_image', 'zv_gallery_1', 'zv_gallery_2' ) as $k ) {
		update_post_meta( $post_id, $k, esc_url_raw( $in[ $k ] ?? '' ) );
	}
	foreach ( array( 'zv_signature', 'zv_seasonal', 'zv_couples', 'zv_baths' ) as $k ) {
		update_post_meta( $post_id, $k, empty( $in[ $k ] ) ? '' : '1' );
	}
	update_post_meta( $post_id, 'zv_hidden', empty( $in['zv_visible'] ) ? '1' : '' );
	update_post_meta( $post_id, 'zv_deposit', absint( $in['zv_deposit'] ?? 25 ) );
	update_post_meta( $post_id, 'zv_popularity', min( 10, absint( $in['zv_popularity'] ?? 5 ) ) );

	$area = absint( $in['zv_area'] ?? 0 );
	wp_set_object_terms( $post_id, $area ? array( $area ) : array(), 'area' );
	wp_set_object_terms( $post_id, array_map( 'absint', (array) ( $in['zv_needs'] ?? array() ) ), 'need' );
} );

// New rituals go to the end of the list.
add_filter( 'wp_insert_post_data', function ( $data, $arr ) {
	if ( 'ritual' === $data['post_type'] && empty( $arr['ID'] ) && 0 === (int) $data['menu_order'] ) {
		global $wpdb;
		$data['menu_order'] = 1 + (int) $wpdb->get_var( "SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = 'ritual'" );
	}
	return $data;
}, 10, 2 );

/* --------------------------------------------- ritual list: photo, switch, order */

add_filter( 'manage_ritual_posts_columns', function ( $cols ) {
	$out = array( 'cb' => $cols['cb'], 'zv_drag' => '', 'zv_thumb' => 'Photo', 'title' => 'Ritual' );
	$out['zv_price']   = 'Durations & prices';
	$out['zv_visible'] = 'On website';
	$out['taxonomy-area'] = 'Area';
	return $out;
} );

add_action( 'manage_ritual_posts_custom_column', function ( $col, $id ) {
	if ( 'zv_drag' === $col ) {
		echo '<span class="zv-handle dashicons dashicons-menu" title="Drag to reorder"></span>';
	} elseif ( 'zv_thumb' === $col ) {
		$img = get_post_meta( $id, 'zv_image', true );
		echo $img ? '<img class="zv-thumb" src="' . esc_url( $img ) . '" alt="">' : '<span class="zv-thumb empty"></span>';
	} elseif ( 'zv_price' === $col ) {
		echo esc_html( implode( ' · ', array_map( fn( $o ) => $o[0] . ' min ' . zv_eur( $o[1] ), zv_parse_options( get_post_meta( $id, 'zv_options', true ) ) ) ) );
	} elseif ( 'zv_visible' === $col ) {
		$on = ! get_post_meta( $id, 'zv_hidden', true ) && 'publish' === get_post_status( $id );
		printf(
			'<label class="zv-switch" title="Show or hide on the website"><input type="checkbox" data-zv-visible="%d" %s><span></span></label>',
			(int) $id,
			checked( $on, true, false )
		);
	}
}, 10, 2 );

add_filter( 'edit_ritual_per_page', fn() => 200 );

add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( $s && 'edit-ritual' === $s->id ) {
		echo '<div class="notice notice-info zv-tip"><p><b>Tip:</b> drag <span class="dashicons dashicons-menu"></span> to change the order on the website (on a computer) · use the switch to show or hide a ritual · click a name to change its price, photos and texts.</p></div>';
	}
} );

add_action( 'wp_ajax_zv_visible', function () {
	check_ajax_referer( 'zv_admin' );
	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id || ! in_array( get_post_type( $id ), array( 'ritual', 'zv_event' ), true ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( 'Not allowed', 403 );
	}
	$on = ! empty( $_POST['on'] ) && '0' !== $_POST['on'];
	if ( $on && 'publish' !== get_post_status( $id ) ) {
		$type = get_post_type_object( get_post_type( $id ) );
		if ( ! in_array( get_post_status( $id ), array( 'draft', 'pending' ), true ) || ! current_user_can( $type->cap->publish_posts ) ) {
			wp_send_json_error( 'This item is not published yet. Open it and click Publish.', 400 );
		}
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	}
	update_post_meta( $id, 'zv_hidden', $on ? '' : '1' );
	clean_post_cache( $id );
	wp_send_json_success( array( 'on' => $on ) );
} );

add_action( 'wp_ajax_zv_sort', function () {
	check_ajax_referer( 'zv_admin' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Not allowed', 403 );
	}
	global $wpdb;
	foreach ( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) as $i => $id ) {
		if ( $id && 'ritual' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $i + 1 ), array( 'ID' => $id ) );
			clean_post_cache( $id );
		}
	}
	wp_send_json_success();
} );

/* ------------------------------------------- Area & Need: colour, photo, order */

function zv_term_fields( $term, $tax ) {
	$v = fn( $k, $d = '' ) => $term ? ( get_term_meta( $term->term_id, $k, true ) ?: $d ) : $d;
	$rows = array();
	if ( 'area' === $tax ) {
		$rows[] = array( 'Photo', function () use ( $v ) {
			zv_media_field( 'zv_image', $v( 'zv_image' ), '', 'Shown on the area tile and at the top of the area page.' );
		} );
		$rows[] = array( 'Colour', function () use ( $v ) {
			echo '<input type="text" class="zv-color" name="zv_color" value="' . esc_attr( $v( 'zv_color', '#B0956A' ) ) . '">';
		} );
		$rows[] = array( 'Longer text', function () use ( $v ) {
			echo '<textarea name="zv_long" rows="4" class="large-text">' . esc_textarea( $v( 'zv_long' ) ) . '</textarea><p class="description">Shown on the area page under the short description.</p>';
		} );
	}
	$rows[] = array( 'Position', function () use ( $v ) {
		echo '<input type="number" name="zv_order" min="0" value="' . esc_attr( $v( 'zv_order', '' ) ) . '" style="width:80px"><p class="description">1 = first. Leave empty to put it last. Controls the order of filters and tiles.</p>';
	} );
	foreach ( $rows as $r ) {
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row">' . esc_html( $r[0] ) . '</th><td>';
			$r[1]();
			echo '</td></tr>';
		} else {
			echo '<div class="form-field"><label>' . esc_html( $r[0] ) . '</label>';
			$r[1]();
			echo '</div>';
		}
	}
	wp_nonce_field( 'zv_term', 'zv_term_nonce' );
}

foreach ( array( 'area', 'need' ) as $zv_tax ) {
	add_action( "{$zv_tax}_add_form_fields", fn() => zv_term_fields( null, $zv_tax ) );
	add_action( "{$zv_tax}_edit_form_fields", fn( $term ) => zv_term_fields( $term, $zv_tax ) );
	$zv_save = function ( $term_id ) {
		if ( ! isset( $_POST['zv_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zv_term_nonce'] ), 'zv_term' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$in = wp_unslash( $_POST );
		if ( isset( $in['zv_image'] ) ) {
			update_term_meta( $term_id, 'zv_image', esc_url_raw( $in['zv_image'] ) );
		}
		if ( isset( $in['zv_color'] ) ) {
			update_term_meta( $term_id, 'zv_color', sanitize_hex_color( $in['zv_color'] ) ?: '#B0956A' );
		}
		if ( isset( $in['zv_long'] ) ) {
			update_term_meta( $term_id, 'zv_long', sanitize_textarea_field( $in['zv_long'] ) );
		}
		$pos = absint( $in['zv_order'] ?? 0 );
		if ( ! $pos ) { // empty: put it last
			$pos = 1 + max( array_map( fn( $t ) => (int) get_term_meta( $t, 'zv_order', true ), get_terms( array( 'taxonomy' => get_term( $term_id )->taxonomy, 'hide_empty' => false, 'fields' => 'ids', 'exclude' => array( $term_id ) ) ) ?: array( 0 ) ) );
		}
		update_term_meta( $term_id, 'zv_order', $pos );
	};
	add_action( "created_{$zv_tax}", $zv_save );
	add_action( "edited_{$zv_tax}", $zv_save );
	add_filter( "manage_edit-{$zv_tax}_columns", function ( $cols ) use ( $zv_tax ) {
		unset( $cols['slug'] );
		return array_slice( $cols, 0, 1 ) + ( 'area' === $zv_tax ? array( 'zv_thumb' => 'Photo' ) : array() ) + array_slice( $cols, 1 ) + array( 'zv_order' => 'Position' );
	} );
	add_filter( "manage_{$zv_tax}_custom_column", function ( $out, $col, $id ) {
		if ( 'zv_thumb' === $col ) {
			$img = get_term_meta( $id, 'zv_image', true );
			return $img ? '<img class="zv-thumb" src="' . esc_url( $img ) . '" alt="">' : '';
		}
		return 'zv_order' === $col ? (string) (int) get_term_meta( $id, 'zv_order', true ) : $out;
	}, 10, 3 );
}

/* ---------------------------------------------------------- simpler admin */

// No comments on this site.
add_action( 'init', function () {
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'page', 'comments' );
} );
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_action( 'admin_menu', function () {
	remove_menu_page( 'edit-comments.php' );
}, 99 );
add_action( 'admin_bar_menu', function ( $bar ) {
	$bar->remove_node( 'comments' );
	$bar->remove_node( 'wp-logo' );
}, 99 );

// "Posts" are the Journal.
add_filter( 'register_post_type_args', function ( $args, $type ) {
	if ( 'post' === $type ) {
		$args['labels'] = array(
			'name'          => 'Journal',
			'singular_name' => 'Journal post',
			'menu_name'     => 'Journal',
			'all_items'     => 'All journal posts',
			'add_new_item'  => 'Add journal post',
			'edit_item'     => 'Edit journal post',
		);
		$args['menu_icon'] = 'dashicons-book-alt';
	}
	return $args;
}, 10, 2 );

// Menu order: the things an editor touches most come first.
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', fn() => array( 'index.php', 'zv-settings', 'edit.php?post_type=ritual', 'edit.php?post_type=zv_event', 'edit.php?post_type=zv_popup', 'edit.php?post_type=page', 'edit.php', 'upload.php', 'separator1', 'themes.php' ) );

// Dashboard: a quick guide instead of WordPress news.
add_action( 'wp_dashboard_setup', function () {
	foreach ( array( 'dashboard_quick_press', 'dashboard_primary', 'dashboard_site_health', 'dashboard_activity', 'dashboard_right_now' ) as $w ) {
		remove_meta_box( $w, 'dashboard', 'normal' );
		remove_meta_box( $w, 'dashboard', 'side' );
	}
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
	wp_add_dashboard_widget( 'zv_guide', 'ZenVida — what do you want to change?', 'zv_dashboard_guide' );
} );

function zv_dashboard_guide() {
	$home  = (int) get_option( 'page_on_front' );
	$rit   = (int) zv_opt( 'rituals_page' );
	$deluxe = get_page_by_path( 'deluxe-reflexology', OBJECT, 'ritual' );
	$tasks = array(
		array( 'money-alt', 'Change a price or a photo of a ritual', 'Open the ritual: the form with prices and photos opens first.', admin_url( 'edit.php?post_type=ritual' ), 'Rituals' ),
		array( 'visibility', 'Hide or show a ritual', 'Use the switch in the "On website" column.', admin_url( 'edit.php?post_type=ritual' ), 'Rituals' ),
		array( 'sort', 'Change the order of rituals', 'Drag the ☰ handle in the rituals list.', admin_url( 'edit.php?post_type=ritual' ), 'Rituals' ),
		array( 'plus-alt', 'Add a new ritual', 'Type the name, price and texts, choose a photo, click Publish (top right). FAQ and "Visit us" are added for you.', admin_url( 'post-new.php?post_type=ritual' ), 'Add ritual' ),
		array( 'edit', 'Change the homepage text, buttons or photos', 'Click any text to type. Click a photo or button to change it in the right panel.', $home ? get_edit_post_link( $home ) : admin_url( 'edit.php?post_type=page' ), 'Edit homepage' ),
		array( 'editor-help', 'Add or remove an FAQ question', 'Click a question, then + below it to add, or ⋮ → Delete to remove.', $deluxe ? get_edit_post_link( $deluxe ) : admin_url( 'edit.php?post_type=ritual' ), 'Example page' ),
		array( 'calendar-alt', 'Add an event (workshop, open day, special week)', 'Name, dates, photo, price and a description. It moves to "Past events" by itself.', admin_url( 'post-new.php?post_type=zv_event' ), 'Add event' ),
		array( 'megaphone', 'Create a popup for an event', 'Photo, title, button, start and end date. It switches on and off by itself.', admin_url( 'post-new.php?post_type=zv_popup' ), 'Add popup' ),
		array( 'clock', 'Opening hours, phone, address, promo bar', 'One page for everything that appears on every page.', admin_url( 'admin.php?page=zv-settings' ), 'Site settings' ),
		array( 'menu', 'Change the menu', 'Add, remove or drag menu items.', admin_url( 'nav-menus.php' ), 'Menus' ),
		array( 'format-image', 'Change the logo', 'Upload your logo under "Site Identity".', admin_url( 'customize.php?autofocus[section]=title_tagline' ), 'Logo' ),
	);
	if ( $rit ) {
		array_splice( $tasks, 6, 0, array( array( 'filter', 'Change the rituals page texts', 'The intro, tiles and FAQ on /rituals/.', get_edit_post_link( $rit ), 'Edit rituals page' ) ) );
	}
	echo '<ul class="zv-guide">';
	foreach ( $tasks as $t ) {
		printf(
			'<li><span class="dashicons dashicons-%s"></span><div><b>%s</b><small>%s</small></div><a class="button" href="%s">%s</a></li>',
			esc_attr( $t[0] ), esc_html( $t[1] ), esc_html( $t[2] ), esc_url( $t[3] ), esc_html( $t[4] )
		);
	}
	echo '</ul><p class="zv-guide-foot">Made a mistake? In the editor, press <b>Undo</b> (↶ at the top) or open <b>Revisions</b> in the right panel to go back to an earlier version.</p>';
}

/* ------------------------------------------------------- editor: fewer blocks */

add_filter( 'allowed_block_types_all', function ( $allowed, $ctx ) {
	$type = $ctx->post->post_type ?? '';
	if ( ! in_array( $type, array( 'page', 'ritual' ), true ) ) {
		return $allowed;
	}
	// Hide WordPress's site-building blocks (navigation, query loops, widgets…) that do not fit this
	// theme; keep content blocks, embeds and every block that comes from a plugin (forms, booking…).
	$core_ok = array( 'paragraph', 'heading', 'list', 'list-item', 'image', 'gallery', 'quote', 'separator', 'spacer', 'buttons', 'button', 'columns', 'column', 'group', 'table', 'video', 'audio', 'file', 'embed', 'html', 'shortcode', 'block', 'details', 'pullquote', 'media-text' );
	$names   = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
	return array_values( array_filter( $names, fn( $n ) => 0 !== strpos( $n, 'core/' ) || in_array( substr( $n, 5 ), $core_ok, true ) ) );
}, 10, 2 );

add_action( 'after_setup_theme', function () {
	remove_theme_support( 'core-block-patterns' );
} );
add_filter( 'should_load_remote_block_patterns', '__return_false' );
remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );

// Ritual editor: open the details form by default, sidebar buttons to switch.
add_action( 'enqueue_block_editor_assets', function () {
	$s = get_current_screen();
	if ( $s && 'ritual' === $s->post_type ) {
		wp_enqueue_script( 'zv-ritual-editor', get_template_directory_uri() . '/assets/js/ritual-editor.js', array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-dom-ready' ), ZV_VERSION . '.' . filemtime( get_template_directory() . '/assets/js/ritual-editor.js' ), true );
	}
} );

/* ------------------------------------------------ compatibility guards */

// Pages and rituals are built from ZenVida blocks. If the Classic Editor plugin is
// installed they would open as an empty text box, so they always use the block editor.
// (Journal posts and events still follow the Classic Editor setting.)
add_filter( 'use_block_editor_for_post_type', fn( $use, $type ) => in_array( $type, array( 'page', 'ritual' ), true ) ? true : $use, 200, 2 );
add_filter( 'use_block_editor_for_post', fn( $use, $post ) => ( $post && in_array( $post->post_type, array( 'page', 'ritual' ), true ) ) ? true : $use, 200, 2 );

// Old address of the settings page (Appearance → ZenVida settings).
add_action( 'admin_init', function () {
	global $pagenow;
	if ( 'themes.php' === $pagenow && isset( $_GET['page'] ) && 'zv-settings' === $_GET['page'] ) { // phpcs:ignore
		wp_safe_redirect( admin_url( 'admin.php?page=zv-settings' ) );
		exit;
	}
} );

// Customizer: the logo markup is the theme's own, so refresh the preview when it changes.
add_action( 'customize_register', function ( $wp_customize ) {
	if ( $wp_customize->get_setting( 'custom_logo' ) ) {
		$wp_customize->get_setting( 'custom_logo' )->transport = 'refresh';
		$wp_customize->selective_refresh->remove_partial( 'custom_logo' );
	}
}, 20 );

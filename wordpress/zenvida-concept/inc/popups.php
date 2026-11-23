<?php
/**
 * Popups for events and campaigns.
 *
 * Admin: Popups → Add new. One plain form: content (photo, title, text, button),
 * when (start / end in Madrid time), where (all pages, homepage, rituals, chosen pages),
 * how (after a few seconds, on scroll, when leaving), how often, which devices.
 *
 * Front end: matching popups are printed in the footer; assets/js/popups.js checks the
 * dates in the browser (so cached pages stay correct), picks the first one and shows it.
 * Never more than one popup per page view.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------- post type */

add_action( 'init', function () {
	register_post_type( 'zv_popup', array(
		'labels'          => array(
			'name'               => 'Popups',
			'singular_name'      => 'Popup',
			'menu_name'          => 'Popups',
			'all_items'          => 'All popups',
			'add_new'            => 'Add new popup',
			'add_new_item'       => 'Add new popup',
			'edit_item'          => 'Edit popup',
			'not_found'          => 'No popups yet. Click "Add new popup" to create one for your next event.',
			'not_found_in_trash' => 'No popups in the trash.',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false, // simple form, no block editor
		'menu_icon'       => 'dashicons-megaphone',
		'menu_position'   => 6,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
	) );
} );

add_filter( 'post_updated_messages', function ( $m ) {
	$m['zv_popup'] = array_fill( 0, 11, 'Popup saved.' );
	$m['zv_popup'][6] = 'Popup published. It shows on the website within the dates you set.';
	$m['zv_popup'][10] = 'Draft saved. You can preview it now.';
	return $m;
} );

add_filter( 'enter_title_here', fn( $t, $post ) => 'zv_popup' === $post->post_type ? 'Popup name (only you see it), e.g. Christmas gift cards' : $t, 10, 2 );

/** Field defaults for a popup. */
function zv_popup_defaults() {
	return array(
		'active'   => '1',
		'image'    => '',
		'eyebrow'  => '',
		'heading'  => '',
		'text'     => '',
		'btn'      => '',
		'url'      => '',
		'layout'   => 'center',
		'start'    => '',
		'end'      => '',
		'where'    => 'all',
		'pages'    => array(),
		'trigger'  => 'delay',
		'delay'    => 5,
		'scroll'   => 40,
		'freq'     => 'day',
		'device'   => 'all',
	);
}

function zv_popup( $id ) {
	$m = get_post_meta( $id, 'zv_popup', true );
	return wp_parse_args( is_array( $m ) ? $m : array(), zv_popup_defaults() );
}

/** "2026-12-01T10:00" in the site's time zone (Madrid) → Unix time, or 0. */
function zv_popup_time( $local ) {
	if ( ! $local ) {
		return 0;
	}
	try {
		return ( new DateTimeImmutable( $local, wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

/** Live / Scheduled / Ended / Off, in plain words. */
function zv_popup_status( $id ) {
	$p   = zv_popup( $id );
	$now = time();
	if ( 'publish' !== get_post_status( $id ) || ! $p['active'] ) {
		return array( 'off', 'Off' );
	}
	$s = zv_popup_time( $p['start'] );
	$e = zv_popup_time( $p['end'] );
	if ( $e && $e < $now ) {
		return array( 'ended', 'Ended ' . wp_date( 'j M', $e ) );
	}
	if ( $s && $s > $now ) {
		return array( 'scheduled', 'Starts ' . wp_date( 'j M, H:i', $s ) );
	}
	return array( 'live', $e ? 'Live until ' . wp_date( 'j M, H:i', $e ) : 'Live' );
}

/* ------------------------------------------------------------------ form */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'zv_popup_box', 'Popup', 'zv_popup_box', 'zv_popup', 'normal', 'high' );
} );

function zv_popup_box( $post ) {
	wp_nonce_field( 'zv_popup', 'zv_popup_nonce' );
	$p   = zv_popup( $post->ID );
	$new = 'auto-draft' === $post->post_status;
	$n   = fn( $k ) => 'zv_popup[' . $k . ']';
	$opt = function ( $name, $value, $choices ) {
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( $choices as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '" ' . selected( (string) $value, (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select>';
	};
	$pages = get_posts( array( 'post_type' => array( 'page', 'ritual', 'zv_event' ), 'numberposts' => -1, 'orderby' => 'type menu_order title', 'order' => 'ASC' ) );
	?>
	<div class="zv-box zv-popup-form">
		<div class="zv-visible">
			<label class="zv-switch"><input type="checkbox" name="<?php echo esc_attr( $n( 'active' ) ); ?>" value="1" <?php checked( (bool) $p['active'] ); ?>><span></span></label>
			<div><b>Popup is on</b><br><span class="description">Switch off to stop showing it straight away. The dates below also switch it on and off automatically.</span></div>
		</div>

		<h3>1. What it says</h3>
		<div class="zv-popup-grid">
			<div>
				<?php zv_media_field( $n( 'image' ), $p['image'], 'Photo (optional)', 'Landscape photos look best. Not shown in the "Bar" style.' ); ?>
			</div>
			<div>
				<p><label class="zv-label">Small line above the title (optional)</label>
					<input type="text" class="widefat" name="<?php echo esc_attr( $n( 'eyebrow' ) ); ?>" value="<?php echo esc_attr( $p['eyebrow'] ); ?>" placeholder="e.g. Christmas · 1–24 December"></p>
				<p><label class="zv-label">Title</label>
					<input type="text" class="widefat zv-big" name="<?php echo esc_attr( $n( 'heading' ) ); ?>" value="<?php echo esc_attr( $p['heading'] ); ?>" placeholder="e.g. Gift a ritual this Christmas"></p>
				<p><label class="zv-label">Text</label>
					<textarea class="widefat" rows="3" name="<?php echo esc_attr( $n( 'text' ) ); ?>" placeholder="One or two short sentences."><?php echo esc_textarea( $p['text'] ); ?></textarea></p>
				<p class="zv-inline">
					<label>Button text <input type="text" name="<?php echo esc_attr( $n( 'btn' ) ); ?>" value="<?php echo esc_attr( $p['btn'] ); ?>" placeholder="e.g. Buy a gift card"></label>
					<label>Button goes to <input type="text" name="<?php echo esc_attr( $n( 'url' ) ); ?>" value="<?php echo esc_attr( $p['url'] ); ?>" placeholder="<?php echo esc_attr( home_url( '/rituals/' ) ); ?>" style="min-width:260px"></label>
				</p>
				<p class="description">Copy the page address from your browser. Leave the button text empty for no button.</p>
			</div>
		</div>

		<h3>2. When</h3>
		<p class="zv-inline">
			<label>Start <input type="datetime-local" name="<?php echo esc_attr( $n( 'start' ) ); ?>" value="<?php echo esc_attr( $p['start'] ); ?>"></label>
			<label>End <input type="datetime-local" name="<?php echo esc_attr( $n( 'end' ) ); ?>" value="<?php echo esc_attr( $p['end'] ); ?>"></label>
		</p>
		<p class="description">Madrid time. Leave Start empty to begin now, End empty to keep it running until you switch it off.</p>

		<h3>3. Where and how</h3>
		<table class="form-table zv-popup-table" role="presentation">
			<tr><th>Show on</th><td>
				<?php $opt( $n( 'where' ), $p['where'], array( 'all' => 'Every page', 'home' => 'Homepage only', 'rituals' => 'Rituals pages (list and every ritual)', 'events' => 'Events pages (list and every event)', 'pages' => 'Only the pages I choose…' ) ); ?>
				<div class="zv-pages" data-zv-show-if="pages">
					<?php foreach ( $pages as $pg ) : ?>
						<label><input type="checkbox" name="<?php echo esc_attr( $n( 'pages' ) ); ?>[]" value="<?php echo (int) $pg->ID; ?>" <?php checked( in_array( $pg->ID, array_map( 'intval', (array) $p['pages'] ), true ) ); ?>> <?php echo esc_html( $pg->post_title ); ?><?php echo 'page' !== $pg->post_type ? ' <small>' . ( 'ritual' === $pg->post_type ? 'ritual' : 'event' ) . '</small>' : ''; ?></label>
					<?php endforeach; ?>
				</div>
			</td></tr>
			<tr><th>Opens</th><td>
				<?php $opt( $n( 'trigger' ), $p['trigger'], array( 'delay' => 'After a few seconds', 'scroll' => 'When the visitor scrolls down the page', 'exit' => 'When the visitor is about to leave (computers)' ) ); ?>
				<span data-zv-show-if="delay"><input type="number" min="0" max="120" name="<?php echo esc_attr( $n( 'delay' ) ); ?>" value="<?php echo (int) $p['delay']; ?>" style="width:70px"> seconds</span>
				<span data-zv-show-if="scroll"><input type="number" min="5" max="100" step="5" name="<?php echo esc_attr( $n( 'scroll' ) ); ?>" value="<?php echo (int) $p['scroll']; ?>" style="width:70px"> % of the page</span>
				<p class="description" data-zv-show-if="exit">On phones (where you cannot "leave" with the mouse) it opens after the visitor scrolls halfway.</p>
			</td></tr>
			<tr><th>How often</th><td>
				<?php $opt( $n( 'freq' ), $p['freq'], array( 'once' => 'Only once per visitor', 'day' => 'Once a day', 'session' => 'Once per visit', 'always' => 'On every page (not recommended)' ) ); ?>
				<p class="description">After the visitor closes it, it stays closed for this long.</p>
			</td></tr>
			<tr><th>Devices</th><td>
				<?php $opt( $n( 'device' ), $p['device'], array( 'all' => 'Phones and computers', 'mobile' => 'Phones only', 'desktop' => 'Computers and tablets only' ) ); ?>
			</td></tr>
			<tr><th>Style</th><td>
				<div class="zv-layouts">
					<?php foreach ( array( 'center' => array( 'Window', 'In the middle of the screen, page dimmed behind.' ), 'corner' => array( 'Corner box', 'Small box in the bottom corner. Does not cover the page.' ), 'bar' => array( 'Bar', 'A slim bar at the bottom: text and a button.' ) ) as $v => $l ) : ?>
						<label class="zv-layout zv-layout-<?php echo esc_attr( $v ); ?>"><input type="radio" name="<?php echo esc_attr( $n( 'layout' ) ); ?>" value="<?php echo esc_attr( $v ); ?>" <?php checked( $p['layout'], $v ); ?>><span class="pic"><i></i></span><b><?php echo esc_html( $l[0] ); ?></b><small><?php echo esc_html( $l[1] ); ?></small></label>
					<?php endforeach; ?>
				</div>
			</td></tr>
		</table>

		<h3>4. Check it</h3>
		<?php if ( $new ) : ?>
			<p class="description">Click <b>Save Draft</b> (on the right) first; then you can preview it on the website.</p>
		<?php else : ?>
			<p><a class="button button-secondary" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'zv_popup_preview', $post->ID, zv_popup_preview_url( $p ) ) ); ?>">Preview on the website ↗</a>
				<span class="description">Shows the saved version straight away, only to you. Save first if you changed something.</span></p>
		<?php endif; ?>
		<p class="description">When it looks right, click <b>Publish</b> (or <b>Update</b>) on the right.</p>
	</div>
	<?php
}

function zv_popup_preview_url( $p ) {
	if ( 'rituals' === $p['where'] ) {
		return zv_rituals_url();
	}
	if ( 'events' === $p['where'] ) {
		return get_post_type_archive_link( 'zv_event' );
	}
	if ( 'pages' === $p['where'] && ! empty( $p['pages'] ) ) {
		return get_permalink( (int) reset( $p['pages'] ) ) ?: home_url( '/' );
	}
	return home_url( '/' );
}

add_action( 'save_post_zv_popup', function ( $id ) {
	if ( ! zv_box_can_save( $id, 'zv_popup_nonce', 'zv_popup' ) ) {
		return;
	}
	$in  = (array) wp_unslash( $_POST['zv_popup'] ?? array() );
	$d   = zv_popup_defaults();
	$dt  = fn( $v ) => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) $v ) ? $v : '';
	$one = fn( $v, $allowed, $def ) => in_array( $v, $allowed, true ) ? $v : $def;
	update_post_meta( $id, 'zv_popup', array(
		'active'  => empty( $in['active'] ) ? '' : '1',
		'image'   => esc_url_raw( $in['image'] ?? '' ),
		'eyebrow' => sanitize_text_field( $in['eyebrow'] ?? '' ),
		'heading' => sanitize_text_field( $in['heading'] ?? '' ),
		'text'    => sanitize_textarea_field( $in['text'] ?? '' ),
		'btn'     => sanitize_text_field( $in['btn'] ?? '' ),
		'url'     => esc_url_raw( $in['url'] ?? '' ),
		'layout'  => $one( $in['layout'] ?? '', array( 'center', 'corner', 'bar' ), $d['layout'] ),
		'start'   => $dt( $in['start'] ?? '' ),
		'end'     => $dt( $in['end'] ?? '' ),
		'where'   => $one( $in['where'] ?? '', array( 'all', 'home', 'rituals', 'events', 'pages' ), $d['where'] ),
		'pages'   => array_values( array_filter( array_map( 'absint', (array) ( $in['pages'] ?? array() ) ) ) ),
		'trigger' => $one( $in['trigger'] ?? '', array( 'delay', 'scroll', 'exit' ), $d['trigger'] ),
		'delay'   => min( 120, absint( $in['delay'] ?? 5 ) ),
		'scroll'  => max( 5, min( 100, absint( $in['scroll'] ?? 40 ) ) ),
		'freq'    => $one( $in['freq'] ?? '', array( 'once', 'day', 'session', 'always' ), $d['freq'] ),
		'device'  => $one( $in['device'] ?? '', array( 'all', 'mobile', 'desktop' ), $d['device'] ),
	) );
} );

// A popup with an end date before its start date: warn.
add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( ! $s || 'zv_popup' !== $s->id || empty( $_GET['post'] ) ) { // phpcs:ignore
		return;
	}
	$p = zv_popup( absint( $_GET['post'] ) ); // phpcs:ignore
	if ( $p['start'] && $p['end'] && zv_popup_time( $p['end'] ) <= zv_popup_time( $p['start'] ) ) {
		echo '<div class="notice notice-error"><p><b>The end date is before the start date</b>, so this popup will never show. Please check the dates.</p></div>';
	}
	if ( ! $p['heading'] && ! $p['text'] ) {
		echo '<div class="notice notice-warning"><p>This popup has no title or text yet.</p></div>';
	}
} );

/* ------------------------------------------------------------------ list */

add_filter( 'manage_zv_popup_posts_columns', fn( $c ) => array( 'cb' => $c['cb'], 'title' => 'Popup', 'zvp_status' => 'Status', 'zvp_where' => 'Shows on', 'zvp_how' => 'Opens', 'zvp_on' => 'On' ) );
add_action( 'manage_zv_popup_posts_custom_column', function ( $col, $id ) {
	$p = zv_popup( $id );
	if ( 'zvp_status' === $col ) {
		list( $k, $label ) = zv_popup_status( $id );
		echo '<span class="zvp-status zvp-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</span>';
	} elseif ( 'zvp_where' === $col ) {
		$w = array( 'all' => 'Every page', 'home' => 'Homepage', 'rituals' => 'Rituals pages', 'events' => 'Events pages', 'pages' => count( (array) $p['pages'] ) . ' chosen page(s)' );
		echo esc_html( $w[ $p['where'] ] ?? '' ) . ( 'all' !== $p['device'] ? ' · ' . esc_html( 'mobile' === $p['device'] ? 'phones' : 'computers' ) : '' );
	} elseif ( 'zvp_how' === $col ) {
		$t = array( 'delay' => 'After ' . (int) $p['delay'] . ' s', 'scroll' => 'At ' . (int) $p['scroll'] . '% scroll', 'exit' => 'When leaving' );
		$f = array( 'once' => 'once per visitor', 'day' => 'once a day', 'session' => 'once per visit', 'always' => 'every page' );
		echo esc_html( ( $t[ $p['trigger'] ] ?? '' ) . ', ' . ( $f[ $p['freq'] ] ?? '' ) );
	} elseif ( 'zvp_on' === $col ) {
		printf( '<label class="zv-switch" title="Switch the popup on or off"><input type="checkbox" data-zv-popup="%d" %s><span></span></label>', (int) $id, checked( (bool) $p['active'] && 'publish' === get_post_status( $id ), true, false ) );
	}
}, 10, 2 );

add_action( 'wp_ajax_zv_popup_active', function () {
	check_ajax_referer( 'zv_admin' );
	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id || 'zv_popup' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( 'Not allowed', 403 );
	}
	$on        = ! empty( $_POST['on'] ) && '0' !== $_POST['on'];
	$m         = zv_popup( $id );
	if ( $on && 'publish' !== get_post_status( $id ) ) {
		if ( ! in_array( get_post_status( $id ), array( 'draft', 'pending' ), true ) || ! current_user_can( get_post_type_object( 'zv_popup' )->cap->publish_posts ) ) {
			wp_send_json_error( 'This popup is not published yet. Open it and click Publish.', 400 );
		}
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	}
	$m['active'] = $on ? '1' : '';
	update_post_meta( $id, 'zv_popup', $m );
	list( $k, $label ) = zv_popup_status( $id );
	wp_send_json_success( array( 'status' => $k, 'label' => $label ) );
} );

/* ------------------------------------------------------------- front end */

function zv_popup_matches( $p ) {
	switch ( $p['where'] ) {
		case 'home':
			return is_front_page();
		case 'rituals':
			return is_post_type_archive( 'ritual' ) || is_tax( 'area' ) || is_singular( 'ritual' );
		case 'events':
			return is_post_type_archive( 'zv_event' ) || is_singular( 'zv_event' );
		case 'pages':
			$ids = array_map( 'intval', (array) $p['pages'] );
			if ( is_post_type_archive( 'ritual' ) && in_array( (int) zv_opt( 'rituals_page' ), $ids, true ) ) {
				return true;
			}
			if ( is_front_page() && in_array( (int) get_option( 'page_on_front' ), $ids, true ) ) {
				return true;
			}
			return is_singular() && in_array( (int) get_queried_object_id(), $ids, true );
		default:
			return true;
	}
}

add_action( 'wp_footer', function () {
	if ( is_admin() || is_404() ) {
		return;
	}
	$preview = isset( $_GET['zv_popup_preview'] ) ? absint( $_GET['zv_popup_preview'] ) : 0; // phpcs:ignore
	if ( $preview && ! current_user_can( 'edit_post', $preview ) ) {
		$preview = 0;
	}
	$posts = $preview
		? array_filter( array( get_post( $preview ) ) )
		: get_posts( array( 'post_type' => 'zv_popup', 'post_status' => 'publish', 'numberposts' => 20, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) );
	$out = array();
	$cfg = array();
	foreach ( $posts as $post ) {
		if ( 'zv_popup' !== $post->post_type ) {
			continue;
		}
		$p   = zv_popup( $post->ID );
		$end = zv_popup_time( $p['end'] );
		if ( ! $preview && ( ! $p['active'] || ( $end && $end < time() ) || ! zv_popup_matches( $p ) ) ) {
			continue;
		}
		$id    = 'zvp-' . $post->ID;
		$tag   = 'center' === $p['layout'] ? 'dialog' : 'div';
		$img   = ( $p['image'] && 'bar' !== $p['layout'] ) ? '<div class="zvp-img">' . zv_image( $p['image'], array( 'sizes' => '(max-width: 760px) 100vw, 480px' ) ) . '</div>' : '';
		$btn   = $p['btn'] ? '<a class="btn btn-dark zvp-go" href="' . esc_url( $p['url'] ?: zv_rituals_url() ) . '">' . zv_e( $p['btn'] ) . '</a>' : '';
		$out[] = sprintf(
			'<%1$s class="zvp zvp-%2$s" id="%3$s" aria-labelledby="%3$s-t" %4$s hidden>%5$s<div class="zvp-body">%6$s<h2 id="%3$s-t">%7$s</h2>%8$s%9$s</div><button type="button" class="zvp-x" aria-label="Close">×</button></%1$s>',
			$tag,
			esc_attr( $p['layout'] ),
			esc_attr( $id ),
			'div' === $tag ? 'role="dialog"' : '',
			$img,
			$p['eyebrow'] ? '<span class="eyebrow">' . zv_e( $p['eyebrow'] ) . '</span>' : '',
			zv_e( $p['heading'] ),
			$p['text'] ? '<p>' . nl2br( zv_e( $p['text'] ) ) . '</p>' : '',
			$btn
		);
		$cfg[] = array(
			'el'      => $id,
			'key'     => $post->ID . '-' . substr( md5( $post->post_modified_gmt ), 0, 6 ), // edited popup = shown again
			'start'   => zv_popup_time( $p['start'] ) * 1000,
			'end'     => $end * 1000,
			'trigger' => $p['trigger'],
			'delay'   => (int) $p['delay'],
			'scroll'  => (int) $p['scroll'],
			'freq'    => $p['freq'],
			'device'  => $p['device'],
			'preview' => (bool) $preview,
		);
	}
	if ( ! $cfg ) {
		return;
	}
	echo implode( "\n", $out ); // phpcs:ignore -- escaped above
	echo '<script>window.ZV_POPUPS=' . wp_json_encode( $cfg ) . ';</script>';
	$f = get_template_directory() . '/assets/js/popups.js';
	echo '<script src="' . esc_url( get_template_directory_uri() . '/assets/js/popups.js?ver=' . ZV_VERSION . '.' . filemtime( $f ) ) . '" defer></script>'; // phpcs:ignore
}, 30 );

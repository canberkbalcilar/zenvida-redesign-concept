<?php
/**
 * Google title & description for every page, ritual, event and journal post.
 *
 * Stored in the same fields Rank Math uses (rank_math_title, rank_math_description), so
 * when Rank Math is installed it shows and keeps them, nothing to re-type. Until then the
 * theme prints them. Spanish: the texts are in the TranslatePress dictionary (inc/data/es.json).
 *
 *  - Pages, rituals, events, posts: "Google search" box under the editor.
 *  - Areas (Feet & Legs, Massage…): Rituals → Areas → edit.
 *  - Rituals page and Events page: Appearance → ZenVida settings → Google.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function zv_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );
}

/** The custom Google title or description for this request ('' when none). */
function zv_seo_meta( $what ) {
	$key = 'title' === $what ? 'rank_math_title' : 'rank_math_description';
	if ( is_front_page() || is_singular() ) {
		return trim( (string) get_post_meta( get_queried_object_id(), $key, true ) );
	}
	if ( is_home() && (int) get_option( 'page_for_posts' ) ) {
		return trim( (string) get_post_meta( (int) get_option( 'page_for_posts' ), $key, true ) );
	}
	if ( is_tax( 'area' ) || is_category() ) {
		return trim( (string) get_term_meta( get_queried_object_id(), $key, true ) );
	}
	if ( is_post_type_archive( 'ritual' ) ) {
		return trim( (string) zv_opt( 'title' === $what ? 'seo_rituals' : 'seo_rituals_desc' ) );
	}
	if ( is_post_type_archive( 'zv_event' ) ) {
		return trim( (string) zv_opt( 'title' === $what ? 'seo_events_title' : 'seo_events_desc' ) );
	}
	return '';
}

// The whole title as written ("Thai Massage in Madrid Centro, from €90 | ZenVida"), in the visitor's language.
add_filter( 'pre_get_document_title', function ( $title ) {
	if ( zv_seo_plugin() || is_search() || is_404() ) {
		return $title;
	}
	$t = zv_seo_meta( 'title' );
	return '' !== $t ? zv_tr( $t ) : $title;
}, 20 );

/* ------------------------------------------------------------------ editor box */

add_action( 'add_meta_boxes', function () {
	if ( zv_seo_plugin() ) {
		return; // the SEO plugin has its own box for the same fields
	}
	add_meta_box( 'zv_seo', 'Google search: title & description', 'zv_seo_box', array( 'page', 'post', 'ritual', 'zv_event' ), 'normal', 'low' );
} );

function zv_seo_fields( $title, $desc, $name_t, $name_d ) {
	?>
	<p><label class="zv-label" style="display:block;font-weight:600;margin-bottom:4px">Title in Google <span class="zv-count" data-for="<?php echo esc_attr( $name_t ); ?>" data-max="60"></span></label>
		<input type="text" class="widefat" name="<?php echo esc_attr( $name_t ); ?>" value="<?php echo esc_attr( $title ); ?>" placeholder="Focus keyword first, then a detail, then | ZenVida (up to 60 characters)"></p>
	<p><label class="zv-label" style="display:block;font-weight:600;margin-bottom:4px">Description in Google <span class="zv-count" data-for="<?php echo esc_attr( $name_d ); ?>" data-min="150" data-max="160"></span></label>
		<textarea class="widefat" rows="3" name="<?php echo esc_attr( $name_d ); ?>" placeholder="150 to 160 characters: the focus keyword, what happens, minutes and price, Sol, a soft call to action."><?php echo esc_textarea( $desc ); ?></textarea></p>
	<p class="description">Empty = the theme writes them from the page. Spanish versions: TranslatePress → Translate Site.</p>
	<script>
	document.querySelectorAll('.zv-count').forEach(function (c) {
		var f = document.querySelector('[name="' + c.dataset.for + '"]'); if (!f) return;
		var upd = function () { var n = f.value.length, min = +(c.dataset.min || 1), max = +c.dataset.max;
			c.textContent = n ? '(' + n + ' / ' + (c.dataset.min ? min + '–' : '') + max + ')' : '';
			c.style.color = n && (n < min || n > max) ? '#b32d2e' : '#2e7d32'; };
		f.addEventListener('input', upd); upd();
	});
	</script>
	<?php
}

function zv_seo_box( $post ) {
	wp_nonce_field( 'zv_seo', 'zv_seo_nonce' );
	zv_seo_fields( get_post_meta( $post->ID, 'rank_math_title', true ), get_post_meta( $post->ID, 'rank_math_description', true ), 'zv_seo_title', 'zv_seo_desc' );
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['zv_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zv_seo_nonce'] ), 'zv_seo' ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'zv_seo_title' => 'rank_math_title', 'zv_seo_desc' => 'rank_math_description' ) as $field => $key ) {
		$v = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		'' === $v ? delete_post_meta( $post_id, $key ) : update_post_meta( $post_id, $key, $v );
	}
} );

// Areas: the same two fields on the area screen.
add_action( 'area_edit_form_fields', function ( $term ) {
	if ( zv_seo_plugin() ) {
		return;
	}
	echo '<tr class="form-field"><th scope="row">Google search</th><td>';
	wp_nonce_field( 'zv_seo', 'zv_seo_nonce' );
	zv_seo_fields( get_term_meta( $term->term_id, 'rank_math_title', true ), get_term_meta( $term->term_id, 'rank_math_description', true ), 'zv_seo_title', 'zv_seo_desc' );
	echo '</td></tr>';
}, 20 );
add_action( 'edited_area', function ( $term_id ) {
	if ( ! isset( $_POST['zv_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zv_seo_nonce'] ), 'zv_seo' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	foreach ( array( 'zv_seo_title' => 'rank_math_title', 'zv_seo_desc' => 'rank_math_description' ) as $field => $key ) {
		$v = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		'' === $v ? delete_term_meta( $term_id, $key ) : update_term_meta( $term_id, $key, $v );
	}
} );

/* ------------------------------------------------------------------ starter texts */

/**
 * Fill in the titles and descriptions from inc/data/seo-meta.json, once (and again when the
 * file gets a new version). A text someone already typed is never replaced.
 */
add_action( 'init', function () {
	$f = __DIR__ . '/data/seo-meta.json';
	if ( wp_installing() || ! get_option( 'zv_seeded' ) || ! file_exists( $f ) ) {
		return;
	}
	$data = json_decode( (string) file_get_contents( $f ), true ); // phpcs:ignore
	if ( ! is_array( $data ) || (string) get_option( 'zv_seo_meta_v' ) === (string) ( $data['version'] ?? '' ) ) {
		return;
	}
	update_option( 'zv_seo_meta_v', (string) ( $data['version'] ?? '' ), false );
	$set_post = function ( $id, $key, $v ) {
		if ( $id && '' === trim( (string) get_post_meta( $id, $key, true ) ) ) {
			update_post_meta( $id, $key, $v );
		}
	};
	$opts = get_option( 'zv_settings', array() );
	$opts = is_array( $opts ) ? $opts : array();
	foreach ( (array) ( $data['pages'] ?? array() ) as $row ) {
		$path = (string) $row['path'];
		$t    = (string) $row['title'];
		$d    = (string) $row['description'];
		$old = array( '', 'Massage & Reflexology Rituals in Madrid', 'All ZenVida rituals with prices: reflexology, foot, back and body massage, couples rituals. Filter by area and how you feel.' );
		$put = function ( $k, $v ) use ( &$opts, $old ) {
			if ( in_array( trim( (string) ( $opts[ $k ] ?? '' ) ), $old, true ) ) {
				$opts[ $k ] = $v;
			}
		};
		if ( '/rituals/' === $path ) {
			$put( 'seo_rituals', $t );
			$put( 'seo_rituals_desc', $d );
			continue;
		}
		if ( '/events/' === $path ) {
			$put( 'seo_events_title', $t );
			$put( 'seo_events_desc', $d );
			continue;
		}
		if ( preg_match( '#^/rituals/([^/]+)/$#', $path, $m ) && ( $term = get_term_by( 'slug', $m[1], 'area' ) ) ) {
			foreach ( array( 'rank_math_title' => $t, 'rank_math_description' => $d ) as $k => $v ) {
				if ( '' === trim( (string) get_term_meta( $term->term_id, $k, true ) ) ) {
					update_term_meta( $term->term_id, $k, $v );
				}
			}
			continue;
		}
		$id = '/' === $path ? (int) get_option( 'page_on_front' ) : url_to_postid( home_url( $path ) );
		if ( ! $id && '/journal/' === $path ) {
			$id = (int) get_option( 'page_for_posts' );
		}
		$set_post( $id, 'rank_math_title', $t );
		$set_post( $id, 'rank_math_description', $d );
	}
	update_option( 'zv_settings', $opts );
}, 34 );

<?php
/**
 * Online booking with ZenVida's own booking widget (the same one zenvida.es uses on Wix).
 *
 * The widget is a self-contained web component, <zenvida-widget>, loaded from ZenVida's
 * booking system. It shows the live menu, real free times, takes the visitor's details
 * and the Stripe deposit. This theme only places it on the page:
 *  - "Rituals & Prices" page (block "Booking widget"), the same address as on Wix;
 *  - ritual pages, opened on that ritual's service (Ritual details → 5. Online booking).
 *
 * Note: the booking system only answers requests from the addresses it knows
 * (zenvida.es). On any other address the widget can't load the menu until ZenVida's
 * developer adds that address.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZV_WIDGET_JS', 'https://storage.googleapis.com/zenvida-widget-staging/zenvida-widget.js' );
define( 'ZV_WIDGET_API', 'https://zenvida-api-gateway-daa7d74i.ew.gateway.dev' );

/**
 * Services in the booking system that match the starter rituals by address (e.g. the ritual
 * "deep-tissue-massage" → deep-tissue-massage-60 / -90). Used when there is no connection.
 */
function zv_widget_known() {
	return array( 'reflexology', 'deluxe-reflexology', 'swedish-massage', 'deep-tissue-massage', 'combined-massage', 'thai-massage', 'lymphatic-drainage-massage', 'sports-massage', 'lomi-lomi-massage', 'pregnancy-massage', 'kobido-facial-massage', 'classic-facial-massage', 'couples-massage', 'full-body-reset', 'thai-andaluz', 'travel-recovery', 'energy-balance', 'ritual-levante', 'zen-balance', 'back-to-rhythm', 'deep-relief-ritual' );
}

/** Is online booking with the widget switched on (Site settings → Online booking)? */
function zv_widget_on() {
	return '1' === (string) zv_opt( 'widget_on' );
}

/**
 * The booking system's menu, read on the server (no browser restrictions there) and kept for 6 hours.
 * Returns [ 'programs' => [ [id, name], … ], 'services' => [ slug => [name, min, price, program] ], 'bases' => [ base => [slug, …] ] ],
 * or null when the booking system can't be reached.
 */
function zv_widget_services( $fresh = false ) {
	$c = get_transient( 'zv_widget_services' );
	if ( ! $fresh && is_array( $c ) ) {
		return $c['services'] ? $c : null;
	}
	$api = untrailingslashit( (string) ( zv_opt( 'widget_api' ) ?: ZV_WIDGET_API ) );
	$res = wp_remote_get( $api . '/api/services?language=en', array( 'timeout' => 8, 'headers' => array( 'Accept' => 'application/json' ) ) );
	$d   = is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	$out = array( 'programs' => array(), 'services' => array(), 'bases' => array() );
	foreach ( (array) ( $d['programs'] ?? array() ) as $p ) {
		$pid = (string) ( $p['externalProgramId'] ?? '' );
		$out['programs'][] = array( $pid, wp_strip_all_tags( (string) ( $p['programDisplayName'] ?? '' ) ) );
		foreach ( (array) ( $p['services'] ?? array() ) as $s ) {
			$slug = (string) ( $s['slug'] ?? '' );
			if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
				continue; // without an address the widget can't open it directly
			}
			$out['services'][ $slug ] = array(
				'name'    => wp_strip_all_tags( (string) ( $s['name'] ?? $slug ) ),
				'min'     => (int) ( $s['durationMinutes'] ?? 0 ),
				'price'   => isset( $s['priceCents'] ) ? (int) $s['priceCents'] / 100 : null,
				'program' => $pid,
			);
			$out['bases'][ preg_replace( '/-\d+$/', '', $slug ) ][] = $slug;
		}
	}
	foreach ( $out['bases'] as &$slugs ) {
		usort( $slugs, fn( $x, $y ) => $out['services'][ $x ]['min'] <=> $out['services'][ $y ]['min'] );
	}
	unset( $slugs );
	set_transient( 'zv_widget_services', $out, $out['services'] ? 6 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
	return $out['services'] ? $out : null;
}

/** "Deep Tissue Massage 90€ (60 min)" → "Deep Tissue Massage". */
function zv_widget_clean_name( $name ) {
	$name = preg_replace( '/\s*\[[^\]]*\]\s*$/u', '', (string) $name );
	$name = preg_replace( '/\s*\d+\s*€.*$/u', '', $name );
	$name = preg_replace( '/\s*\(\d+\s*min\)\s*$/u', '', $name );
	return trim( $name );
}

/**
 * The widget service for one duration of a ritual, e.g. deep-tissue-massage-90.
 * Empty when the ritual isn't bookable online.
 */
function zv_widget_slug( $post_id, $minutes ) {
	$base = (string) get_post_meta( $post_id, 'zv_widget', true );
	if ( '' === $base ) {
		return '';
	}
	$want = $base . '-' . (int) $minutes;
	$svc  = zv_widget_services();
	if ( ! $svc || isset( $svc['services'][ $want ] ) ) {
		return $want;
	}
	if ( isset( $svc['services'][ $base ] ) ) {
		return $base; // a single service was chosen
	}
	return $svc['bases'][ $base ][0] ?? $want;
}

/** The page with the full booking widget ("Rituals & Prices"). */
function zv_booking_page() {
	$p = get_page_by_path( 'rituals-and-prices' );
	return ( $p && 'publish' === $p->post_status ) ? $p : null;
}

/** Where "Book now" buttons go. */
function zv_book_url() {
	if ( is_singular( 'ritual' ) ) {
		return '#book';
	}
	if ( zv_opt( 'header_url' ) ) {
		return zv_opt( 'header_url' );
	}
	$p = zv_widget_on() ? zv_booking_page() : null;
	return $p ? get_permalink( $p ) : zv_rituals_url();
}

function zv_widget_enqueue() {
	$src = (string) ( zv_opt( 'widget_js' ) ?: ZV_WIDGET_JS );
	if ( ! wp_script_is( 'zv-widget', 'registered' ) ) {
		wp_register_script( 'zv-widget', $src, array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	wp_enqueue_script( 'zv-widget' );
}

/**
 * <zenvida-widget> markup. $a: service (slug), category (program id), group (bool), welcome (bool).
 */
function zv_widget_tag( $a = array() ) {
	zv_widget_enqueue();
	$attrs = array( 'lang' => 'es' === zv_lang() ? 'es' : 'en' );
	if ( ! empty( $a['service'] ) ) {
		$attrs['service-id'] = $a['service'];
	}
	if ( ! empty( $a['category'] ) ) {
		$attrs['category'] = $a['category'];
	}
	if ( ! empty( $a['group'] ) ) {
		$attrs['mode'] = 'group';
	}
	if ( ! empty( $a['welcome'] ) ) {
		$attrs['start'] = 'welcome';
	}
	$html = '<zenvida-widget';
	foreach ( $attrs as $k => $v ) {
		$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	$wa = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
	return $html . ' data-no-translation></zenvida-widget>'
		. '<noscript><p class="zvw-noscript">' . zv_e( zv__( 'Online booking needs JavaScript.' ) ) . ' <a href="' . esc_url( $wa ) . '">' . zv_e( zv__( 'Book on WhatsApp' ) ) . '</a></p></noscript>';
}

/* ------------------------------------------------------------------ block */

add_action( 'init', function () {
	zv_register( 'booking-widget', 'Booking widget', 'calendar', array(
		'eyebrow' => 'Rituals & prices', 'title' => 'Book your <em>ritual</em>',
		'text'    => 'Choose a ritual, pick a free time and pay a small deposit to confirm. The rest is paid after your treatment.',
		'service' => '', 'category' => '', 'group' => false, 'welcome' => false,
	), 'zv_render_booking_widget' );
}, 20 );

function zv_render_booking_widget( $a ) {
	$head = '';
	if ( '' !== trim( wp_strip_all_tags( $a['title'] ) ) || '' !== $a['eyebrow'] ) {
		$head = '<header class="zvw-head">'
			. ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
			. ( $a['title'] ? '<h1>' . wp_kses( $a['title'], array( 'em' => array(), 'br' => array() ) ) . '</h1>' : '' )
			. ( $a['text'] ? '<p>' . zv_e( $a['text'] ) . '</p>' : '' )
			. '</header>';
	}
	if ( ! zv_widget_on() ) {
		$body = '<div class="zvw-off"><p>' . zv_e( zv__( 'Online booking is switched off. Message us on WhatsApp and we will find you a time.' ) ) . '</p>'
			. zv_btn( zv__( 'Book on WhatsApp' ), 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ), 'dark' ) . '</div>';
	} else {
		$body = zv_widget_tag( array( 'service' => $a['service'], 'category' => $a['category'], 'group' => $a['group'], 'welcome' => $a['welcome'] ) );
	}
	return '<section class="zvw-page" id="book"><div class="wrap">' . $head . '<div class="zvw-frame">' . $body . '</div></div></section>';
}

// Editor: the list of services and categories for the block's sidebar.
add_action( 'enqueue_block_editor_assets', function () {
	$svc  = zv_widget_services();
	$opts = array();
	foreach ( (array) ( $svc['services'] ?? array() ) as $slug => $s ) {
		$opts[] = array( $slug, $s['name'] );
	}
	wp_add_inline_script( 'zv-blocks', 'window.ZV_WIDGET = ' . wp_json_encode( array(
		'on'       => zv_widget_on(),
		'services' => $opts,
		'programs' => $svc['programs'] ?? array(),
	) ) . ';', 'before' );
}, 20 );

/* -------------------------------------------------------- ritual details */

add_action( 'zv_ritual_details_extra', function ( $post ) {
	$cur  = (string) get_post_meta( $post->ID, 'zv_widget', true );
	$svc  = zv_widget_services();
	$opts = zv_parse_options( get_post_meta( $post->ID, 'zv_options', true ) );
	echo '<h3>5. Online booking</h3>';
	if ( $svc ) {
		echo '<p><label class="zv-label" for="zv_widget">Service in the booking system</label><select id="zv_widget" name="zv_widget" class="widefat">';
		echo '<option value="">Not bookable online: visitors are sent to WhatsApp</option>';
		foreach ( $svc['programs'] as $p ) {
			$group = '';
			foreach ( $svc['bases'] as $base => $slugs ) {
				if ( $svc['services'][ $slugs[0] ]['program'] !== $p[0] ) {
					continue;
				}
				$mins   = implode( ', ', array_map( fn( $s ) => $svc['services'][ $s ]['min'], $slugs ) );
				$group .= '<option value="' . esc_attr( $base ) . '"' . selected( $cur, $base, false ) . '>' . esc_html( zv_widget_clean_name( $svc['services'][ $slugs[0] ]['name'] ) . ' (' . $mins . ' min)' ) . '</option>';
			}
			if ( $group ) {
				echo '<optgroup label="' . esc_attr( $p[1] ) . '">' . $group . '</optgroup>'; // phpcs:ignore
			}
		}
		if ( '' !== $cur && ! isset( $svc['bases'][ $cur ] ) && ! isset( $svc['services'][ $cur ] ) ) {
			echo '<option value="' . esc_attr( $cur ) . '" selected>' . esc_html( $cur ) . ' (not found in the booking system)</option>';
		}
		echo '</select></p>';
		if ( '' !== $cur ) {
			echo '<ul class="zv-widget-check">';
			foreach ( $opts as $o ) {
				$slug = zv_widget_slug( $post->ID, $o[0] );
				$s    = $svc['services'][ $slug ] ?? null;
				if ( ! $s ) {
					echo '<li class="bad">' . (int) $o[0] . ' min: not in the booking system yet.</li>';
				} elseif ( $s['min'] !== (int) $o[0] ) {
					echo '<li class="bad">' . (int) $o[0] . ' min: the booking system has no ' . (int) $o[0] . '-minute version, so the widget opens ' . esc_html( $s['name'] ) . '.</li>';
				} elseif ( null !== $o[1] && null !== $s['price'] && (float) $o[1] !== (float) $s['price'] ) {
					echo '<li class="bad">' . (int) $o[0] . ' min: price here €' . (int) $o[1] . ', in the booking system €' . esc_html( (string) $s['price'] ) . '. Visitors pay the booking system price.</li>';
				} else {
					echo '<li class="ok">' . (int) $o[0] . ' min: ' . esc_html( $s['name'] ) . '</li>';
				}
			}
			echo '</ul>';
		}
	} else {
		echo '<p><label class="zv-label" for="zv_widget">Service in the booking system</label><input type="text" id="zv_widget" name="zv_widget" class="widefat" value="' . esc_attr( $cur ) . '" placeholder="e.g. deep-tissue-massage"></p>';
		echo '<p class="description">The booking system could not be reached right now, so the list is not available. Type the service name, or leave empty to send visitors to WhatsApp.</p>';
	}
	echo '<p class="description">The ritual page shows the booking widget, opened on this service. Each duration opens its own version (e.g. 90 minutes → deep-tissue-massage-90). Times, add-ons and the deposit come from the booking system.</p>';
} );

add_action( 'save_post_ritual', function ( $post_id ) {
	if ( ! isset( $_POST['zv_widget'] ) || ! zv_box_can_save( $post_id, 'zv_details_nonce', 'zv_details' ) ) { // phpcs:ignore
		return;
	}
	update_post_meta( $post_id, 'zv_widget', preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) wp_unslash( $_POST['zv_widget'] ) ) ) ); // phpcs:ignore
}, 20 );

/* ---------------------------------------------------------- setup / seed */

/** Connect the starter rituals to their booking-system services (only rituals not set yet). */
function zv_widget_autofill( $ask = true ) {
	$svc   = $ask ? zv_widget_services() : null;
	$known = $svc ? array_keys( $svc['bases'] ) : zv_widget_known();
	foreach ( get_posts( array( 'post_type' => 'ritual', 'post_status' => 'any', 'numberposts' => -1 ) ) as $p ) {
		if ( ! metadata_exists( 'post', $p->ID, 'zv_widget' ) ) {
			update_post_meta( $p->ID, 'zv_widget', in_array( $p->post_name, $known, true ) ? $p->post_name : '' );
		}
	}
}

/** The "Rituals & Prices" page with the full booking widget (same address as on Wix). */
function zv_seed_booking_page() {
	if ( get_page_by_path( 'rituals-and-prices' ) ) {
		return;
	}
	wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Rituals & Prices',
		'post_name'    => 'rituals-and-prices',
		'post_content' => wp_slash( serialize_blocks( array( zv_b( 'zv/booking-widget', array() ) ) ) ),
	) );
}

// Sites installed before online booking existed (theme updated in place): connect the rituals and
// add the page, once, on the first request after the update. Visitors don't wait for the booking
// system: the built-in list is used for them.
add_action( 'init', function () {
	if ( '1' === get_option( 'zv_booking_setup' ) || ! get_option( 'zv_seeded' ) || wp_installing() ) {
		return;
	}
	update_option( 'zv_booking_setup', '1', false );
	zv_widget_autofill( is_admin() );
	zv_seed_booking_page();
}, 30 );

/* -------------------------------------------------------------- tracking */

/** Google Tag Manager (Site settings → Online booking). The widget reports bookings to it. */
function zv_gtm_id() {
	$id = strtoupper( trim( (string) zv_opt( 'gtm_id' ) ) );
	return ( preg_match( '/^GTM-[A-Z0-9]{4,12}$/', $id ) && ! zv_demo() ) ? $id : '';
}
add_action( 'wp_head', function () {
	$id = zv_gtm_id();
	if ( $id ) {
		echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . esc_js( $id ) . "');</script>\n";
	}
}, 1 );
add_action( 'wp_body_open', function () {
	$id = zv_gtm_id();
	if ( $id ) {
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
	}
} );

// TranslatePress: the widget translates itself.
add_filter( 'trp_skip_selectors_from_dynamic_translation', fn( $sel ) => array_merge( (array) $sel, array( 'zenvida-widget', '.zvw-frame' ) ) );

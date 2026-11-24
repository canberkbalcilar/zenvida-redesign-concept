<?php
/**
 * "How to find us": route steps with photos, one-tap Google / Apple Maps, copy address,
 * and a full-screen "show to the taxi driver" card. Address comes from Site settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$in_section = array( 'parent' => array( 'zv/section' ) );
	zv_register( 'directions', 'Directions & maps', 'location-alt', array(
		'transport' => "Metro: Sol (lines 1, 2 and 3), 3 minutes on foot\nTrain: Cercanías Sol\nTaxi: show the driver our address with the button below",
	), 'zv_render_directions', $in_section );
	zv_register( 'route', 'Route steps', 'location', array(), 'zv_render_route', $in_section );
	zv_register( 'route-step', 'Route step', 'flag', array(
		'image' => '',
		'icon'  => 'walk',
		'title' => '',
		'text'  => '',
		'time'  => '',
	), 'zv_render_route_step', array( 'parent' => array( 'zv/route' ) ) );
}, 20 );

/** Google / Apple directions links to the studio. */
function zv_maps_links() {
	$addr = trim( zv_opt( 'address1' ) . ', 28012 Madrid, Spain' );
	return array(
		'google' => 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $addr ),
		'apple'  => 'https://maps.apple.com/?daddr=' . rawurlencode( $addr ),
		'addr'   => $addr,
	);
}

function zv_render_directions( $a ) {
	$m     = zv_maps_links();
	$lines = array_filter( array_map( 'trim', explode( "\n", (string) $a['transport'] ) ) );
	$icons = array( 'metro' => 'pin', 'train' => 'clock', 'taxi' => 'arrow' );
	$out   = '<div class="dir">'
		. '<a class="map-tile dir-map" href="' . esc_url( $m['google'] ) . '" target="_blank" rel="noopener"><span class="pin">Open the route ↗</span></a>'
		. '<div class="dir-info"><p class="dir-addr">' . zv_e( zv_opt( 'address1' ) ) . '<small>' . zv_e( zv_opt( 'address2' ) ) . '</small></p>'
		. '<div class="dir-btns">'
		. '<a class="btn btn-dark" href="' . esc_url( $m['google'] ) . '" target="_blank" rel="noopener">Google Maps</a>'
		. '<a class="btn btn-ghost" href="' . esc_url( $m['apple'] ) . '" target="_blank" rel="noopener">Apple Maps</a>'
		. '<button type="button" class="btn btn-ghost" data-copy="' . esc_attr( $m['addr'] ) . '">Copy address</button>'
		. '</div>';
	if ( $lines ) {
		$out .= '<ul class="dir-ways">';
		foreach ( $lines as $l ) {
			$parts = array_map( 'trim', explode( ':', $l, 2 ) );
			$key   = strtolower( $parts[0] );
			$out  .= '<li>' . zv_icon( $icons[ $key ] ?? 'check' ) . '<span>' . ( isset( $parts[1] ) ? '<b>' . zv_e( $parts[0] ) . '</b> ' . zv_e( $parts[1] ) : zv_e( $l ) ) . '</span></li>';
		}
		$out .= '</ul>';
	}
	$out .= '<button type="button" class="link dir-taxi" data-taxi>Show to the taxi driver</button></div></div>'
		. '<dialog class="taxi" aria-label="Address for the taxi driver"><p class="taxi-k">Por favor, lléveme a</p><p class="taxi-addr">' . zv_e( zv_opt( 'address1' ) ) . '</p><p class="taxi-sub">28012 Madrid · ZenVida</p><button type="button" class="btn btn-ghost" data-taxi-close>Close</button></dialog>';
	wp_add_inline_script( 'zv-site', zv_directions_js() );
	return $out;
}

function zv_directions_js() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;
	return "document.addEventListener('click',function(e){var c=e.target.closest('[data-copy]');if(c){navigator.clipboard&&navigator.clipboard.writeText(c.dataset.copy).then(function(){window.zvToast&&zvToast('Address copied');});}var t=e.target.closest('[data-taxi]');if(t){var d=t.closest('.dir').nextElementSibling;d&&d.showModal();}var x=e.target.closest('[data-taxi-close]');if(x){x.closest('dialog').close();}});";
}

function zv_render_route( $a, $content ) {
	return '<ol class="route">' . $content . '</ol>';
}

function zv_render_route_step( $a ) {
	if ( '' === trim( wp_strip_all_tags( $a['title'] ) ) ) {
		return '';
	}
	$media = $a['image']
		? zv_image( $a['image'], array( 'sizes' => '(max-width: 760px) 100vw, 280px', 'alt' => wp_strip_all_tags( $a['title'] ) ) )
		: '<span class="route-ph">' . zv_icon( $a['icon'] ) . '</span>';
	return '<li class="route-step"><div class="route-img">' . $media . ( $a['time'] ? '<span class="route-time">' . zv_e( $a['time'] ) . '</span>' : '' ) . '</div>'
		. '<div class="route-body"><h3>' . zv_e( $a['title'] ) . '</h3><p>' . zv_k( $a['text'] ) . '</p></div></li>';
}

/** "Getting here" and "Before you come" sections, used on the Contact page. */
function zv_findus_sections() {
	$step = fn( $icon, $t, $x, $time = '' ) => zv_b( 'zv/route-step', array( 'icon' => $icon, 'title' => $t, 'text' => $x, 'time' => $time ) );
	return array(
		zv_sec( array( 'background' => 'cream', 'anchor' => 'find-us', 'eyebrow' => 'Getting here', 'title' => 'Three minutes from <em>Sol</em>', 'text' => 'Right in the centre of Madrid. Open the route on your phone, or follow the steps below.' ), array(
			zv_b( 'zv/directions' ),
			zv_b( 'zv/route', array(), array(
				$step( 'pin', 'Take the metro to Sol', 'Lines 1, 2 and 3 and the Cercanías trains all stop at Sol, the very heart of Madrid.' ),
				$step( 'walk', 'Walk to Calle de la Cruz', 'From Puerta del Sol it is about three minutes on foot. Open the route for the exact turns.', '3 min' ),
				$step( 'home', 'Find number 16', 'Our door is at Calle de la Cruz, 16.' ),
				$step( 'clock', 'Arrive 15 minutes early', 'Time to check in, fill in a short health form and slip off your shoes.' ),
			) ),
		) ),
		zv_sec( array(), array( zv_faq( array(
			array( 'Can I just walk in?', 'Yes, walk-ins are always welcome. Booking ahead guarantees your time, especially at weekends.', true ),
			array( 'How early should I arrive?', '15 minutes before your appointment, to check in and fill in the Customer Intake Form.' ),
			array( 'I am running late. What should I do?', 'Send us a WhatsApp. Your treatment may be shorter if you arrive late, so let us know as soon as you can.' ),
		), array( 'eyebrow' => 'Good to know', 'title' => 'Before you come' ) ) ) ),
	);
}

// Addresses of pages that were merged or removed keep working.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	$map  = array(
		'how-to-find-us'   => array( 'contact', '#find-us' ),
		'redeem-gift-card' => array( 'gift-cards', '' ),
	);
	if ( isset( $map[ $path ] ) && ( $page = get_page_by_path( $map[ $path ][0] ) ) ) {
		wp_safe_redirect( get_permalink( $page ) . $map[ $path ][1], 301 );
		exit;
	}
} );

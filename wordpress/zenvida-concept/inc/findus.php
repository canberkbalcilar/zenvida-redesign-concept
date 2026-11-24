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

/** Page content for the starter content. */
function zv_seed_findus_page() {
	$step = fn( $icon, $t, $x, $time = '' ) => zv_b( 'zv/route-step', array( 'icon' => $icon, 'title' => $t, 'text' => $x, 'time' => $time ) );
	return wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'How to find us', 'post_name' => 'how-to-find-us', 'post_content' => wp_slash( serialize_blocks( array(
		zv_sec( array( 'eyebrow' => 'Getting here', 'title' => 'Three minutes from <em>Sol</em>', 'text' => 'Right in the centre of Madrid. Open the route on your phone, or follow the steps below.' ), array( zv_b( 'zv/directions' ) ) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'Step by step', 'title' => 'From Sol to our door' ), array( zv_b( 'zv/route', array(), array(
			$step( 'pin', 'Take the metro to Sol', 'Lines 1, 2 and 3 and the Cercanías trains all stop at Sol, the very heart of Madrid.' ),
			$step( 'walk', 'Walk to Calle de la Cruz', 'From Puerta del Sol it is about three minutes on foot. Open the route for the exact turns.', '3 min' ),
			$step( 'home', 'Find number 16', 'Our door is at Calle de la Cruz, 16.' ),
			$step( 'clock', 'Arrive 15 minutes early', 'Time to check in, fill in a short health form and slip off your shoes.' ),
		) ) ) ),
		zv_sec( array(), array( zv_b( 'zv/split', array(), array(
			zv_faq( array(
				array( 'Can I just walk in?', 'Yes, walk-ins are always welcome. Booking ahead guarantees your time, especially at weekends.', true ),
				array( 'How early should I arrive?', '15 minutes before your appointment, to check in and fill in the Customer Intake Form.' ),
				array( 'I am running late. What should I do?', 'Send us a WhatsApp. Your treatment may be shorter if you arrive late, so let us know as soon as you can.' ),
			), array( 'eyebrow' => 'Good to know', 'title' => 'Before you come' ) ),
			zv_b( 'zv/visit', array( 'eyebrow' => '', 'layout' => 'card' ) ),
		) ) ) ),
	) ) ) ) );
}

/** "I have a gift card": how to use it, book with it, ask about it. */
function zv_seed_redeem_page( $gift_page ) {
	$wa   = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) . '?text=' . rawurlencode( 'Hi! I have a ZenVida gift card and would like to book. My code is: ' );
	$step = fn( $t, $x ) => zv_b( 'zv/step', array( 'title' => $t, 'text' => $x ) );
	return wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'I have a gift card', 'post_name' => 'redeem-gift-card', 'post_content' => wp_slash( serialize_blocks( array(
		zv_sec( array( 'eyebrow' => 'Gift cards', 'title' => 'Someone gave you <em>calm</em>', 'text' => 'Lucky you. Here is how to use your ZenVida gift card, in four easy steps.' ), array(
			zv_b( 'zv/steps', array(), array(
				$step( 'Find your code', 'It is in the gift card email, and on the PDF if you printed it.' ),
				$step( 'Choose your ritual', 'Any ritual you like. If it costs more than your card, you simply pay the difference at the studio.' ),
				$step( 'Book your time', 'Send us a WhatsApp or call with your code, or walk in and show your card at reception.' ),
				$step( 'Enjoy', 'Arrive 15 minutes early and show your card when you check in.' ),
			) ),
			zv_b( 'zv/callout', array( 'icon' => '', 'text' => '<strong>Ready to book?</strong> Send us your code on WhatsApp and we will find a time for you.', 'price' => '', 'linkLabel' => 'Book on WhatsApp', 'linkUrl' => $wa ) ),
		) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'Questions about your card', 'title' => 'Check your balance or ask us anything' ), array( zv_b( 'zv/split', array(), array(
			zv_b( 'zv/contact-form', array( 'topics' => 'Book with my gift card, Check my balance, My card has expired, Something else', 'codeLabel' => 'Gift card code', 'buttonLabel' => 'Send' ) ),
			zv_faq( array(
				array( 'How long is my gift card valid?', '12 months from the day it was sent. The date is on your card.', true ),
				array( 'Can I choose a different ritual?', 'Yes. Your card can be used for any ritual; if the new one costs more, you pay the difference at the studio.' ),
				array( 'I lost my code. What can I do?', 'Write to us with the name or email of the person who bought the card, and we will look it up.' ),
				array( 'Can I book for two people?', 'Yes. Groups of two or more are arranged by our team, so send us a WhatsApp or call.' ),
			), array( 'eyebrow' => '', 'title' => 'Good to know' ) ),
		) ) ) ),
		zv_sec( array( 'padTop' => false ), array( zv_b( 'zv/callout', array( 'icon' => '', 'text' => '<strong>Want to give one too?</strong> Gift cards for any ritual or amount, emailed in minutes.', 'price' => '', 'linkLabel' => 'Send a gift card', 'linkUrl' => get_permalink( $gift_page ) ) ) ) ),
	) ) ) ) );
}

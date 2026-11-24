<?php
/**
 * Body map: "Where do you feel it?" — tap a body zone, see the rituals that help there.
 *
 * Each ritual has a "Where it helps" field (Ritual details → Category & labels) with the
 * zones below. The zv/body-map block draws the figure and lists matching rituals, most
 * popular first. Zones are fixed in code; which rituals belong to them is edited per ritual.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** slug => [label, what people feel there] */
function zv_body_zones() {
	return array(
		'mind'  => array( 'Head & mind', 'Stress, a busy mind, poor sleep' ),
		'face'  => array( 'Face', 'A tight jaw, tired or puffy skin' ),
		'neck'  => array( 'Neck & shoulders', 'Desk posture, screens, knots' ),
		'back'  => array( 'Back', 'Stiffness, upper or lower back pain' ),
		'legs'  => array( 'Legs', 'Heavy legs, sore muscles, long flights' ),
		'feet'  => array( 'Feet', 'Walking all day, standing at work' ),
		'whole' => array( 'Whole body', 'A full reset, head to toe' ),
	);
}

/* ------------------------------------------------ ritual form: "Where it helps" */

add_action( 'zv_ritual_details_extra', function ( $post ) {
	$sel = (array) get_post_meta( $post->ID, 'zv_zones', true );
	echo '<span class="zv-label">Where it helps (body map on the website)</span><p class="zv-checks">';
	foreach ( zv_body_zones() as $slug => $z ) {
		printf( '<label><input type="checkbox" name="zv_zones[]" value="%s" %s> %s</label>', esc_attr( $slug ), checked( in_array( $slug, $sel, true ), true, false ), esc_html( $z[0] ) );
	}
	echo '</p>';
} );

add_action( 'save_post_ritual', function ( $id ) {
	if ( ! zv_box_can_save( $id, 'zv_details_nonce', 'zv_details' ) ) {
		return;
	}
	$in = array_map( 'sanitize_key', (array) wp_unslash( $_POST['zv_zones'] ?? array() ) ); // phpcs:ignore
	update_post_meta( $id, 'zv_zones', array_values( array_intersect( $in, array_keys( zv_body_zones() ) ) ) );
}, 20 );

/* ------------------------------------------------------------ the block */

add_action( 'init', function () {
	zv_register( 'body-map', 'Body map', 'universal-access', array(
		'prompt' => 'Tap where you feel it',
		'count'  => 4,
	), 'zv_render_body_map', array( 'parent' => array( 'zv/section' ) ) );
}, 20 );

/** A small version of a photo for cards. */
function zv_image_small( $url, $w = 560 ) {
	$id = zv_image_id( $url );
	if ( $id ) {
		return wp_get_attachment_image_url( $id, 'medium_large' ) ?: $url;
	}
	if ( preg_match( '#/v1/fill/w_(\d+),h_(\d+)#', (string) $url, $m ) ) {
		return preg_replace( '#/v1/fill/w_\d+,h_\d+#', '/v1/fill/w_' . $w . ',h_' . (int) round( $w * $m[2] / max( 1, $m[1] ) ), $url );
	}
	return (string) $url;
}

function zv_render_body_map( $a ) {
	$f = get_template_directory() . '/assets/js/bodymap.js';
	wp_enqueue_script( 'zv-bodymap', get_template_directory_uri() . '/assets/js/bodymap.js', array(), ZV_VERSION . '.' . filemtime( $f ), true );

	$rituals = array();
	foreach ( zv_all_rituals() as $r ) {
		$zones = (array) get_post_meta( $r['id'], 'zv_zones', true );
		if ( ! $zones ) {
			continue;
		}
		$prices    = array_filter( array_column( $r['opts'], 1 ), fn( $p ) => null !== $p );
		$mins      = array_column( $r['opts'], 0 );
		$rituals[] = array(
			'name'  => $r['name'],
			'area'  => html_entity_decode( $r['areaName'], ENT_QUOTES, 'UTF-8' ),
			'desc'  => $r['desc'],
			'url'   => $r['url'],
			'img'   => zv_image_small( (string) get_post_meta( $r['id'], 'zv_image', true ) ),
			'from'  => $prices ? min( $prices ) : null,
			'mins'  => count( $mins ) > 1 ? min( $mins ) . '–' . max( $mins ) : (string) $mins[0],
			'two'   => in_array( 'two', $r['flags'], true ),
			'pop'   => $r['pop'],
			'zones' => array_values( $zones ),
		);
	}
	usort( $rituals, fn( $x, $y ) => $y['pop'] <=> $x['pop'] );

	$zones = zv_body_zones();
	$data  = array( 'zones' => array_map( fn( $z ) => array( 'label' => $z[0], 'hint' => $z[1] ), $zones ), 'rituals' => $rituals, 'count' => max( 1, (int) $a['count'] ), 'all' => zv_rituals_url() );

	// The figure: one neutral silhouette; zones are separate shapes so each can light up.
	$zone = fn( $slug, $shape ) => '<g class="bm-zone" data-zone="' . esc_attr( $slug ) . '" role="button" tabindex="0" aria-label="' . esc_attr( $zones[ $slug ][0] ) . '">' . $shape . '</g>';
	$svg  = '<svg class="bm-figure" viewBox="0 0 200 470" aria-hidden="false" role="group" aria-label="Body map">'
		. '<g class="bm-base">'
		. '<ellipse cx="100" cy="48" rx="27" ry="33"/>'
		. '<rect x="89" y="76" width="22" height="22" rx="6"/>'
		. '<path d="M56 100 Q100 86 144 100 L150 110 Q154 150 140 176 L136 254 Q100 266 64 254 L60 176 Q46 150 50 110 Z"/>'
		. '<path d="M50 108 Q36 112 34 140 L28 236 Q27 250 36 252 Q44 252 45 238 L54 150 Z"/><path d="M150 108 Q164 112 166 140 L172 236 Q173 250 164 252 Q156 252 155 238 L146 150 Z"/>'
		. '<path d="M66 252 L98 258 L96 412 Q95 420 86 420 L80 420 Q72 420 72 410 Z"/><path d="M134 252 L102 258 L104 412 Q105 420 114 420 L120 420 Q128 420 128 410 Z"/>'
		. '<ellipse cx="82" cy="440" rx="17" ry="12"/><ellipse cx="118" cy="440" rx="17" ry="12"/>'
		. '</g>'
		. $zone( 'mind', '<path d="M73 48 A27 33 0 0 1 127 48 Z"/>' )
		. $zone( 'face', '<path d="M73 48 A27 33 0 0 0 127 48 Z"/>' )
		. $zone( 'neck', '<path d="M89 78 L111 78 L111 94 Q130 92 146 100 L151 118 Q100 106 49 118 L54 100 Q70 92 89 94 Z"/>' )
		. $zone( 'back', '<path d="M51 120 Q100 108 149 120 Q152 152 140 176 L136 252 Q100 264 64 252 L60 176 Q48 152 51 120 Z"/>' )
		. $zone( 'legs', '<path d="M66 256 L98 262 L96 412 Q95 420 86 420 L80 420 Q72 420 72 410 Z M134 256 L102 262 L104 412 Q105 420 114 420 L120 420 Q128 420 128 410 Z"/>' )
		. $zone( 'feet', '<path d="M65 440 a17 12 0 1 0 34 0 a17 12 0 1 0 -34 0 Z M101 440 a17 12 0 1 0 34 0 a17 12 0 1 0 -34 0 Z"/>' )
		. '</svg>';

	$chips = '';
	foreach ( $zones as $slug => $z ) {
		$chips .= '<button type="button" class="bm-chip" data-zone="' . esc_attr( $slug ) . '" aria-pressed="false">' . esc_html( $z[0] ) . '</button>';
	}

	return '<div class="bodymap" data-bodymap>'
		. '<div class="bm-left"><p class="bm-prompt">' . zv_e( $a['prompt'] ) . '</p>' . $svg . '<div class="bm-chips" role="group" aria-label="Choose an area">' . $chips . '</div></div>'
		. '<div class="bm-right" aria-live="polite"><div class="bm-head"><span class="eyebrow" data-bm-k>Most booked</span><h3 data-bm-title>Not sure where to start?</h3><p data-bm-hint>Tap an area of the body, or choose one of these favourites.</p></div>'
		. '<div class="bm-list" data-bm-list></div><a class="link bm-all" href="' . esc_url( zv_rituals_url() ) . '">See all rituals and prices</a></div>'
		. '<script type="application/json" data-bm-data>' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>'
		. '</div>';
}

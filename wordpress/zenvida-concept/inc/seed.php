<?php
/**
 * Demo content: Areas, Needs and the 19 rituals from zenvida.es (November 2026).
 * Runs once (from the Playground blueprint, or on theme activation).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function zv_img( $id, $w = 600, $h = 450 ) {
	return "https://static.wixstatic.com/media/$id/v1/fill/w_$w,h_$h,al_c,q_80,enc_auto/$id";
}

function zv_seed() {
	if ( get_option( 'zv_seeded' ) ) {
		return;
	}

	// Seed as the admin so post HTML (SVG, data attributes) is kept intact.
	wp_set_current_user( 1 );
	kses_remove_filters();

	// Areas ---------------------------------------------------------------
	$areas = array(
		'feet-legs' => array( 'Feet & Legs', '#C9A97A', zv_img( 'b84849_bc97f0d7591f40d3a0dce6cb229264c7~mv2.png' ),
			'Tension that starts in the feet rarely stays there. Reflexology and Spanish aromatherapy, in a plush recliner in your own curtained nook.',
			'Our treatments work pressure points along the soles and calves to ease heaviness, boost circulation and help your whole body recover — ideal after a day exploring the city or on your feet at work.' ),
		'massage'   => array( 'Massage', '#8E9A82', zv_img( 'b84849_053fa3c0ce6547089bbdce2b11a786e6~mv2.png' ),
			'Full-body massage in private rooms — for deep muscular relief, gentle relaxation or renewed energy.',
			'Our therapists adapt every massage to your needs, to restore balance, release deep-seated tension, improve circulation and help you move more freely.' ),
		'face'      => array( 'Face', '#C79A8E', zv_img( 'b84849_a884823698e844ed8db2619d9bece665~mv2.png' ),
			'Lymphatic facial work, Japanese Kobido and deep muscle release — always including neck, shoulders and décolletage.',
			"Facial massage stimulates circulation, collagen and lymphatic flow to ease puffiness and jaw tension and bring back your skin's natural glow, with Spanish aromatherapy and organic creams." ),
		'spa'       => array( 'Spa & Body', '#8FA3B0', '',
			"Seasonal body treatments made with Spain's natural ingredients.",
			'Limited-edition rituals that change with the seasons, from salt scrubs to herbal soaks.' ),
	);
	$i = 0;
	foreach ( $areas as $slug => $a ) {
		$t = term_exists( $slug, 'area' ) ?: wp_insert_term( $a[0], 'area', array( 'slug' => $slug, 'description' => $a[3] ) );
		$tid = is_array( $t ) ? (int) $t['term_id'] : (int) $t;
		update_term_meta( $tid, 'zv_color', $a[1] );
		update_term_meta( $tid, 'zv_image', $a[2] );
		update_term_meta( $tid, 'zv_long', $a[4] );
		update_term_meta( $tid, 'zv_order', $i++ );
	}

	// Needs ---------------------------------------------------------------
	$needs = array(
		'feet' => 'Tired feet', 'stress' => 'Stress & sleep', 'back' => 'Back & neck', 'sport' => 'Sports recovery',
		'travel' => 'Travel & jet lag', 'glow' => 'Glow & skin', 'preg' => 'Pregnancy', 'two' => 'For two',
	);
	$i = 0;
	foreach ( $needs as $slug => $name ) {
		$t   = term_exists( $slug, 'need' ) ?: wp_insert_term( $name, 'need', array( 'slug' => $slug ) );
		$tid = is_array( $t ) ? (int) $t['term_id'] : (int) $t;
		update_term_meta( $tid, 'zv_order', $i++ );
	}

	// Rituals: slug, name, area, card text, options, needs, flags, popularity
	$rituals = array(
		array( 'foot-massage', 'Foot Massage', 'feet-legs', 'A quick, relaxing foot massage. Add 10 min of neck & shoulders for €10.', '25:25', 'feet', '', 7 ),
		array( 'reflexology', 'Reflexology', 'feet-legs', 'Targeted pressure points on feet and calves to restore balance and rest tired feet.', '30:40', 'feet,travel', '', 9 ),
		array( 'deluxe-reflexology', 'Deluxe Reflexology', 'feet-legs', 'Aromatherapy foot spa, reflexology and calf work, plus a neck & shoulder massage.', '60:60', 'feet,stress', 'sig', 10 ),
		array( 'swedish-massage', 'Swedish Massage', 'massage', 'Smooth, flowing full-body strokes to lower stress and improve sleep.', '60:80, 90:110', 'stress', '', 8 ),
		array( 'deep-tissue-massage', 'Deep Tissue Massage', 'massage', 'Firm, slow pressure for chronic tension, knots and stiffness.', '60:90, 90:130', 'back,sport', '', 9 ),
		array( 'thai-massage', 'Thai Massage', 'massage', 'Stretching and pressure to release deep tension from sitting and screens.', '60:90, 90:125', 'back,sport', '', 7 ),
		array( 'sports-massage', 'Sports Massage', 'massage', 'Recovery and muscle care before or after training or a race.', '60:90, 90:125', 'sport', '', 6 ),
		array( 'lomi-lomi-massage', 'Lomi Lomi Massage', 'massage', 'Flowing Hawaiian forearm strokes with warm Spanish oils.', '60:80, 90:110', 'stress', '', 6 ),
		array( 'lymphatic-drainage-massage', 'Lymphatic Drainage', 'massage', 'A gentle, rhythmic technique to reduce fluid retention and feel lighter.', '60:80, 90:110', 'travel,sport', '', 6 ),
		array( 'combined-massage', 'Combined Massage', 'massage', 'Deep tissue, Swedish and reflexology blended around what you need today.', '90:115', 'back,stress', '', 7 ),
		array( 'pregnancy-massage', 'Pregnancy Massage', 'massage', 'Adapted for every trimester to ease back pain, swelling and tired legs.', '60:90', 'preg', '', 5 ),
		array( 'travel-recovery', 'Travel Recovery', 'massage', 'Made for arrival day: eases jet lag, swollen feet and flight stiffness.', '80:120', 'travel,feet', 'sig', 8 ),
		array( 'energy-balance', 'Energy Balance', 'massage', 'Full-body massage, meridian work, Reiki and chakra balancing.', '100:130', 'stress', 'sig', 6 ),
		array( 'thai-andaluz', 'Thai Andaluz', 'massage', 'Royal Thai massage with Andalusian aromatherapy oils and herbal compresses.', '110:145', 'back,stress', 'sig', 7 ),
		array( 'full-body-reset', 'Full Body Reset', 'massage', 'Facial and scalp massage, a slow full-body massage, then reflexology to finish.', '120:160', 'stress,travel', 'sig', 8 ),
		array( 'couples-massage', 'Couples Massage', 'massage', 'Two therapists, one shared room — for partners, friends or family. Price for two.', '90:220, 120:280', 'two', 'sig,two', 7 ),
		array( 'classic-facial-massage', 'Classic Facial Massage', 'face', 'Soft, soothing face and neck massage to reduce puffiness and refresh your skin.', '45:50, 60:70', 'glow,stress', '', 6 ),
		array( 'kobido-facial-massage', 'Kobido Facial Massage', 'face', 'The Japanese lifting technique for firmer, brighter skin — a natural facelift.', '45:60, 60:80', 'glow', '', 7 ),
		array( 'ritual-levante', 'Ritual Levante', 'spa', 'Full-body scrub with Mar Menor salt, olive pit and almond oil. No massage needed.', '50:', 'glow', 'seas', 5 ),
	);

	$deluxe_html = file_get_contents( __DIR__ . '/deluxe-content.html' );
	$area_imgs   = wp_list_pluck( array_map( fn( $a ) => array( 'i' => $a[2] ), $areas ), 'i' );

	foreach ( $rituals as $order => $r ) {
		list( $slug, $name, $area, $desc, $opts, $need, $flags, $pop ) = $r;
		$is_deluxe = 'deluxe-reflexology' === $slug;
		$flags     = array_filter( explode( ',', $flags ) );

		$content = $is_deluxe
			? $deluxe_html
			: '<section class="block alt"><div class="wrap"><div class="sec-head"><span class="eyebrow">About this ritual</span><h2>' . esc_html( $name ) . '</h2><p class="lead">' . esc_html( $desc ) . '</p></div></div></section>';

		$id = wp_insert_post( array(
			'post_type'    => 'ritual',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_name'    => $slug,
			'menu_order'   => $order,
			'post_excerpt' => $is_deluxe
				? 'Our signature 60-minute ritual: an aromatherapy herbal foot spa, deep reflexology for the feet and calves, and a neck, shoulder and upper-back massage — a complete release from head to toe.'
				: $desc,
			'post_content' => "<!-- wp:html -->\n" . $content . "\n<!-- /wp:html -->",
		) );
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		wp_set_object_terms( $id, $area, 'area' );
		wp_set_object_terms( $id, explode( ',', $need ), 'need' );
		update_post_meta( $id, 'zv_desc', $desc );
		update_post_meta( $id, 'zv_options', $opts );
		update_post_meta( $id, 'zv_popularity', $pop );
		update_post_meta( $id, 'zv_deposit', 25 );
		update_post_meta( $id, 'zv_signature', in_array( 'sig', $flags, true ) ? '1' : '' );
		update_post_meta( $id, 'zv_seasonal', in_array( 'seas', $flags, true ) ? '1' : '' );
		update_post_meta( $id, 'zv_couples', in_array( 'two', $flags, true ) ? '1' : '' );
		update_post_meta( $id, 'zv_baths', $is_deluxe ? '1' : '' );
		update_post_meta( $id, 'zv_image', $is_deluxe
			? zv_img( 'b84849_9e0380cb868c40ad91407065069912cc~mv2.jpg', 1600, 1000 )
			: str_replace( 'w_600,h_450', 'w_1600,h_1000', (string) $area_imgs[ $area ] ) );
		if ( $is_deluxe ) {
			update_post_meta( $id, 'zv_highlights', "Foot spa | Spanish aromatherapy herbal soak\nReflexology | Pressure-point work on feet & calves\nUpper body | Neck, shoulders & upper back" );
		}
	}

	update_option( 'zv_seeded', 1 );
	flush_rewrite_rules();
}

// Fires on 'init' (priority 99) after the switch, so the post type and taxonomies exist.
add_action( 'after_switch_theme', 'zv_seed' );

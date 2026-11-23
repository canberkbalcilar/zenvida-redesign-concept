<?php
/**
 * Demo content: areas, needs, 19 rituals, journal posts, and the Home / Rituals / Journal
 * pages built from ZenVida blocks, plus menus and settings. Runs once, on theme activation
 * (or from the Playground blueprint). Define ZV_SKIP_SEED to turn it off.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function zv_img( $id, $w = 600, $h = 450 ) {
	return "https://static.wixstatic.com/media/$id/v1/fill/w_$w,h_$h,al_c,q_80,enc_auto/$id";
}

/** Block array helpers for serialize_blocks(). */
function zv_b( $name, $attrs = array(), $inner = array() ) {
	return array(
		'blockName'    => $name,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner,
		'innerHTML'    => '',
		'innerContent' => $inner ? array_fill( 0, count( $inner ), null ) : array(),
	);
}
function zv_p( $html ) {
	return array( 'blockName' => 'core/paragraph', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => "<p>$html</p>", 'innerContent' => array( "<p>$html</p>" ) );
}
function zv_sec( $attrs, $inner ) {
	return zv_b( 'zv/section', $attrs, $inner );
}
function zv_faq( $items, $attrs = array() ) {
	return zv_b( 'zv/faq', $attrs, array_map( fn( $q ) => zv_b( 'zv/faq-item', array( 'question' => $q[0], 'answer' => $q[1] ) + ( ! empty( $q[2] ) ? array( 'open' => true ) : array() ) ), $items ) );
}

function zv_seed() {
	if ( get_option( 'zv_seeded' ) || defined( 'ZV_SKIP_SEED' ) ) {
		return;
	}
	update_option( 'zv_seeded', 1 );
	kses_remove_filters();

	// WordPress's own sample content would show up in the journal and page list.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $d ) {
		$p = get_page_by_path( $d[0], OBJECT, $d[1] );
		if ( $p && strtotime( $p->post_date_gmt ) > time() - DAY_IN_SECONDS ) {
			wp_delete_post( $p->ID, true );
		}
	}

	/* ---------- Areas ---------- */
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
		'spa'       => array( 'Spa & Body', '#8FA3B0', zv_img( 'b84849_f8657f30cf25410c9d3a2c4b899b82f3~mv2.jpg' ),
			"Seasonal body treatments made with Spain's natural ingredients.",
			'Limited-edition rituals that change with the seasons, from salt scrubs to herbal soaks.' ),
	);
	$i = 0;
	foreach ( $areas as $slug => $a ) {
		$t   = term_exists( $slug, 'area' ) ?: wp_insert_term( $a[0], 'area', array( 'slug' => $slug, 'description' => $a[3] ) );
		$tid = is_array( $t ) ? (int) $t['term_id'] : (int) $t;
		update_term_meta( $tid, 'zv_color', $a[1] );
		update_term_meta( $tid, 'zv_image', $a[2] );
		update_term_meta( $tid, 'zv_long', $a[4] );
		update_term_meta( $tid, 'zv_order', $i++ );
	}

	/* ---------- Needs ---------- */
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

	/* ---------- Shared blocks ---------- */
	$visit_section = zv_sec( array( 'background' => 'cream', 'anchor' => 'visit', 'eyebrow' => 'Visit us', 'title' => 'A sanctuary in the heart of Madrid', 'text' => "A private, calm studio five minutes' walk from Puerta del Sol." ),
		array( zv_b( 'zv/visit', array( 'eyebrow' => '', 'title' => '', 'layout' => 'wide' ) ) ) );
	$basic_faq = array(
		array( 'Do I need to book?', 'No, walk-ins are welcome. Booking online guarantees your time; a €25 deposit secures it and the rest is paid after your ritual.', true ),
		array( 'What is your cancellation policy?', "Please give at least 4 hours' notice to cancel or reschedule. No-shows and late cancellations are charged 50%." ),
		array( 'Can I come with a friend?', 'Yes — many rituals can be booked for small groups so you can relax side by side. Choose the number of guests in the booking card.' ),
	);

	/* ---------- Rituals ---------- */
	// slug, name, area, card text, options, needs, flags, popularity, photo
	$rituals = array(
		array( 'foot-massage', 'Foot Massage', 'feet-legs', 'A quick, relaxing foot massage. Add 10 min of neck & shoulders for €10.', '25:25', 'feet', '', 7, '' ),
		array( 'reflexology', 'Reflexology', 'feet-legs', 'Targeted pressure points on feet and calves to restore balance and rest tired feet.', '30:40', 'feet,travel', '', 9, '' ),
		array( 'deluxe-reflexology', 'Deluxe Reflexology', 'feet-legs', 'Aromatherapy foot spa, reflexology and calf work, plus a neck & shoulder massage.', '60:60', 'feet,stress', 'sig', 10, 'b84849_9e0380cb868c40ad91407065069912cc~mv2.jpg' ),
		array( 'swedish-massage', 'Swedish Massage', 'massage', 'Smooth, flowing full-body strokes to lower stress and improve sleep.', '60:80, 90:110', 'stress', '', 8, '' ),
		array( 'deep-tissue-massage', 'Deep Tissue Massage', 'massage', 'Firm, slow pressure for chronic tension, knots and stiffness.', '60:90, 90:130', 'back,sport', '', 9, '' ),
		array( 'thai-massage', 'Thai Massage', 'massage', 'Stretching and pressure to release deep tension from sitting and screens.', '60:90, 90:125', 'back,sport', '', 7, '' ),
		array( 'sports-massage', 'Sports Massage', 'massage', 'Recovery and muscle care before or after training or a race.', '60:90, 90:125', 'sport', '', 6, '' ),
		array( 'lomi-lomi-massage', 'Lomi Lomi Massage', 'massage', 'Flowing Hawaiian forearm strokes with warm Spanish oils.', '60:80, 90:110', 'stress', '', 6, '' ),
		array( 'lymphatic-drainage-massage', 'Lymphatic Drainage', 'massage', 'A gentle, rhythmic technique to reduce fluid retention and feel lighter.', '60:80, 90:110', 'travel,sport', '', 6, '' ),
		array( 'combined-massage', 'Combined Massage', 'massage', 'Deep tissue, Swedish and reflexology blended around what you need today.', '90:115', 'back,stress', '', 7, '' ),
		array( 'pregnancy-massage', 'Pregnancy Massage', 'massage', 'Adapted for every trimester to ease back pain, swelling and tired legs.', '60:90', 'preg', '', 5, '' ),
		array( 'travel-recovery', 'Travel Recovery', 'massage', 'Made for arrival day: eases jet lag, swollen feet and flight stiffness.', '80:120', 'travel,feet', 'sig', 8, 'b84849_4e8d66b9b6d145ca963603d3e50950ee~mv2.jpg' ),
		array( 'energy-balance', 'Energy Balance', 'massage', 'Full-body massage, meridian work, Reiki and chakra balancing.', '100:130', 'stress', 'sig', 6, 'b84849_3c6a459dcd71493ea41290b153870b6c~mv2.jpg' ),
		array( 'thai-andaluz', 'Thai Andaluz', 'massage', 'Royal Thai massage with Andalusian aromatherapy oils and herbal compresses.', '110:145', 'back,stress', 'sig', 7, 'b84849_92debe14e8e14ebd940e25f5dbcf316f~mv2.png' ),
		array( 'full-body-reset', 'Full Body Reset', 'massage', 'Facial and scalp massage, a slow full-body massage, then reflexology to finish.', '120:160', 'stress,travel', 'sig', 8, 'b84849_1f1fa72b2a3f4ef89474097bb85fdecc~mv2.jpg' ),
		array( 'couples-massage', 'Couples Massage', 'massage', 'Two therapists, one shared room — for partners, friends or family. Price for two.', '90:220, 120:280', 'two', 'sig,two', 7, 'b84849_c9b0f029c3b049788e35b0fcc213cf7b~mv2.jpg' ),
		array( 'classic-facial-massage', 'Classic Facial Massage', 'face', 'Soft, soothing face and neck massage to reduce puffiness and refresh your skin.', '45:50, 60:70', 'glow,stress', '', 6, '' ),
		array( 'kobido-facial-massage', 'Kobido Facial Massage', 'face', 'The Japanese lifting technique for firmer, brighter skin — a natural facelift.', '45:60, 60:80', 'glow', '', 7, '' ),
		array( 'ritual-levante', 'Ritual Levante', 'spa', 'Full-body scrub with Mar Menor salt, olive pit and almond oil. No massage needed.', '50:', 'glow', 'seas', 5, '' ),
	);

	foreach ( $rituals as $order => $r ) {
		list( $slug, $name, $area, $desc, $opts, $need, $flags, $pop, $photo ) = $r;
		$deluxe = 'deluxe-reflexology' === $slug;
		$flags  = array_filter( explode( ',', $flags ) );

		$blocks = $deluxe ? zv_deluxe_blocks( $visit_section ) : array(
			zv_sec( array( 'background' => 'cream', 'eyebrow' => 'About this ritual', 'title' => $name, 'text' => $desc ), array() ),
			zv_sec( array( 'eyebrow' => 'FAQ', 'title' => 'Good to know' ), array( zv_faq( $basic_faq ) ) ),
			$visit_section,
		);

		$id = wp_insert_post( array(
			'post_type'    => 'ritual',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_name'    => $slug,
			'menu_order'   => $order,
			'post_excerpt' => $deluxe
				? 'Our signature 60-minute ritual: an aromatherapy herbal foot spa, deep reflexology for the feet and calves, and a neck, shoulder and upper-back massage — a complete release from head to toe.'
				: $desc,
			'post_content' => wp_slash( serialize_blocks( $blocks ) ),
		) );
		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}
		wp_set_object_terms( $id, $area, 'area' );
		wp_set_object_terms( $id, explode( ',', $need ), 'need' );
		$meta = array(
			'zv_desc'       => $desc,
			'zv_options'    => $opts,
			'zv_popularity' => $pop,
			'zv_deposit'    => 25,
			'zv_signature'  => in_array( 'sig', $flags, true ) ? '1' : '',
			'zv_seasonal'   => in_array( 'seas', $flags, true ) ? '1' : '',
			'zv_couples'    => in_array( 'two', $flags, true ) ? '1' : '',
			'zv_baths'      => $deluxe ? '1' : '',
			'zv_image'      => $photo ? zv_img( $photo, 1600, 1000 ) : str_replace( 'w_600,h_450', 'w_1600,h_1000', $areas[ $area ][2] ),
		);
		if ( $deluxe ) {
			$meta['zv_highlights'] = "Foot spa | Spanish aromatherapy herbal soak\nReflexology | Pressure-point work on feet & calves\nUpper body | Neck, shoulders & upper back";
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
	}

	/* ---------- Journal posts ---------- */
	$posts = array(
		array( 'Walking Madrid? What tired feet are telling you', 'Reflexology', 'b84849_9e0380cb868c40ad91407065069912cc~mv2.jpg', 'Why a city made for walking is the perfect place for reflexology.' ),
		array( "September in Madrid: the locals' secret season", 'Madrid guide', 'b84849_4e8d66b9b6d145ca963603d3e50950ee~mv2.jpg', 'Softer light, shorter queues and the city at its best.' ),
		array( 'The 5pm slump: what your body needs after work', 'Mind & stress', 'b84849_3c6a459dcd71493ea41290b153870b6c~mv2.jpg', 'Small resets for the most tired hour of the day.' ),
	);
	foreach ( array_reverse( $posts ) as $n => $p ) {
		$cat = term_exists( $p[1], 'category' ) ?: wp_insert_term( $p[1], 'category' );
		$pid = wp_insert_post( array(
			'post_status'   => 'publish',
			'post_title'    => $p[0],
			'post_excerpt'  => $p[3],
			'post_date'     => gmdate( 'Y-m-d H:i:s', time() - ( 3 - $n ) * DAY_IN_SECONDS ),
			'post_content'  => wp_slash( serialize_blocks( array( zv_p( $p[3] ), zv_p( 'Sample article for the redesign concept — the real Respiro Journal posts would appear here.' ) ) ) ),
			'post_category' => array( is_array( $cat ) ? (int) $cat['term_id'] : (int) $cat ),
		) );
		update_post_meta( $pid, 'zv_image', zv_img( $p[2], 1200, 800 ) );
	}

	/* ---------- Pages ---------- */
	$rituals_url = get_post_type_archive_link( 'ritual' );
	$term_url    = fn( $s ) => get_term_link( $s, 'area' );
	$home        = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Home', 'post_name' => 'home', 'post_content' => wp_slash( serialize_blocks( zv_home_blocks( $rituals_url, $term_url ) ) ) ) );
	$rpage       = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Rituals', 'post_name' => 'rituals-page', 'post_content' => wp_slash( serialize_blocks( zv_rituals_blocks() ) ) ) );
	$journal     = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Journal', 'post_name' => 'journal' ) );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $journal );

	/* ---------- Settings ---------- */
	$foot = get_page_by_path( 'foot-massage', OBJECT, 'ritual' );
	update_option( 'zv_settings', array_merge( zv_defaults(), array(
		'rituals_page' => (string) $rpage,
		'promo_text'   => 'Short on time? <a href="' . esc_url( $foot ? get_permalink( $foot ) : $rituals_url ) . '">Foot Massage 25 min — €25</a> · add neck &amp; shoulders for €10',
	) ) );

	/* ---------- Menus ---------- */
	$home_url = home_url( '/' );
	zv_seed_menu( 'Main menu', 'primary', array(
		array( 'Rituals', $rituals_url ), array( 'Experiences', '#' ), array( 'Offers & Packs', $home_url . '#offers' ),
		array( 'Gift Cards', $home_url . '#offers' ), array( 'Journal', get_permalink( $journal ) ), array( 'Visit', '#visit' ),
	) );
	$area_links = array();
	foreach ( $areas as $slug => $a ) {
		$area_links[] = array( $a[0], $term_url( $slug ) );
	}
	$area_links[] = array( 'All rituals & prices', $rituals_url );
	zv_seed_menu( 'Rituals', 'footer-1', $area_links );
	zv_seed_menu( 'Offers', 'footer-2', array( array( 'Membership', $home_url . '#offers' ), array( 'Packs', $home_url . '#offers' ), array( 'Gift Cards', $home_url . '#offers' ), array( 'Loyalty Card', $home_url . '#offers' ) ) );
	zv_seed_menu( 'Visit', 'footer-3', array( array( 'Calle de la Cruz, 16', zv_opt( 'maps_url' ) ), array( '+34 690 829 142', 'tel:+34690829142' ), array( 'hola@zenvida.es', 'mailto:hola@zenvida.es' ), array( 'Respiro Journal', get_permalink( $journal ) ) ) );
	zv_seed_menu( 'Legal', 'legal', array( array( 'Privacy', '#' ), array( 'Cookies', '#' ), array( 'Terms', '#' ), array( 'Booking policy', '#' ) ) );

	// Skip the block editor's welcome popup for administrators.
	global $wpdb;
	foreach ( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) as $uid ) {
		$key   = $wpdb->get_blog_prefix() . 'persisted_preferences';
		$prefs = (array) get_user_meta( $uid, $key, true );
		$prefs['core/edit-post'] = array_merge( (array) ( $prefs['core/edit-post'] ?? array() ), array( 'welcomeGuide' => false ) );
		$prefs['_modified']      = gmdate( 'c' );
		update_user_meta( $uid, $key, $prefs );
	}

	flush_rewrite_rules();
}

function zv_seed_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	$id   = $menu ? $menu->term_id : wp_create_nav_menu( $name );
	if ( is_wp_error( $id ) ) {
		return;
	}
	foreach ( $items as $pos => $it ) {
		wp_update_nav_menu_item( $id, 0, array( 'menu-item-title' => $it[0], 'menu-item-url' => $it[1], 'menu-item-status' => 'publish', 'menu-item-position' => $pos + 1 ) );
	}
	$locs              = get_theme_mod( 'nav_menu_locations', array() );
	$locs[ $location ] = $id;
	set_theme_mod( 'nav_menu_locations', $locs );
}

function zv_home_blocks( $rituals_url, $term_url ) {
	$tile = fn( $title, $sub, $url, $img, $color ) => zv_b( 'zv/area-tile', array( 'title' => $title, 'subtitle' => $sub, 'url' => $url, 'image' => $img, 'color' => $color ) );
	return array(
		zv_b( 'zv/hero', array( 'image' => zv_img( 'b84849_1f76460dcaab4a9d9b293ecf6b206bf4~mv2.jpg', 2000, 1200 ) ) ),
		zv_b( 'zv/quick-book' ),
		zv_sec( array( 'eyebrow' => 'Our rituals', 'title' => 'Where do you feel it today?', 'linkLabel' => 'See all rituals with prices', 'linkUrl' => $rituals_url ), array(
			zv_b( 'zv/area-grid', array(), array(
				$tile( 'Feet & Legs', 'Reflexology from €25 · our speciality', $term_url( 'feet-legs' ), zv_img( 'b84849_bc97f0d7591f40d3a0dce6cb229264c7~mv2.png', 900, 1100 ), '#C9A97A' ),
				$tile( 'Massage', '13 rituals · from €80', $term_url( 'massage' ), zv_img( 'b84849_053fa3c0ce6547089bbdce2b11a786e6~mv2.png', 700, 520 ), '#8E9A82' ),
				$tile( 'Face', 'Kobido & facial · from €50', $term_url( 'face' ), zv_img( 'b84849_a884823698e844ed8db2619d9bece665~mv2.png', 700, 520 ), '#C79A8E' ),
				$tile( 'For two', 'Couples Massage · from €220', add_query_arg( 'need', 'two', $rituals_url ), zv_img( 'b84849_c9b0f029c3b049788e35b0fcc213cf7b~mv2.jpg', 700, 520 ), '#8d4a58' ),
				$tile( 'Spa & Body', 'Seasonal: Ritual Levante', $term_url( 'spa' ), zv_img( 'b84849_f8657f30cf25410c9d3a2c4b899b82f3~mv2.jpg', 700, 520 ), '#8FA3B0' ),
			) ),
			zv_b( 'zv/need-chips' ),
		) ),
		zv_sec( array( 'background' => 'dark', 'eyebrow' => 'Signature rituals', 'title' => "Made only here — you'll walk out on clouds", 'text' => 'Longer journeys that blend several techniques with Spanish botanicals. Choose one when you have time to fully switch off.', 'linkLabel' => 'Full menu', 'linkUrl' => $rituals_url, 'linkStyle' => 'button' ), array(
			zv_b( 'zv/signature' ),
		) ),
		zv_sec( array(), array(
			zv_b( 'zv/pillars', array( 'title' => "A refuge from Madrid's beautiful chaos" ), array(
				zv_b( 'zv/pillar', array( 'icon' => 'home', 'title' => 'Sanctuary', 'text' => 'A calm space to slow down, exhale and remember what it feels like to just be present — private rooms and curtained nooks.' ) ),
				zv_b( 'zv/pillar', array( 'icon' => 'heart', 'title' => 'Rituals', 'text' => 'Wellness is a practice, not a luxury. Certified therapists restore your body, soothe your mind and refresh your spirit.' ) ),
				zv_b( 'zv/pillar', array( 'icon' => 'leaf', 'title' => 'Culture', 'text' => 'From locally harvested botanicals to premium olive oil — everything is connected to the land and stories that make Spain special.' ) ),
			) ),
		) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'First time?', 'title' => 'How a visit works' ), array(
			zv_b( 'zv/steps', array(), array(
				zv_b( 'zv/step', array( 'title' => 'Book or walk in', 'text' => 'Pick a time online in under a minute, or just drop by — walk-ins are always welcome.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Arrive & unwind', 'text' => 'Slip off your shoes. Your therapist asks about sore spots and the pressure you like.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Your ritual', 'text' => 'A private room or curtained nook, Spanish botanicals, and nothing to do but breathe.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Leave lighter', 'text' => 'Pay the balance after your ritual. For best results we recommend a session every two to four weeks.' ) ),
			) ),
			zv_b( 'zv/callout', array( 'text' => '<strong>Visiting Madrid?</strong> Our rituals are made for tired legs after a day of sightseeing or a long flight. The whole team speaks English and booking is simple.', 'linkLabel' => 'Rituals for travellers', 'linkUrl' => add_query_arg( 'need', 'travel', $rituals_url ) ) ),
		) ),
		zv_sec( array(), array( zv_reviews_block( array(
			'After three days walking Madrid my feet were done. I floated out — the foot bath alone is worth it.',
			'Booked the Couples Massage for our anniversary. Calm, beautiful room and two wonderful therapists.',
			"I walked in on a Tuesday afternoon and was on the recliner ten minutes later. Now it's my monthly ritual.",
		) ) ) ),
		zv_sec( array( 'anchor' => 'offers', 'padTop' => false, 'eyebrow' => 'Come back for less', 'title' => 'Make it a ritual' ), array( zv_offers_block() ) ),
		zv_sec( array( 'padTop' => false ), array( zv_b( 'zv/newsletter' ) ) ),
		zv_sec( array( 'padTop' => false, 'eyebrow' => 'Respiro Journal', 'title' => 'Slow living in Madrid', 'linkLabel' => 'All stories', 'linkUrl' => home_url( '/journal/' ) ), array( zv_b( 'zv/journal' ) ) ),
		zv_sec( array( 'anchor' => 'visit', 'padTop' => false ), array(
			zv_b( 'zv/split', array(), array(
				zv_faq( array(
					array( 'What is ZenVida?', 'A wellness studio for restorative foot reflexology and full-body massage — luxurious, culturally immersive rituals rooted in Spanish heritage.', true ),
					array( 'Do I need to book?', 'No, walk-ins are welcome. Booking online guarantees your time; a €25 deposit secures it and the rest is paid after your ritual.' ),
					array( 'What is your cancellation policy?', "Please give at least 4 hours' notice to cancel or reschedule. No-shows and late cancellations are charged 50%." ),
					array( 'Do you offer couples or group rituals?', 'Yes. Couples Massage has two therapists in one shared room, and many rituals can be booked for groups of up to six.' ),
					array( 'Are your rituals suitable for pregnancy?', "Our Pregnancy Massage is adapted for every trimester. Tell us you're pregnant when you book so we can guide you." ),
					array( 'Is ZenVida suitable for tourists?', 'Absolutely. Our rituals restore tired legs and feet after exploring Madrid or a long flight. All our staff speak English.' ),
				), array( 'eyebrow' => 'FAQ', 'title' => 'Good to know' ) ),
				zv_b( 'zv/visit' ),
			) ),
		) ),
	);
}

function zv_reviews_block( $quotes ) {
	return zv_b( 'zv/reviews', array(), array_map( fn( $q ) => zv_b( 'zv/review', array( 'text' => '"' . $q . '"' ) ), $quotes ) );
}

function zv_offers_block() {
	return zv_b( 'zv/offers', array(), array(
		zv_b( 'zv/offer', array( 'kicker' => 'Membership', 'title' => '€55 / month', 'text' => 'A monthly ritual, priority booking and member discounts. No sign-on fee.', 'linkLabel' => 'Join →', 'featured' => true ) ),
		zv_b( 'zv/offer', array( 'kicker' => 'Packs', 'title' => '5 or 10 sessions', 'text' => 'Zen Starter and Zen Balance. Save up to 20% on reflexology and massage.', 'linkLabel' => 'See packs →' ) ),
		zv_b( 'zv/offer', array( 'kicker' => 'Gift cards', 'title' => 'Give calm', 'text' => 'Any ritual or amount, delivered by email in minutes.', 'linkLabel' => 'Send a gift →' ) ),
		zv_b( 'zv/offer', array( 'kicker' => 'Loyalty card', 'title' => 'Earn stamps', 'text' => 'Every massage earns stamps toward a free ritual.', 'linkLabel' => 'How it works →' ) ),
	) );
}

function zv_rituals_blocks() {
	return array(
		zv_b( 'zv/ritual-hero', array( 'image' => zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 960 ) ) ),
		zv_b( 'zv/ritual-listing' ),
		zv_sec( array( 'background' => 'dark', 'eyebrow' => 'Signature rituals', 'title' => 'Longer journeys, made only here', 'text' => 'Our own rituals, built from several techniques. Choose one when you have time to fully switch off.' ), array( zv_b( 'zv/signature' ) ) ),
		zv_sec( array( 'eyebrow' => 'Come back for less', 'title' => 'Make it a habit' ), array( zv_offers_block() ) ),
		zv_sec( array( 'background' => 'cream', 'anchor' => 'visit' ), array(
			zv_b( 'zv/split', array(), array(
				zv_faq( array(
					array( 'Do I need to book?', 'No — walk in anytime. Booking online guarantees your time and lets you choose your therapist.', true ),
					array( 'Which ritual should I choose first?', 'For tired feet after walking Madrid, start with Deluxe Reflexology. For back and neck tension, try Deep Tissue or Thai. Not sure? Use "Help me choose" above.' ),
					array( "What's the difference between reflexology and massage?", 'Massage works the muscles directly. Reflexology works pressure points on the feet that connect to the whole body, for deep, whole-body relaxation.' ),
					array( 'Can I come with friends or my partner?', 'Yes. Couples Massage has two therapists in one shared room, and several rituals can be booked for small groups.' ),
				), array( 'eyebrow' => 'Before you come', 'title' => 'Good to know' ) ),
				zv_b( 'zv/visit' ),
			) ),
		) ),
	);
}

function zv_deluxe_blocks( $visit_section ) {
	return array(
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'Your 60 minutes', 'title' => 'What happens during the ritual', 'text' => 'Arrive a few minutes early, slip off your shoes, and leave the rest to your therapist.' ), array(
			zv_b( 'zv/steps', array(), array(
				zv_b( 'zv/step', array( 'title' => 'Welcome', 'text' => 'A short chat about how you feel, any sore spots, and the pressure you prefer.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Herbal foot spa', 'text' => 'A warm Spanish aromatherapy soak softens the skin and lets the muscles let go.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Reflexology', 'text' => 'Precise pressure on the reflex zones of your feet and calves to release tension across the body.' ) ),
				zv_b( 'zv/step', array( 'title' => 'Upper-body release', 'text' => 'We finish with a neck, shoulder and upper-back massage — the places city stress hides.' ) ),
			) ),
		) ),
		zv_sec( array(), array( zv_b( 'zv/foot-map' ) ) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'Benefits', 'title' => 'Why guests come back' ), array(
			zv_b( 'zv/benefits', array(), array(
				zv_b( 'zv/benefit', array( 'icon' => 'wave', 'title' => 'Deep relaxation', 'text' => 'Lowers tension, stress and anxiety for a calm, clear head.' ) ),
				zv_b( 'zv/benefit', array( 'icon' => 'heart', 'title' => 'Better circulation', 'text' => "Stimulates blood flow and the body's natural healing." ) ),
				zv_b( 'zv/benefit', array( 'icon' => 'moon', 'title' => 'Better sleep', 'text' => 'Many guests report deeper, more restful sleep.' ) ),
				zv_b( 'zv/benefit', array( 'icon' => 'walk', 'title' => 'Tired feet, fixed', 'text' => 'Ideal after a long day walking Madrid or a flight.' ) ),
			) ),
		) ),
		zv_sec( array( 'title' => 'Reflexology vs. a foot massage' ), array(
			zv_b( 'zv/compare', array(
				'leftTitle' => 'Foot massage', 'leftText' => 'Relaxes the muscles and tissue of the foot itself.',
				'leftItems' => "Local relaxation of the feet\nSoothes soreness and fatigue\nGreat as a quick 25-minute break",
				'rightTitle' => 'Deluxe Reflexology', 'rightText' => 'Works reflex points that connect to the whole body.',
				'rightItems' => "Whole-body balance and calm\nHerbal aromatherapy foot spa included\nNeck, shoulder & upper-back massage\nCalves included, a full 60 minutes",
			) ),
		) ),
		zv_sec( array( 'padTop' => false ), array(
			zv_b( 'zv/story', array( 'eyebrow' => 'An ancient practice', 'title' => 'Used for thousands of years in China and India', 'quote' => 'Your body works as one connected system — and your feet are the map.' ), array(
				zv_p( "Foot reflexology focuses on specific pressure points on the feet and calves that correspond to different parts of the body, including the head, lungs, stomach and lower back. By working these reflex zones with precise podal reflexology techniques, we support the body's natural balance." ),
				zv_p( 'The principle is simple: when stress or tension builds up, it shows up throughout the body. Reflexology uses the feet as a map to identify and release that tension.' ),
				zv_p( 'Reflexology also supports the connection between physical and emotional wellbeing. Working specific pressure points can reduce tension, ease anxiety and lead to a feeling of calm and mental clarity.' ),
				zv_p( 'For people walking a lot in a city like Madrid, it is especially helpful for easing tired feet, reducing soreness in the legs and lower back, and helping with travel fatigue.' ),
			) ),
		) ),
		zv_sec( array( 'background' => 'cream' ), array(
			zv_reviews_block( array(
				'After three days walking Madrid my feet were done. I floated out of here — the foot bath alone is worth it.',
				'Booked for four of us before a wedding. Calm room, lovely therapists, and the neck massage was a bonus.',
				"Best sleep I've had in weeks. I'm coming back monthly as they recommended.",
			) ),
			zv_b( 'zv/callout', array( 'icon' => '', 'text' => '<strong>Short on time? Foot Massage — 25 min.</strong> A quick, relaxing foot massage. Add neck & shoulders for 10 min (+€10).', 'price' => '€25', 'linkLabel' => 'View ritual', 'linkUrl' => home_url( '/rituals/feet-legs/foot-massage/' ) ) ),
		) ),
		zv_sec( array( 'eyebrow' => 'FAQ', 'title' => 'Good to know' ), array( zv_faq( array(
			array( 'What is foot reflexology?', 'It applies targeted pressure to specific reflex zones on the feet that correspond to different organs and systems throughout the body — supporting balance, circulation and whole-body relaxation.', true ),
			array( 'How is it different from a foot massage?', 'A foot massage relaxes the muscles and tissue of the foot itself. Reflexology works on pressure points that correspond to the rest of the body, aiming for whole-body balance and wellbeing — not just local relaxation.' ),
			array( 'How does the deposit work?', 'You pay a €25 deposit per guest by card when you book. The rest — plus any foot bath upgrade — is paid at the studio after your treatment.' ),
			array( 'What is your cancellation policy?', "Please give at least 4 hours' notice to cancel or reschedule. No-shows and late cancellations are charged 50%." ),
			array( 'Can I book for a group?', "Yes. Set the number of guests (2–6) in the booking box and we'll schedule your treatments together." ),
			array( 'How often should I come?', 'We recommend every two weeks or monthly for best results, though this can vary with your needs.' ),
		) ) ) ),
		$visit_section,
	);
}

// Fires on 'init' (priority 99) after the switch, so the post type and taxonomies exist.
add_action( 'after_switch_theme', 'zv_seed' );

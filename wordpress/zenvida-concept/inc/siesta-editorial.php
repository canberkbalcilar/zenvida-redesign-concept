<?php
/**
 * Siesta Club, editorial layout: a magazine spread rather than a landing page.
 * Full-bleed photographs, very large type, chapters (I, II, III…), prices set as typography.
 *
 * Blocks (each renders its own full-width section, so the page is a simple list of blocks):
 *   Cover · Statement · Membership (one per plan) · Details table · Photo pause ·
 *   The first forty · Terms · Reserve (calculator).
 * The Reserve calculator reads the prices from the Membership blocks on the same page.
 * Lists are one item per line, fields separated with "|".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------- helpers */

/** The memberships on a page: [[id, name, tagline, price6, price12], …] from the Membership blocks (or the older Plans block). */
function zv_club_page_plans( $post = null ) {
	$post = get_post( $post );
	$reg  = WP_Block_Type_Registry::get_instance();
	$out  = array();
	$walk = function ( $blocks ) use ( &$walk, &$out, $reg ) {
		foreach ( $blocks as $b ) {
			if ( 'zv/club-plan' === $b['blockName'] ) {
				$t     = $reg->get_registered( 'zv/club-plan' );
				$a     = $t ? $t->prepare_attributes_for_render( (array) $b['attrs'] ) : (array) $b['attrs'];
				$out[] = array( sanitize_title( $a['name'] ) ?: 'plan' . count( $out ), $a['name'], wp_strip_all_tags( $a['tagline'] ), (float) $a['price6'], (float) $a['price12'] );
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	if ( $post ) {
		$walk( parse_blocks( $post->post_content ) );
	}
	if ( ! $out ) { // the first layout kept both plans in one block
		foreach ( zv_club_plan_list( zv_club_plans_attrs( $post ) ) as $p ) {
			$out[] = array( sanitize_title( $p[1] ), $p[1], $p[2], $p[3], $p[4] );
		}
	}
	return $out;
}

/** "12 of 40 founding places remain." */
function zv_club_places_sentence() {
	list( $left, $total ) = zv_club_places();
	if ( null === $left ) {
		return zv__( 'Only {n} founding places', array( 'n' => $total ) ) . '.';
	}
	return 0 === $left ? zv__( 'All {n} founding places are taken', array( 'n' => $total ) ) . '.' : zv__( '{left} of {total} founding places remain.', array( 'left' => $left, 'total' => $total ) );
}

function zv_ed_label( $s ) {
	return '' !== trim( (string) $s ) ? '<span class="ed-label">' . zv_e( $s ) . '</span>' : '';
}

/* ----------------------------------------------------------------- blocks */

add_action( 'init', function () {
	zv_register( 'club-cover', 'Siesta Club: cover', 'cover-image', array(
		'image'     => '',
		'title'     => 'Your time. Your place. <em>Something extraordinary.</em>',
		'info'      => "The Siesta Club\nForty Founding Members\nUntil 28 February 2027",
		'linkLabel' => 'Reserve a place',
		'linkUrl'   => '#reserve',
	), 'zv_render_club_cover' );

	zv_register( 'club-intro', 'Siesta Club: statement', 'editor-quote', array(
		'label'     => 'The Siesta Club',
		'statement' => 'Forty people. Unlimited massages for six or twelve months in the heart of Madrid, at a rate that stays theirs when they renew.',
		'text'      => 'One ritual a day, booked up to seven days ahead. Prepaid, never renewed without asking. When the fortieth place is taken, the founding offer closes.',
	), 'zv_render_club_intro' );

	zv_register( 'club-plan', 'Siesta Club: membership', 'money-alt', array(
		'chapter'  => 'I',
		'name'     => 'Gold',
		'tagline'  => 'Unlimited body, midweek.',
		'text'     => 'Sixty-minute Swedish, Lomi Lomi, Lymphatic Drainage, Sports, Deep Tissue and Thai Massage. Monday to Wednesday all day, Thursday and Friday until four. Other hours for fifteen euros more, when there is space.',
		'price6'   => '1494',
		'price12'  => '2529',
		'label6'   => 'six months',
		'label12'  => 'twelve months',
		'note'     => 'Ten per cent less when paid at once. VAT included.',
		'image'    => '',
		'side'     => 'left',
		'dark'     => false,
		'btnLabel' => 'Reserve Gold',
	), 'zv_render_club_plan' );

	zv_register( 'club-details', 'Siesta Club: details table', 'editor-table', array(
		'chapter' => 'III',
		'title'   => 'Beyond the room',
		'head'    => 'Gold | Platinum',
		'rows'    => "A Siesta Club evening each quarter, by invitation | Included | Included\nWorkshop tickets, every six months | Two | Four\nPremium events, booked 48 hours early | 15% off | 20% off and one free\nA Deluxe Reflexology for a first-time guest | Every two months | Every month\nA Platinum ritual to try, once per term | Included | Not needed\nUnused passes carried over when you renew early | Up to two | Up to four\nA friend who joins on your word | Two weeks more | Two weeks more",
		'note'    => 'Guest passes are used off-peak. Carried-over perks are used within three months of renewing. Referral Rewards terms apply.',
	), 'zv_render_club_details' );

	zv_register( 'club-pause', 'Siesta Club: photo', 'format-image', array(
		'image'   => '',
		'caption' => 'Calle de la Cruz 16, three minutes from Sol',
	), 'zv_render_club_pause' );

	zv_register( 'club-forty', 'Siesta Club: the first forty', 'awards', array(
		'chapter' => 'IV',
		'lead'    => 'For the first forty, a few things more.',
		'items'   => "Your founding rate | Today’s price for your first renewal, when you renew at least fourteen days before your plan ends.\nA holiday pause | Two weeks on a six-month plan, four weeks on a twelve-month plan.\nA welcome kit | A ZenVida gift and your numbered Founding Member card.\nYour preferences, remembered | Therapist, pressure, oils and tea, ready every time you arrive.\nNew rituals first | New rituals and products, before anyone else.\nLast-minute seats | Unsold workshop places, free to members forty-eight hours before.",
	), 'zv_render_club_forty' );

	zv_register( 'club-terms', 'Siesta Club: terms', 'media-text', array(
		'chapter' => 'V',
		'title'   => 'The terms, briefly',
		'items'   => "One ritual a day. | Back-to-back rituals are not included; add-ons at menu price.\nSeven days ahead. | Book up to a week in advance. Walk-ins are welcome when we have space.\nFour hours’ notice. | Later cancellations and no-shows cost half the menu price.\nPrepaid. | At once for ten per cent less, or 40 and 60, or 40, 30 and 30 per cent over sixty days, without interest.\nFourteen days | to change your mind. After that, cancel with seven days’ notice for a refund of the unused balance.\nYours alone. | Memberships are personal and never renew by themselves.",
	), 'zv_render_club_terms' );

	zv_register( 'club-reserve', 'Siesta Club: reserve', 'email-alt', array(
		'chapter'    => 'VI',
		'title'      => 'Reserve a place',
		'image'      => '',
		'discount'   => '10',
		'two'        => '40, 60',
		'three'      => '40, 30, 30',
		'twoLimit'   => 'Up to 2 rituals a week until paid in full.',
		'threeLimit' => 'Up to 4 rituals a month until paid in full.',
		'btnLabel'   => 'Reserve on WhatsApp',
		'fine'       => 'No payment now. We confirm your place and how to pay.',
		'termsLabel' => 'Siesta Club terms and conditions',
		'termsUrl'   => 'https://www.zenvida.es/membership-terms-and-conditions',
	), 'zv_render_club_reserve' );
}, 20 );

/* ---------------------------------------------------------------- renders */

function zv_render_club_cover( $a ) {
	zv_club_assets();
	$img  = $a['image'] ?: zv_img( 'b84849_3c6a459dcd71493ea41290b153870b6c~mv2.jpg', 2000, 1300 );
	$info = implode( '<br>', array_map( 'zv_e', array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $a['info'] ) ) ) ) );
	return '<section class="ed-cover">' . zv_image( $img, array( 'sizes' => '100vw', 'eager' => true, 'alt' => 'A therapist easing tension at the temples during a ZenVida massage' ) )
		. '<div class="ed-wrap ed-cover-in"><h1>' . zv_k( $a['title'] ) . '</h1>'
		. '<div class="ed-cover-side">' . ( $info ? '<p>' . $info . '</p>' : '' )
		. ( $a['linkLabel'] ? '<a class="ed-link" href="' . esc_url( $a['linkUrl'] ?: '#reserve' ) . '">' . zv_e( $a['linkLabel'] ) . '</a>' : '' ) . '</div></div></section>';
}

function zv_render_club_intro( $a ) {
	return '<section class="ed-intro"><div class="ed-wrap ed-grid">' . zv_ed_label( $a['label'] )
		. '<div class="ed-intro-copy"><p class="ed-statement">' . zv_k( $a['statement'] ) . '</p>'
		. ( $a['text'] ? '<p class="ed-body">' . zv_k( $a['text'] ) . '</p>' : '' ) . '</div></div></section>';
}

function zv_render_club_plan( $a ) {
	zv_club_assets();
	$id     = sanitize_title( $a['name'] );
	$fallbk = $a['dark'] ? 'b84849_c9b0f029c3b049788e35b0fcc213cf7b~mv2.jpg' : 'b84849_9e0380cb868c40ad91407065069912cc~mv2.jpg';
	$img    = $a['image'] ?: zv_img( $fallbk, 1200, 1500 );
	$prices = '';
	foreach ( array( 6 => 'price6', 12 => 'price12' ) as $m => $k ) {
		if ( (float) $a[ $k ] > 0 ) {
			$prices .= '<b>' . zv_e( zv_club_eur( $a[ $k ] ) ) . '</b><span>' . zv_e( zv_tr( $a[ 'label' . $m ] ) ) . '</span>';
			if ( function_exists( 'zv_sd_add' ) ) {
				zv_sd_add( 'pack', array( 'kicker' => 'Siesta Club', 'title' => $a['name'] . ', ' . zv__( '{n} months', array( 'n' => $m ) ), 'price' => (string) (float) $a[ $k ], 'items' => $a['text'] ) );
			}
		}
	}
	$cls = 'ed-plan is-' . ( 'right' === $a['side'] ? 'right' : 'left' ) . ( $a['dark'] ? ' is-dark' : '' );
	return '<section class="' . esc_attr( $cls ) . '" id="' . esc_attr( 'plan-' . $id ) . '"><div class="ed-wrap ed-plan-in">'
		. '<figure class="ed-plan-img">' . zv_image( $img, array( 'sizes' => '(max-width: 900px) 100vw, 50vw', 'alt' => $a['name'] . ' membership at ZenVida' ) ) . '</figure>'
		. '<div class="ed-plan-copy">' . zv_ed_label( $a['chapter'] )
		. '<h2>' . zv_e( $a['name'] ) . '</h2>'
		. ( $a['tagline'] ? '<p class="ed-tag">' . zv_k( $a['tagline'] ) . '</p>' : '' )
		. ( $a['text'] ? '<p class="ed-body">' . zv_k( $a['text'] ) . '</p>' : '' )
		. ( $prices ? '<div class="ed-prices" data-no-translation>' . $prices . '</div>' : '' )
		. ( $a['note'] ? '<p class="ed-note">' . zv_e( $a['note'] ) . '</p>' : '' )
		. ( $a['btnLabel'] ? '<a class="ed-link" href="#reserve" data-club-pick="' . esc_attr( $id ) . '">' . zv_e( $a['btnLabel'] ) . '</a>' : '' )
		. '</div></div></section>';
}

function zv_render_club_details( $a ) {
	$head = array_pad( array_map( 'trim', explode( '|', (string) $a['head'], 2 ) ), 2, '' );
	$body = '';
	foreach ( zv_club_lines( $a['rows'], 3 ) as $r ) {
		$body .= '<tr><th scope="row">' . zv_e( $r[0] ) . '</th><td data-label="' . esc_attr( $head[0] ) . '">' . zv_e( $r[1] ) . '</td><td data-label="' . esc_attr( $head[1] ) . '">' . zv_e( $r[2] ) . '</td></tr>';
	}
	return '<section class="ed-details"><div class="ed-wrap ed-grid"><div class="ed-head">' . zv_ed_label( $a['chapter'] ) . '<h2>' . zv_k( $a['title'] ) . '</h2></div>'
		. '<div class="ed-side"><table class="ed-table"><thead><tr><th scope="col"><span class="screen-reader-text">' . zv_e( zv__( 'Benefit' ) ) . '</span></th><th scope="col">' . zv_e( $head[0] ) . '</th><th scope="col">' . zv_e( $head[1] ) . '</th></tr></thead><tbody>' . $body . '</tbody></table>'
		. ( $a['note'] ? '<p class="ed-note">' . zv_e( $a['note'] ) . '</p>' : '' ) . '</div></div></section>';
}

function zv_render_club_pause( $a ) {
	$img = $a['image'] ?: zv_img( 'b84849_1f76460dcaab4a9d9b293ecf6b206bf4~mv2.jpg', 2000, 900 );
	return '<figure class="ed-pause">' . zv_image( $img, array( 'sizes' => '100vw', 'alt' => 'Feet resting among rose petals in a foot bath at ZenVida' ) )
		. ( $a['caption'] ? '<figcaption>' . zv_e( $a['caption'] ) . '</figcaption>' : '' ) . '</figure>';
}

function zv_render_club_forty( $a ) {
	list( , $total ) = zv_club_places();
	$roman = array( 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x' );
	$items = '';
	foreach ( zv_club_lines( $a['items'], 2 ) as $i => $r ) {
		$items .= '<li><span class="n">' . ( $roman[ $i ] ?? $i + 1 ) . '.</span><b>' . zv_e( $r[0] ) . '</b><span class="t">' . zv_e( $r[1] ) . '</span></li>';
	}
	return '<section class="ed-forty"><div class="ed-wrap ed-forty-in"><div class="ed-forty-head">' . zv_ed_label( $a['chapter'] )
		. '<b class="ed-big" aria-hidden="true">' . (int) $total . '</b>'
		. ( $a['lead'] ? '<p class="ed-tag">' . zv_k( $a['lead'] ) . '</p>' : '' ) . '</div>'
		. '<ol class="ed-perks">' . $items . '</ol></div></section>';
}

function zv_render_club_terms( $a ) {
	$out = '';
	foreach ( zv_club_lines( $a['items'], 2 ) as $r ) {
		$out .= '<p><b>' . zv_e( $r[0] ) . '</b> ' . zv_e( $r[1] ) . '</p>';
	}
	return '<section class="ed-terms"><div class="ed-wrap ed-grid"><div class="ed-head">' . zv_ed_label( $a['chapter'] ) . '<h2>' . zv_k( $a['title'] ) . '</h2></div>'
		. '<div class="ed-side ed-cols">' . $out . '</div></div></section>';
}

function zv_render_club_reserve( $a ) {
	zv_club_assets();
	$list  = zv_club_page_plans();
	$d     = max( 0, min( 90, (float) $a['discount'] ) );
	$split = fn( $s ) => array_values( array_filter( array_map( 'floatval', explode( ',', (string) $s ) ) ) );
	$two   = $split( $a['two'] ) ?: array( 40, 60 );
	$three = $split( $a['three'] ) ?: array( 40, 30, 30 );
	$wa    = preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
	$i18n  = array(
		'today'  => zv__( 'Today' ),
		'in'     => zv__( 'In {n} days' ),
		'save'   => zv__( 'You save {amount}.' ),
		'msg'    => zv__( 'Hi ZenVida! I would like to reserve a Siesta Club Founding Member place: {plan}, {term}, {pay}.' ),
		'months' => zv__( '{n} months' ),
		'full'   => zv__( 'paid in full' ),
		'two'    => zv__( 'two payments' ),
		'three'  => zv__( 'three payments' ),
		'lang'   => zv_lang(),
	);
	$data  = array(
		'plans' => array_map( fn( $p ) => array( 'id' => $p[0], 'name' => $p[1], 'p6' => $p[3], 'p12' => $p[4] ), $list ),
		'd'     => $d,
		'two'   => $two,
		'three' => $three,
		'wa'    => $wa,
		'i18n'  => $i18n,
	);
	$p0    = $list[0] ?? array( 'plan', '', '', 0, 0 );
	$total = $p0[3];
	$opt   = fn( $name, $val, $on, $label ) => '<label class="ed-opt"><input type="radio" name="' . $name . '" value="' . esc_attr( $val ) . '"' . ( $on ? ' checked' : '' ) . '><span>' . zv_e( $label ) . '</span></label>';

	$plans = '';
	foreach ( $list as $i => $p ) {
		$plans .= $opt( 'club_plan', $p[0], 0 === $i, $p[1] );
	}
	$fs = fn( $legend, $inner ) => '<fieldset><legend>' . zv_e( $legend ) . '</legend><div class="ed-opts">' . $inner . '</div></fieldset>';

	$msg  = str_replace( array( '{plan}', '{term}', '{pay}' ), array( $p0[1], zv__( '{n} months', array( 'n' => 6 ) ), zv__( 'paid in full' ) ), $i18n['msg'] );
	$waic = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 18.7z"/></svg>';
	$img  = $a['image'] ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 1500 );

	return '<section class="ed-reserve" id="reserve"><figure>' . zv_image( $img, array( 'sizes' => '(max-width: 900px) 100vw, 50vw', 'alt' => 'A treatment room at ZenVida with fresh towels and rose petals' ) ) . '</figure>'
		. '<form class="ed-form" data-club-calc data-club="' . esc_attr( wp_json_encode( $data ) ) . '" onsubmit="return false">'
		. zv_ed_label( $a['chapter'] )
		. '<h2>' . zv_k( $a['title'] ) . '</h2>'
		. '<p data-no-translation>' . zv_e( zv_club_places_sentence() ) . '</p>'
		. '<div class="ed-fields" data-no-translation>'
		. $fs( zv__( 'Membership' ), $plans )
		. $fs( zv__( 'Length' ), $opt( 'club_term', '6', true, zv__( '{n} months', array( 'n' => 6 ) ) ) . $opt( 'club_term', '12', false, zv__( '{n} months', array( 'n' => 12 ) ) ) )
		. $fs( zv__( 'Payment' ), $opt( 'club_pay', 'full', true, zv__( 'At once' ) ) . $opt( 'club_pay', 'two', false, zv__( 'In two' ) ) . $opt( 'club_pay', 'three', false, zv__( 'In three' ) ) )
		. '</div>'
		. '<div class="ed-sum" aria-live="polite" data-no-translation><ul data-club-schedule><li><span>' . zv_e( $i18n['today'] ) . '</span><b>' . zv_e( zv_club_eur( $total * ( 1 - $d / 100 ) ) ) . '</b></li></ul>'
		. '<p data-club-note data-two="' . esc_attr( zv_tr( $a['twoLimit'] ) ) . '" data-three="' . esc_attr( zv_tr( $a['threeLimit'] ) ) . '">' . zv_e( str_replace( '{amount}', zv_club_eur( $total * $d / 100 ), $i18n['save'] ) ) . '</p></div>'
		. '<a class="ed-btn" href="' . esc_url( 'https://wa.me/' . $wa . '?text=' . rawurlencode( $msg ) ) . '" target="_blank" rel="noopener" data-club-wa>' . $waic . '<span>' . zv_e( $a['btnLabel'] ) . '</span></a>'
		. ( $a['fine'] ? '<p class="ed-fine">' . zv_e( $a['fine'] ) . '</p>' : '' )
		. '<p class="ed-fine">' . zv_e( zv_opt( 'phone' ) ) . ' · <a href="mailto:' . esc_attr( zv_opt( 'email' ) ) . '">' . zv_e( zv_opt( 'email' ) ) . '</a>' . ( $a['termsLabel'] ? ' · <a href="' . esc_url( $a['termsUrl'] ?: '#' ) . '">' . zv_e( $a['termsLabel'] ) . '</a>' : '' ) . '</p>'
		. '</form></section>';
}

/* ------------------------------------------------------------------- page */

/** The editorial page, as blocks. */
function zv_siesta_editorial_blocks() {
	$faq = array(
		array( 'When does my plan start?', 'On the day of your first ritual, or when your 14 days to change your mind end, whichever comes first. During those 14 days you can enjoy up to 2 rituals a week.' ),
		array( 'Can I move from Gold to Platinum?', 'Yes, at any time. You pay the difference for the months left, and your plan keeps its original end date.' ),
		array( 'I bought a pack recently. Does it count?', 'If you join within 30 days of buying a pack, the value of your unused sessions can be credited against your plan.' ),
		array( 'What if I travel?', 'Pause your plan for 2 weeks on a 6-month plan, or 4 weeks on a 12-month plan.' ),
		array( 'How do I book my rituals?', 'On WhatsApp or at reception, up to 7 days ahead. Your therapist, pressure, oils and tea are noted for every visit.' ),
	);
	return array(
		zv_b( 'zv/club-cover' ),
		zv_b( 'zv/club-intro' ),
		zv_b( 'zv/club-plan' ),
		zv_b( 'zv/club-plan', array(
			'chapter'  => '',
			'name'     => 'Platinum',
			'tagline'  => 'Unlimited everything, any hour.',
			'text'     => 'All of Gold, with the ninety-minute massages, Deluxe Reflexology, the Classic and Kobido facials and the Signature Rituals: Travel Recovery, Combined Massage, Energy Balance, Thai Andaluz and Full Body Reset. Every day we are open; up to four rituals of ninety minutes or longer a month. A guest at your Couples Retreat pays fifteen per cent less.',
			'price6'   => '2529',
			'price12'  => '4553',
			'side'     => 'right',
			'dark'     => true,
			'btnLabel' => 'Reserve Platinum',
		) ),
		zv_b( 'zv/club-details' ),
		zv_b( 'zv/club-pause' ),
		zv_b( 'zv/club-forty' ),
		zv_b( 'zv/club-terms' ),
		zv_sec( array( 'padTop' => false, 'eyebrow' => '', 'title' => 'Questions, answered' ), array( zv_faq( $faq ) ) ),
		zv_b( 'zv/club-reserve' ),
	);
}

// Move the Siesta Club page to the editorial layout, once. A page someone has edited is kept as it is;
// the new layout then waits as a draft ("Siesta Club, new layout") to copy from.
add_action( 'init', function () {
	if ( '1' === get_option( 'zv_siesta_v2' ) || ! get_option( 'zv_seeded' ) || wp_installing() ) {
		return;
	}
	update_option( 'zv_siesta_v2', '1', false );
	$p = get_page_by_path( 'siesta-club' );
	if ( ! $p || false !== strpos( $p->post_content, 'wp:zv/club-cover' ) ) {
		return;
	}
	$content = wp_slash( serialize_blocks( zv_siesta_editorial_blocks() ) );
	if ( $p->post_modified_gmt === $p->post_date_gmt ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_content' => $content ) );
		// Keep "never edited" true, so a later layout update can move it again.
		$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_modified' => $p->post_date, 'post_modified_gmt' => $p->post_date_gmt ), array( 'ID' => $p->ID ) );
		clean_post_cache( $p->ID );
	} else {
		wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Siesta Club, new layout', 'post_content' => $content ) );
	}
}, 32 );

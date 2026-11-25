<?php
/**
 * Siesta Club: the founding membership page (/siesta-club/).
 *
 * The page is made of ordinary blocks, so every line, price and list is editable:
 *  - Siesta Club hero, Siesta Club facts and Siesta Club join are full-width blocks;
 *  - plans, "which is right", benefits table, founding perks, rules and payment & dates
 *    sit inside Section blocks.
 * Prices live in the Plans block only; the join calculator reads them from there.
 * Places left: Appearance → ZenVida settings → Siesta Club.
 * Lists are one item per line, fields separated with "|".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------- helpers */

/** "a | b | c" lines → [[a, b, c], …], each row padded to $n fields. */
function zv_club_lines( $s, $n ) {
	$rows = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $s ) as $line ) {
		if ( '' === trim( wp_strip_all_tags( $line ) ) ) {
			continue;
		}
		$rows[] = array_pad( array_map( 'trim', explode( '|', $line, $n ) ), $n, '' );
	}
	return $rows;
}

/** A price in the visitor's language: €1,344.60 / 1.344,60 €. */
function zv_club_eur( $n ) {
	$n   = round( (float) $n, 2 );
	$dec = abs( $n - floor( $n ) ) > 0.001 ? 2 : 0;
	return 'es' === zv_lang() ? number_format( $n, $dec, ',', '.' ) . ' €' : '€' . number_format( $n, $dec, '.', ',' );
}

/** [left, total]; left is null when the places counter is not set. */
function zv_club_places() {
	$total = max( 1, (int) zv_opt( 'club_total' ) ?: 40 );
	$raw   = trim( (string) zv_opt( 'club_left' ) );
	return array( '' === $raw ? null : max( 0, min( $total, (int) $raw ) ), $total );
}

/** "12 of 40 places left" (bottom bar and join section). */
function zv_club_places_short() {
	list( $left, $total ) = zv_club_places();
	if ( null === $left ) {
		return zv__( 'Only {n} founding places', array( 'n' => $total ) );
	}
	return 0 === $left ? zv__( 'All {n} founding places are taken', array( 'n' => $total ) ) : zv__( '{left} of {total}', array( 'left' => $left, 'total' => $total ) ) . ' ' . zv__( 'places left' );
}

/** The places line with its dots ("12 of 40 founding places left"). */
function zv_club_places_html( $extra = '' ) {
	list( $left, $total ) = zv_club_places();
	$open = null === $left ? $total : $left;
	$dots = '';
	for ( $i = 0; $i < $total; $i++ ) {
		$dots .= '<i' . ( $i < $total - $open ? ' class="taken"' : '' ) . '></i>';
	}
	if ( null === $left ) {
		$txt = '<b>' . zv_e( zv__( 'Only {n} founding places', array( 'n' => $total ) ) ) . '</b>';
	} elseif ( 0 === $left ) {
		$txt = '<b>' . zv_e( zv__( 'All {n} founding places are taken', array( 'n' => $total ) ) ) . '</b>';
	} else {
		$txt = '<b>' . zv_e( zv__( '{left} of {total}', array( 'left' => $left, 'total' => $total ) ) ) . '</b> <span>' . zv_e( zv__( 'founding places left' ) ) . '</span>';
	}
	$label = null === $left ? wp_strip_all_tags( $txt ) : zv__( '{left} of {total}', array( 'left' => $left, 'total' => $total ) ) . ' ' . zv__( 'founding places left' );
	return '<div class="club-places" data-no-translation><div class="club-dots" role="img" aria-label="' . esc_attr( $label ) . '">' . $dots . '</div>'
		. '<p>' . $txt . ( $extra ? ' <span>· ' . zv_e( $extra ) . '</span>' : '' ) . '</p></div>';
}

/** Stroke icons for the founding perks. */
function zv_club_icon( $name ) {
	$p = array(
		'tag'     => '<path d="M3 12V4h8l10 10-8 8z"/><path d="M7.5 7.5h.01"/>',
		'pause'   => '<path d="M9 5v14M15 5v14"/>',
		'gift'    => '<path d="M4 11h16v9H4zM3 7h18v4H3zM12 7v13"/><path d="M12 7C10 3 6 4 7.5 6.5S12 7 12 7zm0 0c2-4 6-3 4.5-.5S12 7 12 7z"/>',
		'sliders' => '<path d="M4 7h9M17 7h3M15 5v4M4 17h3M11 17h9M9 15v4"/>',
		'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 17l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7z"/>',
		'ticket'  => '<path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4z"/><path d="M14 7v10"/>',
		'heart'   => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
		'star'    => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $p[ $name ] ?? $p['star'] ) . '</svg>';
}

function zv_club_check( $dark = false ) {
	return '<svg class="ck" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12l4.5 4.5L19 7"/></svg>';
}

/** The numbered Founding Member card (decoration). */
function zv_club_card( $no ) {
	list( , $total ) = zv_club_places();
	return '<div class="club-card" aria-hidden="true">' . zv_brand_svg( 'isotype', 'club-card-iso' )
		. '<div class="club-card-top"><img src="' . esc_url( get_template_directory_uri() . '/assets/brand/logo-primary-ivory.svg' ) . '" alt="" width="96" height="24"><span>Siesta Club</span></div>'
		. '<div class="club-card-bottom"><span>Founding Member</span><b>Nº ' . zv_e( $no ) . '<small> / ' . (int) $total . '</small></b></div></div>';
}

/** The Plans block on this page (the join calculator uses its prices). */
function zv_club_plans_attrs( $post = null ) {
	$post = get_post( $post );
	$type = WP_Block_Type_Registry::get_instance()->get_registered( 'zv/club-plans' );
	$find = function ( $blocks ) use ( &$find ) {
		foreach ( $blocks as $b ) {
			if ( 'zv/club-plans' === $b['blockName'] ) {
				return (array) $b['attrs'];
			}
			if ( ! empty( $b['innerBlocks'] ) && null !== ( $r = $find( $b['innerBlocks'] ) ) ) {
				return $r;
			}
		}
		return null;
	};
	$attrs = $post ? $find( parse_blocks( $post->post_content ) ) : null;
	return $type ? $type->prepare_attributes_for_render( (array) $attrs ) : (array) $attrs;
}

/** Both plans as [id, name, tagline, price6, price12]. */
function zv_club_plan_list( $a ) {
	return array(
		array( 'p1', $a['p1Name'], $a['p1Tag'], (float) $a['p1Price6'], (float) $a['p1Price12'] ),
		array( 'p2', $a['p2Name'], $a['p2Tag'], (float) $a['p2Price6'], (float) $a['p2Price12'] ),
	);
}

/* ----------------------------------------------------------------- blocks */

add_action( 'init', function () {
	$in = array( 'parent' => array( 'zv/section' ) );

	zv_register( 'club-hero', 'Siesta Club hero', 'star-filled', array(
		'eyebrow'   => 'Siesta Club · Founding Members',
		'title'     => 'Your time. Your place. <em>Something extraordinary.</em>',
		'text'      => 'Unlimited massages in the heart of Madrid, for just 40 Founding Members. Prepaid for 6 or 12 months, with a founding rate you keep when you renew.',
		'image'     => '',
		'badge'     => 'Members only · Calle de la Cruz 16',
		'placesNote' => 'Founding offer ends 28 February 2027',
		'btn1Label' => 'Reserve my place',
		'btn1Url'   => '#join',
		'btn2Label' => 'Compare Gold and Platinum',
		'btn2Url'   => '#plans',
		'cardNo'    => '07',
		'barText'   => '',
		'barLabel'  => 'Reserve',
	), 'zv_render_club_hero' );

	zv_register( 'club-facts', 'Siesta Club facts', 'editor-ol', array(
		'items' => "40 | founding places, never more\n1 | ritual a day, every day of your plan\n0 | auto-renewals: you decide",
	), 'zv_render_club_facts' );

	zv_register( 'club-plans', 'Siesta Club plans', 'money-alt', array(
		'discount'  => '10',
		'p1Name'    => 'Gold',
		'p1Tag'     => 'Unlimited body',
		'p1Lead'    => 'Unlimited 60-minute massages, midweek.',
		'p1Price6'  => '1494',
		'p1Price12' => '2529',
		'p1Items'   => "Swedish, Lomi Lomi, Lymphatic Drainage, Sports, Deep Tissue and Thai Massage, 60 minutes\nMonday to Wednesday all day; Thursday and Friday until 16:00\nOther times: +€15 per ritual, bookable up to 7 days ahead, subject to availability\nOne ritual a day, booked up to 7 days ahead",
		'p1Btn'     => 'Choose Gold',
		'p2Name'    => 'Platinum',
		'p2Tag'     => 'Unlimited everything',
		'p2Lead'    => 'Unlimited massages, facials and Signature Rituals, any time we are open.',
		'p2Price6'  => '2529',
		'p2Price12' => '4553',
		'p2Items'   => "Everything in Gold, plus the 90-minute massages\nDeluxe Reflexology, Classic and Kobido facial massages\nSignature Rituals: Travel Recovery, Combined Massage, Energy Balance, Thai Andaluz, Full Body Reset\nBook any day, any time we are open\nCouples Retreat: your guest gets 15% off their portion\nUp to four rituals of 90 minutes or longer a month",
		'p2Btn'     => 'Choose Platinum',
		'note'      => 'Prices include 21% VAT. Not included in either plan: 30-minute Reflexology and Pregnancy Massage. Add-ons at menu price.',
	), 'zv_render_club_plans', $in );

	zv_register( 'club-fit', 'Siesta Club: which is right', 'groups', array(
		'items' => "“I just love reflexology” | 10-Pack | €48 a ritual, about two to three rituals a month. Share it off-peak. | See the packs | /offers/#packs\n“I want regular massages” | Gold | Pays for itself from three massages a month. Add a Deluxe Reflexology Pack if you love both. | |\n“I want it all, any time” | Platinum | Facial massages, Signature Rituals, 90-minute massages and weekends included. | |",
	), 'zv_render_club_fit', $in );

	zv_register( 'club-table', 'Siesta Club benefits table', 'editor-table', array(
		'head'  => 'Member benefit | Gold | Platinum',
		'rows'  => "Quarterly Siesta Club evening, invitation only | yes | yes\nFree workshop tickets, every 6 months | 2 | 4\nPremium events: book 48 hours early | 15% off | 20% off + 1 free ticket\nGuest pass: a free Deluxe Reflexology for a first-time guest | Every 2 months | Every month\nTry a Platinum ritual, once per term | yes | no\nRenew early and carry unused guest passes or tickets | Up to 2 | Up to 4\nRefer a friend who joins: extra time on your plan | +2 weeks | +2 weeks",
		'note'  => 'Guest passes are used off-peak. Premium free ticket: Platinum, one every 6 months. Carried-over perks are used within 3 months of renewing. Referral Rewards terms apply.',
		'image' => '',
	), 'zv_render_club_table', $in );

	zv_register( 'club-perks', 'Siesta Club founding perks', 'awards', array(
		'items'    => "tag | Your founding rate | Keep today’s price for your first renewal, when you renew at least 14 days before your plan ends.\npause | A holiday pause | Pause for 2 weeks on a 6-month plan, or 4 weeks on a 12-month plan.\ngift | A welcome kit | A ZenVida gift and your numbered Founding Member card.\nsliders | Your preferences, remembered | Therapist, pressure, oils and tea, ready every time.\nsparkle | New rituals first | Taste new rituals and products before anyone else.\nticket | Last-minute seats | Unsold workshop places are free to members 48 hours before.",
		'showCard' => true,
		'cardNo'   => '07',
	), 'zv_render_club_perks', $in );

	zv_register( 'club-rules', 'Siesta Club rules', 'list-view', array(
		'items' => "One ritual a day | Book one ritual per day. Back-to-back rituals are not included. Add-ons are charged at menu price.\nBook up to 7 days ahead | Reserve your rituals up to 7 days in advance. Walk-ins are welcome when we have space.\nCancel on 4 hours’ notice | Later cancellations and no-shows cost 50% of the menu price.\nPrepaid, no auto-renewal | Pay once for 6 or 12 months. Nothing renews unless you choose to.\n14 days to change your mind | After that, cancel any time with 7 days’ notice for a refund of the unused balance.\nJust for you | Memberships are personal and cannot be used by anyone else.",
		'note'  => 'After 14 days, each month or part-month started is charged at the monthly reference rate.',
	), 'zv_render_club_rules', $in );

	zv_register( 'club-pay', 'Siesta Club payment & dates', 'calendar-alt', array(
		'payTitle'   => 'Three ways to pay',
		'payItems'   => "Pay in full | Save 10% on your plan | Best value\nTwo payments | 40% today, 60% thirty days later. Up to 2 rituals a week until paid in full. |\nThree payments | 40% today, then 30% at 30 days and 30% at 60 days. Up to 4 rituals a month until paid in full. |",
		'payNote'    => 'No interest, no fees. Pay the balance early at any time.',
		'datesTitle' => 'Be one of the first 40',
		'dates'      => "14 October | Opened to our VIP guests first | past\n20 October | Open to everyone | now\n28 February 2027 | Founding offer closes, or sooner when all 40 places are taken | next",
		'nowLabel'   => 'Now open',
		'image'      => '',
	), 'zv_render_club_pay', $in );

	zv_register( 'club-join', 'Siesta Club join', 'email-alt', array(
		'title'      => 'Join the <em>Siesta Club</em>',
		'text'       => 'Ask at reception or message us, and we will reserve your place. Choose your plan below and send it to us on WhatsApp.',
		'two'        => '40, 60',
		'three'      => '40, 30, 30',
		'twoLimit'   => 'Up to 2 rituals a week until paid in full.',
		'threeLimit' => 'Up to 4 rituals a month until paid in full.',
		'btnLabel'   => 'Reserve on WhatsApp',
		'fine'       => 'No payment now. We confirm your place and how to pay.',
		'legal'      => 'Prices include 21% VAT. Memberships are personal and cannot be used by anyone else.',
		'termsLabel' => 'Siesta Club terms and conditions',
		'termsUrl'   => 'https://www.zenvida.es/membership-terms-and-conditions',
	), 'zv_render_club_join' );
}, 20 );

/* ---------------------------------------------------------------- renders */

function zv_render_club_hero( $a ) {
	zv_club_assets();
	$img = $a['image'] ?: zv_img( 'b84849_3c6a459dcd71493ea41290b153870b6c~mv2.jpg', 1400, 1000 );
	$from = zv_tr( $a['barText'] );
	if ( '' === $from ) { // "From €211 a month": the lowest monthly price of the plans on this page
		$months = array();
		foreach ( zv_club_plan_list( zv_club_plans_attrs() ) as $p ) {
			$months = array_merge( $months, array_filter( array( $p[3] / 6, $p[4] / 12 ) ) );
		}
		$from = $months ? zv__( 'From {price} a month', array( 'price' => zv_club_eur( round( min( $months ) ) ) ) ) : '';
	}
	$bar = '<div class="club-bar" data-club-bar data-no-translation><div><b>' . zv_e( zv_club_places_short() ) . '</b>'
		. ( $from ? '<span>' . zv_e( $from ) . '</span>' : '' ) . '</div>'
		. zv_btn( zv_tr( $a['barLabel'] ), $a['btn1Url'] ?: '#join', 'dark' ) . '</div>';
	return '<section class="club-hero"><div class="wrap club-hero-in">'
		. '<div class="club-hero-copy">'
		. ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<h1>' . zv_k( $a['title'] ) . '</h1>'
		. ( $a['text'] ? '<p class="lead">' . zv_k( $a['text'] ) . '</p>' : '' )
		. zv_club_places_html( zv_tr( $a['placesNote'] ) )
		. '<div class="club-ctas">' . zv_btn( $a['btn1Label'], $a['btn1Url'] ?: '#join', 'light' ) . zv_btn( $a['btn2Label'], $a['btn2Url'] ?: '#plans', 'ghost' ) . '</div>'
		. '</div>'
		. '<div class="club-hero-media">' . zv_image( $img, array( 'sizes' => '(max-width: 900px) 100vw, 50vw', 'eager' => true, 'alt' => 'A therapist easing tension at the temples during a ZenVida massage' ) )
		. ( $a['badge'] ? '<span class="club-badge">' . zv_e( $a['badge'] ) . '</span>' : '' )
		. zv_club_card( $a['cardNo'] ) . '</div>'
		. '</div></section>' . $bar;
}

function zv_render_club_facts( $a ) {
	zv_club_assets();
	$out = '';
	foreach ( zv_club_lines( $a['items'], 2 ) as $r ) {
		$out .= '<div><b>' . zv_e( $r[0] ) . '</b><span>' . zv_e( $r[1] ) . '</span></div>';
	}
	return '<section class="club-facts"><div class="wrap">' . $out . '</div></section>';
}

function zv_render_club_plans( $a ) {
	zv_club_assets();
	$d     = max( 0, min( 90, (float) $a['discount'] ) );
	$cards = '';
	foreach ( zv_club_plan_list( $a ) as $i => $p ) {
		list( $id, $name, $tag, $p6, $p12 ) = $p;
		$k     = 'p' . ( $i + 1 );
		$data  = '';
		foreach ( array( 6 => $p6, 12 => $p12 ) as $m => $price ) {
			$sub   = zv__( 'About {month} a month · or {full} paid in full (save {d}%)', array( 'month' => zv_club_eur( round( $price / $m ) ), 'full' => zv_club_eur( $price * ( 1 - $d / 100 ) ), 'd' => (int) $d ) );
			$data .= ' data-price' . $m . '="' . esc_attr( zv_club_eur( $price ) ) . '" data-for' . $m . '="' . esc_attr( zv__( 'for {n} months', array( 'n' => $m ) ) ) . '" data-sub' . $m . '="' . esc_attr( $sub ) . '"';
			if ( function_exists( 'zv_sd_add' ) && $price > 0 ) {
				zv_sd_add( 'pack', array( 'kicker' => 'Siesta Club', 'title' => $name . ', ' . zv__( '{n} months', array( 'n' => $m ) ), 'price' => (string) $price, 'items' => $a[ $k . 'Items' ] ) );
			}
		}
		$items = '';
		foreach ( zv_club_lines( $a[ $k . 'Items' ], 1 ) as $it ) {
			$items .= '<li>' . zv_club_check() . '<span>' . zv_e( $it[0] ) . '</span></li>';
		}
		$dark   = 1 === $i;
		$cards .= '<article class="club-plan' . ( $dark ? ' is-dark' : '' ) . '"' . $data . '>'
			. ( $dark ? zv_brand_svg( 'isotype', 'club-plan-iso' ) : '' )
			. '<header><h3>' . zv_e( $name ) . '</h3>' . ( $tag ? '<span>' . zv_e( $tag ) . '</span>' : '' ) . '</header>'
			. ( $a[ $k . 'Lead' ] ? '<p class="club-plan-lead">' . zv_e( $a[ $k . 'Lead' ] ) . '</p>' : '' )
			. '<div class="club-price" data-no-translation><div><b data-club-price>' . zv_e( zv_club_eur( $p6 ) ) . '</b><span data-club-for>' . zv_e( zv__( 'for {n} months', array( 'n' => 6 ) ) ) . '</span></div>'
			. '<small data-club-sub>' . zv_e( zv__( 'About {month} a month · or {full} paid in full (save {d}%)', array( 'month' => zv_club_eur( round( $p6 / 6 ) ), 'full' => zv_club_eur( $p6 * ( 1 - $d / 100 ) ), 'd' => (int) $d ) ) ) . '</small></div>'
			. '<ul>' . $items . '</ul>'
			. '<a class="btn ' . ( $dark ? 'btn-light' : 'btn-dark' ) . '" href="#join" data-club-pick="' . esc_attr( $id ) . '">' . zv_e( $a[ $k . 'Btn' ] ) . '</a>'
			. '</article>';
	}
	$tabs = '<div class="club-term" role="radiogroup" aria-label="' . esc_attr( zv__( 'Plan length' ) ) . '" data-no-translation>'
		. '<button type="button" role="radio" aria-checked="true" data-term="6">' . zv_e( zv__( '{n} months', array( 'n' => 6 ) ) ) . '</button>'
		. '<button type="button" role="radio" aria-checked="false" data-term="12">' . zv_e( zv__( '{n} months', array( 'n' => 12 ) ) ) . '</button></div>';
	return '<div class="club-plans" data-club-plans>' . $tabs . '<div class="club-cards">' . $cards . '</div>'
		. ( $a['note'] ? '<p class="club-fine">' . zv_e( $a['note'] ) . '</p>' : '' ) . '</div>';
}

function zv_render_club_fit( $a ) {
	zv_club_assets();
	$rows = zv_club_lines( $a['items'], 5 );
	$out  = '';
	foreach ( $rows as $i => $r ) {
		$out .= '<div class="club-fit-card' . ( count( $rows ) - 1 === $i ? ' is-strong' : '' ) . '"><span class="q">' . zv_e( $r[0] ) . '</span>'
			. '<p><b>' . zv_e( $r[1] ) . '.</b> ' . zv_e( $r[2] ) . '</p>'
			. ( $r[3] ? '<a class="link" href="' . esc_url( $r[4] ?: '#' ) . '">' . zv_e( $r[3] ) . '</a>' : '' ) . '</div>';
	}
	return '<div class="club-fit">' . $out . '</div>';
}

function zv_render_club_table( $a ) {
	zv_club_assets();
	$head = array_pad( array_map( 'trim', explode( '|', (string) $a['head'], 3 ) ), 3, '' );
	$cell = function ( $v ) {
		$k = strtolower( trim( $v ) );
		if ( in_array( $k, array( 'yes', 'sí', 'si', '✓', 'x' ), true ) ) {
			return zv_club_check() . '<span class="screen-reader-text">' . zv_e( zv__( 'Included' ) ) . '</span>';
		}
		if ( in_array( $k, array( 'no', '—', '-', '' ), true ) ) {
			return '<span aria-hidden="true" class="no">—</span><span class="screen-reader-text">' . zv_e( zv__( 'Not included' ) ) . '</span>';
		}
		return zv_e( $v );
	};
	$body = '';
	foreach ( zv_club_lines( $a['rows'], 3 ) as $r ) {
		$body .= '<tr><th scope="row">' . zv_e( $r[0] ) . '</th><td>' . $cell( $r[1] ) . '</td><td>' . $cell( $r[2] ) . '</td></tr>';
	}
	$table = '<div class="club-table-box"><table class="club-table"><thead><tr><th scope="col">' . zv_e( $head[0] ) . '</th><th scope="col">' . zv_e( $head[1] ) . '</th><th scope="col">' . zv_e( $head[2] ) . '</th></tr></thead><tbody>' . $body . '</tbody></table>'
		. ( $a['note'] ? '<p class="club-fine">' . zv_e( $a['note'] ) . '</p>' : '' ) . '</div>';
	return '<div class="club-benefits' . ( $a['image'] ? ' has-img' : '' ) . '">' . ( $a['image'] ? '<figure>' . zv_image( $a['image'], array( 'sizes' => '(max-width: 900px) 100vw, 33vw' ) ) . '</figure>' : '' ) . $table . '</div>';
}

function zv_render_club_perks( $a ) {
	zv_club_assets();
	$out = '';
	foreach ( zv_club_lines( $a['items'], 3 ) as $r ) {
		$out .= '<div class="club-perk">' . zv_club_icon( $r[0] ) . '<div><b>' . zv_e( $r[1] ) . '</b><p>' . zv_e( $r[2] ) . '</p></div></div>';
	}
	return '<div class="club-perks' . ( $a['showCard'] ? ' has-card' : '' ) . '">' . ( $a['showCard'] ? '<div class="club-perks-card">' . zv_club_card( $a['cardNo'] ) . '</div>' : '' ) . '<div class="club-perk-list">' . $out . '</div></div>';
}

function zv_render_club_rules( $a ) {
	zv_club_assets();
	$out = '';
	foreach ( zv_club_lines( $a['items'], 2 ) as $i => $r ) {
		$out .= '<li><span class="n">' . sprintf( '%02d', $i + 1 ) . '</span><div><b>' . zv_e( $r[0] ) . '</b><p>' . zv_e( $r[1] ) . '</p></div></li>';
	}
	return '<ol class="club-rules">' . $out . '</ol>' . ( $a['note'] ? '<p class="club-fine">' . zv_e( $a['note'] ) . '</p>' : '' );
}

function zv_render_club_pay( $a ) {
	zv_club_assets();
	$pay = '';
	foreach ( zv_club_lines( $a['payItems'], 3 ) as $r ) {
		$pay .= '<li><div><b>' . zv_e( $r[0] ) . '</b><span>' . zv_e( $r[1] ) . '</span></div>' . ( $r[2] ? '<em>' . zv_e( $r[2] ) . '</em>' : '' ) . '</li>';
	}
	$dates = '';
	foreach ( zv_club_lines( $a['dates'], 3 ) as $r ) {
		$st     = in_array( $r[2], array( 'past', 'now', 'next' ), true ) ? $r[2] : 'next';
		$dates .= '<li class="is-' . $st . '"><i></i><span><b>' . zv_e( $r[0] ) . '</b> · ' . zv_e( $r[1] ) . ( 'now' === $st && $a['nowLabel'] ? ' <strong>' . zv_e( $a['nowLabel'] ) . '</strong>' : '' ) . '</span></li>';
	}
	$img = $a['image'] ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 600 );
	return '<div class="club-pay">'
		. '<div class="club-pay-box"><h3>' . zv_e( $a['payTitle'] ) . '</h3><ul>' . $pay . '</ul>' . ( $a['payNote'] ? '<p class="club-fine">' . zv_e( $a['payNote'] ) . '</p>' : '' ) . '</div>'
		. '<div class="club-dates">' . zv_image( $img, array( 'sizes' => '(max-width: 900px) 100vw, 50vw', 'alt' => 'A treatment room at ZenVida with fresh towels and rose petals' ) )
		. '<div><h3>' . zv_e( $a['datesTitle'] ) . '</h3><ol>' . $dates . '</ol></div></div>'
		. '</div>';
}

function zv_render_club_join( $a ) {
	zv_club_assets();
	$plans = zv_club_plans_attrs();
	$list  = zv_club_plan_list( $plans );
	$d     = max( 0, min( 90, (float) $plans['discount'] ) );
	$split = fn( $s ) => array_values( array_filter( array_map( 'floatval', explode( ',', (string) $s ) ) ) );
	$two   = $split( $a['two'] ) ?: array( 40, 60 );
	$three = $split( $a['three'] ) ?: array( 40, 30, 30 );
	$wa    = preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );

	$i18n = array(
		'today'   => zv__( 'Today' ),
		'in'      => zv__( 'In {n} days' ),
		'save'    => zv__( 'You save {amount}.' ),
		'msg'     => zv__( 'Hi ZenVida! I would like to reserve a Siesta Club Founding Member place: {plan}, {term}, {pay}.' ),
		'months'  => zv__( '{n} months' ),
		'full'    => zv__( 'paid in full' ),
		'two'     => zv__( 'two payments' ),
		'three'   => zv__( 'three payments' ),
		'lang'    => zv_lang(),
	);
	$data = array(
		'plans' => array_map( fn( $p ) => array( 'id' => $p[0], 'name' => $p[1], 'p6' => $p[3], 'p12' => $p[4] ), $list ),
		'd'     => $d,
		'two'   => $two,
		'three' => $three,
		'wa'    => $wa,
		'i18n'  => $i18n,
	);

	// Starting state: first plan, 6 months, paid in full.
	$p0    = $list[0];
	$total = $p0[3];
	$opt   = fn( $name, $val, $on, $inner, $cls = '' ) => '<label class="club-opt' . $cls . '"><input type="radio" name="' . $name . '" value="' . esc_attr( $val ) . '"' . ( $on ? ' checked' : '' ) . '>' . $inner . '</label>';

	$plan_opts = '';
	foreach ( $list as $i => $p ) {
		$plan_opts .= $opt( 'club_plan', $p[0], 0 === $i, '<span class="club-opt-plan"><b>' . zv_e( $p[1] ) . '</b><small>' . zv_e( $p[2] ) . '</small><em data-club-plan-price="' . esc_attr( $p[0] ) . '">' . zv_e( zv_club_eur( $p[3] ) ) . '</em></span>', ' is-plan' );
	}
	$term_opts = $opt( 'club_term', '6', true, '<span>' . zv_e( zv__( '{n} months', array( 'n' => 6 ) ) ) . '</span>', ' is-term' )
		. $opt( 'club_term', '12', false, '<span>' . zv_e( zv__( '{n} months', array( 'n' => 12 ) ) ) . '</span>', ' is-term' );
	$pct       = fn( $arr ) => implode( ' + ', array_map( fn( $x ) => (int) $x . '%', $arr ) );
	$pay_opts  = $opt( 'club_pay', 'full', true, '<span class="club-opt-pay"><i></i><span><b>' . zv_e( zv__( 'Pay in full' ) ) . '</b><small>' . zv_e( zv__( 'Save {d}%', array( 'd' => (int) $d ) ) ) . '</small></span><em data-club-today="full">' . zv_e( zv_club_eur( $total * ( 1 - $d / 100 ) ) ) . '</em></span>', ' is-pay' )
		. $opt( 'club_pay', 'two', false, '<span class="club-opt-pay"><i></i><span><b>' . zv_e( zv__( 'Two payments' ) ) . '</b><small>' . zv_e( $pct( $two ) ) . '</small></span><em data-club-today="two">' . zv_e( zv_club_eur( $total * $two[0] / 100 ) ) . '</em></span>', ' is-pay' )
		. $opt( 'club_pay', 'three', false, '<span class="club-opt-pay"><i></i><span><b>' . zv_e( zv__( 'Three payments' ) ) . '</b><small>' . zv_e( $pct( $three ) ) . '</small></span><em data-club-today="three">' . zv_e( zv_club_eur( $total * $three[0] / 100 ) ) . '</em></span>', ' is-pay' );

	$msg  = str_replace( array( '{plan}', '{term}', '{pay}' ), array( $p0[1], zv__( '{n} months', array( 'n' => 6 ) ), zv__( 'paid in full' ) ), $i18n['msg'] );
	$href = 'https://wa.me/' . $wa . '?text=' . rawurlencode( $msg );
	$waic = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 20l1.3-3.9A8 8 0 1 1 8 18.7z"/></svg>';

	$calc = '<form class="club-calc" data-club-calc data-club="' . esc_attr( wp_json_encode( $data ) ) . '" data-no-translation onsubmit="return false">'
		. '<fieldset><legend>' . zv_e( zv__( '1 · Your plan' ) ) . '</legend><div class="club-row2">' . $plan_opts . '</div></fieldset>'
		. '<fieldset><legend>' . zv_e( zv__( '2 · Length' ) ) . '</legend><div class="club-row2 club-seg">' . $term_opts . '</div></fieldset>'
		. '<fieldset><legend>' . zv_e( zv__( '3 · How you pay' ) ) . '</legend><div class="club-col">' . $pay_opts . '</div></fieldset>'
		. '<div class="club-sum" aria-live="polite"><ul data-club-schedule><li><span>' . zv_e( $i18n['today'] ) . '</span><b>' . zv_e( zv_club_eur( $total * ( 1 - $d / 100 ) ) ) . '</b></li></ul>'
		. '<p data-club-note data-full="' . esc_attr( '' ) . '" data-two="' . esc_attr( zv_tr( $a['twoLimit'] ) ) . '" data-three="' . esc_attr( zv_tr( $a['threeLimit'] ) ) . '">' . zv_e( str_replace( '{amount}', zv_club_eur( $total * $d / 100 ), $i18n['save'] ) ) . '</p></div>'
		. '<a class="btn btn-light club-wa" href="' . esc_url( $href ) . '" target="_blank" rel="noopener" data-club-wa>' . $waic . '<span>' . zv_e( zv_tr( $a['btnLabel'] ) ) . '</span></a>'
		. ( $a['fine'] ? '<p class="club-calc-fine">' . zv_e( zv_tr( $a['fine'] ) ) . '</p>' : '' )
		. '</form>';

	$contact = '<dl class="club-contact">'
		. '<div><dt>WhatsApp</dt><dd><a href="https://wa.me/' . esc_attr( $wa ) . '">' . zv_e( zv_opt( 'phone' ) ) . '</a></dd></div>'
		. '<div><dt>' . zv_e( zv__( 'Email' ) ) . '</dt><dd><a href="mailto:' . esc_attr( zv_opt( 'email' ) ) . '">' . zv_e( zv_opt( 'email' ) ) . '</a></dd></div>'
		. '<div><dt>' . zv_e( zv__( 'Visit' ) ) . '</dt><dd><a href="' . esc_url( zv_opt( 'maps_url' ) ) . '" target="_blank" rel="noopener">' . zv_e( zv_opt( 'address1' ) ) . ', Madrid</a></dd></div></dl>';

	$eyebrow = zv_club_places_short();

	return '<section class="club-join" id="join"><div class="wrap club-join-in">'
		. '<div class="club-join-copy"><span class="eyebrow" data-no-translation>' . zv_e( $eyebrow ) . '</span>'
		. '<h2>' . zv_k( $a['title'] ) . '</h2>' . ( $a['text'] ? '<p class="lead">' . zv_k( $a['text'] ) . '</p>' : '' ) . '</div>'
		. $calc
		. '<div class="club-join-info">' . $contact
		. '<p class="club-fine">' . zv_e( $a['legal'] ) . ( $a['termsLabel'] ? ' <a href="' . esc_url( $a['termsUrl'] ?: '#' ) . '">' . zv_e( $a['termsLabel'] ) . '</a>' : '' ) . '</p></div>'
		. '</div></section>';
}

/** Styles are in zenvida.css; the toggle, calculator and bottom bar need a small script. */
function zv_club_assets() {
	if ( is_admin() || wp_is_json_request() ) {
		return;
	}
	$f = get_template_directory() . '/assets/js/club.js';
	wp_enqueue_script( 'zv-club', get_template_directory_uri() . '/assets/js/club.js', array(), ZV_VERSION . '.' . ( file_exists( $f ) ? filemtime( $f ) : '' ), true );
}

/* ------------------------------------------------------------------- page */

/** The /siesta-club/ page with the starter content (only when it does not exist yet). */
function zv_seed_siesta_page() {
	if ( get_page_by_path( 'siesta-club' ) ) {
		return 0;
	}
	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Siesta Club',
		'post_name'    => 'siesta-club',
		'post_excerpt' => 'Siesta Club: unlimited massages in Madrid Centro for 40 Founding Members. Gold and Platinum plans, prepaid for 6 or 12 months.',
		'post_content' => wp_slash( serialize_blocks( zv_siesta_editorial_blocks() ) ),
	) );
	return $id;
}

/** Add "Siesta Club" to the footer Offers menu, once. */
function zv_siesta_menu_link( $page_id ) {
	$locs = get_nav_menu_locations();
	$menu = $locs['footer-2'] ?? 0;
	if ( ! $menu || ! $page_id ) {
		return;
	}
	foreach ( (array) wp_get_nav_menu_items( $menu ) as $it ) {
		if ( (int) $it->object_id === (int) $page_id || false !== stripos( $it->title, 'siesta' ) ) {
			return;
		}
	}
	wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => 'Siesta Club', 'menu-item-object' => 'page', 'menu-item-object-id' => $page_id, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish', 'menu-item-position' => 1 ) );
}

// Sites installed before the Siesta Club page existed: add it once, on the first request after the update.
add_action( 'init', function () {
	if ( '1' === get_option( 'zv_siesta_setup' ) || ! get_option( 'zv_seeded' ) || wp_installing() ) {
		return;
	}
	update_option( 'zv_siesta_setup', '1', false );
	$id = zv_seed_siesta_page();
	zv_siesta_menu_link( $id ?: (int) ( get_page_by_path( 'siesta-club' )->ID ?? 0 ) );
}, 31 );

// The old Wix address of the membership page.
add_action( 'template_redirect', function () {
	if ( is_404() && preg_match( '#^/(?:es/)?membership/?$#', (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ) && ( $p = get_page_by_path( 'siesta-club' ) ) ) { // phpcs:ignore
		wp_safe_redirect( get_permalink( $p ), 301 );
		exit;
	}
} );

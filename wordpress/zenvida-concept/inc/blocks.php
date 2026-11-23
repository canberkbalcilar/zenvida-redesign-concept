<?php
/**
 * ZenVida blocks: registration and front-end rendering.
 * Every section of every page is one of these blocks, so texts, buttons,
 * images and list items (FAQ, steps, offers…) are edited in the block editor.
 * Editor UI lives in assets/js/blocks.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------- helpers */

function zv_e( $s ) { return esc_html( (string) $s ); }
function zv_k( $s ) { return wp_kses( (string) $s, array( 'em' => array(), 'strong' => array(), 'b' => array(), 'i' => array(), 'br' => array(), 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ); }

function zv_btn( $label, $url, $style = 'dark', $extra = '' ) {
	if ( '' === trim( (string) $label ) ) {
		return '';
	}
	return sprintf( '<a class="btn btn-%s" href="%s"%s>%s</a>', esc_attr( $style ), esc_url( $url ?: '#' ), $extra, zv_e( $label ) );
}

function zv_icon( $name ) {
	$p = array(
		'home'    => '<path d="M4 20V10l8-6 8 6v10z"/><path d="M10 20v-5h4v5"/>',
		'heart'   => '<path d="M12 21s-8-5.3-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.7-8 11-8 11z"/>',
		'leaf'    => '<path d="M5 19c0-8 6-14 14-14 0 8-6 14-14 14z"/><path d="M5 19l7-7"/>',
		'wave'    => '<path d="M3 12c3-4 6-4 9 0s6 4 9 0"/><path d="M3 17c3-4 6-4 9 0s6 4 9 0" opacity=".5"/>',
		'moon'    => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
		'walk'    => '<path d="M7 21l2-7-4-3 2-6h4l2 6 4 2v4"/><circle cx="13" cy="3" r="1.5"/>',
		'star'    => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
		'pin'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'award'   => '<circle cx="12" cy="9" r="5"/><path d="M8.5 13 7 21l5-3 5 3-1.5-8"/>',
		'users'   => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3 3 0 0 1 0 6M21 20c0-2.6-1.6-4.8-4-5.6"/>',
		'check'   => '<path d="M5 12l5 5L19 7"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">' . ( $p[ $name ] ?? $p['star'] ) . '</svg>';
}

function zv_rituals_url( $args = array() ) {
	$url = get_post_type_archive_link( 'ritual' ) ?: home_url( '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/**
 * Register a dynamic block. $attrs maps attribute => default (type is inferred).
 */
function zv_register( $name, $title, $icon, $attrs, $render, $extra = array() ) {
	$attributes = array();
	foreach ( $attrs as $k => $def ) {
		$type = is_bool( $def ) ? 'boolean' : ( is_int( $def ) ? 'number' : 'string' );
		$attributes[ $k ] = array( 'type' => $type, 'default' => $def );
	}
	register_block_type( "zv/$name", array_merge( array(
		'api_version'     => 3,
		'title'           => $title,
		'icon'            => $icon,
		'category'        => 'zenvida',
		'attributes'      => $attributes,
		'render_callback' => $render,
		'editor_script'   => 'zv-blocks',
		'supports'        => array( 'html' => false ),
	), $extra ) );
}

add_filter( 'block_categories_all', function ( $cats ) {
	array_unshift( $cats, array( 'slug' => 'zenvida', 'title' => 'ZenVida' ) );
	return $cats;
} );

/* ------------------------------------------------------------- registration */

add_action( 'init', function () {
	wp_register_script( 'zv-blocks', get_template_directory_uri() . '/assets/js/blocks.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ), ZV_VERSION . '.' . filemtime( get_template_directory() . '/assets/js/blocks.js' ), true );

	$in_section = array( 'parent' => array( 'zv/section' ) );

	/* Page-level blocks */
	zv_register( 'hero', 'Hero (full image)', 'cover-image', array(
		'image' => '', 'eyebrow' => 'Foot & Body · Madrid Centro', 'title' => 'Relax. Recover.<br><em>Revive.</em>',
		'text' => 'Authentically Spanish foot and body massage, three minutes from Puerta del Sol. Walk in, or book in under a minute.',
		'btn1Label' => 'Book a ritual', 'btn1Url' => '#book', 'btn1Style' => 'light',
		'btn2Label' => 'Explore all rituals', 'btn2Url' => '', 'btn2Style' => 'glass',
		'showRating' => true, 'showOpen' => true, 'trust1' => 'Walk-ins welcome', 'trust2' => 'English-speaking team',
	), 'zv_render_hero' );

	zv_register( 'quick-book', 'Quick booking bar', 'calendar-alt', array(
		'buttonLabel' => 'See available times', 'note1' => '€25 deposit, rest after your ritual',
		'note2' => 'Free changes up to 4 hours before', 'note3' => 'Or simply walk in',
	), 'zv_render_quick_book' );

	zv_register( 'section', 'Section', 'align-wide', array(
		'background' => 'paper', 'anchor' => '', 'padTop' => true,
		'eyebrow' => '', 'title' => '', 'text' => '', 'linkLabel' => '', 'linkUrl' => '', 'linkStyle' => 'link',
	), 'zv_render_section' );

	zv_register( 'ritual-hero', 'Rituals hero (split)', 'columns', array(
		'eyebrow' => 'Rituals · Madrid Centro', 'title' => 'Find the ritual your <em>body</em> is asking for',
		'text' => 'Reflexology, massage and facial rituals, 3 minutes from Puerta del Sol. Walk in anytime or book online in under a minute.',
		'quizLabel' => 'Help me choose — 3 questions', 'btn2Label' => 'See all {count} rituals',
		'image' => '', 'featured' => 'deluxe-reflexology',
	), 'zv_render_ritual_hero' );

	zv_register( 'ritual-listing', 'Rituals list with filters', 'filter', array( 'showTiles' => true ), 'zv_render_listing' );

	/* Section content */
	zv_register( 'area-grid', 'Area tiles', 'grid-view', array(), 'zv_render_wrap_inner', $in_section );
	zv_register( 'area-tile', 'Area tile', 'format-image', array( 'image' => '', 'title' => '', 'subtitle' => '', 'url' => '', 'color' => '#8E9A82' ), 'zv_render_area_tile', array( 'parent' => array( 'zv/area-grid' ) ) );
	zv_register( 'need-chips', 'Need shortcuts', 'tag', array( 'label' => 'Or start from how you feel:' ), 'zv_render_need_chips', $in_section );
	zv_register( 'signature', 'Signature rituals', 'star-filled', array( 'count' => 6, 'cta' => 'Book →' ), 'zv_render_signature', $in_section );
	zv_register( 'pillars', 'Image + feature list', 'excerpt-view', array(
		'image' => '', 'badgeTitle' => '3 min', 'badgeText' => "from Puerta del Sol, in Madrid's historic centre", 'eyebrow' => 'Why ZenVida', 'title' => '',
	), 'zv_render_pillars', $in_section );
	zv_register( 'pillar', 'Feature', 'yes', array( 'icon' => 'home', 'title' => '', 'text' => '' ), 'zv_render_pillar', array( 'parent' => array( 'zv/pillars' ) ) );
	zv_register( 'steps', 'Steps', 'editor-ol', array(), 'zv_render_wrap_inner', $in_section );
	zv_register( 'step', 'Step', 'marker', array( 'title' => '', 'text' => '' ), 'zv_render_step', array( 'parent' => array( 'zv/steps' ) ) );
	zv_register( 'callout', 'Callout', 'megaphone', array( 'icon' => '✈︎', 'text' => '', 'price' => '', 'linkLabel' => '', 'linkUrl' => '' ), 'zv_render_callout', $in_section );
	zv_register( 'reviews', 'Reviews', 'format-quote', array( 'eyebrow' => 'Guest reviews', 'showSample' => true ), 'zv_render_reviews', $in_section );
	zv_register( 'review', 'Review', 'testimonial', array( 'text' => '', 'author' => 'Guest name', 'source' => 'Google', 'stars' => 5 ), 'zv_render_review', array( 'parent' => array( 'zv/reviews' ) ) );
	zv_register( 'offers', 'Offer cards', 'money-alt', array(), 'zv_render_wrap_inner', $in_section );
	zv_register( 'offer', 'Offer card', 'tickets-alt', array( 'kicker' => '', 'title' => '', 'text' => '', 'linkLabel' => 'Learn more →', 'url' => '', 'featured' => false ), 'zv_render_offer', array( 'parent' => array( 'zv/offers' ) ) );
	zv_register( 'newsletter', 'Newsletter signup', 'email', array(
		'eyebrow' => 'Respiro newsletter', 'title' => '10% off your first ritual',
		'text' => 'Join the ZenVida community for exclusive offers, invitations and the Respiro Journal — inspiration for a good life in Spain.',
		'smallprint' => 'Use your discount within 2 months. Not combinable with other promotions.',
		'buttonLabel' => 'Get my 10%', 'consent' => 'I agree to receive offers and news and accept the privacy policy. Unsubscribe anytime.',
	), 'zv_render_newsletter', $in_section );
	zv_register( 'journal', 'Latest journal posts', 'admin-post', array( 'count' => 3 ), 'zv_render_journal', $in_section );
	zv_register( 'split', 'Two columns', 'columns', array(), 'zv_render_wrap_inner', $in_section );
	zv_register( 'faq', 'FAQ', 'editor-help', array( 'eyebrow' => '', 'title' => '' ), 'zv_render_faq', array( 'parent' => array( 'zv/section', 'zv/split' ) ) );
	zv_register( 'faq-item', 'Question', 'editor-help', array( 'question' => '', 'answer' => '', 'open' => false ), 'zv_render_faq_item', array( 'parent' => array( 'zv/faq' ) ) );
	zv_register( 'visit', 'Visit us', 'location', array(
		'eyebrow' => 'Visit us', 'title' => 'Calle de la Cruz, 16', 'layout' => 'card',
		'bookLabel' => 'Book a time', 'bookUrl' => '', 'waLabel' => 'WhatsApp us',
	), 'zv_render_visit', array( 'parent' => array( 'zv/section', 'zv/split' ) ) );
	zv_register( 'foot-map', 'Foot map', 'location-alt', array(
		'eyebrow' => 'How reflexology works', 'title' => 'Your feet are a map of your body',
		'text' => 'Each zone of the foot corresponds to a different part of the body. By working these points, we support natural balance and encourage deep, whole-body relaxation. Tap a zone to explore.',
		'z1t' => 'Head & mind', 'z1d' => 'Toes — tension headaches, mental clarity',
		'z2t' => 'Lungs & chest', 'z2d' => 'Ball of the foot — breathing, calm',
		'z3t' => 'Stomach & digestion', 'z3d' => 'Arch — digestion, inner balance',
		'z4t' => 'Lower back', 'z4d' => 'Heel — lower back and leg soreness',
	), 'zv_render_foot_map', $in_section );
	zv_register( 'benefits', 'Benefits', 'heart', array(), 'zv_render_wrap_inner', $in_section );
	zv_register( 'benefit', 'Benefit', 'heart', array( 'icon' => 'wave', 'title' => '', 'text' => '' ), 'zv_render_benefit', array( 'parent' => array( 'zv/benefits' ) ) );
	zv_register( 'compare', 'Comparison', 'image-flip-horizontal', array(
		'leftTitle' => '', 'leftText' => '', 'leftItems' => '', 'rightTitle' => '', 'rightText' => '', 'rightItems' => '',
	), 'zv_render_compare', $in_section );
	zv_register( 'story', 'Story', 'book', array( 'eyebrow' => '', 'title' => '', 'quote' => '' ), 'zv_render_story', $in_section );
} );

// register_block_type() drops unknown keys, so keep the wrapper classes here.
function zv_inner_class( $name ) {
	return array( 'zv/area-grid' => 'areas', 'zv/steps' => 'steps', 'zv/offers' => 'offers', 'zv/split' => 'fv', 'zv/benefits' => 'benefits' )[ $name ] ?? '';
}

/* --------------------------------------------------------------- renderers */

function zv_render_wrap_inner( $a, $content, $block ) {
	return '<div class="' . esc_attr( zv_inner_class( $block->name ) ) . '">' . $content . '</div>';
}

function zv_render_hero( $a ) {
	$img   = $a['image'] ?: zv_img( 'b84849_1f76460dcaab4a9d9b293ecf6b206bf4~mv2.jpg', 2000, 1200 );
	$trust = '';
	if ( $a['showRating'] ) {
		$trust .= '<span><span class="stars">★★★★★</span><b>' . zv_e( zv_opt( 'rating' ) ) . '</b>&nbsp;from ' . zv_e( zv_opt( 'rating_count' ) ) . '</span>';
	}
	if ( $a['showOpen'] ) {
		$trust .= '<span class="open" data-open-now>Open 7 days a week</span>';
	}
	foreach ( array( 'trust1', 'trust2' ) as $k ) {
		if ( '' !== $a[ $k ] ) {
			$trust .= '<span>' . zv_e( $a[ $k ] ) . '</span>';
		}
	}
	return '<section class="hero"><img src="' . esc_url( $img ) . '" alt="" onerror="this.remove()"><div class="wrap">'
		. ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<h1>' . zv_k( $a['title'] ) . '</h1>'
		. ( $a['text'] ? '<p>' . zv_k( $a['text'] ) . '</p>' : '' )
		. '<div class="hero-ctas">' . zv_btn( $a['btn1Label'], $a['btn1Url'], $a['btn1Style'] ) . zv_btn( $a['btn2Label'], $a['btn2Url'] ?: zv_rituals_url(), $a['btn2Style'] ) . '</div>'
		. ( $trust ? '<div class="hero-trust">' . $trust . '</div>' : '' )
		. '</div></section>';
}

function zv_render_quick_book( $a ) {
	$rituals = array_filter( zv_all_rituals(), fn( $r ) => ! in_array( 'two', $r['flags'], true ) );
	usort( $rituals, fn( $x, $y ) => $y['pop'] <=> $x['pop'] );
	$opts = '';
	foreach ( array_slice( $rituals, 0, 3 ) as $r ) {
		$o     = $r['opts'];
		$price = count( $o ) > 1 ? 'from ' . zv_eur( $o[0][1] ) : $o[0][0] . ' min · ' . zv_eur( $o[0][1] );
		$opts .= '<option value="' . esc_url( $r['url'] . '#book' ) . '">' . zv_e( $r['name'] . ' · ' . $price ) . '</option>';
	}
	$areas = '';
	foreach ( zv_areas() as $slug => $ar ) {
		$areas .= '<option value="' . esc_url( $ar['url'] ) . '">' . zv_e( $ar['name'] ) . '</option>';
	}
	$notes = '';
	foreach ( array( 'note1', 'note2', 'note3' ) as $k ) {
		if ( '' !== $a[ $k ] ) {
			$notes .= '<span>' . zv_icon( 'check' ) . zv_e( $a[ $k ] ) . '</span>';
		}
	}
	return '<div class="wrap qb-wrap" id="book" data-mbar-anchor><form class="qb" data-quick-book aria-label="Quick booking">'
		. '<div class="qf"><label for="qbWhat">What</label><select id="qbWhat" data-qb-what><optgroup label="Most booked">' . $opts . '</optgroup><optgroup label="Browse by area">' . $areas . '</optgroup><optgroup label="Not sure yet"><option value="' . esc_url( zv_rituals_url( array( 'quiz' => 1 ) ) ) . '">Help me choose</option></optgroup></select></div>'
		. '<div class="qf"><label for="qbWhen">When</label><select id="qbWhen" data-qb-when><option>Today</option></select></div>'
		. '<div class="qf"><label for="qbWho">Guests</label><select id="qbWho">' . implode( '', array_map( fn( $n ) => "<option value=\"$n\">$n guest" . ( $n > 1 ? 's' : '' ) . '</option>', range( 1, 6 ) ) ) . '</select></div>'
		. '<button class="btn btn-dark" type="submit">' . zv_e( $a['buttonLabel'] ) . '</button></form>'
		. ( $notes ? '<div class="qb-note">' . $notes . '</div>' : '' ) . '</div>';
}

function zv_section_head( $a ) {
	if ( '' === $a['eyebrow'] && '' === $a['title'] && '' === $a['text'] && '' === $a['linkLabel'] ) {
		return '';
	}
	$link = '';
	if ( '' !== $a['linkLabel'] ) {
		$link = 'button' === $a['linkStyle'] ? zv_btn( $a['linkLabel'], $a['linkUrl'], 'ghost' ) : '<a class="link" href="' . esc_url( $a['linkUrl'] ?: '#' ) . '">' . zv_e( $a['linkLabel'] ) . '</a>';
	}
	return '<div class="sec-head"><div>'
		. ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h2>' . zv_k( $a['title'] ) . '</h2>' : '' )
		. ( $a['text'] ? '<p class="lead">' . zv_k( $a['text'] ) . '</p>' : '' )
		. '</div>' . $link . '</div>';
}

function zv_render_section( $a, $content ) {
	$cls = 'block bg-' . sanitize_html_class( $a['background'] ) . ( $a['padTop'] ? '' : ' pt0' );
	$id  = $a['anchor'] ? ' id="' . esc_attr( $a['anchor'] ) . '"' : '';
	return '<section class="' . $cls . '"' . $id . '><div class="wrap">' . zv_section_head( $a ) . $content . '</div></section>';
}

function zv_render_area_tile( $a ) {
	return '<a class="area" href="' . esc_url( $a['url'] ?: '#' ) . '" style="--c:' . esc_attr( $a['color'] ) . '">'
		. ( $a['image'] ? '<img src="' . esc_url( $a['image'] ) . '" alt="" loading="lazy" onerror="this.remove()">' : '' )
		. '<span class="t"><span><b>' . zv_e( $a['title'] ) . '</b><small>' . zv_e( $a['subtitle'] ) . '</small></span><span class="go">' . zv_icon( 'arrow' ) . '</span></span></a>';
}

function zv_render_need_chips( $a ) {
	$out = '<div class="needs-row">' . ( $a['label'] ? '<span>' . zv_e( $a['label'] ) . '</span>' : '' );
	foreach ( zv_needs() as $slug => $name ) {
		$out .= '<a class="chip" href="' . esc_url( zv_rituals_url( array( 'need' => $slug ) ) ) . '">' . zv_e( $name ) . '</a>';
	}
	return $out . '</div>';
}

function zv_render_signature( $a ) {
	$sig = array_values( array_filter( zv_all_rituals(), fn( $r ) => in_array( 'sig', $r['flags'], true ) ) );
	usort( $sig, fn( $x, $y ) => $y['pop'] <=> $x['pop'] );
	$out = '';
	foreach ( array_slice( $sig, 0, max( 1, (int) $a['count'] ) ) as $r ) {
		$two   = in_array( 'two', $r['flags'], true );
		$o     = $r['opts'][0];
		$img   = str_replace( 'w_1600,h_1000', 'w_800,h_600', (string) get_post_meta( $r['id'], 'zv_image', true ) );
		$out  .= '<a class="sig-card" href="' . esc_url( $r['url'] ) . '"><div class="img">'
			. ( $img ? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $r['name'] ) . ' at ZenVida" loading="lazy" onerror="this.remove()">' : '' )
			. '<span class="tag">' . zv_e( $two ? 'For two' : $r['areaName'] ) . '</span></div>'
			. '<div class="body"><h3>' . zv_e( $r['name'] ) . '</h3><p>' . zv_e( $r['desc'] ) . '</p>'
			. '<div class="foot"><span>' . (int) $o[0] . ' min · <b>' . zv_e( zv_eur( $o[1] ) . ( $two ? ' for two' : '' ) ) . '</b></span><span>' . zv_e( $a['cta'] ) . '</span></div></div></a>';
	}
	return '<div class="sig-grid">' . $out . '</div>';
}

function zv_render_pillars( $a, $content ) {
	$img = $a['image'] ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1000, 1250 );
	return '<div class="pillars"><div class="photo"><img src="' . esc_url( $img ) . '" alt="" loading="lazy" onerror="this.remove()">'
		. ( $a['badgeTitle'] ? '<div class="badge"><b>' . zv_e( $a['badgeTitle'] ) . '</b>' . zv_e( $a['badgeText'] ) . '</div>' : '' )
		. '</div><div>' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h2 style="margin-top:12px">' . zv_k( $a['title'] ) . '</h2>' : '' )
		. '<div class="pillar-list">' . $content . '</div></div></div>';
}

function zv_render_pillar( $a ) {
	return '<div class="pillar"><span class="ic">' . zv_icon( $a['icon'] ) . '</span><div><h3>' . zv_e( $a['title'] ) . '</h3><p>' . zv_k( $a['text'] ) . '</p></div></div>';
}

function zv_render_step( $a ) {
	return '<div class="step"><h3>' . zv_e( $a['title'] ) . '</h3><p>' . zv_k( $a['text'] ) . '</p></div>';
}

function zv_render_callout( $a ) {
	return '<div class="callout">' . ( $a['icon'] ? '<span class="ic" aria-hidden="true">' . zv_e( $a['icon'] ) . '</span>' : '' )
		. '<p>' . zv_k( $a['text'] ) . '</p>'
		. ( $a['price'] ? '<strong class="price">' . zv_e( $a['price'] ) . '</strong>' : '' )
		. ( $a['linkLabel'] ? '<a class="link" href="' . esc_url( $a['linkUrl'] ?: '#' ) . '">' . zv_e( $a['linkLabel'] ) . '</a>' : '' ) . '</div>';
}

function zv_render_reviews( $a, $content ) {
	return '<div class="reviews"><div>' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<div class="score" style="margin-top:14px">' . zv_e( zv_opt( 'rating' ) ) . '</div><div class="stars" aria-hidden="true">★★★★★</div>'
		. '<p style="color:var(--muted);font-size:14px;margin:8px 0 0">From ' . zv_e( zv_opt( 'rating_count' ) ) . '</p>'
		. ( $a['showSample'] ? '<span class="sample">Sample quotes — connect real reviews</span>' : '' )
		. '</div><div class="rev-list">' . $content . '</div></div>';
}

function zv_render_review( $a ) {
	$n = max( 1, min( 5, (int) $a['stars'] ) );
	return '<div class="rev"><div class="stars">' . str_repeat( '★', $n ) . '</div><p>' . zv_k( $a['text'] ) . '</p><small>' . zv_e( $a['author'] . ( $a['source'] ? ' · ' . $a['source'] : '' ) ) . '</small></div>';
}

function zv_render_offer( $a ) {
	return '<a class="offer' . ( $a['featured'] ? ' feature' : '' ) . '" href="' . esc_url( $a['url'] ?: '#' ) . '">'
		. ( $a['kicker'] ? '<span class="k">' . zv_e( $a['kicker'] ) . '</span>' : '' )
		. '<b>' . zv_e( $a['title'] ) . '</b><p>' . zv_k( $a['text'] ) . '</p>'
		. ( $a['linkLabel'] ? '<span class="go">' . zv_e( $a['linkLabel'] ) . '</span>' : '' ) . '</a>';
}

function zv_render_newsletter( $a ) {
	return '<div class="news"><div>' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<h2>' . zv_k( $a['title'] ) . '</h2><p>' . zv_k( $a['text'] ) . '</p>'
		. ( $a['smallprint'] ? '<small>' . zv_e( $a['smallprint'] ) . '</small>' : '' ) . '</div>'
		. '<form class="nform" data-newsletter novalidate>'
		. '<div class="seg" role="group" aria-label="Send it to"><button type="button" aria-pressed="true" data-ch="email">Email</button><button type="button" aria-pressed="false" data-ch="wa">WhatsApp</button></div>'
		. '<div class="nrow"><input data-nval type="email" placeholder="you@example.com" aria-label="Email address" autocomplete="email"><button class="btn btn-dark" type="submit">' . zv_e( $a['buttonLabel'] ) . '</button></div>'
		. '<label class="consent"><input type="checkbox" data-nconsent> ' . zv_k( $a['consent'] ) . '</label>'
		. '<div class="nok" data-nok hidden></div></form></div>';
}

function zv_post_image( $id, $w = 800, $h = 533 ) {
	$img = get_the_post_thumbnail_url( $id, 'large' );
	return $img ?: (string) get_post_meta( $id, 'zv_image', true );
}

function zv_journal_cards( $posts ) {
	$out = '';
	foreach ( $posts as $p ) {
		$cat = get_the_category( $p->ID );
		$img = zv_post_image( $p->ID );
		$out .= '<a class="post" href="' . esc_url( get_permalink( $p ) ) . '"><div class="img">'
			. ( $img ? '<img src="' . esc_url( $img ) . '" alt="" loading="lazy" onerror="this.remove()">' : '' ) . '</div>'
			. ( $cat ? '<span class="k">' . zv_e( $cat[0]->name ) . '</span>' : '' )
			. '<h3>' . zv_e( get_the_title( $p ) ) . '</h3><p>' . zv_e( wp_trim_words( get_the_excerpt( $p ), 18 ) ) . '</p></a>';
	}
	return '<div class="journal">' . $out . '</div>';
}

function zv_render_journal( $a ) {
	$posts = get_posts( array( 'numberposts' => max( 1, (int) $a['count'] ), 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
	return $posts ? zv_journal_cards( $posts ) : '<p style="color:var(--muted)">No journal posts yet.</p>';
}

function zv_render_faq( $a, $content ) {
	return '<div class="faq">' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h2>' . zv_k( $a['title'] ) . '</h2>' : '' ) . $content . '</div>';
}

function zv_render_faq_item( $a ) {
	if ( '' === trim( wp_strip_all_tags( $a['question'] ) ) ) {
		return '';
	}
	return '<details' . ( $a['open'] ? ' open' : '' ) . '><summary>' . zv_k( $a['question'] ) . '</summary><p>' . zv_k( $a['answer'] ) . '</p></details>';
}

function zv_render_visit( $a ) {
	$wide = 'wide' === $a['layout'];
	$info = '<p class="addr">' . zv_e( zv_opt( 'address2' ) ) . '</p>'
		. '<p class="addr">' . zv_e( zv_opt( 'phone' ) ) . ' · ' . zv_e( zv_opt( 'email' ) ) . '</p>'
		. '<div style="margin-top:16px"><span class="open-now" data-open-now>Open 7 days a week</span></div>'
		. '<table class="hours" data-hours-table>' . zv_hours_rows() . '</table>'
		. '<div class="vrow">' . zv_btn( $a['bookLabel'], $a['bookUrl'] ?: zv_rituals_url(), 'dark' ) . zv_btn( $a['waLabel'], 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ), 'ghost' ) . '</div>';
	$map  = '<a class="map-tile" href="' . esc_url( zv_opt( 'maps_url' ) ) . '" target="_blank" rel="noopener"><span class="pin">Get directions ↗</span></a>';
	return '<div class="visit">' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h2>' . zv_e( $a['title'] ) . '</h2>' : '' )
		. '<div class="visit-card' . ( $wide ? ' wide' : '' ) . '">' . ( $wide ? $map . '<div>' . $info . '</div>' : $map . $info ) . '</div></div>';
}

/** Static hours table (the script marks today); keeps the block useful without JS. */
function zv_hours_rows() {
	$days = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday' );
	$h    = zv_hours();
	$fmt  = fn( $m ) => sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	$out  = '';
	foreach ( $days as $i => $name ) {
		$out .= '<tr><td>' . $name . '</td><td>' . $fmt( $h[ $i ][0] ) . ' – ' . $fmt( $h[ $i ][1] ) . '</td></tr>';
	}
	return $out;
}

function zv_render_ritual_hero( $a ) {
	$img  = $a['image'] ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 960 );
	$feat = $a['featured'] ? get_page_by_path( $a['featured'], OBJECT, 'ritual' ) : null;
	$f    = $feat ? zv_ritual_data( $feat ) : null;
	$cnt  = count( zv_all_rituals() );
	return '<div class="wrap"><section class="hero-split"><div>'
		. ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<h1>' . zv_k( $a['title'] ) . '</h1>'
		. ( $a['text'] ? '<p class="lead">' . zv_k( $a['text'] ) . '</p>' : '' )
		. '<div class="hero-ctas"><button class="btn btn-dark" type="button" data-open-quiz>' . zv_e( $a['quizLabel'] ) . '</button>'
		. zv_btn( str_replace( '{count}', (string) $cnt, $a['btn2Label'] ), '#results', 'ghost' ) . '</div>'
		. '<div class="trust"><span><span class="stars">★★★★★</span><b>' . zv_e( zv_opt( 'rating' ) ) . '</b>&nbsp;from ' . zv_e( zv_opt( 'rating_count' ) ) . '</span>'
		. '<span>' . zv_icon( 'pin' ) . zv_e( zv_opt( 'address1' ) ) . '</span><span class="open" data-open-now>Open 7 days a week</span></div></div>'
		. '<div class="hero-img"><img src="' . esc_url( $img ) . '" alt="" onerror="this.remove()">'
		. ( $f ? '<div class="float"><div><small style="color:var(--muted)">Most booked</small><b>' . zv_e( $f['name'] ) . '</b></div><span style="color:var(--ink-2)">' . (int) $f['opts'][0][0] . ' min · ' . zv_e( zv_eur( $f['opts'][0][1] ) ) . '</span><a href="' . esc_url( $f['url'] . '#book' ) . '">Book →</a></div>' : '' )
		. '</div></section></div>';
}

function zv_render_listing( $a ) {
	static $done = false;
	$zv      = zv_archive_data();
	$areas   = $zv['areas'];
	$needs   = $zv['needs'];
	$rituals = $zv['rituals'];
	$initial = $zv['initialArea'];
	if ( ! $done && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		wp_enqueue_script( 'zv-listing', get_template_directory_uri() . '/assets/js/listing.js', array( 'zv-site' ), ZV_VERSION . '.' . filemtime( get_template_directory() . '/assets/js/listing.js' ), true );
		wp_add_inline_script( 'zv-listing', 'window.ZV = ' . wp_json_encode( $zv ) . ';', 'before' );
		$done = true;
	}
	$min_p = function ( $r ) {
		$p = array_filter( array_column( $r['opts'], 1 ), fn( $x ) => null !== $x );
		return $p ? min( $p ) : null;
	};

	ob_start();
	?>
	<div id="zvListing">
	<?php if ( $a['showTiles'] ) : ?><div class="wrap"><nav class="listing-areas" aria-label="Browse by area" id="areaTiles"></nav></div><?php endif; ?>
	<div class="filters" id="filters" data-mbar-anchor>
	  <div class="wrap">
	    <div class="frow top">
	      <div class="seg" role="group" aria-label="Area" id="areaSeg"></div>
	      <span class="sp"></span>
	      <select id="time" aria-label="Duration"><option value="any">Any length</option><option value="short">Under 45 min</option><option value="hour">About an hour</option><option value="long">90 min or more</option></select>
	      <select id="sort" aria-label="Sort"><option value="rec">Recommended</option><option value="price">Price: low to high</option><option value="time">Shortest first</option></select>
	      <div class="view" role="group" aria-label="View">
	        <button aria-pressed="true" data-v="cards" title="Cards"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>Cards</button>
	        <button aria-pressed="false" data-v="list" title="Price list"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>Price list</button>
	      </div>
	    </div>
	    <div class="frow"><span class="flabel">I need</span><div class="scroller" role="group" aria-label="Need" id="needChips"></div></div>
	  </div>
	</div>
	<div class="wrap results" id="results">
	  <div class="rhead" id="rhead"><span><b><?php echo count( $rituals ); ?></b> rituals</span></div>
	  <div id="out">
	  <?php
	  foreach ( $areas as $slug => $ar ) :
		  if ( 'all' !== $initial && $slug !== $initial ) {
			  continue;
		  }
		  $group = array_values( array_filter( $rituals, fn( $r ) => $r['area'] === $slug ) );
		  if ( ! $group ) {
			  continue;
		  }
		  usort( $group, fn( $x, $y ) => $y['pop'] <=> $x['pop'] );
		  $prices = array_filter( array_map( $min_p, $group ), fn( $x ) => null !== $x );
		  ?>
		  <section class="group" id="g-<?php echo esc_attr( $slug ); ?>">
			<div class="ghead" style="--c:<?php echo esc_attr( $ar['c'] ); ?>">
			  <div class="gimg"><?php if ( $ar['img'] ) : ?><img src="<?php echo esc_url( $ar['img'] ); ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></div>
			  <div><h2><?php echo esc_html( $ar['name'] ); ?></h2><p><?php echo esc_html( $ar['intro'] ); ?></p></div>
			  <span class="cnt"><?php echo count( $group ) . ( 1 === count( $group ) ? ' ritual' : ' rituals' ) . ( $prices ? ' · from €' . min( $prices ) : '' ); ?></span>
			</div>
			<div class="grid"><?php foreach ( $group as $r ) { echo zv_card_html( $r, $areas, $needs ); } // phpcs:ignore ?></div>
		  </section>
	  <?php endforeach; ?>
	  </div>
	</div>
	<dialog id="quiz" class="quiz" aria-label="Find your ritual">
	  <div class="qz">
	    <div class="qz-top"><div class="qz-bar"><i id="qzBar" style="width:33%"></i></div><button class="qz-x" id="qzClose" aria-label="Close">×</button></div>
	    <span class="eyebrow" id="qzStep">Question 1 of 3</span>
	    <h3 id="qzQ"></h3>
	    <div class="qz-opts" id="qzOpts"></div>
	    <button class="qz-back" id="qzBack" hidden>← Back</button>
	  </div>
	</dialog>
	</div>
	<?php
	return ob_get_clean();
}

function zv_render_foot_map( $a ) {
	$zones = '';
	foreach ( array( 1 => 'head', 2 => 'lungs', 3 => 'stomach', 4 => 'back' ) as $i => $z ) {
		$zones .= '<li><button type="button" data-z="' . $z . '" aria-pressed="' . ( 1 === $i ? 'true' : 'false' ) . '"><span class="n">' . $i . '</span><span><b>' . zv_e( $a[ "z{$i}t" ] ) . '</b><small>' . zv_e( $a[ "z{$i}d" ] ) . '</small></span></button></li>';
	}
	return '<div class="map-grid"><div>' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. '<h2>' . zv_k( $a['title'] ) . '</h2><p class="lead">' . zv_k( $a['text'] ) . '</p><ul class="zones">' . $zones . '</ul></div>'
		. '<div class="foot-wrap" aria-hidden="true">' . zv_foot_svg() . '</div></div>';
}

function zv_foot_svg() {
	return '<svg class="foot" viewBox="0 0 200 440">'
		. '<path class="sole" d="M70 72C40 82 30 122 34 162c4 40 24 70 26 110 2 40-10 68-4 103 6 35 34 50 54 47 30-4 46-27 44-62-2-40-8-70-4-110 4-40 22-80 20-125-2-40-30-63-60-63-15 0-28 3-40 10z"/>'
		. '<path class="sole" d="M58 26c12 0 20 12 20 26s-8 24-20 24-20-10-20-24 8-26 20-26z"/>'
		. '<ellipse class="sole" cx="93" cy="38" rx="12" ry="15"/><ellipse class="sole" cx="119" cy="38" rx="11" ry="13"/><ellipse class="sole" cx="142" cy="48" rx="10" ry="12"/><ellipse class="sole" cx="161" cy="66" rx="9" ry="11"/>'
		. '<g class="z" data-z="head"><ellipse cx="58" cy="51" rx="17" ry="21"/><ellipse cx="93" cy="38" rx="11" ry="14"/><ellipse cx="119" cy="38" rx="10" ry="12"/><ellipse cx="142" cy="48" rx="9" ry="11"/><ellipse cx="161" cy="66" rx="8" ry="10"/></g>'
		. '<ellipse class="z" data-z="lungs" cx="103" cy="124" rx="56" ry="32"/><ellipse class="z" data-z="stomach" cx="100" cy="226" rx="42" ry="50"/><ellipse class="z" data-z="back" cx="104" cy="378" rx="42" ry="38"/>'
		. '<g fill="#5E4D33" font-family="Figtree" font-size="13" font-weight="600" text-anchor="middle" pointer-events="none"><text x="58" y="56">1</text><text x="103" y="129">2</text><text x="100" y="231">3</text><text x="104" y="383">4</text></g></svg>';
}

function zv_render_benefit( $a ) {
	return '<div class="benefit">' . zv_icon( $a['icon'] ) . '<b>' . zv_e( $a['title'] ) . '</b><p>' . zv_k( $a['text'] ) . '</p></div>';
}

function zv_render_compare( $a ) {
	$col = function ( $title, $text, $items, $cls ) {
		$li = implode( '', array_map( fn( $i ) => '<li>' . zv_e( $i ) . '</li>', array_filter( array_map( 'trim', explode( "\n", (string) $items ) ) ) ) );
		return '<div class="cmp' . $cls . '"><h3>' . zv_e( $title ) . '</h3>' . ( $text ? '<p class="sub">' . zv_e( $text ) . '</p>' : '' ) . '<ul>' . $li . '</ul></div>';
	};
	return '<div class="compare">' . $col( $a['leftTitle'], $a['leftText'], $a['leftItems'], '' ) . $col( $a['rightTitle'], $a['rightText'], $a['rightItems'], ' us' ) . '</div>';
}

function zv_render_story( $a, $content ) {
	return '<div class="story"><div>' . ( $a['eyebrow'] ? '<span class="eyebrow">' . zv_e( $a['eyebrow'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h2>' . zv_k( $a['title'] ) . '</h2>' : '' )
		. ( $a['quote'] ? '<blockquote class="quote">' . zv_k( $a['quote'] ) . '</blockquote>' : '' )
		. '</div><div><div class="prose clamp">' . $content . '</div><button type="button" class="read-more" aria-expanded="false">Read more</button></div></div>';
}

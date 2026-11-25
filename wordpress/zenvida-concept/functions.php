<?php
/**
 * ZenVida Concept theme.
 *
 * Content model
 *  - Pages are built from ZenVida blocks (inc/blocks.php): every heading, button,
 *    image and list item is editable in the block editor.
 *  - Rituals are a post type with Area and Need taxonomies; prices live in "Ritual details".
 *  - Contact details, opening hours, rating, promo bar and demo mode live in
 *    Appearance → ZenVida settings (inc/settings.php). Menus live in Appearance → Menus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZV_VERSION', '1.15.0' );
define( 'ZV_CONCEPTS_URL', 'https://canberkbalcilar.github.io/zenvida-redesign-concept/' );

require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/brand.php';
require_once __DIR__ . '/inc/i18n.php';
require_once __DIR__ . '/inc/media.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/seed.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/popups.php';
require_once __DIR__ . '/inc/events.php';
require_once __DIR__ . '/inc/messages.php';
require_once __DIR__ . '/inc/offers.php';
require_once __DIR__ . '/inc/careers.php';
require_once __DIR__ . '/inc/bodymap.php';
require_once __DIR__ . '/inc/findus.php';
require_once __DIR__ . '/inc/pricing.php';
require_once __DIR__ . '/inc/referral.php';
require_once __DIR__ . '/inc/updater.php';
require_once __DIR__ . '/inc/booking.php';
require_once __DIR__ . '/inc/greviews.php';
require_once __DIR__ . '/inc/schema.php';
require_once __DIR__ . '/inc/siesta.php';

/* -------------------------------------------------------------------------
 * Theme setup
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 400, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( array( 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Figtree:wght@400;500;600&display=swap', 'assets/css/zenvida.css', 'assets/css/editor.css' ) );
	register_nav_menus( array(
		'primary'  => 'Main menu',
		'footer-1' => 'Footer column 1',
		'footer-2' => 'Footer column 2',
		'footer-3' => 'Footer column 3',
		'legal'    => 'Footer legal links',
	) );
} );

add_filter( 'document_title_separator', fn() => '|' );
add_filter( 'document_title_parts', function ( $parts ) {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return $parts; // An SEO plugin manages titles.
	}
	if ( is_post_type_archive( 'ritual' ) ) {
		$parts['title'] = zv_tr( zv_opt( 'seo_rituals' ) );
	} elseif ( is_singular( 'ritual' ) ) {
		$parts['title'] = trim( zv_tr( get_the_title() ) . ' ' . zv_tr( zv_opt( 'seo_ritual_suffix' ) ) );
	} elseif ( is_front_page() ) {
		$parts['title'] = zv_tr( zv_opt( 'seo_home' ) );
	}
	unset( $parts['tagline'] );
	return $parts;
} );
add_action( 'wp_head', function () {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$d = '';
	if ( is_front_page() ) {
		$d = zv_opt( 'seo_home_desc' );
	} elseif ( is_singular( 'ritual' ) ) {
		$d = get_post_meta( get_queried_object_id(), 'zv_desc', true );
	} elseif ( is_post_type_archive( 'ritual' ) ) {
		$d = zv_opt( 'seo_rituals_desc' );
	} elseif ( is_tax( 'area' ) ) {
		$d = term_description();
	}
	if ( $d ) {
		echo '<meta name="description" content="' . esc_attr( zv_tr( wp_strip_all_tags( $d ) ) ) . '">' . "\n";
	}
}, 1 );

/** Questions every ritual starts with (also used by the demo content). */
function zv_basic_faq() {
	return array(
		array( 'Do I need to book?', 'No, walk-ins are welcome. Booking ahead guarantees your time, especially at weekends; a €25 deposit per person (€45 for rituals from €90) secures it and counts towards the price.', true ),
		array( 'What is your cancellation policy?', "Please give at least 4 hours' notice to cancel or reschedule. Appointments cancelled with less notice, or missed, are charged in full." ),
		array( 'Can I come with a friend?', 'Yes, you can relax side by side. Groups of two or more are arranged by our reception team: send us a WhatsApp or call and we will find times for everyone.' ),
	);
}

/** Page sections a new ritual starts with (block editor template). */
function zv_ritual_template() {
	$faq = array_map( fn( $q ) => array( 'zv/faq-item', array( 'question' => $q[0], 'answer' => $q[1], 'open' => ! empty( $q[2] ) ) ), zv_basic_faq() );
	return array(
		array( 'zv/section', array( 'eyebrow' => 'FAQ', 'title' => 'Good to know' ), array( array( 'zv/faq', array(), $faq ) ) ),
		array( 'zv/section', array( 'background' => 'cream', 'anchor' => 'visit', 'eyebrow' => 'Visit us', 'title' => 'A sanctuary in the heart of Madrid', 'text' => "A private, calm studio five minutes' walk from Puerta del Sol." ), array( array( 'zv/visit', array( 'eyebrow' => '', 'title' => '', 'layout' => 'wide' ) ) ) ),
	);
}

/* -------------------------------------------------------------------------
 * Content model: Area + Need taxonomies, Ritual post type.
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	register_taxonomy( 'area', 'ritual', array(
		'labels'            => array( 'name' => 'Areas', 'singular_name' => 'Area', 'menu_name' => 'Areas', 'edit_item' => 'Edit area', 'add_new_item' => 'Add new area', 'update_item' => 'Save area', 'search_items' => 'Search areas', 'back_to_items' => '← Back to areas', 'not_found' => 'No areas yet.' ),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => false, // Chosen in "Ritual details".
		'meta_box_cb'       => false,
		'rewrite'           => array( 'slug' => 'rituals', 'with_front' => false ),
	) );

	register_taxonomy( 'need', 'ritual', array(
		'labels'             => array( 'name' => 'Needs', 'singular_name' => 'Need', 'menu_name' => 'Needs ("how do you feel")', 'edit_item' => 'Edit need', 'add_new_item' => 'Add new need', 'update_item' => 'Save need', 'search_items' => 'Search needs', 'back_to_items' => '← Back to needs', 'not_found' => 'No needs yet.' ),
		'public'             => true,
		'publicly_queryable' => false,
		'hierarchical'       => true,
		'show_admin_column'  => false,
		'show_in_rest'       => false, // Chosen in "Ritual details".
		'meta_box_cb'        => false,
		'rewrite'            => false,
	) );

	register_post_type( 'ritual', array(
		'labels'        => array(
			'name'          => 'Rituals',
			'singular_name' => 'Ritual',
			'add_new_item'  => 'Add new ritual',
			'edit_item'     => 'Edit ritual',
			'all_items'     => 'All rituals',
		),
		'public'        => true,
		'menu_icon'     => 'dashicons-heart',
		'menu_position' => 5,
		'has_archive'   => 'rituals',
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'editor', 'revisions' ),
		'taxonomies'    => array( 'area', 'need' ),
		'rewrite'       => array( 'slug' => 'rituals/%area%', 'with_front' => false ),
		'template'      => zv_ritual_template(), // New rituals start with an FAQ and "Visit us".
	) );

	add_rewrite_tag( '%area%', '([^/]+)', 'area=' );
} );

// /rituals/%area%/slug/ → the ritual's first Area term.
add_filter( 'post_type_link', function ( $link, $post ) {
	if ( 'ritual' !== $post->post_type || false === strpos( $link, '%area%' ) ) {
		return $link;
	}
	$terms = get_the_terms( $post, 'area' );
	$slug  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : 'all';
	return str_replace( '%area%', $slug, $link );
}, 10, 2 );

add_action( 'template_redirect', function () {
	// A ritual opened under the wrong area (e.g. /rituals/massage/deluxe-reflexology/) → 301 to its real URL.
	if ( is_singular( 'ritual' ) && get_option( 'permalink_structure' ) ) {
		$req   = get_query_var( 'area' );
		$terms = get_the_terms( get_queried_object(), 'area' );
		if ( $req && $terms && ! is_wp_error( $terms ) && $req !== $terms[0]->slug ) {
			wp_safe_redirect( get_permalink( get_queried_object() ), 301 );
			exit;
		}
	}
	// A ritual switched off in the admin is not shown to visitors.
	if ( is_singular( 'ritual' ) && get_post_meta( get_queried_object_id(), 'zv_hidden', true ) && ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return;
	}
	// Author archives only list journal posts and reveal user names: send them to the journal.
	if ( is_author() ) {
		wp_safe_redirect( get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/' ), 301 );
		exit;
	}
	// The page that holds the Rituals blocks is shown at /rituals/, not at its own URL.
	$rp = (int) zv_opt( 'rituals_page' );
	if ( $rp && is_page( $rp ) && ! is_preview() ) {
		wp_safe_redirect( zv_rituals_url(), 301 );
		exit;
	}
} );

/* -------------------------------------------------------------------------
 * Front-end assets
 * ---------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	foreach ( array( 'wp-block-library-theme', 'global-styles', 'classic-theme-styles' ) as $h ) {
		wp_dequeue_style( $h );
	}
	$uri = get_template_directory_uri() . '/assets';
	$ver = fn( $f ) => ZV_VERSION . '.' . filemtime( get_template_directory() . '/assets/' . $f ); // new file = new URL, no stale cache
	wp_enqueue_style( 'zv-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Figtree:wght@400;500;600&display=swap', array(), null );
	wp_enqueue_style( 'zv-site', "$uri/css/zenvida.css", array(), $ver( 'css/zenvida.css' ) );
	wp_enqueue_script( 'zv-site', "$uri/js/site.js", array(), $ver( 'js/site.js' ), true );
	wp_add_inline_script( 'zv-site', 'window.ZV_SITE = ' . wp_json_encode( array( 'hours' => zv_hours(), 'tz' => zv_tz(), 'whatsapp' => preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ), 'lang' => zv_lang(), 'i18n' => 'es' === zv_lang() ? zv_ui_strings() : new stdClass() ) ) . ';', 'before' );
	if ( is_singular( 'ritual' ) ) {
		wp_enqueue_script( 'zv-single', "$uri/js/single.js", array( 'zv-site' ), $ver( 'js/single.js' ), true );
		wp_add_inline_script( 'zv-single', 'window.ZV_SINGLE = ' . wp_json_encode( zv_tr_row( zv_ritual_data( get_queried_object() ), array( 'name', 'areaName', 'desc' ) ) ) . ';', 'before' );
	}
}, 20 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Editor: data the block previews need, and sidebar styling.
add_action( 'enqueue_block_editor_assets', function () {
	wp_add_inline_script( 'zv-blocks', 'window.ZV_EDITOR = ' . wp_json_encode( array(
		'rating'      => zv_opt( 'rating' ),
		'ratingCount' => zv_opt( 'rating_count' ),
		'footSvg'     => zv_foot_svg(),
		'icons'       => array_merge(
			array_combine( $n = array( 'home', 'heart', 'leaf', 'wave', 'moon', 'walk', 'star', 'pin', 'clock', 'award', 'users', 'check', 'arrow' ), array_map( 'zv_icon', $n ) ),
			array_combine( $b = array_keys( zv_brand_icons() ), array_map( fn( $k ) => zv_brand_svg( 'icon-' . $k ), $b ) )
		),
		'img'         => array(
			'hero'     => zv_img( 'b84849_1f76460dcaab4a9d9b293ecf6b206bf4~mv2.jpg', 2000, 1200 ),
			'room'     => zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 960 ),
			'roomTall' => zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1000, 1250 ),
		),
	) ) . ';', 'before' );
	wp_add_inline_style( 'wp-edit-blocks', '.zv-side-img p{font-size:11px;text-transform:uppercase;font-weight:500;margin:0 0 8px}.zv-side-img img{width:100%;border-radius:6px;margin-bottom:8px;display:block}.zv-side-img{margin-bottom:18px}.zv-note{margin:0 0 16px}' );
	wp_add_inline_script( 'wp-edit-post', "wp.domReady(function(){ wp.data.dispatch('core/preferences').set('core/edit-post','welcomeGuide',false); });" );
} );

/* -------------------------------------------------------------------------
 * Data helpers (shared by blocks, templates and front-end scripts)
 * ---------------------------------------------------------------------- */

/** Parse "60:80, 90:110" into [[60,80],[90,110]]. A missing price becomes null. */
function zv_parse_options( $raw ) {
	$out = array();
	foreach ( array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) ) as $pair ) {
		$parts = array_map( 'trim', explode( ':', $pair ) );
		$min   = (int) $parts[0];
		if ( $min > 0 ) {
			$out[] = array( $min, ( isset( $parts[1] ) && '' !== $parts[1] ) ? (int) $parts[1] : null );
		}
	}
	return $out ?: array( array( 60, null ) );
}

function zv_ritual_data( $post ) {
	$post  = get_post( $post );
	$area  = get_the_terms( $post, 'area' );
	$needs = get_the_terms( $post, 'need' );
	$flags = array_values( array_filter( array(
		get_post_meta( $post->ID, 'zv_signature', true ) ? 'sig' : null,
		get_post_meta( $post->ID, 'zv_seasonal', true ) ? 'seas' : null,
		get_post_meta( $post->ID, 'zv_couples', true ) ? 'two' : null,
	) ) );
	return array(
		'id'       => $post->ID,
		'slug'     => $post->post_name,
		'name'     => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ), // plain text; escaped where printed
		'area'     => ( $area && ! is_wp_error( $area ) ) ? $area[0]->slug : '',
		'areaName' => ( $area && ! is_wp_error( $area ) ) ? $area[0]->name : '',
		'desc'     => (string) get_post_meta( $post->ID, 'zv_desc', true ),
		'opts'     => zv_parse_options( get_post_meta( $post->ID, 'zv_options', true ) ),
		'needs'    => ( $needs && ! is_wp_error( $needs ) ) ? wp_list_pluck( $needs, 'slug' ) : array(),
		'flags'    => $flags,
		'pop'      => (int) get_post_meta( $post->ID, 'zv_popularity', true ),
		'deposit'  => '' !== (string) get_post_meta( $post->ID, 'zv_deposit', true ) ? (int) get_post_meta( $post->ID, 'zv_deposit', true ) : null, // null = standard rule
		'baths'    => (bool) get_post_meta( $post->ID, 'zv_baths', true ),
		'hidden'   => (bool) get_post_meta( $post->ID, 'zv_hidden', true ),
		'url'      => get_permalink( $post ),
	);
}

/** Terms of a taxonomy by their "Position" field (terms without one go last). */
function zv_terms_ordered( $tax ) {
	$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$pos = fn( $t ) => metadata_exists( 'term', $t->term_id, 'zv_order' ) ? (int) get_term_meta( $t->term_id, 'zv_order', true ) : PHP_INT_MAX;
	usort( $terms, fn( $a, $b ) => $pos( $a ) <=> $pos( $b ) ?: strcmp( $a->name, $b->name ) );
	return $terms;
}

function zv_areas() {
	$out = array();
	foreach ( zv_terms_ordered( 'area' ) as $t ) {
		$out[ $t->slug ] = array(
			'name'  => $t->name,
			'c'     => get_term_meta( $t->term_id, 'zv_color', true ) ?: '#B0956A',
			'img'   => get_term_meta( $t->term_id, 'zv_image', true ) ?: null,
			'imgSet' => ( $zv_i = get_term_meta( $t->term_id, 'zv_image', true ) ) ? zv_image_set( $zv_i )[1] : '',
			'intro' => $t->description,
			'long'  => (string) get_term_meta( $t->term_id, 'zv_long', true ),
			'url'   => get_term_link( $t ),
		);
	}
	return $out;
}

function zv_needs() {
	$out = array();
	foreach ( zv_terms_ordered( 'need' ) as $t ) {
		$out[ $t->slug ] = $t->name;
	}
	return $out;
}

function zv_all_rituals() {
	static $cache = null;
	if ( null === $cache ) {
		$posts = get_posts( array( 'post_type' => 'ritual', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		$cache = array_values( array_filter( array_map( 'zv_ritual_data', $posts ), fn( $r ) => ! $r['hidden'] ) );
	}
	return $cache;
}

function zv_archive_data() {
	$initial = is_tax( 'area' ) ? get_queried_object()->slug : 'all';
	return array(
		'areas'       => zv_areas(),
		'needs'       => zv_needs(),
		'rituals'     => zv_all_rituals(),
		'initialArea' => $initial,
		'quiz'        => array_map( fn( $q ) => array( 'q' => $q['q'], 'o' => array_map( fn( $a ) => array( $a[2], $a[0], $a[1] ), $q['a'] ) ), zv_quiz() ),
	);
}

/** Logo: the uploaded one (Customize → Site Identity), otherwise the ZenVida primary logo (brand SVG). */
function zv_logo( $tone = 'dark' ) {
	$id = (int) get_theme_mod( 'custom_logo' );
	echo '<a class="logo" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . ' home">';
	if ( $id ) {
		echo wp_get_attachment_image( $id, 'medium', false, array( 'class' => 'logo-img', 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		echo '<img class="logo-brand" src="' . esc_url( get_template_directory_uri() . '/assets/brand/logo-primary-' . ( 'ivory' === $tone ? 'ivory' : 'dark' ) . '.svg' ) . '" width="150" height="37" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
	}
	echo '</a>';
}

/**
 * Deposit per person (Reservations Policy): €25, or €45 for services of €90 or more.
 * A ritual can set its own amount in "Ritual details".
 */
function zv_deposit( $price, $custom = null ) {
	if ( null !== $custom ) {
		return (int) $custom;
	}
	return ( null !== $price && (int) $price >= 90 ) ? 45 : 25;
}

function zv_eur( $n ) {
	return null === $n ? 'Price at studio' : '€' . $n;
}

/** Server-rendered ritual card. Mirrors card() in assets/js/listing.js. */
function zv_card_html( $r, $areas, $needs ) {
	$a      = $areas[ $r['area'] ] ?? array( 'name' => '', 'c' => '#999' );
	$o      = $r['opts'][0];
	$badges = ( in_array( 'sig', $r['flags'], true ) ? '<span class="badge sig">Signature</span>' : '' )
		. ( in_array( 'seas', $r['flags'], true ) ? '<span class="badge seas">Seasonal</span>' : '' );
	$need   = implode( '', array_map( fn( $n ) => '<span>' . esc_html( $needs[ $n ] ?? $n ) . '</span>', $r['needs'] ) );
	if ( count( $r['opts'] ) > 1 ) {
		$opts = '';
		foreach ( $r['opts'] as $j => $op ) {
			$opts .= sprintf( '<button class="opt" data-slug="%s" data-i="%d" aria-pressed="%s"><b>%d min</b><small>%s</small></button>', esc_attr( $r['slug'] ), $j, 0 === $j ? 'true' : 'false', $op[0], esc_html( zv_eur( $op[1] ) ) );
		}
	} else {
		$opts = sprintf( '<span class="opt" style="cursor:default"><b>%d min</b><small>%s</small></span>', $o[0], esc_html( zv_eur( $o[1] ) ) );
	}
	return sprintf(
		'<article class="card" style="--c:%1$s"><div class="ctop"><span class="area-dot">%2$s</span>%3$s</div><h3>%4$s</h3><p>%5$s</p><div class="needs">%6$s</div><div class="opts" role="group" aria-label="Duration">%7$s</div><div class="cfoot"><a href="%8$s">Details</a><button class="btn btn-dark" data-book="%9$s" data-i="0">Book%10$s</button></div></article>',
		esc_attr( $a['c'] ), esc_html( $a['name'] ), $badges, esc_html( $r['name'] ), esc_html( $r['desc'] ), $need, $opts,
		esc_url( $r['url'] ), esc_attr( $r['slug'] ), $o[1] ? ' · €' . $o[1] : ''
	);
}

/** Render a page's blocks (used for the Rituals page shown at /rituals/). */
function zv_render_page_content( $page_id ) {
	$page = $page_id ? get_post( $page_id ) : null;
	if ( ! $page ) {
		return '';
	}
	return apply_filters( 'the_content', $page->post_content ); // phpcs:ignore
}

// Admin list: same order as the site, with a price column.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'ritual' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'menu_order' );
		$q->set( 'order', 'ASC' );
	}
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'ritual' ) || $q->is_tax( 'area' ) ) ) {
		$q->set( 'posts_per_page', -1 );
	}
} );

/* -------------------------------------------------------------------------
 * Rituals and events switched off in the admin: out of search, feeds and sitemaps too.
 * ---------------------------------------------------------------------- */
function zv_hidden_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$ids = get_posts( array( 'post_type' => array( 'ritual', 'zv_event' ), 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => 'zv_hidden', 'meta_value' => '1', 'suppress_filters' => false ) );
	}
	return $ids;
}
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_search() || $q->is_feed() ) ) {
		$exclude = zv_hidden_ids();
		if ( (int) zv_opt( 'rituals_page' ) ) {
			$exclude[] = (int) zv_opt( 'rituals_page' ); // helper page, shown at /rituals/
		}
		if ( $exclude ) {
			$q->set( 'post__not_in', array_merge( (array) $q->get( 'post__not_in' ), $exclude ) );
		}
	}
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $type ) {
	if ( in_array( $type, array( 'ritual', 'zv_event' ), true ) && zv_hidden_ids() ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), zv_hidden_ids() );
	}
	return $args;
}, 10, 2 );
// Yoast SEO sitemaps.
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', fn( $ids ) => array_merge( (array) $ids, zv_hidden_ids(), array_filter( array( (int) zv_opt( 'rituals_page' ) ) ) ) );

/* -------------------------------------------------------------------------
 * Sitemap: no user list, no helper page that only redirects to /rituals/.
 * ---------------------------------------------------------------------- */
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $type ) {
	if ( 'page' === $type && (int) zv_opt( 'rituals_page' ) ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), array( (int) zv_opt( 'rituals_page' ) ) );
	}
	return $args;
}, 10, 2 );

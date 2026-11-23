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

define( 'ZV_VERSION', '0.2.0' );
define( 'ZV_CONCEPTS_URL', 'https://canberkbalcilar.github.io/zenvida-redesign-concept/' );

require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/seed.php';

/* -------------------------------------------------------------------------
 * Theme setup
 * ---------------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
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
	if ( is_post_type_archive( 'ritual' ) ) {
		$parts['title'] = 'Massage & Reflexology Rituals in Madrid';
	} elseif ( is_singular( 'ritual' ) ) {
		$parts['title'] = get_the_title() . ' in Madrid Centro';
	} elseif ( is_front_page() ) {
		$parts['title'] = 'Reflexology & Body Massage in Madrid Centro';
	}
	unset( $parts['tagline'] );
	return $parts;
} );

/* -------------------------------------------------------------------------
 * Content model: Area + Need taxonomies, Ritual post type.
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	register_taxonomy( 'area', 'ritual', array(
		'labels'            => array( 'name' => 'Areas', 'singular_name' => 'Area', 'menu_name' => 'Areas' ),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'rituals', 'with_front' => false ),
	) );

	register_taxonomy( 'need', 'ritual', array(
		'labels'             => array( 'name' => 'Needs', 'singular_name' => 'Need', 'menu_name' => 'Needs' ),
		'public'             => true,
		'publicly_queryable' => false,
		'hierarchical'       => true,
		'show_admin_column'  => true,
		'show_in_rest'       => true,
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
		'supports'      => array( 'title', 'editor', 'excerpt' ),
		'taxonomies'    => array( 'area', 'need' ),
		'rewrite'       => array( 'slug' => 'rituals/%area%', 'with_front' => false ),
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
	// The page that holds the Rituals blocks is shown at /rituals/, not at its own URL.
	$rp = (int) zv_opt( 'rituals_page' );
	if ( $rp && is_page( $rp ) ) {
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
	wp_enqueue_style( 'zv-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Figtree:wght@400;500;600&display=swap', array(), null );
	wp_enqueue_style( 'zv-site', "$uri/css/zenvida.css", array(), ZV_VERSION );
	wp_enqueue_script( 'zv-site', "$uri/js/site.js", array(), ZV_VERSION, true );
	wp_add_inline_script( 'zv-site', 'window.ZV_SITE = ' . wp_json_encode( array( 'hours' => zv_hours() ) ) . ';', 'before' );
	if ( is_singular( 'ritual' ) ) {
		wp_enqueue_script( 'zv-single', "$uri/js/single.js", array( 'zv-site' ), ZV_VERSION, true );
		wp_add_inline_script( 'zv-single', 'window.ZV_SINGLE = ' . wp_json_encode( zv_ritual_data( get_queried_object() ) ) . ';', 'before' );
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
		'icons'       => array_combine( $n = array( 'home', 'heart', 'leaf', 'wave', 'moon', 'walk', 'star', 'pin', 'clock', 'award', 'users', 'check', 'arrow' ), array_map( 'zv_icon', $n ) ),
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
		'name'     => get_the_title( $post ),
		'area'     => ( $area && ! is_wp_error( $area ) ) ? $area[0]->slug : '',
		'areaName' => ( $area && ! is_wp_error( $area ) ) ? $area[0]->name : '',
		'desc'     => (string) get_post_meta( $post->ID, 'zv_desc', true ),
		'opts'     => zv_parse_options( get_post_meta( $post->ID, 'zv_options', true ) ),
		'needs'    => ( $needs && ! is_wp_error( $needs ) ) ? wp_list_pluck( $needs, 'slug' ) : array(),
		'flags'    => $flags,
		'pop'      => (int) get_post_meta( $post->ID, 'zv_popularity', true ),
		'deposit'  => (int) ( get_post_meta( $post->ID, 'zv_deposit', true ) ?: 25 ),
		'baths'    => (bool) get_post_meta( $post->ID, 'zv_baths', true ),
		'url'      => get_permalink( $post ),
	);
}

function zv_areas() {
	$out   = array();
	$terms = get_terms( array( 'taxonomy' => 'area', 'hide_empty' => false, 'meta_key' => 'zv_order', 'orderby' => 'meta_value_num' ) );
	if ( is_wp_error( $terms ) ) {
		return $out;
	}
	foreach ( $terms as $t ) {
		$out[ $t->slug ] = array(
			'name'  => $t->name,
			'c'     => get_term_meta( $t->term_id, 'zv_color', true ) ?: '#B0956A',
			'img'   => get_term_meta( $t->term_id, 'zv_image', true ) ?: null,
			'intro' => $t->description,
			'long'  => (string) get_term_meta( $t->term_id, 'zv_long', true ),
			'url'   => get_term_link( $t ),
		);
	}
	return $out;
}

function zv_needs() {
	$out   = array();
	$terms = get_terms( array( 'taxonomy' => 'need', 'hide_empty' => false, 'meta_key' => 'zv_order', 'orderby' => 'meta_value_num' ) );
	if ( is_wp_error( $terms ) ) {
		return $out;
	}
	foreach ( $terms as $t ) {
		$out[ $t->slug ] = $t->name;
	}
	return $out;
}

function zv_all_rituals() {
	static $cache = null;
	if ( null === $cache ) {
		$posts = get_posts( array( 'post_type' => 'ritual', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		$cache = array_map( 'zv_ritual_data', $posts );
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
	);
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

/* -------------------------------------------------------------------------
 * Admin: "Ritual details" box for prices, durations and labels.
 * ---------------------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'zv_details', 'Ritual details', 'zv_details_box', 'ritual', 'normal', 'high' );
} );

function zv_details_box( $post ) {
	wp_nonce_field( 'zv_details', 'zv_details_nonce' );
	$v = fn( $k ) => esc_attr( get_post_meta( $post->ID, $k, true ) );
	$c = fn( $k ) => checked( (bool) get_post_meta( $post->ID, $k, true ), true, false );
	?>
	<p><label><strong>Durations &amp; prices</strong><br>
		<input type="text" name="zv_options" value="<?php echo $v( 'zv_options' ); ?>" class="widefat" placeholder="60:80, 90:110">
	</label><br><span class="description">Minutes:price, separated by commas. Leave the price empty to show "Price at studio".</span></p>
	<p><label><strong>Card description</strong> (one sentence on the rituals page)<br>
		<textarea name="zv_desc" rows="2" class="widefat"><?php echo esc_textarea( get_post_meta( $post->ID, 'zv_desc', true ) ); ?></textarea>
	</label></p>
	<p><label><strong>Photo URL</strong><br><input type="url" name="zv_image" value="<?php echo $v( 'zv_image' ); ?>" class="widefat"></label></p>
	<p><label><strong>Highlights</strong> (one per line, "Title | text")<br>
		<textarea name="zv_highlights" rows="3" class="widefat"><?php echo esc_textarea( get_post_meta( $post->ID, 'zv_highlights', true ) ); ?></textarea>
	</label></p>
	<p style="display:flex;gap:18px;flex-wrap:wrap">
		<label><input type="checkbox" name="zv_signature" value="1" <?php echo $c( 'zv_signature' ); ?>> Signature</label>
		<label><input type="checkbox" name="zv_seasonal" value="1" <?php echo $c( 'zv_seasonal' ); ?>> Seasonal</label>
		<label><input type="checkbox" name="zv_couples" value="1" <?php echo $c( 'zv_couples' ); ?>> Priced for two</label>
		<label><input type="checkbox" name="zv_baths" value="1" <?php echo $c( 'zv_baths' ); ?>> Offer foot-bath upgrades</label>
	</p>
	<p style="display:flex;gap:18px">
		<label>Deposit (€) <input type="number" name="zv_deposit" value="<?php echo $v( 'zv_deposit' ); ?>" style="width:80px"></label>
		<label>Popularity (0–10) <input type="number" name="zv_popularity" value="<?php echo $v( 'zv_popularity' ); ?>" style="width:80px" min="0" max="10"></label>
	</p>
	<p class="description">The page sections below the booking card (steps, FAQ, visit…) are edited as blocks in the editor above.</p>
	<?php
}

add_action( 'save_post_ritual', function ( $post_id ) {
	if ( ! isset( $_POST['zv_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zv_details_nonce'] ), 'zv_details' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( 'zv_options', 'zv_desc', 'zv_highlights' ) as $k ) {
		update_post_meta( $post_id, $k, sanitize_textarea_field( wp_unslash( $_POST[ $k ] ?? '' ) ) );
	}
	update_post_meta( $post_id, 'zv_image', esc_url_raw( wp_unslash( $_POST['zv_image'] ?? '' ) ) );
	foreach ( array( 'zv_signature', 'zv_seasonal', 'zv_couples', 'zv_baths' ) as $k ) {
		update_post_meta( $post_id, $k, empty( $_POST[ $k ] ) ? '' : '1' );
	}
	foreach ( array( 'zv_deposit', 'zv_popularity' ) as $k ) {
		update_post_meta( $post_id, $k, absint( $_POST[ $k ] ?? 0 ) );
	}
} );

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
add_filter( 'manage_ritual_posts_columns', function ( $cols ) {
	$cols['zv_price'] = 'Durations & prices';
	return $cols;
} );
add_action( 'manage_ritual_posts_custom_column', function ( $col, $id ) {
	if ( 'zv_price' === $col ) {
		echo esc_html( implode( ' · ', array_map( fn( $o ) => $o[0] . ' min ' . zv_eur( $o[1] ), zv_parse_options( get_post_meta( $id, 'zv_options', true ) ) ) ) );
	}
}, 10, 2 );

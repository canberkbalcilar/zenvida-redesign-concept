<?php
/**
 * ZenVida brand assets (Brand Guidelines, Dec 2025): logo, isotype, icons, brand fonts.
 * Files live in assets/brand/ (SVGs take the text colour) and assets/fonts/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An SVG from assets/brand/ inlined, e.g. zv_brand_svg( 'logo-primary', 'logo-svg' ).
 * Decorative by default (aria-hidden); pass a label to make it an image with a name.
 */
function zv_brand_svg( $name, $class = '', $label = '' ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$f              = get_template_directory() . '/assets/brand/' . sanitize_file_name( $name ) . '.svg';
		$cache[ $name ] = file_exists( $f ) ? trim( (string) file_get_contents( $f ) ) : ''; // phpcs:ignore
	}
	if ( '' === $cache[ $name ] ) {
		return '';
	}
	$attrs = ( $class ? ' class="' . esc_attr( $class ) . '"' : '' )
		. ( $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"' ) . ' focusable="false"';
	return preg_replace( '/^<svg\b/', '<svg' . $attrs, $cache[ $name ], 1 );
}

/** Storytelling icons from the brand set (pillars: Sanctuary, Ritual, Culture…). */
function zv_brand_icons() {
	return array( 'sanctuary' => 'Sanctuary', 'ritual' => 'Ritual', 'culture' => 'Culture', 'herbal' => 'Herbal spring', 'hands' => 'Hands massage', 'sun' => 'Sun' );
}

// Browser tab and home-screen icon: the isotype, unless one is set in Customize → Site Identity.
add_action( 'wp_head', 'zv_brand_favicon', 2 );
add_action( 'admin_head', 'zv_brand_favicon', 2 );
function zv_brand_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	$u = get_template_directory_uri() . '/assets/brand/';
	echo '<link rel="icon" href="' . esc_url( $u . 'favicon.svg' ) . '" type="image/svg+xml">' . "\n"
		. '<link rel="apple-touch-icon" href="' . esc_url( $u . 'apple-touch-icon.png' ) . '">' . "\n";
}

/**
 * Brand fonts. Averta PE (text) and Riccione (titles) are licensed fonts that are not in the theme yet.
 * With the web files present under these names the site switches to them by itself:
 *   averta-pe-regular.woff2, averta-pe-semibold.woff2, averta-pe-bold.woff2, riccione-regular.woff2
 * Looked for in wp-content/uploads/zenvida-fonts/ (kept by theme updates), then in the theme's assets/fonts/.
 * Until then the stand-ins (Figtree, Cormorant Garamond) are used.
 */
function zv_brand_fonts() {
	static $f = null;
	if ( null !== $f ) {
		return $f;
	}
	$up    = wp_upload_dir( null, false );
	$where = array(
		array( trailingslashit( $up['basedir'] ) . 'zenvida-fonts/', trailingslashit( $up['baseurl'] ) . 'zenvida-fonts/' ),
		array( get_template_directory() . '/assets/fonts/', get_template_directory_uri() . '/assets/fonts/' ),
	);
	$find  = function ( $name ) use ( $where ) {
		foreach ( $where as $w ) {
			if ( file_exists( $w[0] . $name ) ) {
				return $w[1] . $name;
			}
		}
		return '';
	};
	$f = array(
		'averta'   => array_filter( array( 400 => $find( 'averta-pe-regular.woff2' ), 600 => $find( 'averta-pe-semibold.woff2' ), 700 => $find( 'averta-pe-bold.woff2' ) ) ),
		'riccione' => $find( 'riccione-regular.woff2' ),
	);
	return $f;
}

function zv_brand_font_css() {
	$f   = zv_brand_fonts();
	$css = '';
	foreach ( $f['averta'] as $w => $url ) {
		$css .= "@font-face{font-family:'Averta PE';src:url('" . esc_url( $url ) . "') format('woff2');font-weight:$w;font-style:normal;font-display:swap}";
	}
	if ( $f['riccione'] ) {
		$css .= "@font-face{font-family:'Riccione';src:url('" . esc_url( $f['riccione'] ) . "') format('woff2');font-weight:400;font-style:normal;font-display:swap}";
	}
	$vars = ( $f['averta'] ? "--sans:'Averta PE',Figtree,system-ui,-apple-system,sans-serif;" : '' )
		. ( $f['riccione'] ? "--serif:'Riccione',Georgia,serif;" : '' );
	return $css . ( $vars ? ':root{' . $vars . '}' : '' );
}

add_action( 'wp_enqueue_scripts', function () {
	$css = zv_brand_font_css();
	if ( $css ) {
		wp_add_inline_style( 'zv-site', $css );
	}
}, 25 );

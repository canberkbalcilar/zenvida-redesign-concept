<?php
/**
 * Images: one helper for every photo on the site.
 *
 * Photo fields store the image address. When the page is drawn, zv_image() looks the
 * address up in the media library and prints a responsive <img> (srcset/sizes, width and
 * height, and the alt text typed in the media library), so phones download small files.
 * Photos still hosted on Wix get the same treatment through Wix's own resizing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Media library ID for an image address (any size variant), or 0. */
function zv_image_id( $url ) {
	static $cache = array();
	$url = (string) $url;
	if ( '' === $url || false !== strpos( $url, 'wixstatic.com' ) ) {
		return 0;
	}
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}
	$hit = wp_cache_get( md5( $url ), 'zv_image_id' );
	if ( false === $hit ) {
		$hit = attachment_url_to_postid( $url );
		if ( ! $hit && preg_match( '/^(.+)-\d+x\d+(\.[a-z0-9]+)$/i', $url, $m ) ) { // a size variant, e.g. photo-1024x640.jpg
			$hit = attachment_url_to_postid( $m[1] . $m[2] ) ?: attachment_url_to_postid( $m[1] . '-scaled' . $m[2] );
		}
		if ( ! $hit && preg_match( '/^(.+)(\.[a-z0-9]+)$/i', $url, $m ) ) { // large originals are stored as photo-scaled.jpg
			$hit = attachment_url_to_postid( $m[1] . '-scaled' . $m[2] );
		}
		wp_cache_set( md5( $url ), (int) $hit, 'zv_image_id', HOUR_IN_SECONDS );
	}
	return $cache[ $url ] = (int) $hit;
}

/** [src, srcset] for an image address. */
function zv_image_set( $url ) {
	$url = (string) $url;
	$id  = zv_image_id( $url );
	if ( $id ) {
		return array( wp_get_attachment_image_url( $id, 'large' ) ?: $url, (string) wp_get_attachment_image_srcset( $id, 'full' ) );
	}
	// Wix: .../v1/fill/w_1600,h_1000,... → the same crop at several widths.
	if ( preg_match( '#/v1/fill/w_(\d+),h_(\d+)#', $url, $m ) ) {
		$w     = (int) $m[1];
		$ratio = (int) $m[2] / max( 1, $w );
		$set   = array();
		foreach ( array( 480, 800, 1200, 1600, 2000 ) as $nw ) {
			if ( $nw <= max( $w, 800 ) ) {
				$set[] = preg_replace( '#/v1/fill/w_\d+,h_\d+#', '/v1/fill/w_' . $nw . ',h_' . (int) round( $nw * $ratio ), $url ) . ' ' . $nw . 'w';
			}
		}
		return array( $url, implode( ', ', $set ) );
	}
	return array( $url, '' );
}

/**
 * A responsive <img>.
 * $args: sizes (default 100vw), class, alt (used when the media library has none),
 *        eager (true for the first, large photo of a page), extra (raw attributes).
 */
function zv_image( $url, $args = array() ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return '';
	}
	$a    = wp_parse_args( $args, array( 'sizes' => '100vw', 'class' => '', 'alt' => '', 'eager' => false, 'extra' => '' ) );
	$id   = zv_image_id( $url );
	$alt  = $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';
	$alt  = '' !== trim( $alt ) ? $alt : $a['alt'];
	list( $src, $srcset ) = zv_image_set( $url );
	$dims = '';
	if ( $id ) {
		$meta = wp_get_attachment_metadata( $id );
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			$dims = ' width="' . (int) $meta['width'] . '" height="' . (int) $meta['height'] . '"';
		}
	}
	return '<img src="' . esc_url( $src ) . '"'
		. ( $srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $a['sizes'] ) . '"' : '' )
		. $dims
		. ' alt="' . esc_attr( $alt ) . '"'
		. ( $a['class'] ? ' class="' . esc_attr( $a['class'] ) . '"' : '' )
		. ( $a['eager'] ? ' fetchpriority="high"' : ' loading="lazy"' )
		. ' decoding="async"'
		. ( $id ? '' : ' onerror="this.remove()"' )
		. ( $a['extra'] ? ' ' . $a['extra'] : '' ) . '>';
}

/** Small version for admin lists. */
function zv_thumb_url( $url ) {
	$id = zv_image_id( $url );
	return $id ? ( wp_get_attachment_image_url( $id, 'thumbnail' ) ?: $url ) : $url;
}

// Common widths, used as "sizes" hints for the browser.
define( 'ZV_SIZES_CARD', '(max-width: 760px) calc(100vw - 32px), (max-width: 1040px) 50vw, 380px' );
define( 'ZV_SIZES_HALF', '(max-width: 1040px) 100vw, 50vw' );
define( 'ZV_SIZES_MAIN', '(max-width: 1040px) 100vw, 60vw' );

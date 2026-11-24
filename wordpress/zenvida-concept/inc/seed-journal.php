<?php
/**
 * Starter content: the 18 Respiro journal posts moved from zenvida.es (inc/data/journal.json).
 * Each post becomes normal WordPress blocks (paragraphs, headings, lists, images, buttons,
 * tables, questions), so the client edits them like any other post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Like zv_img(), but keeps the whole photo (no crop) for photos inside an article. */
function zv_img_full( $id ) {
	$map = get_option( 'zv_local_images' );
	if ( is_array( $map ) && ! empty( $map[ $id ] ) ) {
		return $map[ $id ];
	}
	if ( ! empty( $GLOBALS['zv_import_images'] ) && ( $local = zv_localize_image( $id ) ) ) {
		return $local;
	}
	return "https://static.wixstatic.com/media/$id/v1/fit/w_1600,h_1600,q_85,enc_auto/$id";
}

/** Old zenvida.es links inside the articles → the matching page of this website. */
function zv_journal_link( $href ) {
	if ( ! preg_match( '~^https?://(www\.)?zenvida\.es(/[^?#]*)?(.*)$~i', $href, $m ) ) {
		return $href; // other websites, e-mail links
	}
	$path = trim( $m[2] ?? '', '/' );
	if ( preg_match( '~^all-rituals/([a-z0-9-]+)$~', $path, $r ) && ( $p = get_page_by_path( $r[1], OBJECT, 'ritual' ) ) ) {
		return get_permalink( $p );
	}
	if ( in_array( $path, array( 'rituals', 'all-rituals', 'all-rituals-booking', 'rituals-and-prices', 'pricing', 'signature-rituals', 'spa-rituals' ), true ) ) {
		return zv_rituals_url();
	}
	$areas = array( 'feet-legs-care' => 'feet-legs', 'body-care' => 'massage', 'face-care' => 'face' );
	if ( isset( $areas[ $path ] ) && ! is_wp_error( $link = get_term_link( $areas[ $path ], 'area' ) ) ) {
		return $link;
	}
	if ( preg_match( '~^post/([a-z0-9-]+)$~', $path, $r ) ) {
		$path = $r[1];
	}
	foreach ( array( 'page', 'post' ) as $type ) {
		if ( $path && ( $p = get_page_by_path( $path, OBJECT, $type ) ) ) {
			return get_permalink( $p );
		}
	}
	return home_url( '/' . ( $path ? $path . '/' : '' ) );
}

function zv_journal_html( $html ) {
	return preg_replace_callback( '/href="([^"]+)"/', fn( $m ) => 'href="' . esc_url( zv_journal_link( html_entity_decode( $m[1] ) ) ) . '"', $html );
}

/** One raw block for serialize_blocks(). */
function zv_raw( $name, $attrs, $html, $inner = array(), $open = '', $close = '' ) {
	if ( $inner ) {
		return array(
			'blockName'    => $name,
			'attrs'        => $attrs,
			'innerBlocks'  => $inner,
			'innerHTML'    => $open . $close,
			'innerContent' => array_merge( array( $open ), array_fill( 0, count( $inner ), null ), array( $close ) ),
		);
	}
	return array( 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => $html, 'innerContent' => array( $html ) );
}

function zv_journal_blocks( $list ) {
	$out = array();
	foreach ( $list as $b ) {
		switch ( $b['t'] ) {
			case 'p':
				$out[] = zv_raw( 'core/paragraph', array(), '<p>' . zv_journal_html( $b['html'] ) . '</p>' );
				break;
			case 'h':
				$lvl   = (int) $b['level'];
				$out[] = zv_raw( 'core/heading', 2 === $lvl ? array() : array( 'level' => $lvl ), "<h$lvl class=\"wp-block-heading\">" . zv_journal_html( $b['html'] ) . "</h$lvl>" );
				break;
			case 'list':
				$tag   = $b['ordered'] ? 'ol' : 'ul';
				$items = array_map( fn( $i ) => zv_raw( 'core/list-item', array(), '<li>' . zv_journal_html( $i ) . '</li>' ), $b['items'] );
				$out[] = zv_raw( 'core/list', $b['ordered'] ? array( 'ordered' => true ) : array(), '', $items, "<$tag class=\"wp-block-list\">", "</$tag>" );
				break;
			case 'img':
				$cap   = $b['caption'] ? '<figcaption class="wp-element-caption">' . esc_html( $b['caption'] ) . '</figcaption>' : '';
				$out[] = zv_raw( 'core/image', array(), '<figure class="wp-block-image"><img src="' . esc_url( zv_img_full( $b['id'] ) ) . '" alt="' . esc_attr( $b['caption'] ) . '"/>' . $cap . '</figure>' );
				break;
			case 'hr':
				$out[] = zv_raw( 'core/separator', array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
				break;
			case 'button':
				$btn   = zv_raw( 'core/button', array(), '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( zv_journal_link( $b['href'] ) ) . '">' . esc_html( $b['text'] ) . '</a></div>' );
				$out[] = zv_raw( 'core/buttons', array(), '', array( $btn ), '<div class="wp-block-buttons">', '</div>' );
				break;
			case 'quote':
				$out[] = zv_raw( 'core/quote', array(), '', array( zv_raw( 'core/paragraph', array(), '<p>' . zv_journal_html( $b['html'] ) . '</p>' ) ), '<blockquote class="wp-block-quote">', '</blockquote>' );
				break;
			case 'table':
				$rows  = implode( '', array_map( fn( $r ) => '<tr>' . implode( '', array_map( fn( $c ) => '<td>' . zv_journal_html( $c ) . '</td>', $r ) ) . '</tr>', $b['rows'] ) );
				$out[] = zv_raw( 'core/table', array(), '<figure class="wp-block-table"><table><tbody>' . $rows . '</tbody></table></figure>' );
				break;
			case 'faq':
				foreach ( $b['items'] as $q ) {
					$answer = array_map( fn( $part ) => zv_raw( 'core/paragraph', array(), '<p>' . zv_journal_html( $part ) . '</p>' ), array_filter( explode( '<br>', $q[1] ) ) );
					$out[]  = zv_raw( 'core/details', array(), '', $answer ?: array( zv_raw( 'core/paragraph', array(), '<p></p>' ) ), '<details class="wp-block-details"><summary>' . esc_html( html_entity_decode( $q[0] ) ) . '</summary>', '</details>' );
				}
				break;
		}
	}
	return $out;
}

/** Creates the journal posts (skips any that already exist). */
function zv_seed_journal() {
	$file = __DIR__ . '/data/journal.json';
	$list = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore
	foreach ( array_reverse( (array) $list ) as $p ) {
		if ( get_page_by_path( $p['slug'], OBJECT, 'post' ) ) {
			continue;
		}
		$cat  = term_exists( $p['category'], 'category' ) ?: wp_insert_term( $p['category'], 'category' );
		$gmt  = gmdate( 'Y-m-d H:i:s', strtotime( $p['date'] ) ?: time() );
		$id   = wp_insert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => wp_slash( $p['title'] ),
			'post_name'     => $p['slug'],
			'post_excerpt'  => wp_slash( $p['excerpt'] ),
			'post_date'     => get_date_from_gmt( $gmt ),
			'post_date_gmt' => $gmt,
			'post_content'  => wp_slash( serialize_blocks( zv_journal_blocks( $p['blocks'] ) ) ),
			'post_category' => array( is_array( $cat ) ? (int) $cat['term_id'] : (int) $cat ),
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		$img = zv_img( $p['image'], 1200, 800 );
		update_post_meta( $id, 'zv_image', $img );
		$att = attachment_url_to_postid( $img );
		if ( $att ) {
			set_post_thumbnail( $id, $att );
			update_post_meta( $att, '_wp_attachment_image_alt', $p['title'] );
		}
	}
}

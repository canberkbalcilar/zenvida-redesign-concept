<?php
/**
 * Structured data (schema.org JSON-LD) for Google and AI search.
 *
 * One @graph per page, printed at the end of the page so it can describe what the page actually
 * showed: its FAQ, the Google reviews on it, its pricing cards. Everything comes from the theme's
 * own data (Site settings, rituals, prices, events, posts, Google reviews), so it updates itself.
 *
 * Every page:      WebSite + DaySpa (the studio) + WebPage + BreadcrumbList (not on the homepage)
 * Homepage:        + OfferCatalog (all rituals), FAQPage
 * /rituals/, area: CollectionPage + ItemList
 * Ritual:          ItemPage + Service (an Offer per duration, foot baths as add-ons) + FAQPage
 * Rituals & Prices + OfferCatalog · Offers & Packs + OfferCatalog (packs, memberships)
 * Gift Cards:      + Product · Group appointments: + Service · Contact: ContactPage
 * Events:          CollectionPage + ItemList · event: Event
 * Journal:         CollectionPage + ItemList · post: BlogPosting
 *
 * The studio carries the Google rating (aggregateRating) on every page, and the Google reviews a
 * page shows as Review. Yoast / Rank Math schema is switched off: the theme is the single source.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------- collectors */

/** Remember something a block showed (faq, review, pack, gift). */
function zv_sd_add( $type, $item ) {
	$GLOBALS['zv_sd'][ $type ][] = $item;
}

function zv_sd_get( $type ) {
	return $GLOBALS['zv_sd'][ $type ] ?? array();
}

// Blocks: read their attributes (with defaults) as they render.
add_filter( 'render_block', function ( $html, $block ) {
	$name = $block['blockName'] ?? '';
	if ( '' === trim( (string) $html ) || ! in_array( $name, array( 'zv/faq-item', 'zv/pack', 'zv/gift-builder' ), true ) ) {
		return $html;
	}
	$type = WP_Block_Type_Registry::get_instance()->get_registered( $name );
	$a    = $type ? $type->prepare_attributes_for_render( (array) ( $block['attrs'] ?? array() ) ) : (array) ( $block['attrs'] ?? array() );
	if ( 'zv/faq-item' === $name ) {
		zv_sd_add( 'faq', array( zv_sd_text( $a['question'] ?? '' ), zv_sd_text( $a['answer'] ?? '' ) ) );
	} elseif ( 'zv/pack' === $name ) {
		zv_sd_add( 'pack', $a );
	} else {
		zv_sd_add( 'gift', $a );
	}
	return $html;
}, 5, 2 );

/* ---------------------------------------------------------------- helpers */

/** Plain text in the visitor's language. */
function zv_sd_text( $s ) {
	$s = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES, 'UTF-8' ) ) );
	return '' === $s ? '' : trim( html_entity_decode( wp_strip_all_tags( (string) zv_tr( $s ) ), ENT_QUOTES, 'UTF-8' ) );
}

/** A URL of this site in the visitor's language (/es/… on the Spanish version). */
function zv_sd_url( $url ) {
	if ( 'es' !== zv_lang() || ! ( $trp = zv_trp() ) || 0 !== strpos( (string) $url, home_url() ) ) {
		return $url;
	}
	global $TRP_LANGUAGE;
	$conv = $trp->get_component( 'url_converter' );
	return $conv ? $conv->get_url_for_language( $TRP_LANGUAGE, $url, '' ) : $url;
}

/** The studio's @id (the same in both languages). */
function zv_sd_id( $frag ) {
	return home_url( '/' ) . '#' . $frag;
}

/** A price as a schema.org number string, or null ("€55 / month" → "55"). */
function zv_sd_price( $s ) {
	return preg_match( '/(\d+(?:[.,]\d{1,2})?)/', (string) $s, $m ) ? str_replace( ',', '.', $m[1] ) : null;
}

/** Canonical URL of the current page (before language). */
function zv_sd_current_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		return (int) get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink( get_queried_object() );
	}
	if ( is_post_type_archive() ) {
		return get_post_type_archive_link( get_query_var( 'post_type' ) ?: get_queried_object()->name );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$l = get_term_link( get_queried_object() );
		return is_wp_error( $l ) ? home_url( '/' ) : $l;
	}
	return home_url( add_query_arg( array() ) );
}

/** Opening hours from Site settings, days with the same hours grouped. */
function zv_sd_hours() {
	$days  = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	$fmt   = fn( $m ) => sprintf( '%02d:%02d', intdiv( $m % 1440, 60 ), $m % 60 );
	$group = array();
	foreach ( zv_hours() as $i => $h ) {
		if ( ! $h || array( 0, 0 ) === $h ) {
			continue; // closed
		}
		$group[ $fmt( $h[0] ) . '|' . $fmt( $h[1] ) ][] = $days[ $i ];
	}
	$out = array();
	foreach ( $group as $k => $d ) {
		list( $o, $c ) = explode( '|', $k );
		$out[]         = array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $d, 'opens' => $o, 'closes' => $c );
	}
	return $out;
}

/** Street, postcode and town from the Contact settings. */
function zv_sd_address() {
	$addr = array( '@type' => 'PostalAddress', 'streetAddress' => zv_opt( 'address1' ), 'addressLocality' => 'Madrid', 'addressRegion' => 'Comunidad de Madrid', 'addressCountry' => 'ES' );
	if ( preg_match( '/\b(\d{5})\s+([\p{L}][\p{L} ]*?)(?:\s*[·,|]|$)/u', zv_opt( 'address2' ), $m ) ) {
		$addr['postalCode']      = $m[1];
		$addr['addressLocality'] = trim( $m[2] );
	}
	return $addr;
}

/** Social profiles (Site settings → Contact) and the Google Maps listing. */
function zv_sd_same_as() {
	$urls = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) zv_opt( 'social' ) ) ) );
	$g    = function_exists( 'zv_g_data' ) ? ( zv_g_data()['maps'] ?? '' ) : '';
	if ( $g ) {
		$urls[] = $g;
	}
	return array_values( array_unique( array_filter( $urls, fn( $u ) => (bool) wp_http_validate_url( $u ) ) ) );
}

function zv_sd_logo() {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( $id && ( $src = wp_get_attachment_image_src( $id, 'full' ) ) ) {
		return array( '@type' => 'ImageObject', '@id' => zv_sd_id( 'logo' ), 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2] );
	}
	if ( get_site_icon_url() ) {
		return array( '@type' => 'ImageObject', '@id' => zv_sd_id( 'logo' ), 'url' => get_site_icon_url( 512 ), 'width' => 512, 'height' => 512 );
	}
	return array( '@type' => 'ImageObject', '@id' => zv_sd_id( 'logo' ), 'url' => get_template_directory_uri() . '/assets/brand/apple-touch-icon.png', 'width' => 180, 'height' => 180 );
}

/** The booking page ("Rituals & Prices"), or the rituals page. */
function zv_sd_booking_url() {
	$p = function_exists( 'zv_widget_on' ) && zv_widget_on() ? zv_booking_page() : null;
	return zv_sd_url( $p ? get_permalink( $p ) : zv_rituals_url() );
}

/** A ritual's priced durations as Offers. */
function zv_sd_ritual_offers( $r ) {
	$url    = zv_sd_url( $r['url'] );
	$offers = array();
	foreach ( $r['opts'] as $o ) {
		if ( null === $o[1] ) {
			continue;
		}
		$offers[] = array(
			'@type'         => 'Offer',
			'name'          => zv_sd_text( $r['name'] ) . ', ' . $o[0] . ' min',
			'price'         => (string) $o[1],
			'priceCurrency' => 'EUR',
			'availability'  => 'https://schema.org/InStock',
			'url'           => $url,
		);
	}
	return $offers;
}

/** A ritual as a Service. $full adds the description, image, booking channel and add-ons. */
function zv_sd_service( $r, $full = false ) {
	$url    = zv_sd_url( $r['url'] );
	$offers = zv_sd_ritual_offers( $r );
	$svc    = array(
		'@type'       => 'Service',
		'@id'         => $url . '#service',
		'name'        => zv_sd_text( $r['name'] ),
		'serviceType' => zv_sd_text( $r['areaName'] ?: 'Massage' ),
		'category'    => zv_sd_text( $r['areaName'] ) ?: null,
		'url'         => $url,
		'provider'    => array( '@id' => zv_sd_id( 'business' ) ),
		'areaServed'  => array( '@type' => 'City', 'name' => 'Madrid' ),
	);
	$svc = array_filter( $svc, fn( $v ) => null !== $v );
	if ( $r['desc'] ) {
		$svc['description'] = zv_sd_text( $r['desc'] );
	}
	if ( 1 === count( $offers ) ) {
		$svc['offers'] = $offers[0];
	} elseif ( $offers ) {
		$prices        = array_map( fn( $o ) => (float) $o['price'], $offers );
		$svc['offers'] = array(
			'@type'         => 'AggregateOffer',
			'lowPrice'      => (string) min( $prices ),
			'highPrice'     => (string) max( $prices ),
			'offerCount'    => count( $offers ),
			'priceCurrency' => 'EUR',
			'offers'        => $offers,
		);
	}
	if ( $full ) {
		$img = get_post_meta( $r['id'], 'zv_image', true );
		if ( $img ) {
			$svc['image'] = $img;
		}
		$svc['availableChannel'] = array(
			'@type'           => 'ServiceChannel',
			'serviceUrl'      => $url . '#book',
			'servicePhone'    => array( '@type' => 'ContactPoint', 'telephone' => zv_opt( 'phone' ) ),
			'serviceLocation' => array( '@id' => zv_sd_id( 'business' ) ),
		);
		if ( $r['baths'] && $offers ) {
			$add = array();
			foreach ( zv_baths() as $b ) {
				if ( null !== zv_sd_price( $b['price'] ?? '' ) ) {
					$add[] = array( '@type' => 'Offer', 'name' => zv_sd_text( $b['name'] ) . ' foot bath', 'description' => zv_sd_text( $b['text'] ?? '' ), 'price' => zv_sd_price( $b['price'] ), 'priceCurrency' => 'EUR' );
				}
			}
			if ( $add ) {
				if ( 'Offer' === $svc['offers']['@type'] ) {
					$svc['offers']['addOn'] = $add;
				} else {
					foreach ( $svc['offers']['offers'] as $i => $o ) {
						$svc['offers']['offers'][ $i ]['addOn'] = $add;
					}
				}
			}
		}
	}
	return $svc;
}

/** All rituals with prices, grouped by area. */
function zv_sd_catalog() {
	$groups = array();
	foreach ( zv_areas() as $slug => $a ) {
		$items = array();
		foreach ( zv_all_rituals() as $r ) {
			if ( $r['area'] !== $slug ) {
				continue;
			}
			foreach ( zv_sd_ritual_offers( $r ) as $o ) {
				$o['itemOffered'] = array( '@id' => zv_sd_url( $r['url'] ) . '#service', '@type' => 'Service', 'name' => zv_sd_text( $r['name'] ), 'url' => zv_sd_url( $r['url'] ) );
				$items[]          = $o;
			}
		}
		if ( $items ) {
			$groups[] = array( '@type' => 'OfferCatalog', 'name' => zv_sd_text( $a['name'] ), 'url' => zv_sd_url( $a['url'] ), 'itemListElement' => $items );
		}
	}
	return array(
		'@type'           => 'OfferCatalog',
		'@id'             => zv_sd_id( 'catalog' ),
		'name'            => zv__( 'Rituals & Prices' ),
		'itemListElement' => $groups,
	);
}

/** A list of links (summary page): [[url, name], …]. */
function zv_sd_list( $id, $rows ) {
	$items = array();
	foreach ( array_values( $rows ) as $i => $row ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => zv_sd_url( $row[0] ), 'name' => zv_sd_text( $row[1] ) );
	}
	return array( '@type' => 'ItemList', '@id' => $id, 'numberOfItems' => count( $items ), 'itemListElement' => $items );
}

/** Home › … › this page, as [[url, name], …] (empty on the homepage). */
function zv_sd_trail() {
	if ( is_front_page() || is_search() || is_404() ) {
		return array();
	}
	$home    = array( home_url( '/' ), zv__( 'Home' ) );
	$rituals = array( zv_rituals_url(), zv__( 'Rituals' ) );
	$journal = (int) get_option( 'page_for_posts' ) ? array( get_permalink( (int) get_option( 'page_for_posts' ) ), get_the_title( (int) get_option( 'page_for_posts' ) ) ) : null;
	$events  = array( get_post_type_archive_link( 'zv_event' ), zv__( 'Events' ) );
	$here    = array( zv_sd_current_url(), is_singular() ? get_the_title( get_queried_object() ) : '' );

	if ( is_singular( 'ritual' ) ) {
		$t = get_the_terms( get_queried_object(), 'area' );
		$a = ( $t && ! is_wp_error( $t ) ) ? array( get_term_link( $t[0] ), $t[0]->name ) : null;
		return array_values( array_filter( array( $home, $rituals, $a, $here ) ) );
	}
	if ( is_tax( 'area' ) ) {
		return array( $home, $rituals, array( zv_sd_current_url(), single_term_title( '', false ) ) );
	}
	if ( is_post_type_archive( 'ritual' ) ) {
		return array( $home, $rituals );
	}
	if ( is_singular( 'zv_event' ) ) {
		return array( $home, $events, $here );
	}
	if ( is_post_type_archive( 'zv_event' ) ) {
		return array( $home, $events );
	}
	if ( is_home() ) {
		return array_values( array_filter( array( $home, $journal ) ) );
	}
	if ( is_singular( 'post' ) ) {
		return array_values( array_filter( array( $home, $journal, $here ) ) );
	}
	if ( is_category() || is_tag() ) {
		return array_values( array_filter( array( $home, $journal, array( zv_sd_current_url(), single_term_title( '', false ) ) ) ) );
	}
	if ( is_page() ) {
		$trail = array( $home );
		foreach ( array_reverse( get_post_ancestors( get_queried_object() ) ) as $pid ) {
			$trail[] = array( get_permalink( $pid ), get_the_title( $pid ) );
		}
		$trail[] = $here;
		return $trail;
	}
	return array( $home, $here );
}

function zv_sd_breadcrumb( $id ) {
	$items = array();
	foreach ( zv_sd_trail() as $i => $row ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => zv_sd_text( $row[1] ), 'item' => zv_sd_url( $row[0] ) );
	}
	return count( $items ) > 1 ? array( '@type' => 'BreadcrumbList', '@id' => $id, 'itemListElement' => $items ) : null;
}

/* ------------------------------------------------------------------ graph */

/** The studio: name, address, hours, contact, rating, reviews shown on this page. */
function zv_sd_business( $with_catalog ) {
	$lang  = 'es' === zv_lang() ? 'es' : 'en';
	$wa    = preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
	$biz   = array(
		'@type'               => 'DaySpa',
		'@id'                 => zv_sd_id( 'business' ),
		'name'                => 'ZenVida',
		'alternateName'       => 'ZenVida Foot & Body',
		'legalName'           => 'ZenVida Foot & Body S.L.',
		'url'                 => zv_sd_url( home_url( '/' ) ),
		'logo'                => zv_sd_logo(),
		'image'               => array( zv_img( 'b84849_1f76460dcaab4a9d9b293ecf6b206bf4~mv2.jpg', 1600, 1000 ), zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1600, 1000 ) ),
		'description'         => zv_sd_text( zv_opt( 'seo_home_desc' ) ?: zv_opt( 'footer_about' ) ),
		'telephone'           => zv_opt( 'phone' ),
		'email'               => zv_opt( 'email' ),
		'address'             => zv_sd_address(),
		'hasMap'              => zv_opt( 'maps_url' ),
		'openingHoursSpecification' => zv_sd_hours(),
		'currenciesAccepted'  => 'EUR',
		'knowsLanguage'       => array( 'en', 'es' ),
		'areaServed'          => array( '@type' => 'City', 'name' => 'Madrid' ),
		'contactPoint'        => array(
			array( '@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => zv_opt( 'phone' ), 'email' => zv_opt( 'email' ), 'availableLanguage' => array( 'English', 'Spanish' ) ),
		),
		'potentialAction'     => array(
			'@type'  => 'ReserveAction',
			'target' => array( '@type' => 'EntryPoint', 'urlTemplate' => zv_sd_booking_url(), 'inLanguage' => $lang, 'actionPlatform' => array( 'https://schema.org/DesktopWebPlatform', 'https://schema.org/MobileWebPlatform' ) ),
			'result' => array( '@type' => 'Reservation', 'name' => zv__( 'Book a ritual' ) ),
		),
	);
	if ( $wa ) {
		$biz['contactPoint'][] = array( '@type' => 'ContactPoint', 'contactType' => 'reservations', 'url' => 'https://wa.me/' . $wa, 'availableLanguage' => array( 'English', 'Spanish' ) );
	}
	$geo = array_map( 'trim', explode( ',', (string) zv_opt( 'geo' ) ) );
	if ( 2 === count( $geo ) && is_numeric( $geo[0] ) && is_numeric( $geo[1] ) ) {
		$biz['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $geo[0], 'longitude' => (float) $geo[1] );
	}
	$prices = array();
	foreach ( zv_all_rituals() as $r ) {
		foreach ( $r['opts'] as $o ) {
			if ( null !== $o[1] ) {
				$prices[] = (int) $o[1];
			}
		}
	}
	if ( $prices ) {
		$biz['priceRange'] = '€' . min( $prices ) . '–€' . max( $prices );
	}
	if ( $same = zv_sd_same_as() ) {
		$biz['sameAs'] = $same;
	}
	$g = function_exists( 'zv_g_data' ) ? zv_g_data() : null;
	if ( $g && ! empty( $g['count'] ) && (float) $g['rating'] > 0 ) {
		$biz['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => number_format( (float) $g['rating'], 1, '.', '' ), 'reviewCount' => (int) $g['count'], 'bestRating' => 5, 'worstRating' => 1 );
	}
	$seen = array();
	foreach ( zv_sd_get( 'review' ) as $rv ) {
		if ( isset( $seen[ $rv['id'] ] ) ) {
			continue;
		}
		$seen[ $rv['id'] ] = true;
		$biz['review'][]   = array(
			'@type'         => 'Review',
			'author'        => array( '@type' => 'Person', 'name' => $rv['name'] ),
			'datePublished' => gmdate( 'Y-m-d', $rv['time'] ),
			'reviewBody'    => $rv['text'],
			'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => (int) $rv['stars'], 'bestRating' => 5, 'worstRating' => 1 ),
			'publisher'     => array( '@type' => 'Organization', 'name' => 'Google' ),
		);
	}
	if ( $with_catalog ) {
		$biz['hasOfferCatalog'] = array( '@id' => zv_sd_id( 'catalog' ) );
	}
	return array_filter( $biz, fn( $v ) => '' !== $v && array() !== $v && null !== $v );
}

/** The whole @graph for the current page (empty on search, 404 and previews). */
function zv_sd_graph() {
	if ( is_search() || is_404() || is_feed() || is_preview() || is_attachment() ) {
		return array();
	}
	$lang = 'es' === zv_lang() ? 'es' : 'en';
	$url  = zv_sd_url( zv_sd_current_url() );
	$obj  = get_queried_object();
	$slug = is_page() ? get_post_field( 'post_name', $obj ) : '';
	$web  = array( '@id' => zv_sd_id( 'website' ) );
	$biz  = array( '@id' => zv_sd_id( 'business' ) );

	$graph   = array();
	$catalog = is_front_page() || is_post_type_archive( 'ritual' ) || 'rituals-and-prices' === $slug;

	$graph[] = array(
		'@type'         => 'WebSite',
		'@id'           => zv_sd_id( 'website' ),
		'url'           => zv_sd_url( home_url( '/' ) ),
		'name'          => 'ZenVida',
		'alternateName' => 'ZenVida Foot & Body',
		'inLanguage'    => $lang,
		'publisher'     => $biz,
	);
	$graph[] = zv_sd_business( $catalog );

	// The page itself.
	$type = 'WebPage';
	if ( is_post_type_archive() || is_tax() || is_category() || is_tag() || is_home() ) {
		$type = 'CollectionPage';
	} elseif ( is_singular( 'ritual' ) ) {
		$type = 'ItemPage';
	} elseif ( 'contact' === $slug ) {
		$type = 'ContactPage';
	} elseif ( in_array( $slug, array( 'about', 'about-us', 'sobre-nosotros' ), true ) ) {
		$type = 'AboutPage';
	}
	$page = array(
		'@type'      => $type,
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => '',
		'isPartOf'   => $web,
		'inLanguage' => $lang,
	);
	// Name: the page's own name in the visitor's language (SEO titles are not translated on the free TranslatePress).
	$trail        = zv_sd_trail();
	$page['name'] = is_front_page() ? zv_sd_text( zv_opt( 'seo_home' ) ) : ( $trail ? zv_sd_text( end( $trail )[1] ) : '' );
	if ( '' === $page['name'] ) {
		$page['name'] = zv_sd_text( wp_get_document_title() );
	}
	if ( is_front_page() || 'ContactPage' === $type || 'AboutPage' === $type ) {
		$page['about'] = $biz;
	}
	$desc = '';
	if ( is_front_page() ) {
		$desc = zv_opt( 'seo_home_desc' );
	} elseif ( is_post_type_archive( 'ritual' ) ) {
		$desc = zv_opt( 'seo_rituals_desc' );
	} elseif ( is_tax( 'area' ) ) {
		$desc = term_description();
	} elseif ( is_singular( 'ritual' ) ) {
		$desc = get_post_meta( $obj->ID, 'zv_desc', true );
	} elseif ( is_singular() && has_excerpt( $obj ) ) {
		$desc = get_the_excerpt( $obj );
	}
	if ( $desc = zv_sd_text( $desc ) ) {
		$page['description'] = $desc;
	}
	if ( is_singular() ) {
		$page['datePublished'] = get_post_time( 'c', true, $obj );
		$page['dateModified']  = get_post_modified_time( 'c', true, $obj );
		$img                   = is_singular( 'ritual' ) ? get_post_meta( $obj->ID, 'zv_image', true ) : ( is_singular( 'post' ) ? zv_post_image( $obj->ID ) : get_the_post_thumbnail_url( $obj, 'large' ) );
		if ( $img ) {
			$page['primaryImageOfPage'] = array( '@type' => 'ImageObject', 'url' => $img );
		}
	}
	$crumb = zv_sd_breadcrumb( $url . '#breadcrumb' );
	if ( $crumb ) {
		$page['breadcrumb'] = array( '@id' => $crumb['@id'] );
	}

	// What the page is about.
	$main = null;
	if ( is_singular( 'ritual' ) ) {
		$main = zv_sd_service( zv_ritual_data( $obj ), true );
	} elseif ( is_post_type_archive( 'ritual' ) || is_tax( 'area' ) ) {
		$area = is_tax( 'area' ) ? $obj->slug : '';
		$rows = array();
		foreach ( zv_all_rituals() as $r ) {
			if ( ! $area || $r['area'] === $area ) {
				$rows[] = array( $r['url'], $r['name'] );
			}
		}
		$main = zv_sd_list( $url . '#list', $rows );
	} elseif ( is_post_type_archive( 'zv_event' ) ) {
		$rows = array();
		foreach ( get_posts( array( 'post_type' => 'zv_event', 'numberposts' => 30, 'post__not_in' => zv_hidden_ids() ) ) as $p ) {
			if ( 'past' !== zv_event_state( zv_event( $p->ID ) ) ) {
				$rows[] = array( get_permalink( $p ), zv_event_name( $p ) );
			}
		}
		$main = $rows ? zv_sd_list( $url . '#list', $rows ) : null;
	} elseif ( is_singular( 'zv_event' ) ) {
		$main = function_exists( 'zv_event_schema' ) ? zv_event_schema( $obj ) : null;
	} elseif ( is_home() || is_category() || is_tag() ) {
		global $wp_query;
		$rows = array_map( fn( $p ) => array( get_permalink( $p ), get_the_title( $p ) ), (array) $wp_query->posts );
		$main = $rows ? zv_sd_list( $url . '#list', $rows ) : null;
	} elseif ( is_singular( 'post' ) ) {
		$cat  = get_the_category( $obj->ID );
		$img  = zv_post_image( $obj->ID );
		$main = array_filter( array(
			'@type'            => 'BlogPosting',
			'@id'              => $url . '#article',
			'headline'         => zv_sd_text( get_the_title( $obj ) ),
			'description'      => zv_sd_text( has_excerpt( $obj ) ? get_the_excerpt( $obj ) : wp_trim_words( $obj->post_content, 30 ) ),
			'image'            => $img ?: null,
			'datePublished'    => get_post_time( 'c', true, $obj ),
			'dateModified'     => get_post_modified_time( 'c', true, $obj ),
			'author'           => $biz,
			'publisher'        => $biz,
			'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			'articleSection'   => $cat ? zv_sd_text( $cat[0]->name ) : null,
			'inLanguage'       => $lang,
			'wordCount'        => str_word_count( wp_strip_all_tags( $obj->post_content ) ),
		) );
	} elseif ( 'rituals-and-prices' === $slug ) {
		$main = array( '@id' => zv_sd_id( 'catalog' ) );
	} elseif ( 'group-appointments' === $slug ) {
		$main = array(
			'@type'            => 'Service',
			'@id'              => $url . '#service',
			'name'             => zv_sd_text( get_the_title( $obj ) ),
			'serviceType'      => 'Group massage',
			'url'              => $url,
			'provider'         => $biz,
			'areaServed'       => array( '@type' => 'City', 'name' => 'Madrid' ),
			'availableChannel' => array( '@type' => 'ServiceChannel', 'serviceUrl' => $url, 'servicePhone' => array( '@type' => 'ContactPoint', 'telephone' => zv_opt( 'phone' ) ), 'serviceLocation' => $biz ),
		);
		if ( $desc ) {
			$main['description'] = $desc;
		}
	} elseif ( zv_sd_get( 'pack' ) ) { // Offers & Packs
		$items = array();
		foreach ( zv_sd_get( 'pack' ) as $a ) {
			$price = zv_sd_price( $a['price'] ?? '' );
			if ( null === $price ) {
				continue;
			}
			$name  = implode( ': ', array_filter( array( zv_sd_text( $a['kicker'] ?? '' ), zv_sd_text( $a['title'] ?? '' ) ) ) );
			$offer = array( '@type' => 'Offer', 'name' => $name, 'price' => $price, 'priceCurrency' => 'EUR', 'url' => $url, 'availability' => 'https://schema.org/InStock' );
			$list  = array_filter( array_map( 'zv_sd_text', explode( "\n", (string) ( $a['items'] ?? '' ) ) ) );
			if ( $list ) {
				$offer['description'] = implode( '. ', $list );
			}
			if ( preg_match( '#/\s*(month|mes)#iu', (string) $a['price'] ) ) {
				$offer['priceSpecification'] = array( '@type' => 'UnitPriceSpecification', 'price' => $price, 'priceCurrency' => 'EUR', 'billingDuration' => 'P1M', 'unitText' => 'month', 'referenceQuantity' => array( '@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'MON' ) );
			}
			$offer['itemOffered'] = array( '@type' => 'Service', 'name' => $name, 'provider' => $biz );
			$items[]              = $offer;
		}
		$main = $items ? array( '@type' => 'OfferCatalog', '@id' => $url . '#offers', 'name' => zv_sd_text( get_the_title( $obj ) ), 'itemListElement' => $items ) : null;
	}
	if ( zv_sd_get( 'gift' ) ) { // Gift Cards
		$a       = zv_sd_get( 'gift' )[0];
		$amounts = array_values( array_filter( array_map( 'absint', explode( ',', (string) $a['amounts'] ) ) ) );
		if ( $amounts ) {
			$product = array(
				'@type'       => 'Product',
				'@id'         => $url . '#giftcard',
				'name'        => zv__( 'ZenVida gift card' ),
				'description' => zv_sd_text( $desc ?: ( $a['note'] ?? '' ) ),
				'brand'       => array( '@type' => 'Brand', 'name' => 'ZenVida' ),
				'image'       => get_the_post_thumbnail_url( $obj, 'large' ) ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 1200 ),
				'url'         => $url,
				'offers'      => array( '@type' => 'AggregateOffer', 'lowPrice' => (string) min( $amounts ), 'highPrice' => (string) max( $amounts ), 'offerCount' => count( $amounts ), 'priceCurrency' => 'EUR', 'availability' => 'https://schema.org/InStock', 'seller' => $biz ),
			);
			$main = $main ? $main : $product;
			if ( $main !== $product ) {
				$graph[] = $product;
			}
		}
	}

	if ( $main ) {
		if ( isset( $main['@id'] ) ) {
			$page['mainEntity'] = array( '@id' => $main['@id'] );
		}
		if ( isset( $main['@type'] ) ) {
			$graph[] = $main;
		}
	}
	$graph[] = $page;
	if ( $crumb ) {
		$graph[] = $crumb;
	}
	if ( $catalog ) {
		$graph[] = zv_sd_catalog();
	}

	// FAQ shown on the page.
	$faq = array();
	foreach ( zv_sd_get( 'faq' ) as $q ) {
		if ( $q[0] && $q[1] && ! isset( $faq[ $q[0] ] ) ) {
			$faq[ $q[0] ] = array( '@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ) );
		}
	}
	if ( $faq ) {
		$graph[] = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'url' => $url, 'isPartOf' => $web, 'inLanguage' => $lang, 'mainEntity' => array_values( $faq ) );
	}
	return apply_filters( 'zv_schema_graph', $graph );
}

// Printed at the end of the page, after the blocks have run.
add_action( 'wp_footer', function () {
	$graph = zv_sd_graph();
	if ( ! $graph ) {
		return;
	}
	echo "\n" . '<script type="application/ld+json" class="zv-schema" data-no-translation>' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";
}, 1 );

/* -------------------------------------------- SEO plugins: one source of schema */

// Rank Math: keep its titles, descriptions, Open Graph and sitemap; drop its schema.
add_filter( 'rank_math/json_ld', fn() => array(), 999 );
// Yoast SEO, if it is ever used instead.
add_filter( 'wpseo_json_ld_output', '__return_false' );
// Rank Math sitemap: no switched-off rituals or events, no helper page that redirects to /rituals/.
add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $post ) {
	if ( 'post' === $type && is_object( $post ) && isset( $post->ID ) && ( in_array( (int) $post->ID, zv_hidden_ids(), true ) || (int) $post->ID === (int) zv_opt( 'rituals_page' ) ) ) {
		return false;
	}
	return $url;
}, 10, 3 );

<?php
/**
 * Spanish version, with the TranslatePress plugin (free).
 *
 * - Pages, rituals, posts, menus: TranslatePress translates them; the client edits a Spanish
 *   text by clicking it on the page ("Translate Site" in the top bar).
 * - Ready-made Spanish for all starter content is in inc/data/es.json and is loaded into
 *   TranslatePress automatically (or with the "Install Spanish translations" button).
 * - Texts written by the theme's scripts (booking card, quiz, opening hours, forms) come from
 *   inc/data/es-ui.json. Content passed to those scripts (ritual names, quiz questions…) is
 *   looked up in the TranslatePress dictionary, so the client's own edits are used there too.
 * Without TranslatePress everything stays in English and nothing here does anything.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ZV_ES = 'es_ES';

function zv_trp() {
	return class_exists( 'TRP_Translate_Press' ) ? TRP_Translate_Press::get_trp_instance() : null;
}

/** 'es' on the Spanish version of the site, otherwise 'en'. */
function zv_lang() {
	if ( wp_doing_ajax() && isset( $_REQUEST['zv_lang'] ) ) { // phpcs:ignore -- language of the page that sent the form
		return 'es' === $_REQUEST['zv_lang'] ? 'es' : 'en'; // phpcs:ignore
	}
	global $TRP_LANGUAGE;
	$s = get_option( 'trp_settings' );
	if ( ! empty( $TRP_LANGUAGE ) && is_array( $s ) && $TRP_LANGUAGE !== ( $s['default-language'] ?? 'en_US' ) ) {
		return 0 === strpos( $TRP_LANGUAGE, 'es' ) ? 'es' : 'en';
	}
	return 'en';
}

/* ------------------------------------------------------------------ UI texts */

function zv_ui_strings() {
	static $map = null;
	if ( null === $map ) {
		$f   = __DIR__ . '/data/es-ui.json';
		$map = file_exists( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array(); // phpcs:ignore
	}
	return $map;
}

/** A theme text in the visitor's language. {name} placeholders are filled from $vars. */
function zv__( $en, $vars = array() ) {
	$t = ( 'es' === zv_lang() && ! empty( zv_ui_strings()[ $en ] ) ) ? zv_ui_strings()[ $en ] : $en;
	foreach ( $vars as $k => $v ) {
		$t = str_replace( '{' . $k . '}', (string) $v, $t );
	}
	return $t;
}

/* ------------------------------------------------- content passed to scripts */

function zv_tr_norm( $s ) {
	$s = html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$s = str_replace( array( "\u{2019}", "\u{2018}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}", "\u{2026}", "\u{00A0}" ), array( "'", "'", '"', '"', '-', '-', '...', ' ' ), $s );
	return trim( preg_replace( '/\s+/u', ' ', $s ) );
}

/** The Spanish dictionary of TranslatePress (translated strings only), by normalised English text. */
function zv_tr_dictionary() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$map = array();
	$trp = zv_trp();
	if ( ! $trp ) {
		return $map;
	}
	global $wpdb;
	$table = $trp->get_component( 'query' )->get_table_name( ZV_ES );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return $map;
	}
	foreach ( (array) $wpdb->get_results( "SELECT original, translated FROM `$table` WHERE status > 0 AND translated <> ''", ARRAY_N ) as $r ) { // phpcs:ignore
		$map[ zv_tr_norm( $r[0] ) ] = html_entity_decode( wp_strip_all_tags( $r[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	return $map;
}

/** Plain-text content in the visitor's language (English if there is no translation yet). */
function zv_tr( $s ) {
	if ( 'es' !== zv_lang() || '' === trim( (string) $s ) ) {
		return $s;
	}
	return zv_tr_dictionary()[ zv_tr_norm( $s ) ] ?? $s;
}

/** zv_tr() on some fields of one row. */
function zv_tr_row( $row, $fields ) {
	foreach ( $fields as $f ) {
		if ( isset( $row[ $f ] ) && is_string( $row[ $f ] ) ) {
			$row[ $f ] = zv_tr( $row[ $f ] );
		}
	}
	return $row;
}

/** zv_tr() on some fields of every row of a list (keys are kept). */
function zv_tr_rows( $rows, $fields ) {
	if ( 'es' !== zv_lang() ) {
		return $rows;
	}
	foreach ( $rows as $k => $row ) {
		$rows[ $k ] = is_array( $row ) ? zv_tr_row( $row, $fields ) : zv_tr( $row );
	}
	return $rows;
}

/** The rituals page data (window.ZV) in the visitor's language. */
function zv_tr_archive( $zv ) {
	if ( 'es' !== zv_lang() ) {
		return $zv;
	}
	$zv['areas']   = zv_tr_rows( $zv['areas'], array( 'name', 'intro', 'long' ) );
	$zv['needs']   = zv_tr_rows( $zv['needs'], array() );
	$zv['rituals'] = zv_tr_rows( $zv['rituals'], array( 'name', 'areaName', 'desc' ) );
	foreach ( $zv['quiz'] as $i => $q ) {
		$zv['quiz'][ $i ]['q'] = zv_tr( $q['q'] );
		foreach ( $q['o'] as $j => $o ) {
			$zv['quiz'][ $i ]['o'][ $j ][1] = zv_tr( $o[1] );
			$zv['quiz'][ $i ]['o'][ $j ][2] = zv_tr( $o[2] );
		}
	}
	return $zv;
}

// Browser titles: TranslatePress (free) does not translate them, so the theme does.
add_filter( 'document_title_parts', function ( $parts ) {
	if ( 'es' === zv_lang() && isset( $parts['title'] ) ) {
		$parts['title'] = zv_tr( $parts['title'] );
	}
	return $parts;
}, 40 );

// Scripts write these parts of the page themselves, already in the right language.
add_filter( 'trp_skip_selectors_from_dynamic_translation', function ( $sel ) {
	return array_merge( (array) $sel, array( '#book', '#quiz', '#results', '#zvListing', '#lgrid', '[data-bodymap]', '[data-open-now]', '[data-hours-table]', '[data-hours-today]', '[data-qb-when]', '.zform', '.zf-done', '#toast', '.read-more', '.story .more' ) );
} );

/* --------------------------------------------------------- language switch */

/** EN / ES links in the header (only when TranslatePress has the Spanish version). */
function zv_language_switch( $class = 'lang' ) {
	if ( ! function_exists( 'trp_custom_language_switcher' ) ) {
		return;
	}
	$langs = trp_custom_language_switcher();
	if ( count( $langs ) < 2 ) {
		return;
	}
	echo '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( zv__( 'Language' ) ) . '" data-no-translation>';
	foreach ( $langs as $code => $l ) {
		$short = strtoupper( substr( $code, 0, 2 ) );
		$on    = 0 === strpos( $code, 'es' ) ? 'es' === zv_lang() : 'en' === zv_lang();
		echo '<a href="' . esc_url( $l['current_page_url'] ) . '" hreflang="' . esc_attr( substr( $code, 0, 2 ) ) . '" lang="' . esc_attr( substr( $code, 0, 2 ) ) . '"' . ( $on ? ' aria-current="true"' : '' ) . ' title="' . esc_attr( $l['language_name'] ?? $short ) . '">' . esc_html( $short ) . '</a>';
	}
	echo '</nav>';
}

/* ------------------------------------------------------------------ install */

/** Is TranslatePress set up with Spanish? */
function zv_es_ready() {
	$s = get_option( 'trp_settings' );
	return zv_trp() && is_array( $s ) && in_array( ZV_ES, (array) ( $s['translation-languages'] ?? array() ), true );
}

/** Adds Spanish to TranslatePress (address /es/, English stays the main language). */
function zv_es_setup_plugin() {
	$trp = zv_trp();
	if ( ! $trp ) {
		return false;
	}
	$s = (array) get_option( 'trp_settings', array() );
	if ( ! in_array( ZV_ES, (array) ( $s['translation-languages'] ?? array() ), true ) ) {
		$def                        = $s['default-language'] ?? 'en_US';
		$s['translation-languages'] = array_values( array_unique( array_merge( (array) ( $s['translation-languages'] ?? array( $def ) ), array( ZV_ES ) ) ) );
		$s['publish-languages']     = array_values( array_unique( array_merge( (array) ( $s['publish-languages'] ?? array( $def ) ), array( ZV_ES ) ) ) );
		$s['url-slugs']             = array_merge( array_filter( (array) ( $s['url-slugs'] ?? array() ) ), array( $def => substr( $def, 0, 2 ), ZV_ES => 'es' ) );
		$s['trp-ls-floater']        = 'no'; // the theme has its own EN / ES switch
	}
	// On SQLite (the online demo) TranslatePress fails to store WordPress system texts and runs out of
	// memory; those texts come from WordPress's own Spanish files anyway, so this part is switched off there.
	if ( class_exists( 'WP_SQLite_DB' ) || ( defined( 'DB_ENGINE' ) && 'sqlite' === DB_ENGINE ) ) {
		$adv = (array) get_option( 'trp_advanced_settings', array() );
		$adv['disable_translation_for_gettext_strings'] = 'yes';
		update_option( 'trp_advanced_settings', $adv );
	}
	$ls = get_option( 'trp_language_switcher_settings' );
	if ( is_array( $ls ) && isset( $ls['floater'] ) ) {
		$ls['floater']['enabled'] = false;
		update_option( 'trp_language_switcher_settings', $ls );
	}
	update_option( 'trp_settings', $trp->get_component( 'settings' )->sanitize_settings( $s ) );
	return true;
}

/**
 * Loads the ready-made Spanish texts (inc/data/es.json) into TranslatePress.
 * Texts the client already changed are kept.
 */
function zv_es_import() {
	if ( ! zv_es_setup_plugin() ) {
		return 0;
	}
	$f   = __DIR__ . '/data/es.json';
	$map = file_exists( $f ) ? (array) json_decode( (string) file_get_contents( $f ), true ) : array(); // phpcs:ignore
	if ( ! $map ) {
		return 0;
	}
	// "Book <trp-post-container data-trp-post-id='6'>Name</…>": the id is the ritual's id on this site.
	$fixed = array();
	foreach ( $map as $en => $es ) {
		if ( false !== strpos( $en, 'data-trp-post-id' ) && preg_match( "~<trp-post-container data-trp-post-id='\d+'>(.*?)</trp-post-container>~", $en, $m ) ) {
			$p = get_posts( array( 'post_type' => 'ritual', 'title' => html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ), 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
			if ( ! $p ) {
				continue;
			}
			$en = preg_replace( "~data-trp-post-id='\d+'~", "data-trp-post-id='{$p[0]}'", $en );
			$es = preg_replace( "~data-trp-post-id='\d+'~", "data-trp-post-id='{$p[0]}'", $es );
		}
		$fixed[ $en ] = $es;
	}
	$q     = zv_trp()->get_component( 'query' );
	$count = 0;
	foreach ( array_chunk( array_keys( $fixed ), 150 ) as $chunk ) {
		$ids = (array) $q->get_string_ids( $chunk, ZV_ES );
		$new = array_values( array_diff( $chunk, array_keys( $ids ) ) );
		if ( $new ) {
			$q->insert_strings( $new, ZV_ES );
			$ids = (array) $q->get_string_ids( $chunk, ZV_ES );
		}
		$have = (array) $q->get_existing_translations( $chunk, ZV_ES ); // only rows that already have a translation
		$rows = array();
		foreach ( $ids as $orig => $row ) {
			if ( ! empty( $have[ $orig ]->translated ) || ! isset( $fixed[ $orig ] ) ) {
				continue; // keep what is already translated (maybe by the client)
			}
			$rows[] = array( 'id' => (int) $row->id, 'translated' => $fixed[ $orig ], 'status' => 2 );
		}
		if ( $rows ) {
			$q->update_strings( $rows, ZV_ES, array( 'id', 'translated', 'status' ) );
			$count += count( $rows );
		}
	}
	update_option( 'zv_es_imported', ZV_VERSION );
	return $count;
}

add_action( 'admin_post_zv_es_import', function () {
	check_admin_referer( 'zv_es_import' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$n = zv_es_import();
	wp_safe_redirect( add_query_arg( 'zv_es', $n, admin_url( 'index.php' ) ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_GET['zv_es'] ) ) { // phpcs:ignore
		$n = (int) $_GET['zv_es']; // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p><b>Spanish version ready.</b> ' . ( $n ? $n . ' texts were added.' : 'All Spanish texts were already in place; nothing was overwritten.' ) . ' Visit the site and use <b>ES</b> at the top to see it. To change a Spanish text, click <b>Translate Site</b> in the black bar on any page.</p></div>';
		return;
	}
	if ( zv_trp() && version_compare( (string) get_option( 'zv_es_imported', '0' ), ZV_VERSION, '<' ) && get_option( 'zv_seeded' ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=zv_es_import' ), 'zv_es_import' );
		echo '<div class="notice notice-info"><p><b>Spanish translations are ready for this theme.</b> They fill in the Spanish version of all pages, rituals and journal posts. Texts you have already translated are kept. <a class="button button-primary" style="margin-left:8px" href="' . esc_url( $url ) . '">Install Spanish translations</a></p></div>';
	} elseif ( ! zv_trp() && 'index.php' === ( $GLOBALS['pagenow'] ?? '' ) ) {
		echo '<div class="notice notice-info is-dismissible"><p><b>Spanish version:</b> install the free plugin <b>TranslatePress</b> (Plugins → Add New → search "TranslatePress"). The theme then adds the ready-made Spanish texts for you. <a href="' . esc_url( admin_url( 'plugin-install.php?s=translatepress&tab=search&type=term' ) ) . '">Find the plugin</a></p></div>';
	}
} );

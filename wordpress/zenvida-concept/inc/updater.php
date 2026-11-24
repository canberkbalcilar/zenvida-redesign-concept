<?php
/**
 * Theme updates from GitHub Pages.
 *
 * style.css has an "Update URI" header, so WordPress asks this theme (not
 * wordpress.org) whether there is a new version. The theme reads update.json
 * next to the theme zip; when its version is newer, WordPress shows
 * "Update available" under Dashboard → Updates and Appearance → Themes and
 * installs the zip like any other theme update. Content, prices, photos and
 * settings live in the database and are not touched by an update.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Address of update.json (filterable for testing). */
function zv_update_url() {
	return (string) apply_filters( 'zv_update_url', ZV_CONCEPTS_URL . 'wordpress/update.json' );
}

/** Latest release info from update.json, cached for 15 minutes. False when it can't be read. */
function zv_update_info( $fresh = false ) {
	$info = get_site_transient( 'zv_update_info' );
	if ( ! $fresh && is_array( $info ) ) {
		return $info['version'] ? $info : false;
	}
	$res  = wp_remote_get( add_query_arg( 't', time(), zv_update_url() ), array( 'timeout' => 10 ) );
	$data = is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );

	// Only accept a zip from the same address as update.json.
	$src = wp_parse_url( zv_update_url() );
	$pkg = is_array( $data ) && ! empty( $data['package'] ) ? wp_parse_url( (string) $data['package'] ) : array();
	$ok  = is_array( $data ) && ! empty( $data['version'] ) && preg_match( '/^\d+(\.\d+)*$/', (string) $data['version'] )
		&& ! empty( $pkg['host'] ) && $pkg['host'] === ( $src['host'] ?? '' )
		&& ( $pkg['scheme'] ?? '' ) === ( $src['scheme'] ?? '' ) && ( $pkg['port'] ?? '' ) === ( $src['port'] ?? '' );

	$info = $ok ? $data : array( 'version' => '' ); // remember a failed check too, so pages don't wait on it
	set_site_transient( 'zv_update_info', $info, 15 * MINUTE_IN_SECONDS );
	return $ok ? $info : false;
}

// WordPress asks "update_themes_{host of Update URI}" during its regular update check.
add_filter( 'update_themes_' . wp_parse_url( ZV_CONCEPTS_URL, PHP_URL_HOST ), function ( $update, $theme_data, $stylesheet ) {
	if ( 'ZenVida Concept' !== ( $theme_data['Name'] ?? '' ) ) {
		return $update;
	}
	// "Check again" on Dashboard → Updates always reads the newest file.
	$info = zv_update_info( ! empty( $_GET['force-check'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $info ) {
		return $update;
	}
	return array(
		'theme'        => $stylesheet,
		'version'      => $info['version'],
		'package'      => $info['package'],
		'url'          => add_query_arg( array( 'action' => 'zv_update_notes' ), admin_url( 'admin-ajax.php' ) ),
		'requires'     => $info['requires'] ?? '',
		'requires_php' => $info['requires_php'] ?? '',
		'tested'       => $info['tested'] ?? '',
	);
}, 10, 3 );

// "View version details": what is new, read from update.json.
add_action( 'wp_ajax_zv_update_notes', function () {
	if ( ! current_user_can( 'update_themes' ) ) {
		wp_die( 'Not allowed.' );
	}
	$info = zv_update_info();
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><html><head><meta charset="utf-8"><title>ZenVida Concept</title>'
		. '<style>body{font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#1d2327;margin:0;padding:24px 28px;max-width:720px}h1{font-size:22px;margin:0 0 4px}h2{font-size:16px;margin:24px 0 6px}p{color:#50575e;margin:0}ul{margin:0;padding-left:20px}li{margin:4px 0}</style></head><body>';
	echo '<h1>ZenVida Concept</h1><p>Installed: ' . esc_html( ZV_VERSION ) . ( $info ? ' · Newest: ' . esc_html( $info['version'] ) : '' ) . '</p>';
	if ( ! $info || empty( $info['notes'] ) || ! is_array( $info['notes'] ) ) {
		echo '<h2>No details right now</h2><p>The update can still be installed.</p>';
	} else {
		foreach ( $info['notes'] as $n ) {
			if ( empty( $n['version'] ) || empty( $n['items'] ) || ! is_array( $n['items'] ) ) {
				continue;
			}
			echo '<h2>Version ' . esc_html( $n['version'] ) . ( ZV_VERSION === $n['version'] ? ' (installed)' : '' ) . '</h2><ul>';
			foreach ( $n['items'] as $item ) {
				echo '<li>' . esc_html( $item ) . '</li>';
			}
			echo '</ul>';
		}
	}
	echo '</body></html>';
	exit;
} );

// The zip always contains "zenvida-concept/". If the theme was installed under another
// folder name, rename the unpacked folder so the update replaces the active theme.
add_filter( 'upgrader_source_selection', function ( $source, $remote_source, $upgrader, $extra = array() ) {
	if ( empty( $extra['theme'] ) || $extra['theme'] !== get_template() || is_wp_error( $source ) ) {
		return $source;
	}
	$want = trailingslashit( $remote_source ) . $extra['theme'] . '/';
	if ( untrailingslashit( $source ) === untrailingslashit( $want ) || ! file_exists( trailingslashit( $source ) . 'style.css' ) ) {
		return $source;
	}
	global $wp_filesystem;
	return $wp_filesystem && $wp_filesystem->move( $source, $want ) ? $want : $source;
}, 10, 4 );

// Updates install by themselves (Appearance → Themes → "Disable auto-updates" turns this off).
// Switched on once; if someone turns it off, it stays off.
add_action( 'admin_init', function () {
	if ( get_option( 'zv_autoupdate_set' ) || ! current_user_can( 'update_themes' ) ) {
		return;
	}
	$auto = (array) get_site_option( 'auto_update_themes', array() );
	if ( ! in_array( get_template(), $auto, true ) ) {
		$auto[] = get_template();
		update_site_option( 'auto_update_themes', array_values( $auto ) );
	}
	update_option( 'zv_autoupdate_set', '1' );
} );

// A new version is in place: read update.json again next time.
add_action( 'upgrader_process_complete', function () {
	delete_site_transient( 'zv_update_info' );
} );

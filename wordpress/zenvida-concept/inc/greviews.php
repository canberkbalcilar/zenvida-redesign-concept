<?php
/**
 * Google reviews from ZenVida's own Business Profile (Google Business Profile API).
 *
 * Site settings → Google reviews: paste the OAuth client (Client ID and secret, made in the
 * Google Cloud project that has Business Profile API access), then "Connect Google account"
 * with the account that manages the business profile. After that the reviews, the average
 * rating and the number of reviews are fetched once a day and shown on the site. When the
 * connection is missing or fails, the reviews typed in the editor are shown instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ZV_G_SCOPE = 'https://www.googleapis.com/auth/business.manage';

/** Connection settings (kept out of Site settings and not autoloaded: the secret lives here). */
function zv_g( $key = null ) {
	$o = get_option( 'zv_google', array() );
	$o = is_array( $o ) ? $o : array();
	return null === $key ? $o : ( $o[ $key ] ?? '' );
}

function zv_g_set( $vals ) {
	update_option( 'zv_google', array_merge( zv_g(), $vals ), false );
}

function zv_g_redirect_uri() {
	return admin_url( 'admin-post.php?action=zv_google_oauth' );
}

/** Cached reviews: [ rating, count, synced, maps, reviews => [ [id, name, stars, text, original, time] ] ]. */
function zv_g_data() {
	$d = get_option( 'zv_google_reviews', array() );
	return is_array( $d ) && ! empty( $d['synced'] ) ? $d : null;
}

/* ------------------------------------------------------------------ API */

function zv_g_access_token() {
	$t = get_transient( 'zv_google_token' );
	if ( $t ) {
		return $t;
	}
	if ( ! zv_g( 'refresh_token' ) ) {
		return new WP_Error( 'zv_g', 'Not connected to Google yet.' );
	}
	$r = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 15,
		'body'    => array(
			'client_id'     => zv_g( 'client_id' ),
			'client_secret' => zv_g( 'client_secret' ),
			'refresh_token' => zv_g( 'refresh_token' ),
			'grant_type'    => 'refresh_token',
		),
	) );
	$b = is_wp_error( $r ) ? null : json_decode( wp_remote_retrieve_body( $r ), true );
	if ( empty( $b['access_token'] ) ) {
		$why = is_wp_error( $r ) ? $r->get_error_message() : ( $b['error_description'] ?? $b['error'] ?? 'no answer' );
		return new WP_Error( 'zv_g', 'Google did not accept the connection (' . $why . '). Connect the Google account again.' );
	}
	set_transient( 'zv_google_token', $b['access_token'], max( 60, (int) ( $b['expires_in'] ?? 3600 ) - 120 ) );
	return $b['access_token'];
}

function zv_g_get( $url ) {
	$token = zv_g_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$r = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => array( 'Authorization' => 'Bearer ' . $token ) ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$b = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		return new WP_Error( 'zv_g', 'Google answered: ' . ( $b['error']['message'] ?? wp_remote_retrieve_response_message( $r ) ) );
	}
	return is_array( $b ) ? $b : array();
}

/** All business locations the connected account manages: [ 'accounts/1/locations/2' => [title, maps] ]. */
function zv_g_locations() {
	$acc = zv_g_get( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts?pageSize=20' );
	if ( is_wp_error( $acc ) ) {
		return $acc;
	}
	$out = array();
	foreach ( (array) ( $acc['accounts'] ?? array() ) as $a ) {
		$loc = zv_g_get( 'https://mybusinessbusinessinformation.googleapis.com/v1/' . $a['name'] . '/locations?pageSize=100&readMask=name,title,metadata' );
		if ( is_wp_error( $loc ) ) {
			continue;
		}
		foreach ( (array) ( $loc['locations'] ?? array() ) as $l ) {
			$out[ $a['name'] . '/' . $l['name'] ] = array( (string) ( $l['title'] ?? $l['name'] ), (string) ( $l['metadata']['mapsUri'] ?? '' ) );
		}
	}
	return $out ?: new WP_Error( 'zv_g', 'This Google account does not manage any business profile.' );
}

/**
 * A review comment that Google translated: "text (Translated by Google) translation", or
 * "(Translated by Google) translation (Original) text". Returns [ shown in English, original ].
 */
function zv_g_split( $c ) {
	$c = trim( (string) $c );
	if ( false === strpos( $c, '(Translated by Google)' ) ) {
		return array( $c, $c );
	}
	if ( preg_match( '/^\(Translated by Google\)\s*(.*?)\s*\(Original\)\s*(.*)$/s', $c, $m ) ) {
		return array( trim( $m[1] ), trim( $m[2] ) );
	}
	$p = array_map( 'trim', explode( '(Translated by Google)', $c, 2 ) );
	return array( $p[1] ?: $p[0], $p[0] ?: $p[1] );
}

/** Rough check that a review was written in Spanish. */
function zv_g_spanish( $s ) {
	return preg_match( '/[ñ¿¡áíóú]/u', $s ) || preg_match_all( '/\b(el|la|los|las|muy|que|y|con|para|una|es|lo|pero|todo|gracias|masaje)\b/iu', $s ) >= 3;
}

/** "Maria Lopez Garcia" → "Maria L." (Brand Codex: first name and last initial). */
function zv_g_name( $r ) {
	$n = trim( (string) ( $r['reviewer']['displayName'] ?? '' ) );
	if ( '' === $n || ! empty( $r['reviewer']['isAnonymous'] ) ) {
		return 'Google user';
	}
	$parts = preg_split( '/\s+/u', $n );
	return count( $parts ) > 1 ? $parts[0] . ' ' . mb_strtoupper( mb_substr( end( $parts ), 0, 1 ) ) . '.' : $parts[0];
}

/** Fetch the newest reviews, the average rating and the count (once a day, or "Sync now"). */
function zv_g_sync() {
	$loc = zv_g( 'location' );
	if ( ! $loc ) {
		return new WP_Error( 'zv_g', 'Choose the business profile first.' );
	}
	$stars   = array( 'ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5 );
	$reviews = array();
	$head    = null;
	$page    = '';
	for ( $i = 0; $i < 2; $i++ ) { // the newest 100 are plenty
		$b = zv_g_get( 'https://mybusiness.googleapis.com/v4/' . $loc . '/reviews?pageSize=50&orderBy=' . rawurlencode( 'updateTime desc' ) . ( $page ? '&pageToken=' . rawurlencode( $page ) : '' ) );
		if ( is_wp_error( $b ) ) {
			zv_g_set( array( 'last_error' => $b->get_error_message(), 'last_try' => time() ) );
			return $b;
		}
		$head = $head ?? $b;
		foreach ( (array) ( $b['reviews'] ?? array() ) as $r ) {
			$split     = zv_g_split( $r['comment'] ?? '' );
			$reviews[] = array(
				'id'       => (string) ( $r['reviewId'] ?? '' ),
				'name'     => zv_g_name( $r ),
				'stars'    => $stars[ $r['starRating'] ?? '' ] ?? 0,
				'text'     => $split[0],
				'original' => $split[1],
				'time'     => strtotime( (string) ( $r['createTime'] ?? '' ) ) ?: 0,
			);
		}
		$page = (string) ( $b['nextPageToken'] ?? '' );
		if ( '' === $page ) {
			break;
		}
	}
	update_option( 'zv_google_reviews', array(
		'rating'  => round( (float) ( $head['averageRating'] ?? 0 ), 1 ),
		'count'   => (int) ( $head['totalReviewCount'] ?? count( $reviews ) ),
		'synced'  => time(),
		'maps'    => zv_g( 'maps' ),
		'reviews' => $reviews,
	), false );
	zv_g_set( array( 'last_error' => '', 'last_try' => time() ) );
	return count( $reviews );
}

add_action( 'zv_google_sync', 'zv_g_sync' );
add_action( 'init', function () {
	if ( zv_g( 'location' ) && ! wp_next_scheduled( 'zv_google_sync' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'zv_google_sync' );
	}
} );

/* ------------------------------------------------------- on the website */

/** Reviews to show: newest first, with text, at least the chosen stars. */
function zv_g_pick( $n ) {
	$d = zv_g_data();
	if ( ! $d ) {
		return array();
	}
	$min  = max( 1, min( 5, (int) ( zv_g( 'min_stars' ) ?: 5 ) ) );
	$list = array_filter( (array) $d['reviews'], fn( $r ) => $r['stars'] >= $min && mb_strlen( $r['text'] ) >= 30 );
	usort( $list, fn( $a, $b ) => $b['time'] <=> $a['time'] );
	return array_slice( $list, 0, $n );
}

// The rating and the number of reviews everywhere on the site ("4.9 from 480+ Google reviews").
add_filter( 'zv_opt', function ( $v, $key ) {
	if ( 'rating' !== $key && 'rating_count' !== $key ) {
		return $v;
	}
	$d = zv_g_data();
	if ( ! $d || ! $d['count'] ) {
		return $v;
	}
	if ( 'rating' === $key ) {
		return number_format( (float) $d['rating'], 1 );
	}
	$n = (int) $d['count'];
	$n = $n >= 100 ? floor( $n / 50 ) * 50 . '+' : (string) $n; // changes now and then, not with every review
	return zv__( '{n} Google reviews', array( 'n' => $n ) );
}, 10, 2 );

/**
 * "from 450+ Google reviews" (or "From …"), already in the visitor's language when the numbers come
 * from Google. Without Google it stays the typed text, which TranslatePress translates as before.
 */
function zv_rating_from( $cap = false ) {
	if ( ! zv_g_data() ) {
		return ( $cap ? 'From ' : 'from ' ) . zv_e( zv_opt( 'rating_count' ) );
	}
	return '<span data-no-translation>' . zv_e( zv__( $cap ? 'From {n}' : 'from {n}', array( 'n' => zv_opt( 'rating_count' ) ) ) ) . '</span>';
}

/** Live reviews for the Reviews block ('' when there are none, then the typed ones show). */
function zv_g_reviews_html( $n ) {
	$list = zv_g_pick( $n );
	if ( ! $list ) {
		return '';
	}
	return implode( '', array_map( 'zv_g_card', $list ) );
}

/** One review card. $words: stems to highlight (ritual pages). */
function zv_g_card( $r, $words = array() ) {
	$html = '';
	foreach ( array( $r ) as $r ) {
		$text  = 'es' === zv_lang() && zv_g_spanish( $r['original'] ) ? $r['original'] : $r['text']; // Spanish site: the guest's own words when they wrote in Spanish
		$date  = 'es' === zv_lang()
			? array( 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' )[ (int) gmdate( 'n', $r['time'] ) - 1 ] . ' ' . gmdate( 'Y', $r['time'] )
			: gmdate( 'F Y', $r['time'] );
		$html .= '<div class="rev" data-no-translation><div class="stars" role="img" aria-label="' . esc_attr( $r['stars'] . ' / 5' ) . '">' . str_repeat( '★', $r['stars'] ) . '</div>'
			. '<p>' . zv_g_mark( esc_html( wp_trim_words( $text, 48 ) ), $words ) . '</p><small>' . esc_html( $r['name'] . ' · Google · ' . $date ) . '</small></div>';
	}
	return $html;
}

/** Bold the words that tie a review to the ritual (already escaped text in, HTML out). */
function zv_g_mark( $html, $words ) {
	if ( ! $words ) {
		return $html;
	}
	$re = '/\\b(' . implode( '|', array_map( fn( $w ) => preg_quote( $w, '/' ), $words ) ) . ')[\\p{L}]*/iu';
	return preg_replace( $re, '<b class="hl">$0</b>', $html, 3 );
}

/** The sliding strip of review cards with its arrows (homepage block and ritual pages). */
function zv_rev_carousel( $cards ) {
	$l = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
	$r = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
	return '<div class="rev-wrap" data-carousel><div class="rev-list" tabindex="0" role="region" aria-label="' . esc_attr( zv__( 'Guest reviews' ) ) . '">' . $cards . '</div>'
		. '<div class="rev-nav" hidden><button type="button" class="rev-prev" aria-label="' . esc_attr( zv__( 'Previous reviews' ) ) . '">' . $l . '</button>'
		. '<button type="button" class="rev-next" aria-label="' . esc_attr( zv__( 'More reviews' ) ) . '">' . $r . '</button></div></div>';
}

/* ----------------------------------------------------- on ritual pages */

/**
 * Words that tie a review to a ritual (English and Spanish stems). Google reviews are about the
 * whole studio, so the ritual page shows the ones that mention this ritual.
 */
function zv_ritual_review_words( $post ) {
	$map = array(
		'reflexology'                => array( 'reflexolog', 'foot', 'feet', 'pies' ),
		'deluxe-reflexology'         => array( 'reflexolog', 'foot spa', 'foot bath', 'feet', 'pies' ),
		'foot-massage'               => array( 'foot massage', 'feet', 'pies', 'masaje de pies' ),
		'couples-massage'            => array( 'couple', 'pareja', 'anniversary', 'aniversario', 'partner', 'husband', 'wife', 'boyfriend', 'girlfriend', 'novi' ),
		'deep-tissue-massage'        => array( 'deep tissue', 'tejido profundo', 'descontracturante' ),
		'swedish-massage'            => array( 'swedish', 'sueco', 'relajante' ),
		'thai-massage'               => array( 'thai', 'tailand' ),
		'thai-andaluz'               => array( 'thai andaluz', 'thai', 'tailand' ),
		'sports-massage'             => array( 'sport', 'deportiv', 'marathon', 'maratón', 'running', 'gym' ),
		'lomi-lomi-massage'          => array( 'lomi' ),
		'lymphatic-drainage-massage' => array( 'lymph', 'linf', 'drenaje', 'drainage' ),
		'pregnancy-massage'          => array( 'pregnan', 'embaraz', 'prenatal' ),
		'kobido-facial-massage'      => array( 'kobido', 'facial', 'face' ),
		'classic-facial-massage'     => array( 'facial', 'face', 'cara' ),
		'combined-massage'           => array( 'combined', 'combinado' ),
		'full-body-reset'            => array( 'full body reset', 'full body' ),
		'travel-recovery'            => array( 'flight', 'jet lag', 'jetlag', 'vuelo', 'travel' ),
		'energy-balance'             => array( 'reiki', 'energy', 'energ', 'chakra' ),
		'ritual-levante'             => array( 'levante', 'scrub', 'exfolia' ),
	);
	$post  = get_post( $post );
	$words = $map[ $post->post_name ] ?? array();
	if ( ! $words ) { // a ritual added later: its own name, without the generic words
		$stop  = array( 'massage', 'masaje', 'ritual', 'classic', 'the', 'and' );
		$words = array_values( array_diff( preg_split( '/[\s&]+/u', mb_strtolower( get_the_title( $post ) ) ), $stop ) );
		$words = array_filter( $words, fn( $w ) => mb_strlen( $w ) >= 4 );
	}
	return array_values( $words );
}

/** Reviews for a ritual page: [ cards, about this ritual? ]; matching reviews first, else the newest. */
function zv_ritual_reviews( $post, $n = 10 ) {
	$all = zv_g_pick( 100 );
	if ( ! $all ) {
		return array( array(), false );
	}
	$words = zv_ritual_review_words( $post );
	$norm  = fn( $s ) => mb_strtolower( remove_accents( $s ) );
	$hit   = array_filter( $all, function ( $r ) use ( $words, $norm ) {
		$t = $norm( $r['text'] . ' ' . $r['original'] );
		foreach ( $words as $w ) {
			if ( false !== strpos( $t, $norm( $w ) ) ) {
				return true;
			}
		}
		return false;
	} );
	if ( count( $hit ) >= 2 ) {
		return array( array_slice( array_values( $hit ), 0, $n ), $words );
	}
	return array( array_slice( $all, 0, $n ), false );
}

/** The "What guests say about …" section of a ritual page ('' without Google reviews). */
function zv_ritual_reviews_section( $post ) {
	list( $list, $words ) = zv_ritual_reviews( $post );
	if ( ! $list ) {
		return '';
	}
	$name  = zv_tr( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	$title = $words ? zv__( 'What guests say about {ritual}', array( 'ritual' => $name ) ) : zv__( 'What guests say about ZenVida' );
	$maps  = zv_g_data()['maps'] ?? '';
	$cards = implode( '', array_map( fn( $r ) => zv_g_card( $r, $words ?: array() ), $list ) );
	return '<section class="block bg-paper r-reviews" id="reviews"><div class="wrap"><div class="reviews"><div data-no-translation>'
		. '<span class="eyebrow">' . zv_e( zv__( 'Guest reviews' ) ) . '</span>'
		. '<h2 class="r-rev-title">' . zv_e( $title ) . '</h2>'
		. '<div class="r-rev-score"><b>' . zv_e( zv_opt( 'rating' ) ) . '</b><span class="stars" aria-hidden="true">★★★★★</span></div>'
		. '<p class="r-rev-from">' . zv_rating_from( true ) . '</p>'
		. ( $maps ? '<p class="g-all"><a class="link" href="' . esc_url( $maps ) . '" target="_blank" rel="noopener">' . zv_e( zv__( 'All reviews on Google' ) ) . '</a></p>' : '' )
		. '</div>' . zv_rev_carousel( $cards ) . '</div></div></section>';
}

// Place it before the FAQ of the ritual page (or at the end when the page has no FAQ).
add_filter( 'render_block', function ( $html, $block ) {
	static $done = false;
	if ( $done || 'zv/section' !== ( $block['blockName'] ?? '' ) || ! is_singular( 'ritual' ) || ! in_the_loop() ) {
		return $html;
	}
	$has_faq = false !== strpos( serialize_blocks( $block['innerBlocks'] ?? array() ), 'wp:zv/faq' );
	if ( ! $has_faq ) {
		return $html;
	}
	$done                          = true;
	$GLOBALS['zv_ritual_reviews_done'] = true;
	return zv_ritual_reviews_section( get_the_ID() ) . $html;
}, 10, 2 );

/** Rating line under the ritual title, linking to the reviews below. */
function zv_ritual_rating_line() {
	$has = zv_g_data() && zv_g_pick( 1 );
	$txt = '<span class="stars" aria-hidden="true">★★★★★</span><b>' . zv_e( zv_opt( 'rating' ) ) . '</b><span class="r-rate-n">' . zv_e( zv_opt( 'rating_count' ) ) . '</span>';
	return $has ? '<a class="r-rate" href="#reviews" data-no-translation>' . $txt . '</a>' : '<span class="r-rate">' . $txt . '</span>';
}

/* --------------------------------------------------------------- admin */

add_action( 'admin_menu', function () {
	add_submenu_page( 'zv-settings', 'Google reviews', 'Google reviews', 'manage_options', 'zv-google', 'zv_g_page' );
}, 20 );

function zv_g_page() {
	$d       = zv_g_data();
	$has     = zv_g( 'client_id' ) && zv_g( 'client_secret' );
	$post    = admin_url( 'admin-post.php' );
	$choices = get_transient( 'zv_google_choices' );
	?>
	<div class="wrap zv-settings">
		<h1>Google reviews</h1>
		<?php if ( isset( $_GET['zv_g'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-<?php echo 'ok' === $_GET['zv_g'] ? 'success' : 'error'; // phpcs:ignore ?> is-dismissible"><p><?php echo esc_html( 'ok' === $_GET['zv_g'] ? 'Done.' : ( zv_g( 'last_error' ) ?: 'Something went wrong. Try again.' ) ); // phpcs:ignore ?></p></div>
		<?php endif; ?>
		<p>Shows ZenVida's real Google reviews, the average rating and the number of reviews on the website. They are fetched once a day. Until this is connected, the reviews typed in the editor are shown.</p>

		<h2 class="title">1. Google Cloud OAuth client</h2>
		<p>In the Google Cloud project with Business Profile API access: <b>Google Auth Platform → Clients → Create client → Web application</b>, and add this address under <b>Authorized redirect URIs</b>:</p>
		<p><input type="text" class="large-text code" readonly value="<?php echo esc_attr( zv_g_redirect_uri() ); ?>" onclick="this.select()"></p>
		<form method="post" action="<?php echo esc_url( $post ); ?>">
			<?php wp_nonce_field( 'zv_google_save' ); ?>
			<input type="hidden" name="action" value="zv_google_save">
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="zv_g_id">Client ID</label></th><td><input class="large-text" id="zv_g_id" name="client_id" value="<?php echo esc_attr( zv_g( 'client_id' ) ); ?>" autocomplete="off"></td></tr>
				<tr><th scope="row"><label for="zv_g_secret">Client secret</label></th><td><input class="regular-text" type="password" id="zv_g_secret" name="client_secret" value="" autocomplete="new-password" placeholder="<?php echo zv_g( 'client_secret' ) ? esc_attr( 'Saved. Leave empty to keep it' ) : ''; ?>"></td></tr>
				<tr><th scope="row"><label for="zv_g_min">Show reviews with</label></th><td><select id="zv_g_min" name="min_stars"><?php foreach ( array( 5 => '5 stars only', 4 => '4 and 5 stars', 1 => 'All reviews' ) as $v => $l ) : ?><option value="<?php echo (int) $v; ?>" <?php selected( (int) ( zv_g( 'min_stars' ) ?: 5 ), $v ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select> <span class="description">Only reviews with a written comment are shown, newest first.</span></td></tr>
			</table>
			<?php submit_button( 'Save', 'secondary', 'submit', false ); ?>
		</form>

		<h2 class="title">2. Connect the business profile</h2>
		<?php if ( ! $has ) : ?>
			<p class="description">Save the Client ID and secret first.</p>
		<?php elseif ( ! zv_g( 'refresh_token' ) ) : ?>
			<p>Sign in with the Google account that manages ZenVida's business profile. Google may say the app is not verified: choose <b>Advanced → Go to …</b> to continue.</p>
			<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'zv_google_start', $post ), 'zv_google_start' ) ); ?>">Connect Google account</a></p>
		<?php else : ?>
			<?php if ( is_array( $choices ) && count( $choices ) > 1 && ! zv_g( 'location' ) ) : ?>
				<form method="post" action="<?php echo esc_url( $post ); ?>"><?php wp_nonce_field( 'zv_google_pick' ); ?><input type="hidden" name="action" value="zv_google_pick">
					<p><label>Which business profile? <select name="location"><?php foreach ( $choices as $k => $c ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $c[0] ); ?></option><?php endforeach; ?></select></label> <?php submit_button( 'Use this one', 'primary', 'submit', false ); ?></p></form>
			<?php endif; ?>
			<p>Connected<?php echo zv_g( 'title' ) ? ': <b>' . esc_html( zv_g( 'title' ) ) . '</b>' : ''; ?>.
				<?php if ( $d ) : ?>
					<?php echo esc_html( sprintf( '%s average from %d reviews, %d fetched. Last update %s ago.', number_format( (float) $d['rating'], 1 ), $d['count'], count( $d['reviews'] ), human_time_diff( $d['synced'] ) ) ); ?>
				<?php endif; ?></p>
			<?php if ( zv_g( 'last_error' ) ) : ?><p style="color:#b32d2e"><?php echo esc_html( zv_g( 'last_error' ) ); ?></p><?php endif; ?>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'zv_google_sync', $post ), 'zv_google_sync' ) ); ?>">Update now</a>
				<a class="button-link-delete" style="margin-left:12px" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'zv_google_disconnect', $post ), 'zv_google_disconnect' ) ); ?>">Disconnect</a>
			</p>
			<?php if ( $d && zv_g_pick( 3 ) ) : ?>
				<h2 class="title">On the website now</h2>
				<?php foreach ( zv_g_pick( 3 ) as $r ) : ?>
					<blockquote style="margin:0 0 12px;padding:10px 14px;background:#fff;border-left:3px solid #191919"><?php echo esc_html( str_repeat( '★', $r['stars'] ) . ' ' . wp_trim_words( $r['text'], 30 ) ); ?><br><small><?php echo esc_html( $r['name'] ); ?></small></blockquote>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

function zv_g_back( $ok ) {
	wp_safe_redirect( admin_url( 'admin.php?page=zv-google&zv_g=' . ( $ok ? 'ok' : 'err' ) ) );
	exit;
}

add_action( 'admin_post_zv_google_save', function () {
	check_admin_referer( 'zv_google_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$new = array(
		'client_id' => sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) ),
		'min_stars' => max( 1, min( 5, absint( $_POST['min_stars'] ?? 5 ) ) ),
	);
	$secret = trim( (string) wp_unslash( $_POST['client_secret'] ?? '' ) ); // phpcs:ignore
	if ( '' !== $secret ) {
		$new['client_secret'] = sanitize_text_field( $secret );
	}
	if ( $new['client_id'] !== zv_g( 'client_id' ) ) { // another OAuth client: connect again
		$new += array( 'refresh_token' => '', 'location' => '' );
		delete_transient( 'zv_google_token' );
	}
	zv_g_set( $new );
	zv_g_back( true );
} );

add_action( 'admin_post_zv_google_start', function () {
	check_admin_referer( 'zv_google_start' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$state = wp_generate_password( 24, false );
	set_transient( 'zv_google_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS );
	wp_redirect( add_query_arg( array( // phpcs:ignore WordPress.Security.SafeRedirect -- Google's sign-in page
		'client_id'     => rawurlencode( zv_g( 'client_id' ) ),
		'redirect_uri'  => rawurlencode( zv_g_redirect_uri() ),
		'response_type' => 'code',
		'scope'         => rawurlencode( ZV_G_SCOPE ),
		'access_type'   => 'offline',
		'prompt'        => 'consent',
		'state'         => $state,
	), 'https://accounts.google.com/o/oauth2/v2/auth' ) );
	exit;
} );

// Google sends the user back here after sign-in.
add_action( 'admin_post_zv_google_oauth', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$state = get_transient( 'zv_google_state_' . get_current_user_id() );
	delete_transient( 'zv_google_state_' . get_current_user_id() );
	if ( ! $state || ! hash_equals( $state, (string) ( $_GET['state'] ?? '' ) ) || empty( $_GET['code'] ) ) { // phpcs:ignore
		zv_g_set( array( 'last_error' => ! empty( $_GET['error'] ) ? 'Google sign-in was cancelled.' : 'The sign-in link expired. Try again.' ) ); // phpcs:ignore
		zv_g_back( false );
	}
	$r = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 15,
		'body'    => array(
			'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore
			'client_id'     => zv_g( 'client_id' ),
			'client_secret' => zv_g( 'client_secret' ),
			'redirect_uri'  => zv_g_redirect_uri(),
			'grant_type'    => 'authorization_code',
		),
	) );
	$b = is_wp_error( $r ) ? array() : (array) json_decode( wp_remote_retrieve_body( $r ), true );
	if ( empty( $b['refresh_token'] ) ) {
		zv_g_set( array( 'last_error' => 'Google did not give access (' . ( $b['error_description'] ?? $b['error'] ?? ( is_wp_error( $r ) ? $r->get_error_message() : 'no answer' ) ) . ').' ) );
		zv_g_back( false );
	}
	set_transient( 'zv_google_token', $b['access_token'], max( 60, (int) ( $b['expires_in'] ?? 3600 ) - 120 ) );
	zv_g_set( array( 'refresh_token' => $b['refresh_token'], 'location' => '', 'last_error' => '' ) );
	$locs = zv_g_locations();
	if ( is_wp_error( $locs ) ) {
		zv_g_set( array( 'last_error' => $locs->get_error_message() ) );
		zv_g_back( false );
	}
	if ( 1 === count( $locs ) ) {
		zv_g_use( array_key_first( $locs ), $locs );
		zv_g_back( ! is_wp_error( zv_g_sync() ) );
	}
	set_transient( 'zv_google_choices', $locs, DAY_IN_SECONDS );
	zv_g_back( true );
} );

function zv_g_use( $key, $locs ) {
	zv_g_set( array( 'location' => $key, 'title' => $locs[ $key ][0] ?? '', 'maps' => $locs[ $key ][1] ?? '' ) );
	wp_clear_scheduled_hook( 'zv_google_sync' );
	wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'zv_google_sync' );
}

add_action( 'admin_post_zv_google_pick', function () {
	check_admin_referer( 'zv_google_pick' );
	$locs = get_transient( 'zv_google_choices' );
	$key  = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );
	if ( ! current_user_can( 'manage_options' ) || ! is_array( $locs ) || ! isset( $locs[ $key ] ) ) {
		zv_g_back( false );
	}
	zv_g_use( $key, $locs );
	delete_transient( 'zv_google_choices' );
	zv_g_back( ! is_wp_error( zv_g_sync() ) );
} );

add_action( 'admin_post_zv_google_sync', function () {
	check_admin_referer( 'zv_google_sync' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	zv_g_back( ! is_wp_error( zv_g_sync() ) );
} );

add_action( 'admin_post_zv_google_disconnect', function () {
	check_admin_referer( 'zv_google_disconnect' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	if ( zv_g( 'refresh_token' ) ) {
		wp_remote_post( 'https://oauth2.googleapis.com/revoke', array( 'timeout' => 10, 'body' => array( 'token' => zv_g( 'refresh_token' ) ) ) );
	}
	zv_g_set( array( 'refresh_token' => '', 'location' => '', 'title' => '', 'maps' => '', 'last_error' => '' ) );
	delete_option( 'zv_google_reviews' );
	delete_transient( 'zv_google_token' );
	wp_clear_scheduled_hook( 'zv_google_sync' );
	zv_g_back( true );
} );

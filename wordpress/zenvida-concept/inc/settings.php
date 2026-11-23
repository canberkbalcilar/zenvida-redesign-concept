<?php
/**
 * Appearance → ZenVida settings: site-wide details used by the header, footer and blocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function zv_defaults() {
	return array(
		'promo_show'   => '1',
		'promo_text'   => 'Short on time? Foot Massage 25 min — €25 · add neck & shoulders for €10',
		'promo_link_label' => 'Book now',
		'promo_link_url' => '',
		'header_label' => 'Book now',
		'header_url'   => '',
		'address1'     => 'Calle de la Cruz, 16',
		'address2'     => "Centro, 28012 Madrid · Metro Sol, 3 minutes' walk",
		'phone'        => '+34 690 829 142',
		'email'        => 'hola@zenvida.es',
		'whatsapp'     => '34690829142',
		'maps_url'     => 'https://www.google.com/maps/search/?api=1&query=Calle+de+la+Cruz+16+28012+Madrid',
		'rating'       => '5.0',
		'rating_count' => '450+ guests',
		'hours_mon'    => '11:30-20:30',
		'hours_tue'    => '11:30-20:30',
		'hours_wed'    => '11:30-20:30',
		'hours_thu'    => '11:30-21:30',
		'hours_fri'    => '11:00-21:30',
		'hours_sat'    => '10:30-21:30',
		'hours_sun'    => '12:00-21:30',
		'footer_about' => 'Authentically Spanish foot & body massage in Madrid Centro. Walk in or book online.',
		'copyright'    => 'ZenVida Foot & Body · Madrid',
		'book_location' => '5 min from Puerta del Sol',
		'book_group'   => 'Solo or groups up to 6',
		'book_couples' => 'Two therapists, one room',
		'book_fine'    => 'Secure card payment · Free changes up to 4 hours before',
		'baths'        => array(
			array( 'name' => 'Relax', 'text' => 'Lavender, geranium & rose', 'price' => '5', 'color' => '#B79AC8' ),
			array( 'name' => 'Revive', 'text' => 'Citrus, fresh mint & rosemary', 'price' => '5', 'color' => '#E3B04B' ),
			array( 'name' => 'Recover', 'text' => 'Mar Menor salts for sore feet', 'price' => '5', 'color' => '#7FA392' ),
		),
		'bath_classic' => 'Herbal aromatherapy soak',
		'quiz'         => array(
			array( 'q' => 'What needs care today?', 'a' => array(
				array( 'Tired feet', 'Walking, standing, long days', 'need:feet' ),
				array( 'Back, neck & shoulders', 'Desk, screens, knots', 'need:back' ),
				array( 'Stress & sleep', 'Busy mind, poor sleep', 'need:stress' ),
				array( 'Just travelled', 'Jet lag, swollen legs', 'need:travel' ),
				array( 'Sore muscles', 'Training or a race', 'need:sport' ),
				array( 'My face & skin', 'Puffiness, dull skin', 'need:glow' ),
			) ),
			array( 'q' => 'How much time do you have?', 'a' => array(
				array( 'Under 45 min', 'A quick reset', 'time:short' ),
				array( 'About an hour', 'The classic choice', 'time:hour' ),
				array( '90 min or more', 'Fully switch off', 'time:long' ),
				array( 'Flexible', 'Show me everything', '' ),
			) ),
			array( 'q' => 'Who is coming?', 'a' => array(
				array( 'Just me', '', '' ),
				array( 'Two of us', 'Side by side', 'need:two' ),
				array( "I'm pregnant", 'Safe, adapted care', 'need:preg' ),
			) ),
		),
		'events_title' => 'Workshops, open days & special weeks',
		'events_intro' => 'Small groups, seasonal rituals and evenings to learn something new about looking after yourself.',
		'events_empty' => 'New events are coming soon. Follow us on Instagram or sign up to the newsletter to hear first.',
		'seo_home'     => 'Reflexology & Body Massage in Madrid Centro',
		'seo_home_desc' => 'Foot reflexology and body massage rituals in Madrid Centro, 3 minutes from Sol. Open 7 days, walk in or book online.',
		'seo_rituals'  => 'Massage & Reflexology Rituals in Madrid',
		'seo_rituals_desc' => 'All ZenVida rituals with prices: reflexology, foot, back and body massage, couples rituals. Filter by area and how you feel.',
		'seo_ritual_suffix' => 'in Madrid Centro',
		'demo_mode'    => '0', // the demo import switches it on; a real install starts without it
		'rituals_page' => '',
	);
}

/**
 * A setting. Once the settings page has been saved, what the editor typed is used,
 * including an empty field (e.g. "no promo link"); before that, the defaults apply.
 * A few fields can never be empty and fall back to their default.
 */
function zv_opt( $key ) {
	$o        = get_option( 'zv_settings', array() );
	$d        = zv_defaults();
	$required = array( 'header_label', 'whatsapp', 'seo_home', 'seo_rituals', 'events_title' );
	if ( is_array( $o ) && array_key_exists( $key, $o ) && ( '' !== $o[ $key ] || ! in_array( $key, $required, true ) ) ) {
		return $o[ $key ];
	}
	return $d[ $key ] ?? '';
}

/**
 * Read one day's opening hours as written by an editor.
 * Accepts "11:30-20:30", "11-20", "11.30 – 20.30", "11h-20h"; "Closed" (or empty) = closed.
 * Returns [open, close] in minutes, [0, 0] when closed, or null when it cannot be read.
 */
function zv_parse_hours( $v ) {
	$v = strtolower( trim( (string) $v ) );
	if ( '' === $v || preg_match( '/^(closed|cerrado|kapal[ıi]|close|-|–)$/u', $v ) ) {
		return array( 0, 0 );
	}
	if ( preg_match( '/^(\d{1,2})(?:[:.h](\d{2}))?\s*h?\s*(?:-|–|—|to|a)\s*(\d{1,2})(?:[:.h](\d{2}))?\s*h?$/u', $v, $m ) ) {
		$o = (int) $m[1] * 60 + (int) ( $m[2] ?? 0 );
		$c = (int) $m[3] * 60 + (int) ( $m[4] ?? 0 );
		if ( $c <= $o ) {
			$c += 1440; // closes after midnight
		}
		return ( $o < 1440 && $c <= 2880 ) ? array( $o, $c ) : null;
	}
	return null;
}

/** Foot-bath upgrades offered in the booking card. */
function zv_baths() {
	$o = get_option( 'zv_settings', array() );
	return ( is_array( $o ) && isset( $o['baths'] ) && is_array( $o['baths'] ) ) ? $o['baths'] : zv_defaults()['baths'];
}

/** Opening hours as [open, close] minutes, index 0 = Sunday. */
function zv_hours() {
	$keys = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' );
	$out  = array();
	foreach ( $keys as $k ) {
		$out[] = zv_parse_hours( zv_opt( "hours_$k" ) ) ?? zv_parse_hours( zv_defaults()[ "hours_$k" ] );
	}
	return $out;
}

/** "Help me choose" questions: [ [ 'q' => text, 'a' => [ [label, small text, filter], … ] ], … ] */
function zv_quiz() {
	$o = get_option( 'zv_settings', array() );
	$q = ( is_array( $o ) && isset( $o['quiz'] ) && is_array( $o['quiz'] ) ) ? $o['quiz'] : zv_defaults()['quiz'];
	return array_values( array_filter( $q, fn( $x ) => '' !== trim( $x['q'] ?? '' ) && ! empty( $x['a'] ) ) );
}

/** What a quiz answer can do: filter by a need or by length, or nothing. */
function zv_quiz_actions() {
	$out = array( '' => '— No filter —' );
	foreach ( zv_needs() as $slug => $name ) {
		$out[ "need:$slug" ] = 'Show rituals good for: ' . $name;
	}
	return $out + array( 'time:short' => 'Length: under 45 min', 'time:hour' => 'Length: about an hour', 'time:long' => 'Length: 90 min or more' );
}

/** The studio's time zone (Settings → General), Madrid unless a named zone is set. */
function zv_tz() {
	$tz = wp_timezone_string();
	return false !== strpos( $tz, '/' ) ? $tz : 'Europe/Madrid';
}

function zv_demo() {
	return '1' === (string) zv_opt( 'demo_mode' );
}

// Demo mode: keep the concept out of search engines and hide the admin bar on the front end.
add_filter( 'wp_robots', function ( $robots ) {
	if ( zv_demo() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );
add_filter( 'show_admin_bar', fn( $show ) => zv_demo() ? false : $show );

/* ------------------------------------------------------------------ admin */

add_action( 'admin_menu', function () {
	add_menu_page( 'Site settings', 'Site settings', 'edit_theme_options', 'zv-settings', 'zv_settings_page', 'dashicons-store', 3 );
} );
// Old address (Appearance → ZenVida settings) still works.
// Saving uses the same permission as seeing the page (options.php would otherwise ask for manage_options).
add_filter( 'option_page_capability_zv_settings', fn() => 'edit_theme_options' );

add_action( 'admin_init', function () {
	register_setting( 'zv_settings', 'zv_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = get_option( 'zv_settings', array() );
			$out = is_array( $out ) ? $out : array();
			foreach ( zv_defaults() as $k => $d ) {
				if ( 'quiz' === $k ) {
					$qs = array();
					foreach ( (array) ( $in['quiz'] ?? array() ) as $q ) {
						$text = sanitize_text_field( $q['q'] ?? '' );
						$ans  = array();
						foreach ( (array) ( $q['label'] ?? array() ) as $i => $label ) {
							$label = sanitize_text_field( $label );
							if ( '' !== $label ) {
								$act   = sanitize_text_field( $q['action'][ $i ] ?? '' );
								$ans[] = array( $label, sanitize_text_field( $q['text'][ $i ] ?? '' ), preg_match( '/^(need|time):[a-z0-9_-]+$/', $act ) ? $act : '' );
							}
						}
						$qs[] = array( 'q' => $text, 'a' => $ans );
					}
					$out['quiz'] = $qs;
				} elseif ( 'baths' === $k ) {
					$rows = array();
					foreach ( (array) ( $in['baths']['name'] ?? array() ) as $i => $name ) {
						$name = sanitize_text_field( $name );
						if ( '' !== $name ) {
							$rows[] = array(
								'name'  => $name,
								'text'  => sanitize_text_field( $in['baths']['text'][ $i ] ?? '' ),
								'price' => (string) absint( $in['baths']['price'][ $i ] ?? 0 ),
								'color' => sanitize_hex_color( $in['baths']['color'][ $i ] ?? '' ) ?: '#B0956A',
							);
						}
					}
					$out['baths'] = $rows;
				} elseif ( in_array( $k, array( 'promo_show', 'demo_mode' ), true ) ) {
					$out[ $k ] = empty( $in[ $k ] ) ? '0' : '1';
				} elseif ( in_array( $k, array( 'header_url', 'maps_url', 'promo_link_url' ), true ) ) {
					$out[ $k ] = esc_url_raw( $in[ $k ] ?? '' );
				} elseif ( 0 === strpos( $k, 'hours_' ) ) {
					$raw    = sanitize_text_field( $in[ $k ] ?? '' );
					$parsed = zv_parse_hours( $raw );
					if ( null === $parsed ) {
						$out[ $k ] = $out[ $k ] ?? zv_defaults()[ $k ];
						add_settings_error( 'zv_settings', 'zv_' . $k, sprintf( 'Opening hours for %s were not changed: "%s" is not a time range. Write it like 11:30-20:30, or Closed. Your other changes were saved.', array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' )[ substr( $k, 6 ) ] ?? $k, $raw ) );
					} else {
						$fmt       = fn( $m ) => sprintf( '%02d:%02d', intdiv( $m % 1440, 60 ), $m % 60 );
						$out[ $k ] = array( 0, 0 ) === $parsed ? 'Closed' : $fmt( $parsed[0] ) . '-' . $fmt( $parsed[1] );
					}
				} elseif ( 'rituals_page' === $k ) {
					$out[ $k ] = isset( $in[ $k ] ) ? (string) absint( $in[ $k ] ) : ( $out[ $k ] ?? '' );
				} else {
					$out[ $k ] = sanitize_text_field( $in[ $k ] ?? '' );
				}
			}
			return $out;
		},
	) );
} );

function zv_settings_page() {
	$o   = wp_parse_args( (array) get_option( 'zv_settings', array() ), zv_defaults() );
	$row = function ( $k, $label, $help = '', $type = 'text' ) use ( $o ) {
		echo '<tr><th scope="row"><label for="zv_' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="zv_' . esc_attr( $k ) . '" name="zv_settings[' . esc_attr( $k ) . ']" value="1" ' . checked( '1', (string) $o[ $k ], false ) . '> ' . esc_html( $help ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="2" id="zv_' . esc_attr( $k ) . '" name="zv_settings[' . esc_attr( $k ) . ']">' . esc_textarea( $o[ $k ] ) . '</textarea>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' );
		} else {
			echo '<input class="regular-text" type="text" id="zv_' . esc_attr( $k ) . '" name="zv_settings[' . esc_attr( $k ) . ']" value="' . esc_attr( $o[ $k ] ) . '">' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' );
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap zv-settings">
		<h1>Site settings</h1>
		<?php settings_errors(); ?>
		<p>Everything here appears on every page — header, footer, "Visit us" sections, the "Open now" label and the booking card. Change a field and click <b>Save changes</b> at the bottom.<br>
		Menus: <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Appearance → Menus</a> · Logo: <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>">Appearance → Customize → Site Identity</a></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'zv_settings' ); ?>
			<h2 class="title">Promo bar &amp; header</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'promo_show', 'Promo bar', 'Show the dark bar above the header', 'checkbox' );
				$row( 'promo_text', 'Promo text' );
				$row( 'promo_link_label', 'Promo link text', 'Leave empty for no link.' );
				$row( 'promo_link_url', 'Promo link goes to', 'Copy the address of a page from your browser, e.g. ' . home_url( '/rituals/' ) . ' — empty = rituals page' );
				$row( 'header_label', 'Header button label' );
				$row( 'header_url', 'Header button link', 'Empty = rituals page' );
				?>
			</table>
			<h2 class="title">Contact</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'address1', 'Address' );
				$row( 'address2', 'Address line 2' );
				$row( 'phone', 'Phone' );
				$row( 'email', 'Email' );
				$row( 'whatsapp', 'WhatsApp number', 'International format, digits only, e.g. 34690829142' );
				$row( 'maps_url', 'Google Maps link' );
				?>
			</table>
			<h2 class="title">Opening hours</h2>
			<p>Write each day like <code>11:30-20:30</code> (or <code>11-20</code>). Write <b>Closed</b> for a day you are closed. Used for "Open now", the hours tables and booking times.</p>
			<table class="form-table" role="presentation">
				<?php
				foreach ( array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' ) as $k => $l ) {
					$row( "hours_$k", $l );
				}
				?>
			</table>
			<h2 class="title">Reviews &amp; footer</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'rating', 'Average rating' );
				$row( 'rating_count', 'Rating count', 'e.g. 450+ guests' );
				$row( 'footer_about', 'Footer text', '', 'textarea' );
				$row( 'copyright', 'Copyright line', 'The year is added automatically.' );
				?>
			</table>
			<h2 class="title">Booking card on ritual pages</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'book_location', 'Location label', 'Small label under the ritual title.' );
				$row( 'book_group', 'Group label', 'For rituals priced per person.' );
				$row( 'book_couples', 'Couples label', 'For rituals priced for two.' );
				$row( 'book_fine', 'Small print under the button' );
				$row( 'bath_classic', 'Included foot bath' );
				?>
				<tr><th scope="row">Foot-bath upgrades</th><td>
					<table class="zv-rows wide" data-zv-repeat>
						<thead><tr><th>Name</th><th>Description</th><th>Price (€)</th><th>Colour</th><th></th></tr></thead>
						<tbody>
						<?php foreach ( zv_baths() ?: array( array( 'name' => '', 'text' => '', 'price' => '', 'color' => '#B0956A' ) ) as $b ) : ?>
							<tr data-zv-row>
								<td data-label="Name"><input type="text" name="zv_settings[baths][name][]" value="<?php echo esc_attr( $b['name'] ); ?>"></td>
								<td data-label="Description"><input type="text" name="zv_settings[baths][text][]" value="<?php echo esc_attr( $b['text'] ); ?>"></td>
								<td data-label="Price (€)"><input type="number" min="0" name="zv_settings[baths][price][]" value="<?php echo esc_attr( $b['price'] ); ?>" style="width:80px"></td>
								<td data-label="Colour"><input type="text" class="zv-color" name="zv_settings[baths][color][]" value="<?php echo esc_attr( $b['color'] ); ?>"></td>
								<td><button type="button" class="button-link zv-remove" data-zv-del>Remove</button></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button" data-zv-add>+ Add foot bath</button></p>
					<p class="description">Shown on rituals where "Offer foot-bath upgrades" is ticked.</p>
				</td></tr>
			</table>
			<h2 class="title">Events page</h2>
			<p>The top of <a href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>" target="_blank">/events/</a>. The events themselves are added under <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=zv_event' ) ); ?>">Events</a>.</p>
			<table class="form-table" role="presentation">
				<?php
				$row( 'events_title', 'Title' );
				$row( 'events_intro', 'Intro', '', 'textarea' );
				$row( 'events_empty', 'Text when nothing is planned', '', 'textarea' );
				?>
			</table>
			<h2 class="title">"Help me choose" quiz</h2>
			<p>The short quiz on the rituals page. Each answer can narrow the list to rituals for a need, or to a length. Leave a question empty to skip it.</p>
			<?php
			$quiz    = zv_quiz();
			$actions = zv_quiz_actions();
			for ( $qi = 0; $qi < 4; $qi++ ) :
				$q   = $quiz[ $qi ] ?? array( 'q' => '', 'a' => array() );
				$ans = $q['a'] ?: array( array( '', '', '' ) );
				$n   = 'zv_settings[quiz][' . $qi . ']';
				?>
			<div class="zv-quiz-q">
				<p><label class="zv-label" for="zv_quiz_<?php echo (int) $qi; ?>">Question <?php echo (int) $qi + 1; ?></label>
					<input type="text" class="regular-text" id="zv_quiz_<?php echo (int) $qi; ?>" name="<?php echo esc_attr( $n ); ?>[q]" value="<?php echo esc_attr( $q['q'] ); ?>" placeholder="<?php echo 3 === $qi ? 'Optional extra question' : ''; ?>"></p>
				<table class="zv-rows wide" data-zv-repeat>
					<thead><tr><th>Answer</th><th>Small text (optional)</th><th>What it does</th><th></th></tr></thead>
					<tbody>
					<?php foreach ( $ans as $a ) : ?>
						<tr data-zv-row>
							<td data-label="Answer"><input type="text" name="<?php echo esc_attr( $n ); ?>[label][]" value="<?php echo esc_attr( $a[0] ); ?>"></td>
							<td data-label="Small text (optional)"><input type="text" name="<?php echo esc_attr( $n ); ?>[text][]" value="<?php echo esc_attr( $a[1] ); ?>"></td>
							<td data-label="What it does"><select name="<?php echo esc_attr( $n ); ?>[action][]">
								<?php foreach ( $actions as $v => $l ) : ?>
									<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $a[2], $v ); ?>><?php echo esc_html( $l ); ?></option>
								<?php endforeach; ?>
							</select></td>
							<td><button type="button" class="button-link zv-remove" data-zv-del>Remove</button></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button" data-zv-add>+ Add answer</button></p>
			</div>
			<?php endfor; ?>
			<h2 class="title">Google</h2>
			<p>How the pages appear in Google search results. (If you install Yoast SEO or Rank Math, these are ignored and the plugin takes over.)</p>
			<table class="form-table" role="presentation">
				<?php
				$row( 'seo_home', 'Homepage title' );
				$row( 'seo_home_desc', 'Homepage description', '', 'textarea' );
				$row( 'seo_rituals', 'Rituals page title' );
				$row( 'seo_rituals_desc', 'Rituals page description', '', 'textarea' );
				$row( 'seo_ritual_suffix', 'Added after each ritual name', 'e.g. "Deluxe Reflexology in Madrid Centro"' );
				?>
			</table>
			<h2 class="title">Demo mode</h2>
			<table class="form-table" role="presentation">
				<?php $row( 'demo_mode', 'Demo mode', 'Show the "Redesign concept" bar, hide from search engines, hide the admin bar. Turn off before going live.', 'checkbox' ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

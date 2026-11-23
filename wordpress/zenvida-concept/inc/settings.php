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
		'seo_home'     => 'Reflexology & Body Massage in Madrid Centro',
		'seo_home_desc' => 'Foot reflexology and body massage rituals in Madrid Centro, 3 minutes from Sol. Open 7 days, walk in or book online.',
		'seo_rituals'  => 'Massage & Reflexology Rituals in Madrid',
		'seo_rituals_desc' => 'All ZenVida rituals with prices: reflexology, foot, back and body massage, couples rituals. Filter by area and how you feel.',
		'seo_ritual_suffix' => 'in Madrid Centro',
		'demo_mode'    => '1',
		'rituals_page' => '',
	);
}

function zv_opt( $key ) {
	$o = get_option( 'zv_settings', array() );
	$d = zv_defaults();
	return ( is_array( $o ) && isset( $o[ $key ] ) && '' !== $o[ $key ] ) ? $o[ $key ] : ( $d[ $key ] ?? '' );
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
		$v = zv_opt( "hours_$k" );
		if ( preg_match( '/^(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})$/', trim( $v ), $m ) ) {
			$out[] = array( $m[1] * 60 + $m[2], $m[3] * 60 + $m[4] );
		} else {
			$out[] = array( 600, 1260 );
		}
	}
	return $out;
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
add_action( 'admin_page_access_denied', function () {
	if ( isset( $_GET['page'] ) && 'zv-settings' === $_GET['page'] ) { // phpcs:ignore
		wp_safe_redirect( admin_url( 'admin.php?page=zv-settings' ) );
		exit;
	}
} );

add_action( 'admin_init', function () {
	register_setting( 'zv_settings', 'zv_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = get_option( 'zv_settings', array() );
			$out = is_array( $out ) ? $out : array();
			foreach ( zv_defaults() as $k => $d ) {
				if ( 'baths' === $k ) {
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
			<p>Format: 11:30-20:30. Used for "Open now", the hours tables and booking times.</p>
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
								<td><input type="text" name="zv_settings[baths][name][]" value="<?php echo esc_attr( $b['name'] ); ?>"></td>
								<td><input type="text" name="zv_settings[baths][text][]" value="<?php echo esc_attr( $b['text'] ); ?>"></td>
								<td><input type="number" min="0" name="zv_settings[baths][price][]" value="<?php echo esc_attr( $b['price'] ); ?>" style="width:80px"></td>
								<td><input type="text" class="zv-color" name="zv_settings[baths][color][]" value="<?php echo esc_attr( $b['color'] ); ?>"></td>
								<td><button type="button" class="button-link zv-remove" data-zv-del>Remove</button></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button" data-zv-add>+ Add foot bath</button></p>
					<p class="description">Shown on rituals where "Offer foot-bath upgrades" is ticked.</p>
				</td></tr>
			</table>
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

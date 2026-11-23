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
		'promo_text'   => 'Short on time? <a href="/rituals/">Foot Massage 25 min — €25</a> · add neck &amp; shoulders for €10',
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
		'demo_mode'    => '1',
		'rituals_page' => '',
	);
}

function zv_opt( $key ) {
	$o = get_option( 'zv_settings', array() );
	$d = zv_defaults();
	return ( is_array( $o ) && isset( $o[ $key ] ) && '' !== $o[ $key ] ) ? $o[ $key ] : ( $d[ $key ] ?? '' );
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
	add_theme_page( 'ZenVida settings', 'ZenVida settings', 'edit_theme_options', 'zv-settings', 'zv_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'zv_settings', 'zv_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$out = get_option( 'zv_settings', array() );
			$out = is_array( $out ) ? $out : array();
			foreach ( zv_defaults() as $k => $d ) {
				if ( in_array( $k, array( 'promo_show', 'demo_mode' ), true ) ) {
					$out[ $k ] = empty( $in[ $k ] ) ? '0' : '1';
				} elseif ( 'promo_text' === $k ) {
					$out[ $k ] = wp_kses( $in[ $k ] ?? '', array( 'a' => array( 'href' => true ), 'b' => array(), 'strong' => array(), 'em' => array() ) );
				} elseif ( in_array( $k, array( 'header_url', 'maps_url' ), true ) ) {
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
	<div class="wrap">
		<h1>ZenVida settings</h1>
		<p>Details used across the whole site — header, footer, "Visit us" blocks, opening status and the booking card.
		Menus are edited in <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Appearance → Menus</a>; page sections are edited as blocks on each page.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'zv_settings' ); ?>
			<h2 class="title">Promo bar &amp; header</h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'promo_show', 'Promo bar', 'Show the dark bar above the header', 'checkbox' );
				$row( 'promo_text', 'Promo text', 'Links allowed, e.g. <a href="/rituals/">Foot Massage</a>', 'textarea' );
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

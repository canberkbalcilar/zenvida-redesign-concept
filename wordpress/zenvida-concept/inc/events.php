<?php
/**
 * Events: workshops, open days, special weeks…
 *
 * Admin: Events → Add new event. A short form (photo, dates in Madrid time, place,
 * price, places, button) and a plain text editor for the description.
 * Site: /events/ lists what is coming up (past events folded away), each event has
 * its own page with "Add to calendar", and the "Upcoming events" block can go on any page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------- post type */

add_action( 'init', function () {
	register_post_type( 'zv_event', array(
		'labels'        => array(
			'name'          => 'Events',
			'singular_name' => 'Event',
			'menu_name'     => 'Events',
			'all_items'     => 'All events',
			'add_new'       => 'Add new event',
			'add_new_item'  => 'Add new event',
			'edit_item'     => 'Edit event',
			'view_item'     => 'View event',
			'not_found'     => 'No events yet. Click "Add new event" to create one.',
		),
		'public'        => true,
		'show_in_rest'  => false, // plain form + classic text editor
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 6,
		'has_archive'   => 'events',
		'rewrite'       => array( 'slug' => 'events', 'with_front' => false ),
		'supports'      => array( 'title', 'editor', 'revisions' ),
	) );
} );

add_filter( 'enter_title_here', fn( $t, $post ) => 'zv_event' === $post->post_type ? 'Event name, e.g. Reflexology for beginners' : $t, 10, 2 );

function zv_event_defaults() {
	return array( 'image' => '', 'label' => '', 'summary' => '', 'start' => '', 'end' => '', 'location' => '', 'price' => '', 'places' => '', 'soldout' => '', 'btn' => '', 'url' => '' );
}

function zv_event( $id ) {
	$m = get_post_meta( $id, 'zv_event', true );
	return wp_parse_args( is_array( $m ) ? $m : array(), zv_event_defaults() );
}

/** [start, end] as Unix time. No end = the start day until 23:59. */
function zv_event_times( $e ) {
	$s = zv_popup_time( $e['start'] );
	$x = zv_popup_time( $e['end'] );
	if ( $s && ( ! $x || $x < $s ) ) {
		$x = ( new DateTimeImmutable( '@' . $s ) )->setTimezone( wp_timezone() )->setTime( 23, 59 )->getTimestamp();
	}
	return array( $s, $x );
}

/** upcoming | now | past */
function zv_event_state( $e ) {
	list( $s, $x ) = zv_event_times( $e );
	$t = time();
	return $x && $x < $t ? 'past' : ( $s && $s <= $t ? 'now' : 'upcoming' );
}

/** "Saturday 5 December · 18:30–20:00" or "1 December – 24 December" (Madrid time). */
function zv_event_when( $e, $short = false ) {
	list( $s, $x ) = zv_event_times( $e );
	if ( ! $s ) {
		return 'Date to be announced';
	}
	$same    = wp_date( 'Ymd', $s ) === wp_date( 'Ymd', $x );
	$timed   = '00:00' !== wp_date( 'H:i', $s );
	$endtime = $e['end'] && '23:59' !== wp_date( 'H:i', $x );
	if ( $same ) {
		$day = wp_date( $short ? 'D j M' : 'l j F', $s );
		return $day . ( $timed ? ' · ' . wp_date( 'H:i', $s ) . ( $endtime ? '–' . wp_date( 'H:i', $x ) : '' ) : '' );
	}
	$f = $short ? 'j M' : 'j F';
	return wp_date( $f, $s ) . ' – ' . wp_date( $f, $x );
}

/** The event name as plain text (no HTML entities) for WhatsApp, calendars and Google. */
function zv_event_name( $post ) {
	return html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
}

function zv_event_location( $e ) {
	return $e['location'] ?: trim( zv_opt( 'address1' ) . ', Madrid' );
}

/** Book button: the event's own link, or a WhatsApp message with the event's name and date. */
function zv_event_button( $post, $e ) {
	if ( $e['url'] ) {
		return $e['url'];
	}
	$msg = sprintf( "Hi! I'd like to join \"%s\" (%s).", zv_event_name( $post ), zv_event_when( $e, true ) );
	return 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) . '?text=' . rawurlencode( $msg );
}

/** Published, switched-on events. $scope: upcoming (incl. happening now) | past. */
function zv_events( $scope = 'upcoming', $limit = -1, $exclude = 0 ) {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'zv_event', 'post_status' => 'publish', 'numberposts' => 200, 'exclude' => $exclude ? array( $exclude ) : array() ) ) as $p ) {
		if ( get_post_meta( $p->ID, 'zv_hidden', true ) ) {
			continue;
		}
		$e     = zv_event( $p->ID );
		$state = zv_event_state( $e );
		if ( ( 'past' === $scope ) === ( 'past' === $state ) ) {
			$out[] = array( $p, $e, $state, zv_event_times( $e )[0] );
		}
	}
	usort( $out, fn( $a, $b ) => 'past' === $scope ? $b[3] <=> $a[3] : $a[3] <=> $b[3] );
	return $limit > 0 ? array_slice( $out, 0, $limit ) : $out;
}

function zv_event_card( $row ) {
	list( $p, $e, $state, $s ) = $row;
	$img   = $e['image'];
	$badge = $e['soldout'] ? 'Fully booked' : ( 'now' === $state ? 'On now' : '' );
	$meta  = array_filter( array( $e['label'], zv_event_when( $e, true ) ) );
	$foot  = array_filter( array( $e['price'], $e['places'] && ! $e['soldout'] ? $e['places'] : '' ) );
	return '<a class="ev ev-' . esc_attr( $state ) . '" href="' . esc_url( get_permalink( $p ) ) . '">'
		. '<div class="ev-img">' . ( $img ? '<img src="' . esc_url( $img ) . '" alt="" loading="lazy" onerror="this.remove()">' : '' )
		. ( $s ? '<span class="ev-date"><b>' . esc_html( wp_date( 'j', $s ) ) . '</b>' . esc_html( wp_date( 'M', $s ) ) . '</span>' : '' )
		. ( $badge ? '<span class="ev-badge">' . zv_e( $badge ) . '</span>' : '' ) . '</div>'
		. '<div class="ev-body"><span class="k">' . zv_e( implode( ' · ', $meta ) ) . '</span><h3>' . zv_e( get_the_title( $p ) ) . '</h3>'
		. ( $e['summary'] ? '<p>' . zv_e( $e['summary'] ) . '</p>' : '' )
		. '<div class="ev-foot"><span>' . zv_e( implode( ' · ', $foot ) ) . '</span><span class="go">Details →</span></div></div></a>';
}

function zv_event_grid( $rows ) {
	return '<div class="ev-grid">' . implode( '', array_map( 'zv_event_card', $rows ) ) . '</div>';
}

/* ------------------------------------------------- "Upcoming events" block */

add_action( 'init', function () {
	zv_register( 'events', 'Upcoming events', 'calendar-alt', array( 'count' => 3, 'empty' => 'New events are coming soon — follow us on Instagram to hear first.' ), 'zv_render_events', array( 'parent' => array( 'zv/section' ) ) );
}, 20 );

function zv_render_events( $a ) {
	$rows = zv_events( 'upcoming', max( 1, (int) $a['count'] ) );
	return $rows ? zv_event_grid( $rows ) : '<p class="ev-empty">' . zv_e( $a['empty'] ) . '</p>';
}

/* ---------------------------------------------- calendar file, Google data */

add_action( 'template_redirect', function () {
	if ( ! is_singular( 'zv_event' ) || ! isset( $_GET['ics'] ) ) { // phpcs:ignore
		return;
	}
	$p = get_queried_object();
	$e = zv_event( $p->ID );
	list( $s, $x ) = zv_event_times( $e );
	if ( ! $s ) {
		return;
	}
	$esc = fn( $t ) => str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\;', '\,', '\n' ), wp_strip_all_tags( $t ) );
	$utc = fn( $t ) => gmdate( 'Ymd\THis\Z', $t );
	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $p->post_name ) . '.ics"' );
	echo implode( "\r\n", array(
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ZenVida//Events//EN', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
		'UID:zvevent-' . $p->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . $utc( time() ), 'DTSTART:' . $utc( $s ), 'DTEND:' . $utc( $x ),
		'SUMMARY:' . $esc( zv_event_name( $p ) ),
		'DESCRIPTION:' . $esc( ( $e['summary'] ? $e['summary'] . ' ' : '' ) . get_permalink( $p ) ),
		'LOCATION:' . $esc( zv_event_location( $e ) ),
		'URL:' . get_permalink( $p ),
		'END:VEVENT', 'END:VCALENDAR', '',
	) ); // phpcs:ignore
	exit;
} );

function zv_event_gcal( $p, $e ) {
	list( $s, $x ) = zv_event_times( $e );
	return add_query_arg( array(
		'action'   => 'TEMPLATE',
		'text'     => rawurlencode( zv_event_name( $p ) ),
		'dates'    => gmdate( 'Ymd\THis\Z', $s ) . '/' . gmdate( 'Ymd\THis\Z', $x ),
		'details'  => rawurlencode( ( $e['summary'] ? $e['summary'] . "\n" : '' ) . get_permalink( $p ) ),
		'location' => rawurlencode( zv_event_location( $e ) ),
	), 'https://calendar.google.com/calendar/render' );
}

// Google can show the event (date, place, price) in search results.
add_action( 'wp_head', function () {
	if ( ! is_singular( 'zv_event' ) ) {
		return;
	}
	$p = get_queried_object();
	$e = zv_event( $p->ID );
	list( $s, $x ) = zv_event_times( $e );
	if ( ! $s ) {
		return;
	}
	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'name'                => zv_event_name( $p ),
		'startDate'           => wp_date( 'c', $s ),
		'endDate'             => wp_date( 'c', $x ),
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'location'            => array( '@type' => 'Place', 'name' => 'ZenVida', 'address' => zv_event_location( $e ) ),
		'description'         => $e['summary'] ?: wp_trim_words( wp_strip_all_tags( $p->post_content ), 40 ),
		'organizer'           => array( '@type' => 'Organization', 'name' => 'ZenVida', 'url' => home_url( '/' ) ),
		'url'                 => get_permalink( $p ),
	);
	if ( $e['image'] ) {
		$data['image'] = array( $e['image'] );
	}
	if ( preg_match( '/(\d+(?:[.,]\d+)?)/', (string) $e['price'], $m ) || preg_match( '/free|gratis/i', (string) $e['price'] ) ) {
		$data['offers'] = array( '@type' => 'Offer', 'price' => isset( $m[1] ) ? str_replace( ',', '.', $m[1] ) : '0', 'priceCurrency' => 'EUR', 'url' => get_permalink( $p ), 'availability' => $e['soldout'] ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock' );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
} );

// Hidden events are not shown to visitors.
add_action( 'template_redirect', function () {
	if ( is_singular( 'zv_event' ) && get_post_meta( get_queried_object_id(), 'zv_hidden', true ) && ! current_user_can( 'edit_post', get_queried_object_id() ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}, 5 );

add_filter( 'document_title_parts', function ( $parts ) {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return $parts;
	}
	if ( is_post_type_archive( 'zv_event' ) ) {
		$parts['title'] = zv_opt( 'events_title' ) . ' at ZenVida Madrid';
	} elseif ( is_singular( 'zv_event' ) ) {
		$parts['title'] = get_the_title() . ' · ' . zv_event_when( zv_event( get_queried_object_id() ), true );
	}
	return $parts;
}, 20 );

/* ------------------------------------------------------------------ admin */

// The form sits right under the event name; the description editor comes after it.
add_action( 'edit_form_after_title', function ( $post ) {
	if ( 'zv_event' !== $post->post_type ) {
		return;
	}
	wp_nonce_field( 'zv_event', 'zv_event_nonce' );
	$e = zv_event( $post->ID );
	$n = fn( $k ) => 'zv_event[' . $k . ']';
	?>
	<div class="postbox zv-event-box"><div class="inside zv-box">
		<div class="zv-visible">
			<label class="zv-switch"><input type="checkbox" name="zv_event_visible" value="1" <?php checked( ! get_post_meta( $post->ID, 'zv_hidden', true ) ); ?>><span></span></label>
			<div><b>Show on the website</b><br><span class="description">Past events move to "Past events" by themselves. Switch off to hide this event completely.</span></div>
		</div>

		<h3>1. When</h3>
		<p class="zv-inline">
			<label>Starts <input type="datetime-local" name="<?php echo esc_attr( $n( 'start' ) ); ?>" value="<?php echo esc_attr( $e['start'] ); ?>" required></label>
			<label>Ends <input type="datetime-local" name="<?php echo esc_attr( $n( 'end' ) ); ?>" value="<?php echo esc_attr( $e['end'] ); ?>"></label>
		</p>
		<p class="description">Madrid time. For a several-day event (e.g. a special week), set the first and last day. Leave "Ends" empty for a one-day event.</p>

		<h3>2. About the event</h3>
		<div class="zv-popup-grid">
			<div><?php zv_media_field( $n( 'image' ), $e['image'], 'Photo', 'Landscape photos look best.' ); ?></div>
			<div>
				<p><label class="zv-label">Type (small label)</label>
					<input type="text" class="regular-text" list="zv-event-types" name="<?php echo esc_attr( $n( 'label' ) ); ?>" value="<?php echo esc_attr( $e['label'] ); ?>" placeholder="e.g. Workshop">
					<datalist id="zv-event-types"><option value="Workshop"><option value="Open day"><option value="Special week"><option value="Group session"><option value="Couples evening"><option value="Holiday opening"></datalist></p>
				<p><label class="zv-label">Short summary (for the events list)</label>
					<textarea class="widefat" rows="2" name="<?php echo esc_attr( $n( 'summary' ) ); ?>" placeholder="One or two sentences."><?php echo esc_textarea( $e['summary'] ); ?></textarea></p>
				<p><label class="zv-label">Place</label>
					<input type="text" class="widefat" name="<?php echo esc_attr( $n( 'location' ) ); ?>" value="<?php echo esc_attr( $e['location'] ); ?>" placeholder="<?php echo esc_attr( zv_event_location( zv_event_defaults() ) ); ?> (our studio)"></p>
			</div>
		</div>

		<h3>3. Price and booking</h3>
		<p class="zv-inline">
			<label>Price <input type="text" name="<?php echo esc_attr( $n( 'price' ) ); ?>" value="<?php echo esc_attr( $e['price'] ); ?>" placeholder="e.g. €35 or Free"></label>
			<label>Places <input type="text" name="<?php echo esc_attr( $n( 'places' ) ); ?>" value="<?php echo esc_attr( $e['places'] ); ?>" placeholder="e.g. 8 places"></label>
			<label><input type="checkbox" name="<?php echo esc_attr( $n( 'soldout' ) ); ?>" value="1" <?php checked( (bool) $e['soldout'] ); ?>> Fully booked</label>
		</p>
		<p class="zv-inline">
			<label>Button text <input type="text" name="<?php echo esc_attr( $n( 'btn' ) ); ?>" value="<?php echo esc_attr( $e['btn'] ); ?>" placeholder="Reserve your place"></label>
			<label>Button goes to <input type="text" name="<?php echo esc_attr( $n( 'url' ) ); ?>" value="<?php echo esc_attr( $e['url'] ); ?>" placeholder="Empty = WhatsApp message" style="min-width:280px"></label>
		</p>
		<p class="description">Leave the link empty and the button opens WhatsApp with a ready message: event name and date.</p>

		<h3>4. Description</h3>
		<p class="description" style="margin-top:-4px">Everything about the event, in the editor below: what happens, what to bring, who it is for. You can add photos and lists.</p>
	</div></div>
	<?php
} );

add_action( 'save_post_zv_event', function ( $id ) {
	if ( ! isset( $_POST['zv_event_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['zv_event_nonce'] ), 'zv_event' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$in = (array) wp_unslash( $_POST['zv_event'] ?? array() );
	$dt = fn( $v ) => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string) $v ) ? $v : '';
	update_post_meta( $id, 'zv_event', array(
		'image'    => esc_url_raw( $in['image'] ?? '' ),
		'label'    => sanitize_text_field( $in['label'] ?? '' ),
		'summary'  => sanitize_textarea_field( $in['summary'] ?? '' ),
		'start'    => $dt( $in['start'] ?? '' ),
		'end'      => $dt( $in['end'] ?? '' ),
		'location' => sanitize_text_field( $in['location'] ?? '' ),
		'price'    => sanitize_text_field( $in['price'] ?? '' ),
		'places'   => sanitize_text_field( $in['places'] ?? '' ),
		'soldout'  => empty( $in['soldout'] ) ? '' : '1',
		'btn'      => sanitize_text_field( $in['btn'] ?? '' ),
		'url'      => esc_url_raw( $in['url'] ?? '' ),
	) );
	update_post_meta( $id, 'zv_hidden', empty( $_POST['zv_event_visible'] ) ? '1' : '' );
	update_post_meta( $id, 'zv_event_start', zv_popup_time( $dt( $in['start'] ?? '' ) ) ); // for sorting the admin list
} );

add_action( 'admin_notices', function () {
	$s = get_current_screen();
	if ( $s && 'zv_event' === $s->id && ! empty( $_GET['post'] ) ) { // phpcs:ignore
		$e = zv_event( absint( $_GET['post'] ) ); // phpcs:ignore
		if ( ! $e['start'] ) {
			echo '<div class="notice notice-warning"><p>This event has no start date yet, so it shows as "Date to be announced".</p></div>';
		} elseif ( $e['end'] && zv_popup_time( $e['end'] ) < zv_popup_time( $e['start'] ) ) {
			echo '<div class="notice notice-error"><p><b>The end is before the start.</b> Please check the dates.</p></div>';
		}
	}
} );

add_filter( 'post_updated_messages', function ( $m ) {
	global $post;
	$view = $post ? ' <a href="' . esc_url( get_permalink( $post ) ) . '">View event</a>' : '';
	$m['zv_event'] = array_fill( 0, 11, 'Event saved.' . $view );
	$m['zv_event'][6] = 'Event published.' . $view;
	return $m;
} );

add_filter( 'manage_zv_event_posts_columns', fn( $c ) => array( 'cb' => $c['cb'], 'zv_thumb' => 'Photo', 'title' => 'Event', 'zve_when' => 'When', 'zve_state' => 'Status', 'zv_visible' => 'On website' ) );
add_action( 'manage_zv_event_posts_custom_column', function ( $col, $id ) {
	$e = zv_event( $id );
	if ( 'zv_thumb' === $col ) {
		echo $e['image'] ? '<img class="zv-thumb" src="' . esc_url( $e['image'] ) . '" alt="">' : '<span class="zv-thumb empty"></span>';
	} elseif ( 'zve_when' === $col ) {
		echo esc_html( zv_event_when( $e, true ) );
	} elseif ( 'zve_state' === $col ) {
		$st = array( 'upcoming' => array( 'scheduled', 'Upcoming' ), 'now' => array( 'live', 'On now' ), 'past' => array( 'ended', 'Past' ) )[ zv_event_state( $e ) ];
		echo '<span class="zvp-status zvp-' . esc_attr( $st[0] ) . '">' . esc_html( $st[1] ) . '</span>' . ( $e['soldout'] ? ' <span class="zvp-status zvp-off">Fully booked</span>' : '' );
	} elseif ( 'zv_visible' === $col ) {
		printf( '<label class="zv-switch" title="Show or hide on the website"><input type="checkbox" data-zv-visible="%d" %s><span></span></label>', (int) $id, checked( ! get_post_meta( $id, 'zv_hidden', true ) && 'publish' === get_post_status( $id ), true, false ) );
	}
}, 10, 2 );

// Admin list: newest dates first.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'zv_event' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'meta_key', 'zv_event_start' );
		$q->set( 'orderby', 'meta_value_num' );
		$q->set( 'order', 'DESC' );
	}
} );

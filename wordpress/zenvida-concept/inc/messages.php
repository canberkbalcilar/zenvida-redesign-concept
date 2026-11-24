<?php
/**
 * Contact form and gift card requests → "Messages" in the admin (and an email).
 *
 * Every submission is saved as a Message first, so nothing is lost when the hosting's
 * email is not set up; then a copy is emailed to the address in Site settings.
 * Spam: hidden trap field, minimum fill time, at most 5 messages per hour per visitor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type( 'zv_message', array(
		'labels'       => array(
			'name'          => 'Messages',
			'singular_name' => 'Message',
			'menu_name'     => 'Messages',
			'all_items'     => 'All messages',
			'edit_item'     => 'Message',
			'not_found'     => 'No messages yet. Messages from the contact form and gift card requests appear here.',
			'search_items'  => 'Search messages',
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => false,
		'menu_icon'    => 'dashicons-email-alt',
		'menu_position'=> 4,
		'supports'     => array( 'title' ),
		'capability_type' => 'post',
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

/* ------------------------------------------------------------- receiving */

function zv_message_kinds() {
	return array( 'contact' => 'Contact form', 'gift' => 'Gift card request', 'pack' => 'Pack / membership request', 'job' => 'Job application' );
}

add_action( 'wp_ajax_zv_message', 'zv_receive_message' );
add_action( 'wp_ajax_nopriv_zv_message', 'zv_receive_message' );

function zv_receive_message() {
	$in   = wp_unslash( $_POST ); // phpcs:ignore -- public form; protected by trap field, timing and rate limit
	$fail = fn( $msg, $field = '' ) => wp_send_json_error( array( 'message' => $msg, 'field' => $field ), 400 );

	// Spam traps: a filled hidden field, or a form sent within 3 seconds of loading.
	if ( ! empty( $in['website'] ) || ( isset( $in['ts'] ) && ( time() * 1000 - (int) $in['ts'] ) < 3000 ) ) {
		wp_send_json_success( array( 'message' => zv_opt( 'contact_success' ) ) ); // look successful, save nothing
	}
	$key   = 'zv_msg_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() ); // phpcs:ignore
	$count = (int) get_transient( $key );
	if ( $count >= 5 ) {
		$fail( 'You have sent several messages in a short time. Please try again in an hour, or write to us on WhatsApp.' );
	}

	$kind    = array_key_exists( $in['kind'] ?? '', zv_message_kinds() ) ? $in['kind'] : 'contact';
	$name    = sanitize_text_field( $in['name'] ?? '' );
	$email   = sanitize_email( $in['email'] ?? '' );
	$phone   = sanitize_text_field( $in['phone'] ?? '' );
	$topic   = sanitize_text_field( $in['topic'] ?? '' );
	$message = sanitize_textarea_field( $in['message'] ?? '' );
	if ( '' === $name ) {
		$fail( 'Please add your name.', 'name' );
	}
	if ( ! is_email( $email ) ) {
		$fail( 'Please check your email address.', 'email' );
	}
	if ( 'contact' === $kind && '' === trim( $message ) ) {
		$fail( 'Please write your message.', 'message' );
	}
	if ( empty( $in['consent'] ) ) {
		$fail( 'Please tick the box so we may keep your message to reply to you.', 'consent' );
	}
	$details = array();
	foreach ( (array) ( $in['details'] ?? array() ) as $label => $value ) {
		$label = sanitize_text_field( $label );
		$value = sanitize_textarea_field( is_array( $value ) ? implode( ', ', $value ) : $value );
		if ( '' !== $label && '' !== $value ) {
			$details[ mb_substr( $label, 0, 60 ) ] = mb_substr( $value, 0, 500 );
		}
	}

	$cv = array( '', '' );
	if ( 'job' === $kind && ! empty( $_FILES['cv'] ) ) { // phpcs:ignore
		$cv = zv_store_cv( $_FILES['cv'] ); // phpcs:ignore
		if ( is_wp_error( $cv ) ) {
			$fail( $cv->get_error_message(), 'cv' );
		}
	}

	$kinds = zv_message_kinds();
	$title = $name . ' — ' . ( $topic ?: $kinds[ $kind ] );
	$id    = wp_insert_post( array(
		'post_type'    => 'zv_message',
		'post_status'  => 'publish',
		'post_title'   => mb_substr( $title, 0, 140 ),
		'post_content' => mb_substr( $message, 0, 5000 ),
	) );
	if ( ! $id || is_wp_error( $id ) ) {
		$fail( 'Sorry, something went wrong. Please write to us on WhatsApp.' );
	}
	update_post_meta( $id, 'zv_msg', array(
		'kind'    => $kind,
		'email'   => $email,
		'phone'   => $phone,
		'topic'   => $topic,
		'details' => $details,
		'page'    => esc_url_raw( $in['page'] ?? '' ),
		'consent' => current_time( 'mysql' ),
		'cv_path' => $cv[0],
		'cv_name' => $cv[1],
	) );
	update_post_meta( $id, 'zv_unread', '1' );
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	// Email copy (the message is already saved, so a failing mail server loses nothing).
	$to    = zv_opt( 'contact_to' ) ?: zv_opt( 'email' );
	$lines = array( $kinds[ $kind ], '', 'Name: ' . $name, 'Email: ' . $email );
	if ( $phone ) {
		$lines[] = 'Phone: ' . $phone;
	}
	if ( $topic ) {
		$lines[] = 'Topic: ' . $topic;
	}
	foreach ( $details as $l => $v ) {
		$lines[] = $l . ': ' . $v;
	}
	if ( $message ) {
		array_push( $lines, '', $message );
	}
	array_push( $lines, '', 'Open in WordPress: ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	if ( $cv[0] ) {
		$lines[] = 'CV attached: ' . $cv[1];
	}
	if ( is_email( $to ) ) {
		wp_mail( $to, '[ZenVida] ' . $title, implode( "\n", $lines ), array( 'Reply-To: ' . $name . ' <' . $email . '>' ), $cv[0] ? array( $cv[0] ) : array() );
	}

	$thanks = array( 'gift' => zv_opt( 'gift_success' ), 'job' => zv_opt( 'job_success' ) );
	wp_send_json_success( array( 'message' => $thanks[ $kind ] ?? zv_opt( 'contact_success' ), 'payUrl' => 'gift' === $kind ? zv_opt( 'gift_url' ) : '' ) );
}

/* ------------------------------------------------------------- the form */

add_action( 'init', function () {
	zv_register( 'contact-form', 'Contact form', 'email', array(
		'topics'      => 'Booking question, Gift cards, Groups & events, Feedback, Other',
		'buttonLabel' => 'Send message',
		'showPhone'   => true,
	), 'zv_render_contact_form', array( 'parent' => array( 'zv/section', 'zv/split' ) ) );
}, 20 );

/** Shared bits of the public forms: privacy consent, spam trap. */
function zv_form_common( $consent_id ) {
	$privacy = get_privacy_policy_url();
	return '<label class="zf-consent" for="' . esc_attr( $consent_id ) . '"><input type="checkbox" id="' . esc_attr( $consent_id ) . '" name="consent" value="1" required> <span>I agree that ZenVida keeps my details to reply to me.'
		. ( $privacy ? ' <a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">Privacy policy</a>' : '' ) . '</span></label>'
		. '<div class="zf-trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
		. '<input type="hidden" name="ts" value="">';
}

function zv_forms_script() {
	wp_enqueue_script( 'zv-forms', get_template_directory_uri() . '/assets/js/forms.js', array(), ZV_VERSION . '.' . filemtime( get_template_directory() . '/assets/js/forms.js' ), true );
	wp_localize_script( 'zv-forms', 'ZV_FORMS', array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'whatsapp' => preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ),
	) );
}

function zv_render_contact_form( $a ) {
	zv_forms_script();
	$uid    = 'zc' . wp_rand( 100, 999 );
	$topics = array_filter( array_map( 'trim', explode( ',', (string) $a['topics'] ) ) );
	$out    = '<form class="zform" data-zv-form="contact" novalidate>'
		. '<div class="zf-row"><label>Your name<input type="text" name="name" required autocomplete="name" maxlength="100"></label>'
		. '<label>Email<input type="email" name="email" required autocomplete="email" maxlength="120"></label></div>';
	if ( $a['showPhone'] || $topics ) {
		$out .= '<div class="zf-row">'
			. ( $a['showPhone'] ? '<label>Phone or WhatsApp <small>(optional)</small><input type="tel" name="phone" autocomplete="tel" maxlength="40"></label>' : '' )
			. ( $topics ? '<label>About<select name="topic">' . implode( '', array_map( fn( $t ) => '<option>' . zv_e( $t ) . '</option>', $topics ) ) . '</select></label>' : '' )
			. '</div>';
	}
	return $out . '<label>Message<textarea name="message" rows="5" required maxlength="5000"></textarea></label>'
		. zv_form_common( $uid . '-c' )
		. '<div class="zf-foot"><button class="btn btn-dark" type="submit">' . zv_e( $a['buttonLabel'] ) . '</button><p class="zf-status" role="status" aria-live="polite"></p></div>'
		. '</form>';
}

/* ------------------------------------------------------------ admin inbox */

add_filter( 'manage_zv_message_posts_columns', fn( $c ) => array( 'cb' => $c['cb'], 'title' => 'From', 'zvm_kind' => 'Type', 'zvm_contact' => 'Reply to', 'date' => 'Received' ) );
add_action( 'manage_zv_message_posts_custom_column', function ( $col, $id ) {
	$m = (array) get_post_meta( $id, 'zv_msg', true );
	if ( 'zvm_kind' === $col ) {
		$new = get_post_meta( $id, 'zv_unread', true );
		echo '<span class="zvp-status ' . ( $new ? 'zvp-live' : 'zvp-off' ) . '">' . ( $new ? 'New' : 'Read' ) . '</span> ' . esc_html( zv_message_kinds()[ $m['kind'] ?? 'contact' ] ?? '' );
	} elseif ( 'zvm_contact' === $col ) {
		echo '<a href="mailto:' . esc_attr( $m['email'] ?? '' ) . '">' . esc_html( $m['email'] ?? '' ) . '</a>' . ( ! empty( $m['phone'] ) ? '<br>' . esc_html( $m['phone'] ) : '' );
	}
}, 10, 2 );
add_filter( 'post_class', function ( $classes, $class, $id ) {
	if ( is_admin() && 'zv_message' === get_post_type( $id ) && get_post_meta( $id, 'zv_unread', true ) ) {
		$classes[] = 'zvm-unread';
	}
	return $classes;
}, 10, 3 );
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'zv_message' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = str_replace( '>Edit<', '>Open<', $actions['edit'] );
		}
	}
	return $actions;
}, 10, 2 );

// Unread count next to "Messages" in the menu.
add_action( 'admin_menu', function () {
	global $menu;
	$n = count( get_posts( array( 'post_type' => 'zv_message', 'numberposts' => 99, 'fields' => 'ids', 'meta_key' => 'zv_unread', 'meta_value' => '1' ) ) );
	if ( ! $n ) {
		return;
	}
	foreach ( (array) $menu as $i => $item ) {
		if ( 'edit.php?post_type=zv_message' === ( $item[2] ?? '' ) ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>'; // phpcs:ignore
		}
	}
}, 99 );

// Reading a message: show it plainly, mark it read, offer reply buttons.
add_action( 'add_meta_boxes_zv_message', function ( $post ) {
	remove_meta_box( 'submitdiv', 'zv_message', 'side' );
	add_meta_box( 'zvm_body', 'Message', 'zv_message_box', 'zv_message', 'normal', 'high' );
} );
// Opening a message marks it read (before the menu counts unread ones).
add_action( 'load-post.php', function () {
	$id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore
	if ( $id && 'zv_message' === get_post_type( $id ) && current_user_can( 'edit_post', $id ) ) {
		delete_post_meta( $id, 'zv_unread' );
	}
} );

function zv_message_box( $post ) {
	$m     = wp_parse_args( (array) get_post_meta( $post->ID, 'zv_msg', true ), array( 'kind' => 'contact', 'email' => '', 'phone' => '', 'topic' => '', 'details' => array(), 'page' => '', 'consent' => '' ) );
	$name  = trim( explode( '—', $post->post_title )[0] );
	$reply = 'mailto:' . rawurlencode( $m['email'] ) . '?subject=' . rawurlencode( 'Re: ' . ( $m['topic'] ?: 'your message to ZenVida' ) );
	$wa    = $m['phone'] ? 'https://wa.me/' . preg_replace( '/\D/', '', $m['phone'] ) : '';
	?>
	<div class="zvm">
		<p class="zvm-meta"><b><?php echo esc_html( zv_message_kinds()[ $m['kind'] ] ?? '' ); ?></b> · <?php echo esc_html( get_the_date( 'j F Y, H:i', $post ) ); ?></p>
		<table class="zvm-table">
			<tr><th>Name</th><td><?php echo esc_html( $name ); ?></td></tr>
			<tr><th>Email</th><td><a href="mailto:<?php echo esc_attr( $m['email'] ); ?>"><?php echo esc_html( $m['email'] ); ?></a></td></tr>
			<?php if ( $m['phone'] ) : ?><tr><th>Phone</th><td><?php echo esc_html( $m['phone'] ); ?></td></tr><?php endif; ?>
			<?php if ( $m['topic'] ) : ?><tr><th>About</th><td><?php echo esc_html( $m['topic'] ); ?></td></tr><?php endif; ?>
			<?php foreach ( (array) $m['details'] as $l => $v ) : ?><tr><th><?php echo esc_html( $l ); ?></th><td><?php echo nl2br( esc_html( $v ) ); ?></td></tr><?php endforeach; ?>
			<?php if ( ! empty( $m['cv_name'] ) ) : ?><tr><th>CV</th><td><a class="button button-small" href="<?php echo esc_url( zv_cv_link( $post->ID ) ); ?>">Download <?php echo esc_html( $m['cv_name'] ); ?></a></td></tr><?php endif; ?>
		</table>
		<?php if ( $post->post_content ) : ?><div class="zvm-text"><?php echo nl2br( esc_html( $post->post_content ) ); ?></div><?php endif; ?>
		<p class="zvm-actions">
			<a class="button button-primary" href="<?php echo esc_url( $reply ); ?>">Reply by email</a>
			<?php if ( $wa ) : ?><a class="button" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Reply on WhatsApp</a><?php endif; ?>
			<a class="button-link zv-remove" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">Delete message</a>
		</p>
		<p class="description">Sent from <?php echo $m['page'] ? '<a href="' . esc_url( $m['page'] ) . '" target="_blank">' . esc_html( wp_parse_url( $m['page'], PHP_URL_PATH ) ?: '/' ) . '</a>' : 'the website'; ?>. The visitor agreed to us keeping it on <?php echo esc_html( $m['consent'] ); ?>. Delete messages you no longer need<?php echo ! empty( $m['cv_name'] ) ? ' — deleting it also deletes the CV' : ''; ?>.</p>
	</div>
	<?php
}

// The message title is not editable.
add_action( 'admin_head-post.php', function () {
	if ( 'zv_message' === get_current_screen()->post_type ) {
		echo '<style>#titlediv input{pointer-events:none;background:transparent;border-color:transparent;font-weight:600}#post-body-content{margin-bottom:0}.page-title-action{display:none}</style>';
	}
} );

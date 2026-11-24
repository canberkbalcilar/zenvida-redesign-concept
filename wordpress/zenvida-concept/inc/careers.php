<?php
/**
 * Work with us: job cards and the application form.
 *
 * Applications arrive under Messages (type "Job application") and by email.
 * CVs (PDF/DOC/DOCX, max 5 MB) are personal data: they are NOT put in the media library.
 * They are stored under uploads/zv-private/ in a random folder (web access denied),
 * downloaded by admins through a checked link, and deleted with the message.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$in_section = array( 'parent' => array( 'zv/section' ) );
	zv_register( 'jobs', 'Job openings', 'groups', array(), 'zv_render_jobs', $in_section );
	zv_register( 'job', 'Job', 'id-alt', array(
		'title'    => '',
		'meta'     => '',
		'text'     => '',
		'needs'    => '',
		'open'     => true,
	), 'zv_render_job', array( 'parent' => array( 'zv/jobs' ) ) );
	zv_register( 'job-form', 'Job application form', 'portfolio', array(
		'roles'       => 'Massage therapist, Reflexologist, Reception & guest care, Other / open application',
		'buttonLabel' => 'Send application',
	), 'zv_render_job_form', $in_section );
}, 20 );

function zv_render_jobs( $a, $content ) {
	return '<div class="jobs">' . $content . '</div>';
}

function zv_render_job( $a ) {
	if ( '' === trim( wp_strip_all_tags( $a['title'] ) ) ) {
		return '';
	}
	$needs = array_filter( array_map( 'trim', explode( "\n", (string) $a['needs'] ) ) );
	return '<article class="job' . ( $a['open'] ? '' : ' closed' ) . '">'
		. '<div class="job-head"><h3>' . zv_e( $a['title'] ) . '</h3>'
		. '<span class="job-badge">' . ( $a['open'] ? 'Hiring' : 'Not hiring right now' ) . '</span></div>'
		. ( $a['meta'] ? '<p class="job-meta">' . zv_e( $a['meta'] ) . '</p>' : '' )
		. ( $a['text'] ? '<p>' . zv_k( $a['text'] ) . '</p>' : '' )
		. ( $needs ? '<ul>' . implode( '', array_map( fn( $n ) => '<li>' . zv_icon( 'check' ) . '<span>' . zv_e( $n ) . '</span></li>', $needs ) ) . '</ul>' : '' )
		. ( $a['open'] ? '<a class="link" href="#apply" data-apply-role="' . esc_attr( wp_strip_all_tags( $a['title'] ) ) . '">Apply for this role →</a>' : '' )
		. '</article>';
}

function zv_render_job_form( $a ) {
	zv_forms_script();
	$uid   = 'zj' . wp_rand( 100, 999 );
	$roles = array_filter( array_map( 'trim', explode( ',', (string) $a['roles'] ) ) );
	return '<form class="zform job-form" id="apply" data-zv-form="job" enctype="multipart/form-data" novalidate>'
		. '<div class="zf-row"><label>Your name<input type="text" name="name" required autocomplete="name" maxlength="100"></label>'
		. '<label>Email<input type="email" name="email" required autocomplete="email" maxlength="120"></label></div>'
		. '<div class="zf-row"><label>Phone or WhatsApp<input type="tel" name="phone" autocomplete="tel" maxlength="40"></label>'
		. '<label>Role<select name="topic">' . implode( '', array_map( fn( $r ) => '<option>' . zv_e( $r ) . '</option>', $roles ) ) . '</select></label></div>'
		. '<div class="zf-row"><label>Experience<select data-k="Experience"><option>Less than 1 year</option><option>1–3 years</option><option>3–5 years</option><option>More than 5 years</option></select></label>'
		. '<label>Availability<select data-k="Availability"><option>Full time</option><option>Part time</option><option>Weekends</option><option>Flexible</option></select></label></div>'
		. '<label>Languages you speak<input type="text" data-k="Languages" maxlength="120" placeholder="e.g. Spanish, English"></label>'
		. '<label>Tell us about yourself<textarea name="message" rows="5" maxlength="5000" placeholder="Techniques you master, where you have worked, what you love about this work."></textarea></label>'
		. '<label class="zf-file">CV <small>(optional · PDF or Word, max 5 MB)</small><input type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></label>'
		. zv_form_common( $uid . '-c' )
		. '<div class="zf-foot"><button class="btn btn-dark" type="submit">' . zv_e( $a['buttonLabel'] ) . '</button><p class="zf-status" role="status" aria-live="polite"></p></div>'
		. '</form>';
}

/* ------------------------------------------------------------- CV files */

/** Private folder for CVs, created on first use. */
function zv_private_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'zv-private';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore
		file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore
	}
	return $dir;
}

/** Validate and store an uploaded CV. Returns [path, name] or a WP_Error. */
function zv_store_cv( $file ) {
	if ( empty( $file['tmp_name'] ) || UPLOAD_ERR_NO_FILE === (int) ( $file['error'] ?? 0 ) ) {
		return array( '', '' );
	}
	if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'cv', 'The CV could not be uploaded. Please try again, or send it to us by email.' );
	}
	if ( $file['size'] > 5 * MB_IN_BYTES ) {
		return new WP_Error( 'cv', 'The CV is larger than 5 MB. Please send a smaller file.' );
	}
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	) );
	if ( empty( $check['ext'] ) ) {
		return new WP_Error( 'cv', 'Please send your CV as a PDF or Word file.' );
	}
	$folder = zv_private_dir() . '/' . wp_generate_password( 24, false );
	wp_mkdir_p( $folder );
	$name = sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ) . '.' . $check['ext'];
	$path = $folder . '/' . $name;
	if ( ! move_uploaded_file( $file['tmp_name'], $path ) ) {
		return new WP_Error( 'cv', 'The CV could not be saved. Please send it to us by email.' );
	}
	return array( $path, $name );
}

// Admins download a CV through this checked link.
add_action( 'admin_post_zv_cv', function () {
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	check_admin_referer( 'zv_cv_' . $id );
	if ( ! $id || 'zv_message' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$m    = (array) get_post_meta( $id, 'zv_msg', true );
	$path = (string) ( $m['cv_path'] ?? '' );
	if ( ! $path || 0 !== strpos( realpath( $path ) ?: '', realpath( zv_private_dir() ) ) || ! is_readable( $path ) ) {
		wp_die( 'This file is no longer available.', 404 );
	}
	nocache_headers();
	header( 'Content-Type: ' . ( wp_check_filetype( $path )['type'] ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path ); // phpcs:ignore
	exit;
} );

function zv_cv_link( $id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=zv_cv&id=' . (int) $id ), 'zv_cv_' . (int) $id );
}

// Deleting a message deletes its CV too.
add_action( 'before_delete_post', function ( $id ) {
	if ( 'zv_message' !== get_post_type( $id ) ) {
		return;
	}
	$m    = (array) get_post_meta( $id, 'zv_msg', true );
	$path = (string) ( $m['cv_path'] ?? '' );
	if ( $path && is_file( $path ) && 0 === strpos( realpath( $path ), realpath( zv_private_dir() ) ) ) {
		wp_delete_file( $path );
		@rmdir( dirname( $path ) ); // phpcs:ignore
	}
} );

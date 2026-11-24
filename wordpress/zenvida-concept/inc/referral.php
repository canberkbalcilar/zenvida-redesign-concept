<?php
/**
 * Refer a friend: a form for members to recommend a friend (saved in Messages as
 * "Refer a friend", emailed like the other forms), its page and its terms page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	zv_register( 'referral-form', 'Refer a friend form', 'groups', array(
		'buttonLabel' => 'Send referral',
		'termsLabel'  => 'Terms and conditions apply',
		'termsUrl'    => '',
	), 'zv_render_referral_form', array( 'parent' => array( 'zv/section', 'zv/split' ) ) );
}, 20 );

function zv_render_referral_form( $a ) {
	zv_forms_script();
	$uid   = 'zr' . wp_rand( 100, 999 );
	$terms = $a['termsUrl'] ?: ( ( $p = get_page_by_path( 'refer-a-friend-terms-and-conditions' ) ) ? get_permalink( $p ) : '' );
	return '<form class="zform referral-form" data-zv-form="referral" novalidate>'
		. '<fieldset class="zf-group"><legend>Your details</legend>'
		. '<div class="zf-row"><label>Your name<input type="text" name="name" required autocomplete="name" maxlength="100"></label>'
		. '<label>Email<input type="email" name="email" required autocomplete="email" maxlength="120"></label></div>'
		. '<label>Phone or WhatsApp<input type="tel" name="phone" required autocomplete="tel" maxlength="40"></label>'
		. '</fieldset>'
		. '<fieldset class="zf-group"><legend>Your friend</legend>'
		. '<div class="zf-row"><label>Friend\'s name<input type="text" data-k="Friend\'s name" required autocomplete="off" maxlength="100"></label>'
		. '<label>Friend\'s email<input type="email" data-k="Friend\'s email" required autocomplete="off" maxlength="120"></label></div>'
		. '<label>Friend\'s phone <small>(optional)</small><input type="tel" data-k="Friend\'s phone" autocomplete="off" maxlength="40"></label>'
		. '</fieldset>'
		. zv_form_common( $uid . '-c' )
		. '<div class="zf-foot"><button class="btn btn-dark" type="submit">' . zv_e( $a['buttonLabel'] ) . '</button><p class="zf-status" role="status" aria-live="polite"></p></div>'
		. ( $terms && $a['termsLabel'] ? '<p class="zf-terms"><a href="' . esc_url( $terms ) . '">' . zv_e( $a['termsLabel'] ) . '</a></p>' : '' )
		. '</form>';
}

/** Extra checks for this form, called from zv_receive_message(). */
function zv_referral_check( $in, $fail ) {
	$d = (array) ( $in['details'] ?? array() );
	if ( '' === trim( (string) ( $in['phone'] ?? '' ) ) ) {
		$fail( zv__( 'Please add your phone number.' ), 'phone' );
	}
	if ( '' === trim( (string) ( $d["Friend's name"] ?? '' ) ) ) {
		$fail( zv__( "Please add your friend's name." ) );
	}
	if ( ! is_email( (string) ( $d["Friend's email"] ?? '' ) ) ) {
		$fail( zv__( "Please check your friend's email address." ) );
	}
}

/* ------------------------------------------------------------ starter pages */

function zv_seed_referral_pages() {
	$terms = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Refer a Friend: Terms & Conditions', 'post_name' => 'refer-a-friend-terms-and-conditions', 'post_content' => wp_slash( serialize_blocks( zv_doc( array(
		array( 'p', '<em>Last updated: 1 June 2026</em>' ),
		array( 'h', '1. Referral rewards' ),
		array( 'p', 'ZenVida members may earn referral rewards when they refer eligible new customers who join ZenVida as members. The rewards are:' ),
		array( 'ul', array( 'an extra 10 minutes added to your next Deluxe Reflexology; and', 'one complimentary add-on to any 60-minute full body or facial massage.' ) ),
		array( 'p', 'Referral rewards do not include:' ),
		array( 'ul', array(
			'premium or extended add-ons, including mini-facials, body scrubs or any add-on that extends a treatment by 30 minutes or more;',
			'upgrades, enhancements or benefits already included in or available with your membership; or',
			'any add-on worth more than the standard complimentary add-on allowance set by ZenVida.',
		) ),
		array( 'p', 'ZenVida decides which add-ons qualify as complimentary referral add-ons.' ),
		array( 'h', '2. Who can take part' ),
		array( 'p', 'To earn referral rewards you must be an active ZenVida member and have completed at least one full membership payment.' ),
		array( 'h', '3. Sending a referral' ),
		array( 'p', 'Referrals must be sent by the member using the official ZenVida referral form before the referred person joins. Referrals cannot be sent afterwards, backdated or transferred between members.' ),
		array( 'h', '4. Using your rewards' ),
		array( 'p', 'Rewards belong to the referring member only and cannot be transferred, shared, exchanged or reassigned. Once applied, they cannot be changed.' ),
		array( 'h', '5. Restrictions' ),
		array( 'p', 'Referral rewards:' ),
		array( 'ul', array(
			'cannot be combined with discounted services, special promotions or other offers;',
			'cannot be exchanged for cash, credit, discounts or other goods or services; and',
			'are subject to availability and our booking conditions.',
		) ),
		array( 'h', '6. Eligible referrals' ),
		array( 'p', 'Rewards apply only when the referred person is a genuine new ZenVida member. They do not apply when the referred person:' ),
		array( 'ul', array(
			'held a ZenVida membership in the previous six months; or',
			'joins through a duplicate, replacement or related account created to obtain referral rewards.',
		) ),
		array( 'p', 'Both the referring member and the new member must meet all the conditions.' ),
		array( 'h', '7. When rewards are given' ),
		array( 'p', 'Rewards are given once the new member has completed their first full membership payment and ZenVida has received it.' ),
		array( 'h', '8. Misuse' ),
		array( 'p', 'ZenVida may refuse, withdraw or cancel rewards if it reasonably believes the referral process has been misused, manipulated or used dishonestly.' ),
		array( 'h', '9. Changes' ),
		array( 'p', 'ZenVida may change, pause or end referral rewards at any time. Changes affecting rewards already earned will be communicated where required.' ),
		array( 'p', 'See also our <a href="' . esc_url( home_url( '/terms-of-use/' ) ) . '">terms of use</a>. Questions? <a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact us</a>.' ),
	) ) ) ) ) );

	$step  = fn( $t, $x ) => zv_b( 'zv/step', array( 'title' => $t, 'text' => $x ) );
	$refer = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Refer a Friend', 'post_name' => 'refer-a-friend', 'post_content' => wp_slash( serialize_blocks( array(
		zv_sec( array( 'eyebrow' => 'Refer a friend', 'title' => 'Share the <em>calm</em>', 'text' => 'Members: recommend ZenVida to family and friends. For every friend who becomes a member, you get an extra 10 minutes on your next Deluxe Reflexology and a free add-on to a 60-minute full body or facial massage.' ), array(
			zv_b( 'zv/steps', array(), array(
				$step( 'Send the form', 'Your details and your friend\'s, before they join.' ),
				$step( 'We call your friend', 'One of our membership advisors gets in touch with them.' ),
				$step( 'They join', 'After their first membership payment, your reward is ready.' ),
			) ),
		) ),
		zv_sec( array( 'anchor' => 'form', 'background' => 'cream', 'eyebrow' => 'Your referral', 'title' => 'Tell us who to invite' ), array( zv_b( 'zv/referral-form' ) ) ),
	) ) ) ) );

	return array( 'refer' => $refer, 'terms' => $terms );
}

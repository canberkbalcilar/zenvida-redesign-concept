<?php
/**
 * Offers & Packs and Gift Cards: pricing cards and the gift card builder.
 * Both pages are ordinary pages made of blocks, so every price, line and button is editable.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	$in_section = array( 'parent' => array( 'zv/section' ) );
	zv_register( 'packs', 'Pricing cards', 'money-alt', array(), 'zv_render_packs', $in_section );
	zv_register( 'pack', 'Pricing card', 'tickets-alt', array(
		'kicker'    => '',
		'title'     => '',
		'price'     => '',
		'was'       => '',
		'save'      => '',
		'items'     => '',
		'btnLabel'  => 'Get this pack',
		'btnUrl'    => '',
		'note'      => '',
		'featured'  => false,
	), 'zv_render_pack', array( 'parent' => array( 'zv/packs' ) ) );
	zv_register( 'gift-builder', 'Gift card builder', 'buttons', array(
		'amounts'   => '25, 50, 75, 100, 150',
		'btnLabel'  => 'Request my gift card',
		'note'      => 'We email you a secure payment link within the hour (10:00–21:00). The card is valid for 12 months.',
	), 'zv_render_gift_builder', $in_section );
}, 20 );

function zv_render_packs( $a, $content ) {
	return '<div class="packs">' . $content . '</div>';
}

function zv_render_pack( $a ) {
	$items = array_filter( array_map( 'trim', explode( "\n", (string) $a['items'] ) ) );
	$name  = trim( wp_strip_all_tags( $a['kicker'] . ' ' . $a['title'] ) );
	$url   = $a['btnUrl'] ?: 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) . '?text=' . rawurlencode( "Hi! I'd like the " . $name . '.' );
	$ext   = 0 !== strpos( $url, home_url() ) ? ' target="_blank" rel="noopener"' : '';
	return '<div class="pack' . ( $a['featured'] ? ' featured' : '' ) . '">'
		. ( $a['save'] ? '<span class="pack-save">' . zv_e( $a['save'] ) . '</span>' : '' )
		. ( $a['kicker'] ? '<span class="k">' . zv_e( $a['kicker'] ) . '</span>' : '' )
		. ( $a['title'] ? '<h3>' . zv_e( $a['title'] ) . '</h3>' : '' )
		. '<div class="pack-price"><b>' . zv_e( $a['price'] ) . '</b>' . ( $a['was'] ? '<s>' . zv_e( $a['was'] ) . '</s>' : '' ) . '</div>'
		. ( $items ? '<ul>' . implode( '', array_map( fn( $i ) => '<li>' . zv_icon( 'check' ) . '<span>' . zv_e( $i ) . '</span></li>', $items ) ) . '</ul>' : '' )
		. zv_btn( $a['btnLabel'], $url, $a['featured'] ? 'sand' : 'dark', $ext )
		. ( $a['note'] ? '<small>' . zv_e( $a['note'] ) . '</small>' : '' )
		. '</div>';
}

function zv_render_gift_builder( $a ) {
	zv_forms_script();
	$amounts = array_values( array_filter( array_map( 'absint', explode( ',', (string) $a['amounts'] ) ) ) );
	$uid     = 'zg' . wp_rand( 100, 999 );
	$options = '';
	foreach ( zv_areas() as $slug => $ar ) {
		$group = array_filter( zv_all_rituals(), fn( $r ) => $r['area'] === $slug );
		if ( ! $group ) {
			continue;
		}
		$options .= '<optgroup label="' . esc_attr( $ar['name'] ) . '">';
		foreach ( $group as $r ) {
			foreach ( $r['opts'] as $o ) {
				if ( null === $o[1] ) {
					continue;
				}
				$two      = in_array( 'two', $r['flags'], true );
				$label    = $r['name'] . ' · ' . $o[0] . ' min' . ( $two ? ' · for two' : '' );
				$options .= '<option value="' . esc_attr( $label ) . '" data-price="' . (int) $o[1] . '">' . esc_html( $label . ' · €' . $o[1] ) . '</option>';
			}
		}
		$options .= '</optgroup>';
	}
	$chips = implode( '', array_map( fn( $v, $i ) => '<button type="button" class="chip" data-amount="' . (int) $v . '" aria-pressed="' . ( 1 === $i ? 'true' : 'false' ) . '">€' . (int) $v . '</button>', $amounts, array_keys( $amounts ) ) );

	return '<div class="gift">'
		. '<form class="zform gift-form" data-zv-form="gift" novalidate>'
		. '<fieldset><legend><span>1</span>What would you like to give?</legend>'
		. '<div class="seg gift-type" role="radiogroup"><button type="button" role="radio" aria-checked="true" data-type="amount">An amount</button><button type="button" role="radio" aria-checked="false" data-type="ritual">A ritual</button></div>'
		. '<div class="gift-amount"><div class="chips">' . $chips . '<label class="gift-other">Other €<input type="number" min="10" max="1000" step="5" inputmode="numeric" aria-label="Other amount in euros"></label></div></div>'
		. '<label class="gift-ritual" hidden>Ritual<select>' . $options . '</select></label>'
		. '</fieldset>'
		. '<fieldset><legend><span>2</span>Make it personal</legend>'
		. '<div class="zf-row"><label>For<input type="text" data-k="To" maxlength="60" placeholder="Their name"></label><label>From<input type="text" data-k="From" maxlength="60" placeholder="Your name"></label></div>'
		. '<label>Message on the card <small>(optional)</small><textarea data-k="Card message" rows="3" maxlength="200" placeholder="Happy birthday! Time for you."></textarea></label>'
		. '<div class="seg gift-send" role="radiogroup"><button type="button" role="radio" aria-checked="true" data-send="me">Email it to me</button><button type="button" role="radio" aria-checked="false" data-send="them">Email it to them</button></div>'
		. '<div class="zf-row gift-them" hidden><label>Their email<input type="email" data-k="Recipient email" maxlength="120"></label><label>Send on<input type="date" data-k="Send on"></label></div>'
		. '</fieldset>'
		. '<fieldset><legend><span>3</span>Your details</legend>'
		. '<div class="zf-row"><label>Your name<input type="text" name="name" required autocomplete="name" maxlength="100"></label><label>Your email<input type="email" name="email" required autocomplete="email" maxlength="120"></label></div>'
		. '<label>Phone or WhatsApp <small>(optional)</small><input type="tel" name="phone" autocomplete="tel" maxlength="40"></label>'
		. zv_form_common( $uid . '-c' )
		. '</fieldset>'
		. '<div class="gift-total"><span>Gift card value</span><b data-gift-total>€' . (int) ( $amounts[1] ?? $amounts[0] ?? 50 ) . '</b></div>'
		. '<div class="zf-foot"><button class="btn btn-dark" type="submit">' . zv_e( $a['btnLabel'] ) . '</button><a class="gift-wa" href="#" data-gift-wa target="_blank" rel="noopener">or ask on WhatsApp</a><p class="zf-status" role="status" aria-live="polite"></p></div>'
		. ( $a['note'] ? '<p class="gift-note">' . zv_e( $a['note'] ) . '</p>' : '' )
		. '</form>'
		. '<div class="gift-preview" aria-hidden="true"><div class="gcard">'
		. '<div class="gc-top"><span class="gc-logo">ZENVIDA<small>FOOT &amp; BODY</small></span><span class="gc-k">Gift card</span></div>'
		. '<div class="gc-what" data-gp="what">€' . (int) ( $amounts[1] ?? $amounts[0] ?? 50 ) . '</div>'
		. '<p class="gc-msg" data-gp="msg">Your message appears here.</p>'
		. '<div class="gc-foot"><span>For <b data-gp="to">…</b></span><span>From <b data-gp="from">…</b></span></div>'
		. '</div><p class="gift-preview-note">Preview of the card we email.</p></div>'
		. '</div>';
}

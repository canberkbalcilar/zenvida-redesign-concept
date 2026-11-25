<?php
/**
 * Single ritual page, e.g. /rituals/feet-legs/deluxe-reflexology/.
 * The booking card is driven by the "Ritual details" fields; the sections
 * below it come from the post content.
 */
the_post();
$zv_r      = zv_ritual_data( get_post() );
$zv_area   = get_the_terms( get_post(), 'area' );
$zv_area   = ( $zv_area && ! is_wp_error( $zv_area ) ) ? $zv_area[0] : null;
$zv_o      = $zv_r['opts'][0];
$zv_two    = in_array( 'two', $zv_r['flags'], true );
$zv_sig    = in_array( 'sig', $zv_r['flags'], true );
$zv_img    = get_post_meta( get_the_ID(), 'zv_image', true ) ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1600, 1000 );
$zv_mins   = array_column( $zv_r['opts'], 0 );
$zv_length = count( $zv_mins ) > 1 ? min( $zv_mins ) . '–' . max( $zv_mins ) . ' minutes' : $zv_mins[0] . ' minutes';
$zv_extra  = array_filter( array( get_post_meta( get_the_ID(), 'zv_gallery_1', true ), get_post_meta( get_the_ID(), 'zv_gallery_2', true ) ) );
$zv_lead   = get_post_meta( get_the_ID(), 'zv_lead', true ) ?: get_the_excerpt();
$zv_wa     = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
$zv_tel    = 'tel:' . preg_replace( '/[^\d+]/', '', zv_opt( 'phone' ) );
$zv_hl     = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( get_the_ID(), 'zv_highlights', true ) ) ) );
$zv_unit   = $zv_two ? 'for two' : '/ person';
// Booking: the live booking widget, a WhatsApp card (ritual not bookable online), or the prototype card (online booking off).
$zv_mode   = ! zv_widget_on() ? 'demo' : ( get_post_meta( get_the_ID(), 'zv_widget', true ) ? 'widget' : 'contact' );
$zv_wa_msg = $zv_wa . '?text=' . rawurlencode( zv__( "Hi! I'd like to book {ritual}.", array( 'ritual' => zv_tr( get_the_title() ) ) ) );

get_header();
?>
<main id="main">
<?php if ( $zv_r['hidden'] ) : ?><div class="zv-hidden-note">This ritual is switched off. Only you can see it, because you are logged in. <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ritual' ) ); ?>">Switch it on</a></div><?php endif; ?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( get_post_type_archive_link( 'ritual' ) ); ?>">Rituals</a><span>/</span><?php if ( $zv_area ) : ?><a href="<?php echo esc_url( get_term_link( $zv_area ) ); ?>"><?php echo esc_html( $zv_area->name ); ?></a><span>/</span><?php endif; ?><?php the_title(); ?></nav>

  <div class="product">
    <div>
      <div class="gallery">
        <figure>
          <?php echo zv_image( $zv_img, array( 'sizes' => ZV_SIZES_MAIN, 'eager' => true, 'alt' => get_the_title() . ' at ZenVida' ) ); // phpcs:ignore ?>
          <span class="tag"><?php echo $zv_sig ? 'Signature ritual' : esc_html( $zv_r['areaName'] ); ?></span>
        </figure>
        <?php foreach ( $zv_extra as $zv_x ) : ?>
        <figure><?php echo zv_image( $zv_x, array( 'sizes' => '(max-width: 1040px) 50vw, 30vw' ) ); // phpcs:ignore ?></figure>
        <?php endforeach; ?>
      </div>

      <div class="intro">
        <span class="eyebrow"><?php echo esc_html( $zv_r['areaName'] ); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php echo zv_ritual_rating_line(); // phpcs:ignore ?>
        <?php if ( $zv_lead ) : ?><p class="lead"><?php echo esc_html( $zv_lead ); ?></p><?php endif; ?>
        <div class="meta">
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?php echo esc_html( $zv_length ); ?></span>
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><?php echo esc_html( zv_opt( 'book_location' ) ); ?></span>
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3 3 0 0 1 0 6M21 20c0-2.6-1.6-4.8-4-5.6"/></svg><?php echo esc_html( zv_opt( $zv_two ? 'book_couples' : 'book_group' ) ); ?></span>
        </div>
        <?php if ( $zv_hl ) : ?>
        <ul class="highlights">
          <?php foreach ( $zv_hl as $zv_line ) : $zv_p = array_map( 'trim', explode( '|', $zv_line, 2 ) ); ?>
          <li><b><?php echo esc_html( $zv_p[0] ); ?></b><?php echo esc_html( $zv_p[1] ?? '' ); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <?php if ( 'widget' === $zv_mode ) : ?>
    <aside class="book has-widget" id="book" data-mbar-anchor aria-label="Book <?php echo esc_attr( get_the_title() ); ?>">
      <?php if ( count( $zv_r['opts'] ) > 1 ) : ?>
      <div class="book-durs">
        <div class="field-label">Duration <span>Choose your length</span></div>
        <div class="baths" role="radiogroup" aria-label="Duration" data-widget-durations>
          <?php foreach ( $zv_r['opts'] as $zv_j => $zv_op ) : ?>
          <button class="bath dur" role="radio" aria-checked="<?php echo 0 === $zv_j ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $zv_j ? '0' : '-1'; ?>" data-service="<?php echo esc_attr( zv_widget_slug( get_the_ID(), $zv_op[0] ) ); ?>"><b><?php echo (int) $zv_op[0]; ?> minutes</b><i><?php echo esc_html( zv_eur( $zv_op[1] ) . ' ' . $zv_unit ); ?></i></button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="zvw-frame"><?php echo zv_widget_tag( array( 'service' => zv_widget_slug( get_the_ID(), $zv_o[0] ) ) ); // phpcs:ignore ?></div>
      <div class="book-foot">
        <a href="<?php echo esc_url( $zv_wa ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.5-.3z"/></svg>Ask on WhatsApp</a>
        <a href="<?php echo esc_url( $zv_tel ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Call us</a>
      </div>
    </aside>
    <?php elseif ( 'contact' === $zv_mode ) : ?>
    <aside class="book" id="book" data-mbar-anchor aria-label="Book <?php echo esc_attr( get_the_title() ); ?>">
      <div class="book-head">
        <div class="price"><?php echo esc_html( zv_eur( $zv_o[1] ) ); ?><small><?php echo esc_html( $zv_unit . ' · ' . $zv_o[0] . ' min' ); ?></small></div>
      </div>
      <div class="book-body">
        <?php if ( count( $zv_r['opts'] ) > 1 ) : ?>
        <ul class="book-prices">
          <?php foreach ( $zv_r['opts'] as $zv_op ) : ?>
          <li><span><?php echo (int) $zv_op[0]; ?> minutes</span><b><?php echo esc_html( zv_eur( $zv_op[1] ) . ' ' . $zv_unit ); ?></b></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p class="book-note">Book this ritual with our team on WhatsApp. We usually reply within minutes.</p>
        <a class="btn btn-dark" href="<?php echo esc_url( $zv_wa_msg ); ?>" target="_blank" rel="noopener">Book on WhatsApp</a>
      </div>
      <div class="book-foot">
        <a href="<?php echo esc_url( $zv_wa ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.5-.3z"/></svg>Ask on WhatsApp</a>
        <a href="<?php echo esc_url( $zv_tel ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Call us</a>
      </div>
    </aside>
    <?php else : ?>
    <aside class="book" id="book" data-mbar-anchor aria-label="Book <?php echo esc_attr( get_the_title() ); ?>">
      <div class="book-head">
        <div class="price" id="priceMain"><?php echo esc_html( zv_eur( $zv_o[1] ) ); ?><small><?php echo esc_html( $zv_unit . ' · ' . $zv_o[0] . ' min' ); ?></small></div>
        <span class="dep" id="depHead">€<?php echo (int) zv_deposit( $zv_o[1], $zv_r['deposit'] ); ?> deposit<?php echo $zv_two ? '' : ' / person'; ?></span>
      </div>
      <div class="book-body">
        <?php if ( count( $zv_r['opts'] ) > 1 ) : ?>
        <div>
          <div class="field-label">Duration <span>Choose your length</span></div>
          <div class="baths" role="radiogroup" aria-label="Duration" id="durations">
            <?php foreach ( $zv_r['opts'] as $zv_j => $zv_op ) : ?>
            <button class="bath dur" role="radio" aria-checked="<?php echo 0 === $zv_j ? 'true' : 'false'; ?>" data-i="<?php echo (int) $zv_j; ?>"><b><?php echo (int) $zv_op[0]; ?> minutes</b><i><?php echo esc_html( zv_eur( $zv_op[1] ) . ' ' . $zv_unit ); ?></i></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ( $zv_r['baths'] && zv_baths() ) : ?>
        <div>
          <div class="field-label">Foot bath upgrade <span>Paid at the studio</span></div>
          <div class="baths" role="radiogroup" aria-label="Foot bath upgrade" id="bathGroup">
            <button class="bath" role="radio" aria-checked="true" data-bath="none" data-price="0"><b>Classic</b><i><?php echo esc_html( zv_opt( 'bath_classic' ) ); ?></i><em>Incl.</em></button>
            <?php foreach ( zv_baths() as $zv_bath ) : ?>
            <button class="bath" role="radio" aria-checked="false" data-bath="<?php echo esc_attr( $zv_bath['name'] ); ?>" data-price="<?php echo (int) $zv_bath['price']; ?>"><b><span class="dot" style="background:<?php echo esc_attr( $zv_bath['color'] ); ?>"></span><?php echo esc_html( $zv_bath['name'] ); ?></b><i><?php echo esc_html( $zv_bath['text'] ); ?></i><em><?php echo (int) $zv_bath['price'] ? '+€' . (int) $zv_bath['price'] : 'Free'; ?></em></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ( ! $zv_two ) : ?>
        <div>
          <div class="field-label">Guests <span>Book together, side by side</span></div>
          <div class="stepper">
            <output id="guests" aria-live="polite">1 guest</output>
            <div class="ctrl">
              <button id="gMinus" aria-label="Fewer guests" disabled>−</button>
              <button id="gPlus" aria-label="More guests">+</button>
            </div>
          </div>
          <p class="group-note" id="groupNote" hidden>Groups of two or more are arranged by our reception team, so everyone is treated at the same time. Send us a WhatsApp and we'll find side-by-side times.</p>
        </div>
        <?php endif; ?>

        <div>
          <div class="field-label">Date &amp; time <span id="dayHours"></span></div>
          <div class="days" id="days" role="group" aria-label="Choose a day"></div>
          <div class="slots" id="slots" role="group" aria-label="Choose a time" aria-live="polite"></div>
          <button class="more-slots" id="moreSlots" aria-controls="slots" aria-expanded="false">Show all times</button>
        </div>

        <div class="summary" aria-live="polite">
          <div><span id="sLine"></span><span id="sBase"></span></div>
          <div id="sBathRow" hidden><span id="sBath">Foot bath</span><span id="sBathP">+€5</span></div>
          <div class="muted"><span id="sWhen">Pick a time</span><span></span></div>
          <div class="total"><span>Due today (deposit)</span><span id="sDep"></span></div>
          <div class="muted"><span>Pay at the studio</span><span id="sRest"></span></div>
        </div>

        <button class="btn btn-dark" id="cta">Choose a time to continue</button>
        <p class="fine"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><?php echo esc_html( zv_opt( 'book_fine' ) ); ?></p>
      </div>
      <div class="book-foot">
        <a href="<?php echo esc_url( $zv_wa ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.5-.3z"/></svg>Ask on WhatsApp</a>
        <a href="<?php echo esc_url( $zv_tel ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Call us</a>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</div>

<?php the_content(); ?>
<?php echo empty( $GLOBALS['zv_ritual_reviews_done'] ) ? zv_ritual_reviews_section( get_the_ID() ) : ''; // phpcs:ignore ?>
</main>

<div class="mbar" id="mbar">
  <div class="p"><b><?php echo esc_html( zv_eur( $zv_o[1] ) ); ?></b><small><?php echo (int) $zv_o[0]; ?> min · €<?php echo (int) zv_deposit( $zv_o[1], $zv_r['deposit'] ); ?> deposit</small></div>
  <a class="btn btn-dark" href="<?php echo 'contact' === $zv_mode ? esc_url( $zv_wa_msg ) : '#book'; ?>"<?php echo 'contact' === $zv_mode ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo 'contact' === $zv_mode ? 'Book on WhatsApp' : 'Book a time'; ?></a>
</div>
<?php
get_footer();

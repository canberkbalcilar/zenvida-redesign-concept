<?php
/**
 * Single ritual page, e.g. /rituals/feet-legs/deluxe-reflexology/.
 * The booking card is driven by the "Ritual details" fields; the sections
 * below it come from the post content.
 */
the_post();
$r      = zv_ritual_data( get_post() );
$area   = get_the_terms( get_post(), 'area' );
$area   = ( $area && ! is_wp_error( $area ) ) ? $area[0] : null;
$o      = $r['opts'][0];
$two    = in_array( 'two', $r['flags'], true );
$sig    = in_array( 'sig', $r['flags'], true );
$img    = get_post_meta( get_the_ID(), 'zv_image', true ) ?: zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1600, 1000 );
$mins   = array_column( $r['opts'], 0 );
$length = count( $mins ) > 1 ? min( $mins ) . '–' . max( $mins ) . ' minutes' : $mins[0] . ' minutes';
$extra  = array_filter( array( get_post_meta( get_the_ID(), 'zv_gallery_1', true ), get_post_meta( get_the_ID(), 'zv_gallery_2', true ) ) );
$lead   = get_post_meta( get_the_ID(), 'zv_lead', true ) ?: get_the_excerpt();
$wa     = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
$tel    = 'tel:' . preg_replace( '/[^\d+]/', '', zv_opt( 'phone' ) );
$hl     = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( get_the_ID(), 'zv_highlights', true ) ) ) );
$unit   = $two ? 'for two' : '/ person';

get_header();
?>
<main>
<?php if ( $r['hidden'] ) : ?><div class="zv-hidden-note">This ritual is switched off — only you can see it because you are logged in. <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ritual' ) ); ?>">Switch it on</a></div><?php endif; ?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( get_post_type_archive_link( 'ritual' ) ); ?>">Rituals</a><span>/</span><?php if ( $area ) : ?><a href="<?php echo esc_url( get_term_link( $area ) ); ?>"><?php echo esc_html( $area->name ); ?></a><span>/</span><?php endif; ?><?php the_title(); ?></nav>

  <div class="product">
    <div>
      <div class="gallery">
        <figure>
          <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> at ZenVida" onerror="this.remove()">
          <span class="tag"><?php echo $sig ? 'Signature ritual' : esc_html( $r['areaName'] ); ?></span>
        </figure>
        <?php foreach ( $extra as $x ) : ?>
        <figure><img src="<?php echo esc_url( $x ); ?>" alt="" loading="lazy" onerror="this.remove()"></figure>
        <?php endforeach; ?>
      </div>

      <div class="intro">
        <span class="eyebrow"><?php echo esc_html( $r['areaName'] ); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php if ( $lead ) : ?><p class="lead"><?php echo esc_html( $lead ); ?></p><?php endif; ?>
        <div class="meta">
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?php echo esc_html( $length ); ?></span>
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><?php echo esc_html( zv_opt( 'book_location' ) ); ?></span>
          <span class="chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3 3 0 0 1 0 6M21 20c0-2.6-1.6-4.8-4-5.6"/></svg><?php echo esc_html( zv_opt( $two ? 'book_couples' : 'book_group' ) ); ?></span>
        </div>
        <?php if ( $hl ) : ?>
        <ul class="highlights">
          <?php foreach ( $hl as $line ) : $p = array_map( 'trim', explode( '|', $line, 2 ) ); ?>
          <li><b><?php echo esc_html( $p[0] ); ?></b><?php echo esc_html( $p[1] ?? '' ); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>

    <aside class="book" id="book" data-mbar-anchor aria-label="Book <?php echo esc_attr( get_the_title() ); ?>">
      <div class="book-head">
        <div class="price" id="priceMain"><?php echo esc_html( zv_eur( $o[1] ) ); ?><small><?php echo esc_html( $unit . ' · ' . $o[0] . ' min' ); ?></small></div>
        <span class="dep">€<?php echo (int) $r['deposit']; ?> deposit today</span>
      </div>
      <div class="book-body">
        <?php if ( count( $r['opts'] ) > 1 ) : ?>
        <div>
          <div class="field-label">Duration <span>Choose your length</span></div>
          <div class="baths" role="radiogroup" aria-label="Duration" id="durations">
            <?php foreach ( $r['opts'] as $j => $op ) : ?>
            <button class="bath dur" role="radio" aria-checked="<?php echo 0 === $j ? 'true' : 'false'; ?>" data-i="<?php echo (int) $j; ?>"><b><?php echo (int) $op[0]; ?> minutes</b><i><?php echo esc_html( zv_eur( $op[1] ) . ' ' . $unit ); ?></i></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ( $r['baths'] && zv_baths() ) : ?>
        <div>
          <div class="field-label">Foot bath upgrade <span>Paid at the studio</span></div>
          <div class="baths" role="radiogroup" aria-label="Foot bath upgrade" id="bathGroup">
            <button class="bath" role="radio" aria-checked="true" data-bath="none" data-price="0"><b>Classic</b><i><?php echo esc_html( zv_opt( 'bath_classic' ) ); ?></i><em>Incl.</em></button>
            <?php foreach ( zv_baths() as $bath ) : ?>
            <button class="bath" role="radio" aria-checked="false" data-bath="<?php echo esc_attr( $bath['name'] ); ?>" data-price="<?php echo (int) $bath['price']; ?>"><b><span class="dot" style="background:<?php echo esc_attr( $bath['color'] ); ?>"></span><?php echo esc_html( $bath['name'] ); ?></b><i><?php echo esc_html( $bath['text'] ); ?></i><em><?php echo (int) $bath['price'] ? '+€' . (int) $bath['price'] : 'Free'; ?></em></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ( ! $two ) : ?>
        <div>
          <div class="field-label">Guests <span>Book together, side by side</span></div>
          <div class="stepper">
            <output id="guests" aria-live="polite">1 guest</output>
            <div class="ctrl">
              <button id="gMinus" aria-label="Fewer guests" disabled>−</button>
              <button id="gPlus" aria-label="More guests">+</button>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div>
          <div class="field-label">Date &amp; time <span id="dayHours"></span></div>
          <div class="days" id="days" role="group" aria-label="Choose a day"></div>
          <div class="slots" id="slots" role="group" aria-label="Choose a time"></div>
          <button class="more-slots" id="moreSlots">Show all times</button>
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
        <a href="<?php echo esc_url( $wa ); ?>"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.1 5.1 0 0 0 1.1 2.7 11.6 11.6 0 0 0 4.4 3.9c1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.1-1.2l-.5-.3z"/></svg>Ask on WhatsApp</a>
        <a href="<?php echo esc_url( $tel ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Call us</a>
      </div>
    </aside>
  </div>
</div>

<?php the_content(); ?>
</main>

<div class="mbar" id="mbar">
  <div class="p"><b><?php echo esc_html( zv_eur( $o[1] ) ); ?></b><small><?php echo (int) $o[0]; ?> min · €<?php echo (int) $r['deposit']; ?> deposit</small></div>
  <a class="btn btn-dark" href="#book">Book a time</a>
</div>
<?php
get_footer();

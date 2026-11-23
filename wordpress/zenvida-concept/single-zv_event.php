<?php
/**
 * One event: photo and description on the left, a card with date, place, price
 * and the booking button on the right (below on phones), then more events.
 */
the_post();
$zv_e      = zv_event( get_the_ID() );
$zv_state  = zv_event_state( $zv_e );
$zv_past   = 'past' === $zv_state;
$zv_when   = zv_event_when( $zv_e );
$zv_place  = zv_event_location( $zv_e );
$zv_book   = zv_event_button( get_post(), $zv_e );
$zv_label  = $zv_e['btn'] ?: 'Reserve your place';
$zv_wa     = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
$zv_tel    = 'tel:' . preg_replace( '/[^\d+]/', '', zv_opt( 'phone' ) );
$zv_more   = zv_events( 'upcoming', 3, get_the_ID() );
$zv_ext    = 0 !== strpos( $zv_book, home_url() ) ? ' target="_blank" rel="noopener"' : '';
$zv_cta    = ( $zv_past || $zv_e['soldout'] ) ? '' : '<a class="btn btn-dark" href="' . esc_url( $zv_book ) . '"' . $zv_ext . '>' . zv_e( $zv_label ) . '</a>';
$zv_icon   = fn( $p ) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' . $p . '</svg>';

get_header();
?>
<main>
<?php if ( get_post_meta( get_the_ID(), 'zv_hidden', true ) ) : ?><div class="zv-hidden-note">This event is switched off — only you can see it because you are logged in.</div><?php endif; ?>
<?php if ( $zv_past ) : ?><div class="ev-ended">This event has finished. <a href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">See what's coming up →</a></div><?php endif; ?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">Events</a><span>/</span><?php the_title(); ?></nav>

  <div class="product ev-single">
    <div>
      <?php if ( $zv_e['image'] ) : ?>
      <div class="gallery"><figure><?php echo zv_image( $zv_e['image'], array( 'sizes' => ZV_SIZES_MAIN, 'eager' => true, 'alt' => get_the_title() ) ); // phpcs:ignore ?><?php if ( $zv_e['label'] ) : ?><span class="tag"><?php echo zv_e( $zv_e['label'] ); ?></span><?php endif; ?></figure></div>
      <?php endif; ?>
      <div class="intro">
        <span class="eyebrow"><?php echo zv_e( zv_event_when( $zv_e, true ) ); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php if ( $zv_e['summary'] ) : ?><p class="lead"><?php echo zv_e( $zv_e['summary'] ); ?></p><?php endif; ?>
        <div class="ev-content"><?php the_content(); ?></div>
      </div>
    </div>

    <aside class="book ev-book" id="book" data-mbar-anchor aria-label="Event details">
      <div class="book-body">
        <dl class="ev-facts">
          <div><dt><?php echo $zv_icon( '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>' ); // phpcs:ignore ?>When</dt><dd><?php echo zv_e( $zv_when ); ?></dd></div>
          <div><dt><?php echo $zv_icon( '<path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>' ); // phpcs:ignore ?>Where</dt><dd><?php echo zv_e( $zv_place ); ?><?php if ( ! $zv_e['location'] ) : ?><br><a href="<?php echo esc_url( zv_opt( 'maps_url' ) ); ?>" target="_blank" rel="noopener">Open in Maps</a><?php endif; ?></dd></div>
          <?php if ( $zv_e['price'] ) : ?><div><dt><?php echo $zv_icon( '<circle cx="12" cy="12" r="9"/><path d="M15 9.5a3.5 3.5 0 1 0 0 5M8 11h5M8 13h5"/>' ); // phpcs:ignore ?>Price</dt><dd><?php echo zv_e( $zv_e['price'] ); ?></dd></div><?php endif; ?>
          <?php if ( $zv_e['places'] || $zv_e['soldout'] ) : ?><div><dt><?php echo $zv_icon( '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3 3 0 0 1 0 6M21 20c0-2.6-1.6-4.8-4-5.6"/>' ); // phpcs:ignore ?>Places</dt><dd><?php echo $zv_e['soldout'] ? '<b class="ev-full">Fully booked</b>' : zv_e( $zv_e['places'] ); ?></dd></div><?php endif; ?>
        </dl>
        <?php if ( $zv_cta ) : ?>
          <?php echo $zv_cta; // phpcs:ignore ?>
          <?php if ( ! $zv_e['url'] ) : ?><p class="fine">Opens WhatsApp with your message ready to send.</p><?php endif; ?>
        <?php elseif ( $zv_e['soldout'] && ! $zv_past ) : ?>
          <a class="btn btn-ghost" href="<?php echo esc_url( $zv_wa . '?text=' . rawurlencode( 'Hi! Please put me on the waiting list for "' . zv_event_name( get_post() ) . '".' ) ); ?>">Join the waiting list</a>
        <?php endif; ?>
        <?php if ( ! $zv_past && zv_event_times( $zv_e )[0] ) : ?>
        <div class="ev-cal"><span>Add to calendar:</span> <a href="<?php echo esc_url( zv_event_gcal( get_post(), $zv_e ) ); ?>" target="_blank" rel="noopener">Google</a> · <a href="<?php echo esc_url( add_query_arg( 'ics', 1, get_permalink() ) ); ?>">Apple / Outlook</a></div>
        <?php endif; ?>
      </div>
      <div class="book-foot">
        <a href="<?php echo esc_url( $zv_wa ); ?>">WhatsApp</a>
        <a href="<?php echo esc_url( $zv_tel ); ?>">Call us</a>
      </div>
    </aside>
  </div>
</div>

<?php if ( $zv_more ) : ?>
<section class="block bg-cream">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">Coming up</span><h2>More events</h2></div><a class="link" href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">All events</a></div>
    <?php echo zv_event_grid( $zv_more ); // phpcs:ignore ?>
  </div>
</section>
<?php endif; ?>
</main>

<?php if ( $zv_cta ) : ?>
<div class="mbar" id="mbar">
  <div class="p"><b class="ev-mdate"><?php echo zv_e( zv_event_when( $zv_e, true ) ); ?></b><small><?php echo zv_e( $zv_e['price'] ?: get_the_title() ); ?></small></div>
  <?php echo $zv_cta; // phpcs:ignore ?>
</div>
<?php endif; ?>
<?php
get_footer();

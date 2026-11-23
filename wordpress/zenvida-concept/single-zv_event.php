<?php
/**
 * One event: photo and description on the left, a card with date, place, price
 * and the booking button on the right (below on phones), then more events.
 */
the_post();
$e      = zv_event( get_the_ID() );
$state  = zv_event_state( $e );
$past   = 'past' === $state;
$when   = zv_event_when( $e );
$place  = zv_event_location( $e );
$book   = zv_event_button( get_post(), $e );
$label  = $e['btn'] ?: 'Reserve your place';
$wa     = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) );
$tel    = 'tel:' . preg_replace( '/[^\d+]/', '', zv_opt( 'phone' ) );
$more   = zv_events( 'upcoming', 3, get_the_ID() );
$ext    = 0 !== strpos( $book, home_url() ) ? ' target="_blank" rel="noopener"' : '';
$cta    = ( $past || $e['soldout'] ) ? '' : '<a class="btn btn-dark" href="' . esc_url( $book ) . '"' . $ext . '>' . zv_e( $label ) . '</a>';
$icon   = fn( $p ) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">' . $p . '</svg>';

get_header();
?>
<main>
<?php if ( get_post_meta( get_the_ID(), 'zv_hidden', true ) ) : ?><div class="zv-hidden-note">This event is switched off — only you can see it because you are logged in.</div><?php endif; ?>
<?php if ( $past ) : ?><div class="ev-ended">This event has finished. <a href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">See what's coming up →</a></div><?php endif; ?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">Events</a><span>/</span><?php the_title(); ?></nav>

  <div class="product ev-single">
    <div>
      <?php if ( $e['image'] ) : ?>
      <div class="gallery"><figure><img src="<?php echo esc_url( $e['image'] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" onerror="this.remove()"><?php if ( $e['label'] ) : ?><span class="tag"><?php echo zv_e( $e['label'] ); ?></span><?php endif; ?></figure></div>
      <?php endif; ?>
      <div class="intro">
        <span class="eyebrow"><?php echo zv_e( zv_event_when( $e, true ) ); ?></span>
        <h1><?php the_title(); ?></h1>
        <?php if ( $e['summary'] ) : ?><p class="lead"><?php echo zv_e( $e['summary'] ); ?></p><?php endif; ?>
        <div class="ev-content"><?php the_content(); ?></div>
      </div>
    </div>

    <aside class="book ev-book" id="book" data-mbar-anchor aria-label="Event details">
      <div class="book-body">
        <dl class="ev-facts">
          <div><dt><?php echo $icon( '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>' ); // phpcs:ignore ?>When</dt><dd><?php echo zv_e( $when ); ?></dd></div>
          <div><dt><?php echo $icon( '<path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>' ); // phpcs:ignore ?>Where</dt><dd><?php echo zv_e( $place ); ?><?php if ( ! $e['location'] ) : ?><br><a href="<?php echo esc_url( zv_opt( 'maps_url' ) ); ?>" target="_blank" rel="noopener">Open in Maps</a><?php endif; ?></dd></div>
          <?php if ( $e['price'] ) : ?><div><dt><?php echo $icon( '<circle cx="12" cy="12" r="9"/><path d="M15 9.5a3.5 3.5 0 1 0 0 5M8 11h5M8 13h5"/>' ); // phpcs:ignore ?>Price</dt><dd><?php echo zv_e( $e['price'] ); ?></dd></div><?php endif; ?>
          <?php if ( $e['places'] || $e['soldout'] ) : ?><div><dt><?php echo $icon( '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 4.5a3 3 0 0 1 0 6M21 20c0-2.6-1.6-4.8-4-5.6"/>' ); // phpcs:ignore ?>Places</dt><dd><?php echo $e['soldout'] ? '<b class="ev-full">Fully booked</b>' : zv_e( $e['places'] ); ?></dd></div><?php endif; ?>
        </dl>
        <?php if ( $cta ) : ?>
          <?php echo $cta; // phpcs:ignore ?>
          <?php if ( ! $e['url'] ) : ?><p class="fine">Opens WhatsApp with your message ready to send.</p><?php endif; ?>
        <?php elseif ( $e['soldout'] && ! $past ) : ?>
          <a class="btn btn-ghost" href="<?php echo esc_url( $wa . '?text=' . rawurlencode( 'Hi! Please put me on the waiting list for "' . zv_event_name( get_post() ) . '".' ) ); ?>">Join the waiting list</a>
        <?php endif; ?>
        <?php if ( ! $past && zv_event_times( $e )[0] ) : ?>
        <div class="ev-cal"><span>Add to calendar:</span> <a href="<?php echo esc_url( zv_event_gcal( get_post(), $e ) ); ?>" target="_blank" rel="noopener">Google</a> · <a href="<?php echo esc_url( add_query_arg( 'ics', 1, get_permalink() ) ); ?>">Apple / Outlook</a></div>
        <?php endif; ?>
      </div>
      <div class="book-foot">
        <a href="<?php echo esc_url( $wa ); ?>">WhatsApp</a>
        <a href="<?php echo esc_url( $tel ); ?>">Call us</a>
      </div>
    </aside>
  </div>
</div>

<?php if ( $more ) : ?>
<section class="block bg-cream">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">Coming up</span><h2>More events</h2></div><a class="link" href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">All events</a></div>
    <?php echo zv_event_grid( $more ); // phpcs:ignore ?>
  </div>
</section>
<?php endif; ?>
</main>

<?php if ( $cta ) : ?>
<div class="mbar" id="mbar">
  <div class="p"><b class="ev-mdate"><?php echo zv_e( zv_event_when( $e, true ) ); ?></b><small><?php echo zv_e( $e['price'] ?: get_the_title() ); ?></small></div>
  <?php echo $cta; // phpcs:ignore ?>
</div>
<?php endif; ?>
<?php
get_footer();

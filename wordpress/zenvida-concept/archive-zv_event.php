<?php
/**
 * /events/ — what is coming up; past events folded away underneath.
 * Title and intro: Site settings → Events page.
 */
$zv_up   = zv_events( 'upcoming' );
$zv_past = zv_events( 'past', 9 );
get_header();
?>
<main id="main">
<section class="block ev-page">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="eyebrow">Events</span>
        <h1 class="ev-h1"><?php echo zv_e( zv_opt( 'events_title' ) ); ?></h1>
        <?php if ( zv_opt( 'events_intro' ) ) : ?><p class="lead"><?php echo zv_e( zv_opt( 'events_intro' ) ); ?></p><?php endif; ?>
      </div>
    </div>

    <?php if ( $zv_up ) : ?>
      <?php echo zv_event_grid( $zv_up ); // phpcs:ignore -- escaped in zv_event_card ?>
    <?php else : ?>
      <div class="ev-none">
        <h2>Nothing planned right now</h2>
        <p><?php echo zv_e( zv_opt( 'events_empty' ) ); ?></p>
        <a class="btn btn-dark" href="<?php echo esc_url( zv_rituals_url() ); ?>">See all rituals</a>
      </div>
    <?php endif; ?>

    <?php if ( $zv_past ) : ?>
    <details class="ev-pastwrap">
      <summary>Past events <span><?php echo count( $zv_past ); ?></span></summary>
      <?php echo zv_event_grid( $zv_past ); // phpcs:ignore ?>
    </details>
    <?php endif; ?>
  </div>
</section>
</main>
<?php
get_footer();

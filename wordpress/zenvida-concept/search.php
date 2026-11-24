<?php
/**
 * Search results: rituals, events, journal posts and pages, each with a type label.
 */
global $wp_query;
$zv_q     = get_search_query();
$zv_types = array( 'ritual' => 'Ritual', 'zv_event' => 'Event', 'post' => 'Journal', 'page' => 'Page' );
get_header();
?>
<main id="main">
<section class="block zv-search">
  <div class="wrap">
    <span class="eyebrow">Search</span>
    <h1 class="ev-h1"><?php echo $zv_q ? 'Results for “' . esc_html( $zv_q ) . '”' : 'Search'; ?></h1>
    <?php get_search_form(); ?>

    <?php if ( have_posts() ) : ?>
      <p class="sr-count"><?php echo (int) $wp_query->found_posts; ?> result<?php echo 1 === (int) $wp_query->found_posts ? '' : 's'; ?></p>
      <ul class="sr-list">
      <?php
      while ( have_posts() ) :
          the_post();
          $zv_type = get_post_type();
          $zv_meta = '';
          $zv_text = get_the_excerpt();
          if ( 'ritual' === $zv_type ) {
              $zv_r    = zv_ritual_data( get_post() );
              $zv_meta = $zv_r['areaName'] . ' · ' . implode( ' / ', array_map( fn( $o ) => $o[0] . ' min ' . zv_eur( $o[1] ), $zv_r['opts'] ) );
              $zv_text = $zv_r['desc'] ?: $zv_text;
          } elseif ( 'zv_event' === $zv_type ) {
              $zv_ev   = zv_event( get_the_ID() );
              $zv_meta = zv_event_when( $zv_ev, true ) . ( 'past' === zv_event_state( $zv_ev ) ? ' · past event' : '' );
              $zv_text = $zv_ev['summary'] ?: $zv_text;
          } elseif ( 'post' === $zv_type ) {
              $zv_meta = get_the_date();
          }
          ?>
        <li><a href="<?php the_permalink(); ?>">
          <span class="k"><?php echo esc_html( $zv_types[ $zv_type ] ?? 'Page' ); ?><?php echo $zv_meta ? ' · ' . esc_html( $zv_meta ) : ''; ?></span>
          <h2><?php the_title(); ?></h2>
          <?php if ( $zv_text ) : ?><p><?php echo esc_html( wp_trim_words( $zv_text, 26 ) ); ?></p><?php endif; ?>
        </a></li>
      <?php endwhile; ?>
      </ul>
      <div style="margin-top:32px"><?php the_posts_pagination(); ?></div>
    <?php else : ?>
      <div class="ev-none">
        <h2>Nothing found</h2>
        <p>Try another word, for example “reflexology”, “back” or “couples”, or browse everything we offer.</p>
        <a class="btn btn-dark" href="<?php echo esc_url( zv_rituals_url() ); ?>">See all rituals</a>
      </div>
    <?php endif; ?>
  </div>
</section>
</main>
<?php
get_footer();

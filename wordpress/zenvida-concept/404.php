<?php
/**
 * Page not found.
 */
get_header();
?>
<main id="main">
<section class="block zv-search">
  <div class="wrap">
    <span class="eyebrow">Page not found</span>
    <h1 class="ev-h1">We couldn't find that page</h1>
    <p class="lead">It may have moved when we refreshed the website. Search below, or start from one of these.</p>
    <?php get_search_form(); ?>
    <div class="nf-links">
      <a class="btn btn-dark" href="<?php echo esc_url( zv_rituals_url() ); ?>">All rituals &amp; prices</a>
      <?php if ( get_post_type_archive_link( 'zv_event' ) ) : ?><a class="btn btn-ghost" href="<?php echo esc_url( get_post_type_archive_link( 'zv_event' ) ); ?>">Events</a><?php endif; ?>
      <a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Homepage</a>
    </div>
  </div>
</section>
</main>
<?php
get_footer();

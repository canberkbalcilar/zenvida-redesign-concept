<?php
/**
 * Journal (blog) list and fallback template.
 */
get_header();
global $wp_query;
?>
<main id="main">
<section class="block">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">Respiro Journal</span><h1><?php echo is_category() ? esc_html( single_cat_title( '', false ) ) : 'Slow living in Madrid'; ?></h1></div></div>
    <?php echo have_posts() ? zv_journal_cards( $wp_query->posts, 'h2' ) : '<p>No posts yet.</p>'; // phpcs:ignore ?>
    <div style="margin-top:40px"><?php the_posts_pagination(); ?></div>
  </div>
</section>
</main>
<?php
get_footer();

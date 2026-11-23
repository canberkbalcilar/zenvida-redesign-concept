<?php
/**
 * Journal (blog) list and fallback template.
 */
get_header();
global $wp_query;
?>
<main>
<section class="block">
  <div class="wrap">
    <div class="sec-head"><div><span class="eyebrow">Respiro Journal</span><h2><?php echo is_category() ? esc_html( single_cat_title( '', false ) ) : 'Slow living in Madrid'; ?></h2></div></div>
    <?php echo have_posts() ? zv_journal_cards( $wp_query->posts ) : '<p>No posts yet.</p>'; // phpcs:ignore ?>
    <div style="margin-top:40px"><?php the_posts_pagination(); ?></div>
  </div>
</section>
</main>
<?php
get_footer();

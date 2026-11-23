<?php
/**
 * Journal post.
 */
get_header();
the_post();
$zv_cat = get_the_category();
$zv_img = zv_post_image( get_the_ID() );
?>
<main>
<article <?php post_class( 'prose-page' ); ?>>
	<?php if ( $zv_cat ) : ?><span class="eyebrow"><?php echo esc_html( $zv_cat[0]->name ); ?></span><?php endif; ?>
	<h1><?php the_title(); ?></h1>
	<?php echo zv_image( $zv_img, array( 'class' => 'post-img', 'sizes' => '(max-width: 800px) 100vw, 760px', 'eager' => true ) ); // phpcs:ignore ?>
	<?php the_content(); ?>
	<?php wp_link_pages(); ?>
	<p style="margin-top:40px"><a class="link" href="<?php echo esc_url( (int) get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' ) ); ?>">← All journal stories</a></p>
</article>
</main>
<?php
get_footer();

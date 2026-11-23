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
<article class="prose-page">
	<?php if ( $zv_cat ) : ?><span class="eyebrow"><?php echo esc_html( $zv_cat[0]->name ); ?></span><?php endif; ?>
	<h1><?php the_title(); ?></h1>
	<?php if ( $zv_img ) : ?><img class="post-img" src="<?php echo esc_url( $zv_img ); ?>" alt=""><?php endif; ?>
	<?php the_content(); ?>
	<p style="margin-top:40px"><a class="link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/' ) ); ?>">← All journal stories</a></p>
</article>
</main>
<?php
get_footer();

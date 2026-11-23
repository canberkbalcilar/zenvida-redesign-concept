<?php
/**
 * Pages. Pages built from ZenVida blocks render full width; plain pages get a reading layout.
 */
get_header();
the_post();
$zv_blocks = false !== strpos( get_post()->post_content, '<!-- wp:zv/' );
?>
<main>
<?php if ( $zv_blocks ) : ?>
	<?php the_content(); ?>
<?php else : ?>
	<article class="prose-page">
		<h1><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</article>
<?php endif; ?>
</main>
<?php
get_footer();

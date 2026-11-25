<?php
/**
 * Pages. Pages built from ZenVida blocks render full width; plain pages get a reading layout.
 */
get_header();
the_post();
$zv_blocks = false !== strpos( get_post()->post_content, '<!-- wp:zv/' );
?>
<main id="main">
<?php if ( $zv_blocks ) : ?>
	<?php
	// Every page has one h1: its hero title, or else the page name (read by search engines and screen readers).
	ob_start();
	the_content();
	$zv_html = ob_get_clean();
	if ( false === stripos( $zv_html, '<h1' ) ) {
		echo '<h1 class="screen-reader-text">' . esc_html( get_the_title() ) . '</h1>';
	}
	echo $zv_html; // phpcs:ignore -- block output
	?>
<?php else : ?>
	<article class="prose-page">
		<h1><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</article>
<?php endif; ?>
</main>
<?php
get_footer();

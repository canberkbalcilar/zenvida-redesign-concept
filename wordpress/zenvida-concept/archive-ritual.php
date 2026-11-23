<?php
/**
 * /rituals/ and /rituals/{area}/ — shows the blocks of the "Rituals" page
 * (Appearance → ZenVida settings remembers which page that is).
 */
get_header();
?>
<main>
<?php
$zv_page = (int) zv_opt( 'rituals_page' );
if ( $zv_page && get_post( $zv_page ) ) {
	echo zv_render_page_content( $zv_page ); // phpcs:ignore
} else {
	echo zv_render_listing( array( 'showTiles' => true ) ); // phpcs:ignore
}
?>
</main>
<?php
get_footer();

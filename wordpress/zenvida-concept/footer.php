<?php
$zv_col = function ( $loc ) {
	if ( ! has_nav_menu( $loc ) ) {
		return;
	}
	$menu = wp_get_nav_menu_object( get_nav_menu_locations()[ $loc ] );
	echo '<div><h4>' . esc_html( $menu ? $menu->name : '' ) . '</h4>';
	wp_nav_menu( array( 'theme_location' => $loc, 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
	echo '</div>';
};
?>
<footer class="site">
  <div class="wrap">
    <div class="fgrid">
      <div>
        <?php zv_logo(); ?>
        <p class="about"><?php echo zv_e( zv_opt( 'footer_about' ) ); ?></p>
      </div>
      <?php $zv_col( 'footer-1' ); $zv_col( 'footer-2' ); $zv_col( 'footer-3' ); ?>
    </div>
    <div class="fbottom">
      <span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . zv_opt( 'copyright' ) ); ?></span>
      <?php if ( has_nav_menu( 'legal' ) ) { wp_nav_menu( array( 'theme_location' => 'legal', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) ); } ?>
    </div>
  </div>
</footer>
<?php if ( ! is_singular( 'ritual' ) ) : ?>
<div class="mbar" id="mbar"><a class="btn btn-ghost" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) ); ?>">WhatsApp</a><a class="btn btn-dark" href="<?php echo esc_url( is_post_type_archive( 'ritual' ) || is_tax( 'area' ) ? '#results' : ( zv_opt( 'header_url' ) ?: zv_rituals_url() ) ); ?>"><?php echo zv_e( zv_opt( 'header_label' ) ); ?></a></div>
<?php endif; ?>
<div class="toast" id="toast" role="status"></div>
<?php wp_footer(); ?>
</body>
</html>

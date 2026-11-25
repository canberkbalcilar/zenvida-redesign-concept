<?php
$zv_col = function ( $loc ) {
	if ( ! has_nav_menu( $loc ) ) {
		return;
	}
	$menu = wp_get_nav_menu_object( get_nav_menu_locations()[ $loc ] );
	echo '<div><h2 class="f-title">' . esc_html( $menu ? $menu->name : '' ) . '</h2>';
	wp_nav_menu( array( 'theme_location' => $loc, 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
	echo '</div>';
};
?>
<footer class="site">
  <div class="wrap">
    <div class="fgrid">
      <div>
        <?php zv_logo( 'ivory' ); ?>
        <p class="about"><?php echo zv_e( zv_opt( 'footer_about' ) ); ?></p>
        <p class="fcontact">
          <a href="<?php echo esc_url( zv_opt( 'maps_url' ) ); ?>" target="_blank" rel="noopener"><?php echo zv_e( zv_opt( 'address1' ) ); ?>, Madrid</a><br>
          <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', zv_opt( 'phone' ) ) ); ?>"><?php echo zv_e( zv_opt( 'phone' ) ); ?></a> · <a href="mailto:<?php echo esc_attr( zv_opt( 'email' ) ); ?>"><?php echo zv_e( zv_opt( 'email' ) ); ?></a>
        </p>
      </div>
      <?php $zv_col( 'footer-1' ); $zv_col( 'footer-2' ); $zv_col( 'footer-3' ); ?>
    </div>
    <div class="fbottom">
      <span>© <?php echo esc_html( wp_date( 'Y' ) . ' ' . zv_opt( 'copyright' ) ); ?></span>
      <?php if ( has_nav_menu( 'legal' ) ) { wp_nav_menu( array( 'theme_location' => 'legal', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) ); } ?>
    </div>
  </div>
</footer>
<?php if ( ! is_singular( array( 'ritual', 'zv_event' ) ) ) : ?>
<div class="mbar" id="mbar"><a class="btn btn-ghost" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) ); ?>">WhatsApp</a><a class="btn btn-dark" href="<?php echo esc_url( is_post_type_archive( 'ritual' ) || is_tax( 'area' ) ? '#results' : ( zv_opt( 'header_url' ) ?: zv_rituals_url() ) ); ?>"><?php echo zv_e( zv_opt( 'header_label' ) ); ?></a></div>
<?php endif; ?>
<?php zv_whatsapp_float(); ?>
<div class="toast" id="toast" role="status"></div>
<?php wp_footer(); ?>
</body>
</html>

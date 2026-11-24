<?php
$zv_book_url = zv_book_url();
$zv_menu     = function ( $loc, $fallback_cls = '' ) {
	if ( has_nav_menu( $loc ) ) {
		wp_nav_menu( array( 'theme_location' => $loc, 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
	} else {
		echo '<ul><li><a href="' . esc_url( zv_rituals_url() ) . '">Rituals</a></li><li><a href="#visit">Visit</a></li></ul>';
	}
};
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<?php if ( zv_demo() ) : ?>
<div class="concept"><b>Redesign concept</b> · Running on WordPress · <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">Edit pages</a> · <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ritual' ) ); ?>">Edit rituals</a> · <?php if ( current_user_can( 'edit_theme_options' ) ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=zv-settings' ) ); ?>">Settings</a> · <?php endif; ?><a href="<?php echo esc_url( ZV_CONCEPTS_URL ); ?>" target="_blank" rel="noopener">All concepts</a></div>
<?php endif; ?>
<?php if ( '1' === (string) zv_opt( 'promo_show' ) ) : ?>
<div class="promo"><?php echo zv_e( zv_opt( 'promo_text' ) ); ?>
  <?php if ( zv_opt( 'promo_link_label' ) ) : ?> <a href="<?php echo esc_url( zv_opt( 'promo_link_url' ) ?: zv_rituals_url() ); ?>"><?php echo zv_e( zv_opt( 'promo_link_label' ) ); ?> →</a><?php endif; ?>
  <button aria-label="Dismiss" onclick="this.parentElement.remove()">×</button></div>
<?php endif; ?>

<header class="site" id="hdr">
  <div class="wrap nav">
    <?php zv_logo(); ?>
    <?php $zv_menu( 'primary' ); ?>
    <div class="right">
      <?php zv_language_switch(); ?>
      <a class="btn btn-dark" href="<?php echo esc_url( $zv_book_url ); ?>"><?php echo zv_e( zv_opt( 'header_label' ) ); ?></a>
      <button class="menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="mnav"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
  </div>
  <div class="mnav" id="mnav" hidden><div class="wrap"><?php $zv_menu( 'primary' ); ?><a class="btn btn-dark" href="<?php echo esc_url( $zv_book_url ); ?>"><?php echo zv_e( zv_opt( 'header_label' ) ); ?></a></div></div>
</header>

<?php
$zv_archive = get_post_type_archive_link( 'ritual' );
$zv_foot    = get_page_by_path( 'foot-massage', OBJECT, 'ritual' );
$zv_book    = is_singular( 'ritual' ) ? '#book' : '#results';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div style="background:#F1EADC;color:#4A443B;font:500 12.5px/1.4 Figtree,system-ui,sans-serif;text-align:center;padding:7px 16px;border-bottom:1px solid #E6DECF"><b style="color:#1E1B17">Redesign concept</b> · Running on WordPress · <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ritual' ) ); ?>" style="color:#8A7350;font-weight:600">Edit rituals in WP admin</a> · <a href="<?php echo esc_url( ZV_CONCEPTS_URL ); ?>" target="_blank" rel="noopener" style="color:#8A7350;font-weight:600">All concepts</a></div>

<div class="promo">Short on time? <a href="<?php echo esc_url( $zv_foot ? get_permalink( $zv_foot ) : $zv_archive ); ?>">Foot Massage 25 min — €25</a> · add neck &amp; shoulders for €10
  <button aria-label="Dismiss" onclick="this.parentElement.remove()">×</button></div>

<header class="site" id="hdr">
  <div class="wrap nav">
    <a class="logo" href="<?php echo esc_url( $zv_archive ); ?>" aria-label="ZenVida home"><b>ZENVIDA</b><small>FOOT &amp; BODY</small></a>
    <ul>
      <li><a href="<?php echo esc_url( $zv_archive ); ?>" aria-current="page">Rituals</a></li>
      <li><a href="#">Experiences</a></li>
      <li><a href="#">Offers &amp; Packs</a></li>
      <li><a href="#">Gift Cards</a></li>
      <li><a href="#">Journal</a></li>
      <li><a href="#visit">Visit</a></li>
    </ul>
    <div class="right">
      <div class="lang" role="group" aria-label="Language"><button aria-pressed="true">EN</button><button aria-pressed="false">ES</button></div>
      <a class="btn btn-dark" href="<?php echo esc_attr( $zv_book ); ?>">Book now</a>
      <button class="menu-btn" aria-label="Open menu"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
  </div>
</header>

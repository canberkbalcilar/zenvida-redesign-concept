<?php $zv_archive = get_post_type_archive_link( 'ritual' ); ?>
<footer>
  <div class="wrap">
    <a class="logo" href="<?php echo esc_url( $zv_archive ); ?>"><b>ZENVIDA</b><small>FOOT &amp; BODY</small></a>
    <nav><a href="<?php echo esc_url( $zv_archive ); ?>">Rituals</a><a href="#">Experiences</a><a href="#">Offers &amp; Packs</a><a href="#">Gift Cards</a><a href="#">Journal</a><a href="#visit">Visit</a></nav>
    <span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> ZenVida · Madrid</span>
  </div>
</footer>
<div class="toast" id="toast" role="status"></div>
<?php wp_footer(); ?>
</body>
</html>

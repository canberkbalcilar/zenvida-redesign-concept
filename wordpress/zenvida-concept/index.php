<?php
// Fallback template. The concept only uses the rituals archive and ritual pages.
get_header();
?>
<main class="wrap" style="padding:80px 24px">
  <h1 style="font-size:48px">ZenVida</h1>
  <p><a href="<?php echo esc_url( get_post_type_archive_link( 'ritual' ) ); ?>">See all rituals →</a></p>
</main>
<?php
get_footer();

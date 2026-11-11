<?php
/**
 * Rituals page (/rituals/) and area pages (/rituals/{area}/).
 * Cards are rendered here for search engines; assets/js/archive.js then takes
 * over filtering, sorting, the quiz and the price-list view.
 */
$zv      = zv_archive_data();
$areas   = $zv['areas'];
$needs   = $zv['needs'];
$rituals = $zv['rituals'];
$deluxe  = get_page_by_path( 'deluxe-reflexology', OBJECT, 'ritual' );
$hero    = $deluxe ? zv_ritual_data( $deluxe ) : null;
$initial = $zv['initialArea'];
$min_p   = function ( $r ) {
	$p = array_filter( array_column( $r['opts'], 1 ), fn( $x ) => null !== $x );
	return $p ? min( $p ) : null;
};

get_header();
?>
<main>
<div class="wrap">
  <section class="hero">
    <div>
      <span class="eyebrow">Rituals · Madrid Centro</span>
      <h1>Find the ritual your <em>body</em> is asking for</h1>
      <p class="lead">Reflexology, massage and facial rituals, 3 minutes from Puerta del Sol. Walk in anytime or book online in under a minute.</p>
      <div class="hero-ctas">
        <button class="btn btn-dark" id="quizOpen">Help me choose — 3 questions</button>
        <a class="btn btn-ghost" href="#results">See all <?php echo count( $rituals ); ?> rituals</a>
      </div>
      <div class="trust">
        <span><span class="stars">★★★★★</span><b>5.0</b>&nbsp;from 450+ guests</span>
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>Calle de la Cruz 16</span>
        <span class="open" id="openNow">Open now</span>
      </div>
    </div>
    <div class="hero-img">
      <img src="<?php echo esc_url( zv_img( 'b84849_f89b8ba73adb4a0f9025817ccebf6178~mv2.jpg', 1200, 960 ) ); ?>" alt="ZenVida treatment room with rose petals" onerror="this.remove()">
      <?php if ( $hero ) : ?>
      <div class="float"><div><small style="color:var(--muted)">Most booked</small><b><?php echo esc_html( $hero['name'] ); ?></b></div><span style="color:var(--ink-2)"><?php echo (int) $hero['opts'][0][0]; ?> min · <?php echo esc_html( zv_eur( $hero['opts'][0][1] ) ); ?></span><a href="<?php echo esc_url( $hero['url'] ); ?>">Book →</a></div>
      <?php endif; ?>
    </div>
  </section>

  <nav class="areas" aria-label="Browse by area" id="areaTiles"></nav>
</div>

<!-- Filters -->
<div class="filters" id="filters">
  <div class="wrap">
    <div class="frow top">
      <div class="seg" role="group" aria-label="Area" id="areaSeg"></div>
      <span class="sp"></span>
      <select id="time" aria-label="Duration">
        <option value="any">Any length</option>
        <option value="short">Under 45 min</option>
        <option value="hour">About an hour</option>
        <option value="long">90 min or more</option>
      </select>
      <select id="sort" aria-label="Sort">
        <option value="rec">Recommended</option>
        <option value="price">Price: low to high</option>
        <option value="time">Shortest first</option>
      </select>
      <div class="view" role="group" aria-label="View">
        <button aria-pressed="true" data-v="cards" title="Cards"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>Cards</button>
        <button aria-pressed="false" data-v="list" title="Price list"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>Price list</button>
      </div>
    </div>
    <div class="frow">
      <span class="flabel">I need</span>
      <div class="scroller" role="group" aria-label="Need" id="needChips"></div>
    </div>
  </div>
</div>

<div class="wrap results" id="results">
  <div class="rhead" id="rhead"><span><b><?php echo count( $rituals ); ?></b> rituals</span></div>
  <div id="out">
  <?php
  // Server-rendered list (same markup the script produces).
  foreach ( $areas as $slug => $a ) :
	  if ( 'all' !== $initial && $slug !== $initial ) {
		  continue;
	  }
	  $group = array_values( array_filter( $rituals, fn( $r ) => $r['area'] === $slug ) );
	  if ( ! $group ) {
		  continue;
	  }
	  usort( $group, fn( $x, $y ) => $y['pop'] <=> $x['pop'] );
	  $prices = array_filter( array_map( $min_p, $group ), fn( $x ) => null !== $x );
	  ?>
	  <section class="group" id="g-<?php echo esc_attr( $slug ); ?>">
		<div class="ghead" style="--c:<?php echo esc_attr( $a['c'] ); ?>">
		  <div class="gimg"><?php if ( $a['img'] ) : ?><img src="<?php echo esc_url( $a['img'] ); ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></div>
		  <div><h2><?php echo esc_html( $a['name'] ); ?></h2><p><?php echo esc_html( $a['intro'] ); ?></p><p class="long" hidden><?php echo esc_html( $a['long'] ); ?></p><button class="more" aria-expanded="false">Read more</button></div>
		  <span class="cnt"><?php echo count( $group ) . ( 1 === count( $group ) ? ' ritual' : ' rituals' ) . ( $prices ? ' · from €' . min( $prices ) : '' ); ?></span>
		</div>
		<div class="grid"><?php foreach ( $group as $r ) { echo zv_card_html( $r, $areas, $needs ); } // phpcs:ignore ?></div>
	  </section>
  <?php endforeach; ?>
  </div>
</div>

<!-- Signature -->
<section class="sig-band">
  <div class="wrap">
    <div class="sig-head">
      <div><span class="eyebrow">Signature rituals</span><h2>Longer journeys, made only here</h2></div>
      <p>Our own rituals, built from several techniques. Choose one when you have time to fully switch off.</p>
    </div>
    <div class="sig-row" id="sigRow"></div>
  </div>
</section>

<!-- Save -->
<section class="save">
  <div class="wrap">
    <span class="eyebrow">Come back for less</span>
    <h2>Make it a habit</h2>
    <div class="save-grid">
      <a class="save-card" href="#"><b>Packs</b><p>Zen Starter (5 sessions) and Zen Balance (10 sessions). Save up to 20%.</p><span>See packs →</span></a>
      <a class="save-card" href="#"><b>Membership</b><p>A monthly ritual, priority booking and member discounts. No sign-on fee.</p><span>€55 / month →</span></a>
      <a class="save-card" href="#"><b>Loyalty Card</b><p>Collect stamps with every massage and earn free rituals.</p><span>How it works →</span></a>
      <a class="save-card" href="#"><b>Gift Cards</b><p>Give any ritual, delivered by email in minutes.</p><span>Send a gift →</span></a>
    </div>
  </div>
</section>

<!-- FAQ + Visit -->
<section class="bottom" id="visit">
  <div class="wrap">
    <div class="faq">
      <span class="eyebrow">Before you come</span>
      <h2>Good to know</h2>
      <details open><summary>Do I need to book?</summary><p>No — walk in anytime. Booking online guarantees your time and lets you choose your therapist in the Mindbody app.</p></details>
      <details><summary>Which ritual should I choose first?</summary><p>For tired feet after walking Madrid, start with Deluxe Reflexology. For back and neck tension, try Deep Tissue or Thai. Not sure? Use "Help me choose" above.</p></details>
      <details><summary>What's the difference between reflexology and massage?</summary><p>Massage works the muscles directly. Reflexology works pressure points on the feet that connect to the whole body, for deep, whole-body relaxation.</p></details>
      <details><summary>Can I come with friends or my partner?</summary><p>Yes. Couples Massage has two therapists in one shared room, and several rituals can be booked for small groups.</p></details>
    </div>
    <div class="visit">
      <span class="eyebrow">Visit us</span>
      <h2>Calle de la Cruz, 16</h2>
      <div class="visit-card">
        <p>Centro, 28012 Madrid · Metro Sol, 3 minutes' walk.</p>
        <p id="hoursToday"></p>
        <p style="margin:0">+34 690 829 142 · hola@zenvida.es</p>
        <div class="row">
          <a class="btn btn-dark" href="https://www.google.com/maps/search/?api=1&amp;query=Calle+de+la+Cruz+16+28012+Madrid" target="_blank" rel="noopener">Get directions</a>
          <a class="btn btn-ghost" href="https://wa.me/34690829142">WhatsApp</a>
        </div>
      </div>
    </div>
  </div>
</section>
</main>

<div class="mbar"><button class="btn btn-ghost" id="quizOpen2">Help me choose</button><a class="btn btn-dark" href="#results">Book now</a></div>

<dialog id="quiz" aria-label="Find your ritual">
  <div class="qz">
    <div class="qz-top"><div class="qz-bar"><i id="qzBar" style="width:33%"></i></div><button class="qz-x" id="qzClose" aria-label="Close">×</button></div>
    <span class="eyebrow" id="qzStep">Question 1 of 3</span>
    <h3 id="qzQ" style="margin-top:10px"></h3>
    <div class="qz-opts" id="qzOpts"></div>
    <button class="qz-back" id="qzBack" hidden>← Back</button>
  </div>
</dialog>
<?php
get_footer();

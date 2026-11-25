/* Siesta Club page: 6 / 12 months switch, join calculator and the mobile bottom bar. */
(function () {
  'use strict';

  var calc = document.querySelector('[data-club-calc]');

  /* ---- Plans: 6 / 12 months ---- */
  function setTerm(term) {
    document.querySelectorAll('[data-club-plans]').forEach(function (box) {
      box.querySelectorAll('[data-term]').forEach(function (b) {
        b.setAttribute('aria-checked', b.getAttribute('data-term') === String(term) ? 'true' : 'false');
      });
      box.querySelectorAll('.club-plan').forEach(function (card) {
        var set = function (sel, key) {
          var el = card.querySelector(sel);
          if (el && card.dataset[key + term]) { el.textContent = card.dataset[key + term]; }
        };
        set('[data-club-price]', 'price');
        set('[data-club-for]', 'for');
        set('[data-club-sub]', 'sub');
      });
    });
  }
  document.querySelectorAll('[data-club-plans] [data-term]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-term');
      setTerm(t);
      if (calc) { pick('club_term', t); }
    });
    b.addEventListener('keydown', function (e) {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') { return; }
      var other = b.parentNode.querySelector('[data-term="' + (b.getAttribute('data-term') === '6' ? '12' : '6') + '"]');
      if (other) { e.preventDefault(); other.focus(); other.click(); }
    });
  });

  // "Choose Gold" / "Choose Platinum" preselect the plan in the calculator.
  document.querySelectorAll('[data-club-pick]').forEach(function (a) {
    a.addEventListener('click', function () { if (calc) { pick('club_plan', a.getAttribute('data-club-pick')); } });
  });

  /* ---- Join calculator ---- */
  function pick(name, value) {
    var input = calc.querySelector('input[name="' + name + '"][value="' + value + '"]');
    if (input) { input.checked = true; update(); }
  }

  var cfg = calc ? JSON.parse(calc.getAttribute('data-club') || '{}') : null;

  function eur(n) {
    n = Math.round(n * 100) / 100;
    var dec = Math.abs(n - Math.floor(n)) > 0.001 ? 2 : 0;
    var es = cfg.i18n.lang === 'es';
    var parts = n.toFixed(dec).split('.');
    var int = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, es ? '.' : ',');
    var num = int + (dec ? (es ? ',' : '.') + parts[1] : '');
    return es ? num + ' €' : '€' + num; // same as zv_club_eur() in PHP
  }

  function val(name) {
    var el = calc.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
  }

  function update() {
    var planId = val('club_plan'), term = val('club_term') || '6', pay = val('club_pay') || 'full';
    var plan = cfg.plans.filter(function (p) { return p.id === planId; })[0] || cfg.plans[0];
    var total = term === '12' ? plan.p12 : plan.p6;

    cfg.plans.forEach(function (p) {
      var el = calc.querySelector('[data-club-plan-price="' + p.id + '"]');
      if (el) { el.textContent = eur(term === '12' ? p.p12 : p.p6); }
    });
    var today = { full: total * (1 - cfg.d / 100), two: total * cfg.two[0] / 100, three: total * cfg.three[0] / 100 };
    Object.keys(today).forEach(function (k) {
      var el = calc.querySelector('[data-club-today="' + k + '"]');
      if (el) { el.textContent = eur(today[k]); }
    });

    var rows = [];
    if (pay === 'full') {
      rows.push([cfg.i18n.today, today.full]);
    } else {
      (pay === 'two' ? cfg.two : cfg.three).forEach(function (pct, i) {
        rows.push([i === 0 ? cfg.i18n.today : cfg.i18n['in'].replace('{n}', String(30 * i)), total * pct / 100]);
      });
    }
    var list = calc.querySelector('[data-club-schedule]');
    list.textContent = '';
    rows.forEach(function (r) {
      var li = document.createElement('li'), s = document.createElement('span'), b = document.createElement('b');
      s.textContent = r[0]; b.textContent = eur(r[1]);
      li.appendChild(s); li.appendChild(b); list.appendChild(li);
    });
    var note = calc.querySelector('[data-club-note]');
    note.textContent = pay === 'full' ? cfg.i18n.save.replace('{amount}', eur(total * cfg.d / 100)) : note.getAttribute('data-' + pay);

    var termTxt = cfg.i18n.months.replace('{n}', term);
    var msg = cfg.i18n.msg.replace('{plan}', plan.name).replace('{term}', termTxt).replace('{pay}', cfg.i18n[pay]);
    calc.querySelector('[data-club-wa]').href = 'https://wa.me/' + cfg.wa + '?text=' + encodeURIComponent(msg);
  }

  if (calc) {
    calc.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'club_term') { setTerm(e.target.value); }
      update();
    });
    update();
  }

  /* ---- Mobile bottom bar: after the hero, until the join section ---- */
  var bar = document.querySelector('[data-club-bar]');
  var hero = document.querySelector('.club-hero');
  var join = document.getElementById('join');
  if (bar && hero && 'IntersectionObserver' in window) {
    var heroOut = false, joinIn = false;
    var sync = function () { bar.classList.toggle('is-on', heroOut && !joinIn); };
    new IntersectionObserver(function (en) { heroOut = !en[0].isIntersecting; sync(); }).observe(hero);
    if (join) { new IntersectionObserver(function (en) { joinIn = en[0].isIntersecting; sync(); }).observe(join); }
  }
})();

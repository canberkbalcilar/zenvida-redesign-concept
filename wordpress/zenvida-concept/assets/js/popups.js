/* Event popups. Config: window.ZV_POPUPS (see inc/popups.php).
   Dates are checked here, in the browser, so cached pages stay correct.
   At most one popup per page view; the first one that qualifies wins. */
(() => {
  const list = window.ZV_POPUPS || [];
  if (!list.length) return;

  const mobile = matchMedia('(max-width: 760px)').matches;
  const DAY = 864e5;
  const store = {
    get(k, session) { try { return (session ? sessionStorage : localStorage).getItem('zvp-' + k); } catch (e) { return null; } },
    set(k, session) { try { (session ? sessionStorage : localStorage).setItem('zvp-' + k, String(Date.now())); } catch (e) {} }
  };
  const seen = p => {
    if (p.preview || p.freq === 'always') return false;
    if (p.freq === 'session') return !!store.get(p.key, true);
    const t = +store.get(p.key);
    if (!t) return false;
    return p.freq === 'once' ? true : Date.now() - t < DAY;
  };
  const remember = p => { if (!p.preview) store.set(p.key, p.freq === 'session'); };

  const now = Date.now();
  const p = list.find(x =>
    x.preview || (
      (!x.start || x.start <= now) && (!x.end || x.end > now) &&
      (x.device === 'all' || (x.device === 'mobile') === mobile) &&
      !seen(x)
    ));
  if (!p) return;
  const el = document.getElementById(p.el);
  if (!el) return;

  let shown = false;
  const busy = () => document.querySelector('dialog[open]:not(.zvp)') || (document.getElementById('mnav') && !document.getElementById('mnav').hidden);

  function show() {
    if (shown) return;
    if (busy()) { setTimeout(show, 3000); return; } // wait until the quiz / menu is closed
    shown = true;
    remember(p);
    el.hidden = false;
    if (el.tagName === 'DIALOG') { el.showModal(); el.querySelector('h2')?.setAttribute('tabindex', '-1'); el.querySelector('h2')?.focus(); }
    else { requestAnimationFrame(() => el.classList.add('open')); }
    document.documentElement.classList.add('zvp-on-' + (el.classList.contains('zvp-bar') ? 'bar' : 'other'));
    cleanup();
  }
  function close() {
    if (el.tagName === 'DIALOG') el.close();
    el.classList.remove('open');
    setTimeout(() => { el.hidden = true; }, 250);
    document.documentElement.classList.remove('zvp-on-bar', 'zvp-on-other');
  }

  el.querySelector('.zvp-x').addEventListener('click', close);
  if (el.tagName === 'DIALOG') {
    el.addEventListener('click', e => { if (e.target === el) close(); });         // click on the dimmed area
    el.addEventListener('cancel', e => { e.preventDefault(); close(); });         // Esc
  } else {
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && shown && !el.hidden) close(); });
  }

  // Triggers
  let timer, onScroll, onLeave;
  const scrolled = () => (scrollY + innerHeight) / Math.max(1, document.documentElement.scrollHeight) * 100;
  const byScroll = pct => {
    onScroll = () => { if (scrolled() >= pct) show(); };
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  };
  function cleanup() {
    clearTimeout(timer);
    if (onScroll) removeEventListener('scroll', onScroll);
    if (onLeave) document.removeEventListener('mouseout', onLeave);
  }

  if (p.preview) { setTimeout(show, 300); return; }
  if (p.trigger === 'scroll') byScroll(p.scroll);
  else if (p.trigger === 'exit') {
    if (mobile) byScroll(50);
    else {
      onLeave = e => { if (!e.relatedTarget && e.clientY <= 0) show(); };
      setTimeout(() => document.addEventListener('mouseout', onLeave), 3000); // not in the first 3 seconds
    }
  } else timer = setTimeout(show, p.delay * 1000);
})();

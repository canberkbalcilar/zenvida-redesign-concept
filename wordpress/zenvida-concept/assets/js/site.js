/* Site-wide behaviour. Settings from the "ZenVida" admin screen arrive in window.ZV_SITE. */
(() => {
const S = window.ZV_SITE || {};
const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

// Toast
const toastEl = $('#toast'); let tt;
window.zvToast = t => {
  if (!toastEl) return;
  toastEl.textContent = t; toastEl.classList.add('show');
  clearTimeout(tt); tt = setTimeout(() => toastEl.classList.remove('show'), 2800);
};

// Header border, mobile menu
const hdr = $('#hdr');
const mnav = $('#mnav'), mbtn = $('.menu-btn');
if (mbtn && mnav) {
  const setMenu = open => {
    mnav.hidden = !open; mbtn.setAttribute('aria-expanded', open);
    mbtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    if (open) mnav.querySelector('a')?.focus();
  };
  mbtn.addEventListener('click', () => setMenu(mnav.hidden));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !mnav.hidden) { setMenu(false); mbtn.focus(); } });
}

// Mobile bar: hidden while an anchor (quick book / booking card / filters) is on screen
const mbar = $('#mbar');
const anchors = $$('[data-mbar-anchor]');
const onScroll = () => {
  hdr && hdr.classList.toggle('scrolled', scrollY > 10);
  if (!mbar) return;
  let show;
  if (anchors.length) {
    const a = anchors[0].getBoundingClientRect();
    show = a.bottom < 0 || a.top > innerHeight;
  } else show = scrollY > 500;
  mbar.classList.toggle('show', show);
};
addEventListener('scroll', onScroll, {passive:true}); onScroll();

// Clock of the studio (Europe/Madrid by default), not of the visitor's device.
// Returns a Date whose local fields (day, hours…) are the studio's wall-clock time.
window.zvNow = () => {
  try {
    const p = {};
    new Intl.DateTimeFormat('en-US', { timeZone: S.tz || 'Europe/Madrid', year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', hourCycle: 'h23' })
      .formatToParts(new Date()).forEach(x => { p[x.type] = x.value; });
    return new Date(+p.year, +p.month - 1, +p.day, +p.hour % 24, +p.minute);
  } catch (e) { return new Date(); }
};

// Opening hours (index 0 = Sunday, values in minutes)
const H = S.hours || [];
const DAYN = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const fmt = m => String(Math.floor((m%1440)/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
const isClosed = d => H[d][0] === H[d][1];          // a day marked "Closed" in Site settings
if (H.length === 7) {
  const now = zvNow(), dow = now.getDay(), nm = now.getHours()*60 + now.getMinutes();
  const [o, c] = H[dow], open = !isClosed(dow) && nm >= o && nm < c;
  // Next opening: later today, or the first day that is not closed.
  let next = '';
  if (!open) {
    if (!isClosed(dow) && nm < o) next = `Opens today at ${fmt(o)}`;
    else for (let i = 1; i <= 7; i++) {
      const d = (dow + i) % 7;
      if (!isClosed(d)) { next = `Opens ${i === 1 ? 'tomorrow' : DAYN[d]} at ${fmt(H[d][0])}`; break; }
    }
  }
  const label = open ? `Open now · until ${fmt(c)}` : (next || 'Closed');
  $$('[data-open-now]').forEach(el => { el.textContent = label; el.classList.toggle('closed', !open); });
  $$('[data-hours-table]').forEach(t => {
    t.innerHTML = [1,2,3,4,5,6,0].map(i => `<tr class="${i===dow?'today':''}"><td>${DAYN[i]}${i===dow?' · today':''}</td><td>${isClosed(i) ? 'Closed' : fmt(H[i][0]) + ' – ' + fmt(H[i][1])}</td></tr>`).join('');
  });
  const everyDay = [0,1,2,3,4,5,6].every(d => !isClosed(d));
  $$('[data-hours-today]').forEach(el => el.textContent = (isClosed(dow) ? 'Closed today.' : `Today: ${fmt(o)} – ${fmt(c)}.`) + (everyDay ? ' Open 7 days a week.' : ''));
  // Quick book "When" options
  $$('[data-qb-when]').forEach(sel => {
    const opts = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(now); d.setDate(d.getDate()+i);
      if (isClosed(d.getDay()) || (i === 0 && nm > c - 90)) continue;
      const name = i===0 ? 'Today' : i===1 ? 'Tomorrow' : d.toLocaleDateString('en',{weekday:'long'});
      opts.push(`<option value="${i}">${name}, ${d.toLocaleDateString('en',{day:'numeric',month:'short'})}</option>`);
    }
    sel.innerHTML = opts.join('') + '<option value="later">Later date…</option>';
  });
}

// Quick book: every option value is a URL
$$('form[data-quick-book]').forEach(f => f.addEventListener('submit', e => {
  e.preventDefault();
  const url = f.querySelector('[data-qb-what]').value;
  if (url) location.href = url;
}));

// Newsletter (prototype: validates, does not send)
$$('form[data-newsletter]').forEach(f => {
  let ch = 'email';
  const input = f.querySelector('input[data-nval]'), ok = f.querySelector('[data-nok]');
  f.querySelectorAll('[data-ch]').forEach(b => b.addEventListener('click', () => {
    ch = b.dataset.ch;
    f.querySelectorAll('[data-ch]').forEach(x => x.setAttribute('aria-pressed', x === b));
    if (ch === 'wa') { input.type = 'tel'; input.placeholder = '+34 600 000 000'; input.setAttribute('aria-label', 'WhatsApp number'); input.autocomplete = 'tel'; }
    else { input.type = 'email'; input.placeholder = 'you@example.com'; input.setAttribute('aria-label', 'Email address'); input.autocomplete = 'email'; }
    input.value = ''; input.focus();
  }));
  f.addEventListener('submit', e => {
    e.preventDefault();
    const v = input.value.trim();
    const valid = ch === 'email' ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) : /^\+?[\d\s]{8,}$/.test(v);
    if (!valid) { zvToast(ch === 'email' ? 'Please enter a valid email address.' : 'Please enter a valid WhatsApp number.'); input.focus(); return; }
    if (!f.querySelector('[data-nconsent]').checked) { zvToast('Please tick the box to accept the privacy policy.'); return; }
    ok.textContent = `Prototype: your welcome code would now be sent by ${ch === 'email' ? 'email' : 'WhatsApp'}.`;
    ok.hidden = false;
  });
});

// Foot map
$$('.map-grid').forEach(g => {
  const btns = g.querySelectorAll('.zones button'), zs = g.querySelectorAll('.foot .z');
  const set = z => { btns.forEach(b => b.setAttribute('aria-pressed', b.dataset.z === z)); zs.forEach(e => e.classList.toggle('on', e.dataset.z === z)); };
  btns.forEach(b => { b.addEventListener('click', () => set(b.dataset.z)); b.addEventListener('mouseenter', () => set(b.dataset.z)); });
  zs.forEach(e => e.addEventListener('mouseenter', () => set(e.dataset.z)));
  set('head');
});

// Story "Read more"
$$('.story').forEach(s => {
  const p = s.querySelector('.prose'), b = s.querySelector('.read-more');
  if (!p || !b) return;
  if (p.children.length <= 2) { b.hidden = true; return; }
  b.addEventListener('click', () => {
    const open = p.classList.toggle('clamp') === false;
    b.textContent = open ? 'Read less' : 'Read more';
    b.setAttribute('aria-expanded', open);
  });
});

// Language switch (visual only in the concept)
$$('.lang button').forEach(b => b.addEventListener('click', () => $$('.lang button').forEach(x => x.setAttribute('aria-pressed', x === b))));
})();

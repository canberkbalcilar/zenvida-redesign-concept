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
if (mbtn && mnav) mbtn.addEventListener('click', () => {
  const open = mnav.hidden; mnav.hidden = !open; mbtn.setAttribute('aria-expanded', open);
});

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

// Opening hours (index 0 = Sunday, values in minutes)
const H = S.hours || [];
const DAYN = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const fmt = m => String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
if (H.length === 7) {
  const now = new Date(), dow = now.getDay(), nm = now.getHours()*60 + now.getMinutes();
  const [o, c] = H[dow], open = nm >= o && nm < c;
  const label = open ? `Open now · until ${fmt(c)}` : (nm < o ? `Opens today at ${fmt(o)}` : `Opens tomorrow at ${fmt(H[(dow+1)%7][0])}`);
  $$('[data-open-now]').forEach(el => { el.textContent = label; el.classList.toggle('closed', !open); });
  $$('[data-hours-table]').forEach(t => {
    t.innerHTML = [1,2,3,4,5,6,0].map(i => `<tr class="${i===dow?'today':''}"><td>${DAYN[i]}${i===dow?' · today':''}</td><td>${fmt(H[i][0])} – ${fmt(H[i][1])}</td></tr>`).join('');
  });
  $$('[data-hours-today]').forEach(el => el.textContent = `Today: ${fmt(o)} – ${fmt(c)}. Open 7 days a week.`);
  // Quick book "When" options
  $$('[data-qb-when]').forEach(sel => {
    const opts = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(now); d.setDate(d.getDate()+i);
      if (i === 0 && nm > c - 90) continue;
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

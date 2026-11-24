/* Body map block: tap a zone (on the figure or the chips) to list the rituals that help there. */
(() => {
  const T = (s, v) => (window.zvT ? zvT(s, v) : s);
  document.querySelectorAll('[data-bodymap]').forEach(root => {
    const D = JSON.parse(root.querySelector('[data-bm-data]').textContent);
    const list = root.querySelector('[data-bm-list]');
    const esc = s => String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    const card = r => `<a class="bm-card" href="${esc(r.url)}">
        <span class="bm-img">${r.img ? `<img src="${esc(r.img)}" alt="" loading="lazy" onerror="this.remove()">` : ''}</span>
        <span class="bm-body"><small>${esc(r.area)} · ${esc(r.mins)} min</small><b>${esc(r.name)}</b><span>${esc(r.desc)}</span>
        <em>${r.from == null ? T('Price at studio') : T('from €{n}', { n: r.from }) + (r.two ? ' ' + T('for two') : '')} <span aria-hidden="true">→</span></em></span></a>`;

    function show(zone) {
      root.querySelectorAll('[data-zone]').forEach(el => {
        const on = el.dataset.zone === zone;
        el.classList.toggle('on', on);
        if (el.matches('.bm-chip')) el.setAttribute('aria-pressed', on);
      });
      root.classList.toggle('has-zone', !!zone);
      const z = zone && D.zones[zone];
      const hits = zone ? D.rituals.filter(r => r.zones.includes(zone)) : D.rituals;
      root.querySelector('[data-bm-k]').textContent = zone ? (hits.length === 1 ? T('1 ritual') : T('{n} rituals', { n: hits.length })) : T('Most booked');
      root.querySelector('[data-bm-title]').textContent = zone ? T('For your {zone}', { zone: z.label.toLowerCase() }) : T('Not sure where to start?');
      root.querySelector('[data-bm-hint]').textContent = zone ? z.hint + '.' : T('Tap an area of the body, or choose one of these favourites.');
      list.innerHTML = hits.slice(0, D.count).map(card).join('') ||
        '<p class="bm-none">' + T('No ritual is linked to this area yet. Ask us on WhatsApp and we will suggest one.') + '</p>';
      // "Whole body" lights up the whole figure
      root.querySelector('.bm-figure').classList.toggle('whole', zone === 'whole');
    }

    let current = null;
    const pick = zone => { current = current === zone ? null : zone; show(current); };
    root.querySelectorAll('[data-zone]').forEach(el => {
      el.addEventListener('click', () => pick(el.dataset.zone));
      if (el.matches('.bm-zone')) el.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(el.dataset.zone); }
      });
      // hover preview on the figure (desktop): show the zone name in the prompt
      if (el.matches('.bm-zone')) {
        el.addEventListener('mouseenter', () => { root.querySelector('.bm-prompt').textContent = D.zones[el.dataset.zone].label; });
        el.addEventListener('mouseleave', () => { root.querySelector('.bm-prompt').textContent = root.querySelector('.bm-prompt').dataset.text; });
      }
    });
    root.querySelector('.bm-prompt').dataset.text = root.querySelector('.bm-prompt').textContent;
    show(null);
  });
})();

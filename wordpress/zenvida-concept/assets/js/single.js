/* Booking card on ritual pages. Data: window.ZV_SINGLE (ritual) + window.ZV_SITE (opening hours). */
(() => {
  const Z = window.ZV_SINGLE || {};
  const $ = s => document.querySelector(s);
  if (!$('#book') || !$('#days')) return;

  const OPTS = Z.opts || [[60, 60]];
  // Reservations Policy: €25 per person, €45 for services of €90 or more (unless the ritual sets its own).
  const dep = () => (Z.deposit != null ? Z.deposit : ((price() || 0) >= 90 ? 45 : 25));
  const WA = 'https://wa.me/' + String((window.ZV_SITE && window.ZV_SITE.whatsapp) || '').replace(/\D/g, '');
  const TWO = (Z.flags || []).includes('two');
  const UNIT = TWO ? 'for two' : '/ person';
  const HOURS = (window.ZV_SITE && window.ZV_SITE.hours) || [[720,1290],[690,1230],[690,1230],[690,1230],[690,1290],[660,1290],[630,1290]];
  const DAYN = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const fmt = m => String(Math.floor((m%1440)/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
  const closedDay = d => HOURS[d.getDay()][0] === HOURS[d.getDay()][1];
  const eur = n => n == null ? 'Price at studio' : '€' + n;

  const st = { opt:0, bath:'none', bathP:0, guests:1, day:0, time:null, showAll:false };
  const len = () => OPTS[st.opt][0];
  const price = () => OPTS[st.opt][1];

  // Radio groups (duration, foot bath): one tab stop, arrow keys move the choice (WAI-ARIA radio pattern)
  const radioKeys = group => {
    if (!group) return;
    const items = [...group.querySelectorAll('[role=radio]')];
    const sync = () => items.forEach(x => x.tabIndex = x.getAttribute('aria-checked') === 'true' ? 0 : -1);
    group.addEventListener('keydown', e => {
      const i = items.indexOf(document.activeElement);
      if (i < 0) return;
      const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
      const to = e.key === 'Home' ? 0 : e.key === 'End' ? items.length - 1 : step ? (i + step + items.length) % items.length : -1;
      if (to < 0) return;
      e.preventDefault(); items[to].click(); items[to].focus();
    });
    group.addEventListener('click', () => setTimeout(sync));
    sync();
  };
  radioKeys($('#durations')); radioKeys($('#bathGroup'));

  // Duration
  document.querySelectorAll('#durations .dur').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('#durations .dur').forEach(x => x.setAttribute('aria-checked', x === b));
    st.opt = +b.dataset.i; st.time = null; render();
  }));

  // Foot bath
  document.querySelectorAll('#bathGroup .bath').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('#bathGroup .bath').forEach(x => x.setAttribute('aria-checked', x === b));
    st.bath = b.dataset.bath; st.bathP = +b.dataset.price; render();
  }));

  // Guests
  if ($('#gMinus')) {
    $('#gMinus').onclick = () => { st.guests = Math.max(1, st.guests-1); render(); };
    $('#gPlus').onclick  = () => { st.guests = Math.min(6, st.guests+1); render(); };
  }

  // Days
  const today = window.zvNow ? zvNow() : new Date(); // studio time, not the visitor's
  const days = [...Array(14)].map((_,i) => { const d = new Date(today); d.setDate(d.getDate()+i); return d; });
  const daysEl = $('#days');
  days.forEach((d,i) => {
    const b = document.createElement('button');
    b.className = 'day'; b.setAttribute('aria-pressed', i===0);
    b.setAttribute('aria-label', (i===0 ? 'Today, ' : i===1 ? 'Tomorrow, ' : '') + d.toLocaleDateString('en',{weekday:'long',day:'numeric',month:'long'}) + (closedDay(d) ? ', closed' : ''));
    b.innerHTML = `${i===0?'Today':i===1?'Tmrw':d.toLocaleDateString('en',{weekday:'short'})}<b>${d.getDate()}</b>${d.toLocaleDateString('en',{month:'short'})}`;
    b.onclick = () => { st.day = i; st.time = null; st.showAll = false; daysEl.querySelectorAll('.day').forEach((x,j)=>x.setAttribute('aria-pressed', j===i)); render(); };
    daysEl.appendChild(b);
  });

  daysEl.addEventListener('keydown', e => {
    const all = [...daysEl.querySelectorAll('.day')], i = all.indexOf(document.activeElement);
    const step = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
    if (i < 0 || !step || !all[i + step]) return;
    e.preventDefault(); all[i + step].focus(); all[i + step].scrollIntoView({ block: 'nearest', inline: 'nearest' });
  });

  // Deterministic "availability" for the demo
  const busy = (dayIdx, m) => ((dayIdx*7 + m/30*13) % 5) === 0;

  function slotsFor(i){
    const d = days[i], [o,c] = HOURS[d.getDay()];
    if (closedDay(d)) return [];
    const nowM = i===0 ? today.getHours()*60 + today.getMinutes() + 30 : 0;
    const out = [];
    for (let m = o; m <= c - len(); m += 30) if (m >= nowM) out.push({m, off: busy(i,m) || (st.guests > 3 && busy(i+1,m))});
    return out;
  }

  $('#moreSlots').onclick = () => { st.showAll = !st.showAll; render(); };

  // Start on the first day that still has free slots
  while (st.day < days.length-1 && !slotsFor(st.day).some(s => !s.off)) st.day++;
  daysEl.querySelectorAll('.day').forEach((x,j)=>x.setAttribute('aria-pressed', j===st.day));

  function render(){
    const g = st.guests, p = price();

    if ($('#guests')) {
      $('#guests').textContent = g + (g===1 ? ' guest' : ' guests');
      $('#gMinus').disabled = g===1; $('#gPlus').disabled = g===6;
    }
    $('#priceMain').innerHTML = `${eur(p)}<small>${UNIT} · ${len()} min</small>`;

    const d = days[st.day], [o,c] = HOURS[d.getDay()];
    $('#dayHours').textContent = closedDay(d) ? `${DAYN[d.getDay()]} · closed` : `${DAYN[d.getDay()]} ${fmt(o)}–${fmt(c)} · Madrid time`;
    const all = slotsFor(st.day), list = st.showAll ? all : all.slice(0,8);
    const sl = $('#slots');
    const focusedTime = document.activeElement && document.activeElement.classList.contains('slot') ? document.activeElement.textContent : null;
    sl.innerHTML = '';
    if (!all.length) sl.innerHTML = `<p style="grid-column:1/-1;margin:0;font-size:14px;color:var(--muted)">${closedDay(d) ? 'We are closed this day. Please pick another.' : 'No times left today. Try tomorrow.'}</p>`;
    list.forEach(s => {
      const b = document.createElement('button');
      b.className='slot'; b.textContent = fmt(s.m); b.disabled = s.off;
      b.setAttribute('aria-pressed', st.time === s.m);
      b.onclick = () => { st.time = s.m; render(); };
      if (s.off) b.setAttribute('aria-label', fmt(s.m) + ', booked');
      sl.appendChild(b);
    });
    if (focusedTime) [...sl.querySelectorAll('.slot')].find(x => x.textContent === focusedTime)?.focus();
    $('#moreSlots').hidden = all.length <= 8;
    $('#moreSlots').setAttribute('aria-expanded', st.showAll);
    $('#moreSlots').textContent = st.showAll ? 'Show fewer times' : `Show all ${all.length} times`;

    $('#sLine').textContent = `${Z.name || 'Ritual'} · ${len()} min${TWO ? '' : ' × ' + g}`;
    $('#sBase').textContent = p == null ? 'At studio' : '€' + p*g;
    $('#sBathRow').hidden = st.bath === 'none';
    $('#sBath').textContent = `${st.bath} foot bath × ${g}`;
    $('#sBathP').textContent = st.bathP ? '+€' + st.bathP*g : 'Free';
    $('#sWhen').textContent = st.time == null ? 'No time selected' :
      `${d.toLocaleDateString('en',{weekday:'long',day:'numeric',month:'long'})} · ${fmt(st.time)}–${fmt(st.time+len())}`;
    const D = dep();
    if ($('#depHead')) $('#depHead').textContent = `€${D} deposit${TWO ? '' : ' / person'}`;
    $('#sDep').textContent = '€' + D*g;
    $('#sRest').textContent = p == null ? 'Balance at studio' : '€' + (Math.max(0, p-D)*g + st.bathP*g);
    if ($('#groupNote')) $('#groupNote').hidden = g < 2;

    const cta = $('#cta');
    cta.textContent = g > 1 ? 'Arrange a group on WhatsApp' : (st.time == null ? 'Choose a time to continue' : `Continue and pay €${D*g} deposit`);
    cta.classList.toggle('btn-sand', st.time != null);
    cta.classList.toggle('btn-dark', st.time == null);
  }
  render();

  $('#cta').onclick = () => {
    if (st.guests > 1) {
      const d = days[st.day];
      const msg = `Hi! We are ${st.guests} people and would like ${Z.name || 'a ritual'} (${len()} min) side by side` + (st.time != null ? ` on ${d.toLocaleDateString('en',{weekday:'long',day:'numeric',month:'long'})} around ${fmt(st.time)}.` : '.');
      window.open(WA + '?text=' + encodeURIComponent(msg), '_blank', 'noopener');
      return;
    }
    if (st.time == null) { $('#slots').scrollIntoView({behavior:'smooth',block:'center'}); $('#slots .slot:not([disabled])')?.focus({preventScroll:true}); window.zvToast && zvToast('Pick a time slot first'); }
    else window.zvToast && zvToast('Prototype: next step is "Your details", then card deposit.');
  };
})();

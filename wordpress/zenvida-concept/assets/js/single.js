(() => {
  const Z = window.ZV_SINGLE || {};
  const OPTS = Z.opts || [[60, 60]];
  const DEPOSIT = Z.deposit || 25;
  const TWO = (Z.flags || []).includes('two');
  const UNIT = TWO ? 'for two' : '/ person';
  // Opening hours: [open, close] in minutes, index 0 = Sunday
  const HOURS = [[720,1290],[690,1230],[690,1230],[690,1230],[690,1290],[660,1290],[630,1290]];
  const DAYN = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const fmt = m => String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0');
  const eur = n => n == null ? 'Price at studio' : '€' + n;
  const $ = s => document.querySelector(s);

  const st = { opt:0, bath:'none', bathP:0, guests:1, day:0, time:null, showAll:false };
  const len = () => OPTS[st.opt][0];
  const price = () => OPTS[st.opt][1];

  // Header border + mobile bar
  const hdr = $('#hdr'), mbar = $('#mbar'), book = $('#book');
  const onScroll = () => {
    hdr && hdr.classList.toggle('scrolled', scrollY > 10);
    if (mbar && book) {
      const r = book.getBoundingClientRect();
      mbar.classList.toggle('show', r.bottom < 0 || r.top > innerHeight);
    }
  };
  addEventListener('scroll', onScroll, {passive:true}); onScroll();

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
  const today = new Date();
  const days = [...Array(14)].map((_,i) => { const d = new Date(today); d.setDate(d.getDate()+i); return d; });
  const daysEl = $('#days');
  days.forEach((d,i) => {
    const b = document.createElement('button');
    b.className = 'day'; b.setAttribute('aria-pressed', i===0);
    b.innerHTML = `${i===0?'Today':i===1?'Tmrw':d.toLocaleDateString('en',{weekday:'short'})}<b>${d.getDate()}</b>${d.toLocaleDateString('en',{month:'short'})}`;
    b.onclick = () => { st.day = i; st.time = null; st.showAll = false; daysEl.querySelectorAll('.day').forEach((x,j)=>x.setAttribute('aria-pressed', j===i)); render(); };
    daysEl.appendChild(b);
  });

  // Deterministic "availability" for the demo
  const busy = (dayIdx, m) => ((dayIdx*7 + m/30*13) % 5) === 0;

  function slotsFor(i){
    const d = days[i], [o,c] = HOURS[d.getDay()];
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

    // slots
    const d = days[st.day], [o,c] = HOURS[d.getDay()];
    $('#dayHours').textContent = `${DAYN[d.getDay()]} ${fmt(o)}–${fmt(c)}`;
    const all = slotsFor(st.day), list = st.showAll ? all : all.slice(0,8);
    const sl = $('#slots'); sl.innerHTML = '';
    if (!all.length) sl.innerHTML = '<p style="grid-column:1/-1;margin:0;font-size:14px;color:var(--muted)">No times left today — try tomorrow.</p>';
    list.forEach(s => {
      const b = document.createElement('button');
      b.className='slot'; b.textContent = fmt(s.m); b.disabled = s.off;
      b.setAttribute('aria-pressed', st.time === s.m);
      b.onclick = () => { st.time = s.m; render(); };
      sl.appendChild(b);
    });
    $('#moreSlots').hidden = all.length <= 8;
    $('#moreSlots').textContent = st.showAll ? 'Show fewer times' : `Show all ${all.length} times`;

    // summary
    $('#sLine').textContent = `${Z.name || 'Ritual'} · ${len()} min${TWO ? '' : ' × ' + g}`;
    $('#sBase').textContent = p == null ? 'At studio' : '€' + p*g;
    $('#sBathRow').hidden = !st.bathP;
    $('#sBath').textContent = `${st.bath} foot bath × ${g}`;
    $('#sBathP').textContent = '+€' + st.bathP*g;
    $('#sWhen').textContent = st.time == null ? 'No time selected' :
      `${d.toLocaleDateString('en',{weekday:'long',day:'numeric',month:'long'})} · ${fmt(st.time)}–${fmt(st.time+len())}`;
    $('#sDep').textContent = '€' + DEPOSIT*g;
    $('#sRest').textContent = p == null ? 'Balance at studio' : '€' + ((p-DEPOSIT)*g + st.bathP*g);

    const cta = $('#cta');
    cta.textContent = st.time == null ? 'Choose a time to continue' : `Continue — pay €${DEPOSIT*g} deposit`;
    cta.classList.toggle('btn-sand', st.time != null);
    cta.classList.toggle('btn-dark', st.time == null);
  }
  render();

  const toast = $('#toast'); let tt;
  $('#cta').onclick = () => {
    if (st.time == null) { $('#slots').scrollIntoView({behavior:'smooth',block:'center'}); toast.textContent = 'Pick a time slot first'; }
    else toast.textContent = 'Prototype: next step is "Your details", then card deposit.';
    toast.classList.add('show'); clearTimeout(tt); tt = setTimeout(()=>toast.classList.remove('show'), 2600);
  };

  // Foot map (only on rituals whose content includes it)
  const zoneBtns = document.querySelectorAll('#zones button');
  if (zoneBtns.length) {
    const setZone = z => {
      zoneBtns.forEach(b => b.setAttribute('aria-pressed', b.dataset.z === z));
      document.querySelectorAll('.foot .z').forEach(e => e.classList.toggle('on', e.dataset.z === z));
    };
    zoneBtns.forEach(b => { b.onclick = () => setZone(b.dataset.z); b.onmouseenter = () => setZone(b.dataset.z); });
    document.querySelectorAll('.foot .z').forEach(e => e.addEventListener('mouseenter', () => setZone(e.dataset.z)));
    setZone('head');
  }

  // Read more
  if ($('#readMore')) $('#readMore').onclick = e => {
    const p = $('#prose'), open = p.classList.toggle('clamp') === false;
    e.target.textContent = open ? 'Read less' : 'Read more';
    e.target.setAttribute('aria-expanded', open);
  };

  // Hours + open now
  const order = [1,2,3,4,5,6,0], tbl = $('#hours'), dow = today.getDay();
  if (tbl) {
    tbl.innerHTML = order.map(i => `<tr class="${i===dow?'today':''}"><td>${DAYN[i]}</td><td>${fmt(HOURS[i][0])} – ${fmt(HOURS[i][1])}</td></tr>`).join('');
    const nowM = today.getHours()*60 + today.getMinutes(), [o,c] = HOURS[dow], on = $('#openNow');
    if (nowM >= o && nowM < c) on.textContent = `Open now · until ${fmt(c)}`;
    else { on.classList.add('closed'); on.textContent = nowM < o ? `Closed · opens ${fmt(o)}` : 'Closed · opens tomorrow'; }
  }

  document.querySelectorAll('.lang button').forEach(b => b.onclick = () =>
    document.querySelectorAll('.lang button').forEach(x => x.setAttribute('aria-pressed', x===b)));
})();

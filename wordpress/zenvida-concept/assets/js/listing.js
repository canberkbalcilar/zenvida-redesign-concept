/* Rituals listing block: filters, sorting, price list, quiz. Data: window.ZV */
(() => {
const D = window.ZV || {};
const AREAS = D.areas || {}, NEEDS = D.needs || {}, R = D.rituals || [];
const $ = s => document.querySelector(s);
if (!$('#zvListing')) return;

const st = {area: AREAS[D.initialArea] ? D.initialArea : 'all', need:null, time:'any', sort:'rec', view:'cards', pick:null, quiz:null, sel:{}};
const eur = n => n==null ? 'Price at studio' : '€'+n;
const minP = r => Math.min(...r.opts.map(o=>o[1] ?? 999));
const minT = r => Math.min(...r.opts.map(o=>o[0]));
const fitsTime = (r,t) => t==='any' || r.opts.some(([m]) => t==='short' ? m<45 : t==='hour' ? m>=45&&m<=80 : m>=90);
const plural = n => n + (n===1 ? ' ritual' : ' rituals');

// Area tiles + segmented control
const tiles = $('#areaTiles');
if (tiles) tiles.innerHTML = Object.entries(AREAS).map(([k,a]) =>
  `<button class="area-tile" style="--c:${a.c}" data-area="${k}">${a.img?`<img src="${a.img}" alt="" loading="lazy" onerror="this.remove()">`:''}<span><b>${a.name}</b><small>${plural(R.filter(r=>r.area===k).length)}</small></span></button>`).join('');
$('#areaSeg').innerHTML = `<button data-area="all" aria-pressed="true">All<span class="n">${R.length}</span></button>` +
  Object.entries(AREAS).map(([k,a]) => `<button data-area="${k}" aria-pressed="false">${a.name}<span class="n">${R.filter(r=>r.area===k).length}</span></button>`).join('');
document.querySelectorAll('#zvListing [data-area]').forEach(b => b.addEventListener('click', () => {
  st.area = b.dataset.area; st.quiz = null; st.pick = null; render();
  if (b.classList.contains('area-tile')) $('#filters').scrollIntoView({behavior:'smooth'});
}));

$('#needChips').innerHTML = Object.entries(NEEDS).map(([k,n]) => `<button class="chip" data-need="${k}" aria-pressed="false">${n}</button>`).join('');
document.querySelectorAll('#needChips [data-need]').forEach(b => b.onclick = () => { st.need = st.need===b.dataset.need ? null : b.dataset.need; st.quiz=null; st.pick=null; render(); });
$('#time').onchange = e => { st.time = e.target.value; st.quiz=null; render(); };
$('#sort').onchange = e => { st.sort = e.target.value; render(); };
document.querySelectorAll('#zvListing .view button').forEach(b => b.onclick = () => { st.view = b.dataset.v; render(); });

function filtered(){
  let list = R.filter(r => (st.area==='all'||r.area===st.area) && (!st.need||r.needs.includes(st.need)) && fitsTime(r,st.time));
  const s = st.sort;
  list.sort((a,b) => s==='price' ? minP(a)-minP(b) : s==='time' ? minT(a)-minT(b) : (b.pop + (st.need&&b.needs[0]===st.need?3:0)) - (a.pop + (st.need&&a.needs[0]===st.need?3:0)));
  return list;
}

function card(r){
  const a = AREAS[r.area] || {name:'', c:'#999'}, i = st.sel[r.slug] ?? 0, o = r.opts[i];
  const badges = (r.slug===st.pick?'<span class="badge pick">Top pick for you</span>':'') +
    (r.flags.includes('sig')?'<span class="badge sig">Signature</span>':'') +
    (r.flags.includes('seas')?'<span class="badge seas">Seasonal</span>':'');
  return `<article class="card${r.slug===st.pick?' pick':''}" style="--c:${a.c}">
    <div class="ctop"><span class="area-dot">${a.name}</span>${badges}</div>
    <h3>${r.name}</h3>
    <p>${r.desc}</p>
    <div class="needs">${r.needs.map(n=>`<span>${NEEDS[n]||n}</span>`).join('')}</div>
    <div class="opts" role="group" aria-label="Duration">${r.opts.length>1 ? r.opts.map(([m,p],j)=>`<button class="opt" data-slug="${r.slug}" data-i="${j}" aria-pressed="${j===i}"><b>${m} min</b><small>${eur(p)}</small></button>`).join('') : `<span class="opt" style="cursor:default"><b>${o[0]} min</b><small>${eur(o[1])}</small></span>`}</div>
    <div class="cfoot"><a href="${r.url}">Details</a><button class="btn btn-dark" data-book="${r.slug}" data-i="${i}">Book${o[1]?` · €${o[1]}`:''}</button></div>
  </article>`;
}

function plist(list){
  return `<table class="plist"><tbody>${list.map(r=>`<tr>
    <td><div class="nm">${r.name}${r.flags.includes('sig')?' <span class="badge sig" style="vertical-align:4px">Signature</span>':''}<small>${r.desc}</small></div></td>
    <td class="pr">${r.opts.map(([m,p])=>`<span>${m} min · ${eur(p)}</span>`).join(' ')}</td>
    <td class="bk"><button class="btn btn-dark" data-book="${r.slug}">Book</button></td></tr>`).join('')}</tbody></table>`;
}

function render(){
  document.querySelectorAll('#areaSeg [data-area]').forEach(b => b.setAttribute('aria-pressed', b.dataset.area===st.area));
  document.querySelectorAll('#needChips [data-need]').forEach(b => b.setAttribute('aria-pressed', b.dataset.need===st.need));
  document.querySelectorAll('#zvListing .view button').forEach(b => b.setAttribute('aria-pressed', b.dataset.v===st.view));
  $('#time').value = st.time; $('#sort').value = st.sort;

  const list = filtered(), out = $('#out');
  const active = st.area!=='all' || st.need || st.time!=='any';
  $('#rhead').innerHTML = `<span><b>${list.length}</b> ${list.length===1?'ritual':'rituals'}${st.need?` for <b>${(NEEDS[st.need]||'').toLowerCase()}</b>`:''}</span>` +
    (st.quiz ? `<span class="quiz-banner">Based on your answers <button class="clear" id="redo">Retake</button></span>` : '') +
    (active ? `<button class="clear" id="clearAll">Clear filters</button>` : '');

  if (!list.length) {
    out.innerHTML = `<div class="empty"><h3>Nothing matches yet</h3><p style="color:var(--muted);margin:0 0 18px">Try a different length of time, or ask us on WhatsApp — we'll suggest something.</p><button class="btn btn-dark" id="clearAll2">Clear filters</button></div>`;
  } else if (st.area==='all' && !st.need && st.time==='any' && st.sort==='rec') {
    out.innerHTML = Object.entries(AREAS).map(([k,a]) => {
      const g = list.filter(r=>r.area===k); if(!g.length) return '';
      return `<section class="group" id="g-${k}">
        <div class="ghead" style="--c:${a.c}">
          <div class="gimg">${a.img?`<img src="${a.img}" alt="" loading="lazy" onerror="this.remove()">`:''}</div>
          <div><h2>${a.name}</h2><p>${a.intro}</p>${a.long?`<p class="long" hidden>${a.long}</p><button class="more" aria-expanded="false">Read more</button>`:''}</div>
          <span class="cnt">${plural(g.length)}${(p=>p.length?` · from €${Math.min(...p)}`:'')(g.map(minP).filter(x=>x<999))}</span>
        </div>
        ${st.view==='cards' ? `<div class="grid">${g.map(card).join('')}</div>` : plist(g)}
      </section>`;
    }).join('');
  } else {
    out.innerHTML = st.view==='cards' ? `<div class="grid">${list.map(card).join('')}</div>` : plist(list);
  }
  const clr = () => { Object.assign(st,{area:'all',need:null,time:'any',quiz:null,pick:null}); render(); };
  $('#clearAll') && ($('#clearAll').onclick = clr);
  $('#clearAll2') && ($('#clearAll2').onclick = clr);
  $('#redo') && ($('#redo').onclick = openQuiz);
}

// Delegated clicks inside the listing: duration options, book, read more
$('#zvListing').addEventListener('click', e => {
  const opt = e.target.closest('button.opt');
  if (opt) { st.sel[opt.dataset.slug] = +opt.dataset.i; render(); return; }
  const more = e.target.closest('.ghead .more');
  if (more) { const l = more.previousElementSibling; l.hidden = !l.hidden; more.textContent = l.hidden?'Read more':'Read less'; more.setAttribute('aria-expanded', !l.hidden); return; }
  const bk = e.target.closest('[data-book]');
  if (bk) {
    e.preventDefault();
    const r = R.find(x=>x.slug===bk.dataset.book);
    if (r) location.href = r.url + '#book';
  }
});

// Quiz
const Q = [
  {q:'What needs care today?', k:'need', o:[['feet','Tired feet','Walking, standing, long days'],['back','Back, neck & shoulders','Desk, screens, knots'],['stress','Stress & sleep','Busy mind, poor sleep'],['travel','Just travelled','Jet lag, swollen legs'],['sport','Sore muscles','Training or a race'],['glow','My face & skin','Puffiness, dull skin']]},
  {q:'How much time do you have?', k:'time', o:[['short','Under 45 min','A quick reset'],['hour','About an hour','The classic choice'],['long','90 min or more','Fully switch off'],['any','Flexible','Show me everything']]},
  {q:'Who is coming?', k:'who', o:[['me','Just me',''],['two','Two of us','Side by side'],['preg','I\'m pregnant','Safe, adapted care']]}
];
let qi = 0, ans = {};
const dlg = $('#quiz');
function drawQ(){
  const q = Q[qi];
  $('#qzStep').textContent = `Question ${qi+1} of ${Q.length}`;
  $('#qzBar').style.width = ((qi+1)/Q.length*100)+'%';
  $('#qzQ').textContent = q.q;
  $('#qzBack').hidden = qi===0;
  $('#qzOpts').innerHTML = q.o.map(([v,t,s]) => `<button data-v="${v}">${t}${s?`<small>${s}</small>`:''}</button>`).join('');
  $('#qzOpts').querySelectorAll('button').forEach(b => b.onclick = () => { ans[q.k] = b.dataset.v; qi < Q.length-1 ? (qi++, drawQ()) : finishQuiz(); });
}
function openQuiz(){ qi=0; ans={}; drawQ(); dlg.showModal(); }
function finishQuiz(){
  dlg.close();
  st.area='all'; st.sort='rec'; st.view='cards';
  st.need = ans.who==='two' ? 'two' : ans.who==='preg' ? 'preg' : ans.need;
  if (!NEEDS[st.need]) st.need = null;
  st.time = (ans.who==='two'||ans.who==='preg') ? 'any' : ans.time;
  if (!filtered().length) st.time = 'any';
  st.pick = filtered()[0]?.slug || null; st.quiz = true;
  render();
  $('#results').scrollIntoView({behavior:'smooth'});
}
document.querySelectorAll('[data-open-quiz]').forEach(b => b.addEventListener('click', e => { e.preventDefault(); openQuiz(); }));
$('#qzClose').onclick = () => dlg.close();
$('#qzBack').onclick = () => { qi--; drawQ(); };
dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });

// Deep links: ?area=feet-legs&need=travel&quiz=1
const qp = new URLSearchParams(location.search);
if (AREAS[qp.get('area')]) st.area = qp.get('area');
if (NEEDS[qp.get('need')]) st.need = qp.get('need');
if (qp.get('area') || qp.get('need')) requestAnimationFrame(() => $('#filters').scrollIntoView());
if (qp.get('quiz')) setTimeout(openQuiz, 200);

render();
})();

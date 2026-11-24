/* Contact form and gift card builder. Sends to admin-ajax (action zv_message); see inc/messages.php. */
(() => {
  const C = window.ZV_FORMS || {};
  const eur = n => '€' + n;

  document.querySelectorAll('form[data-zv-form]').forEach(form => {
    const kind = form.dataset.zvForm;
    const status = form.querySelector('.zf-status');
    const ts = form.querySelector('[name=ts]');
    if (ts) ts.value = Date.now();

    /* ---------- gift card builder ---------- */
    const gift = { type: 'amount', amount: 0, ritual: '', price: 0, send: 'me' };
    const root = form.closest('.gift');
    const $ = s => (root || form).querySelector(s);
    const radio = (group, btn) => group.querySelectorAll('[role=radio]').forEach(b => b.setAttribute('aria-checked', b === btn));
    const detail = k => { const el = form.querySelector(`[data-k="${k}"]`); return el ? el.value.trim() : ''; };
    const what = () => gift.type === 'amount' ? eur(gift.amount || 0) : gift.ritual;
    const value = () => gift.type === 'amount' ? gift.amount : gift.price;

    function paint() {
      if (!root) return;
      const v = value();
      $('[data-gift-total]').textContent = eur(v || 0);
      $('[data-gp="what"]').textContent = what() || 'Choose a gift';
      $('[data-gp="what"]').classList.toggle('long', gift.type === 'ritual');
      $('[data-gp="to"]').textContent = detail('To') || '…';
      $('[data-gp="from"]').textContent = detail('From') || '…';
      $('[data-gp="msg"]').textContent = detail('Card message') || 'Your message appears here.';
      const wa = `Hi! I'd like a gift card: ${what()}${detail('To') ? ' for ' + detail('To') : ''}.`;
      $('[data-gift-wa]').href = 'https://wa.me/' + (C.whatsapp || '') + '?text=' + encodeURIComponent(wa);
    }

    if (kind === 'gift') {
      const chips = [...form.querySelectorAll('[data-amount]')];
      const other = form.querySelector('.gift-other input');
      const sel = form.querySelector('.gift-ritual select');
      const pressed = chips.find(c => c.getAttribute('aria-pressed') === 'true') || chips[0];
      gift.amount = pressed ? +pressed.dataset.amount : 50;
      const pickRitual = () => { const o = sel.selectedOptions[0]; gift.ritual = o ? o.value : ''; gift.price = o ? +o.dataset.price : 0; };
      if (sel) pickRitual();

      chips.forEach(c => c.addEventListener('click', () => {
        chips.forEach(x => x.setAttribute('aria-pressed', x === c)); other.value = ''; gift.amount = +c.dataset.amount; paint();
      }));
      other.addEventListener('input', () => {
        const n = Math.round(+other.value || 0);
        chips.forEach(x => x.setAttribute('aria-pressed', 'false'));
        gift.amount = n; paint();
      });
      form.querySelectorAll('.gift-type [role=radio]').forEach(b => b.addEventListener('click', () => {
        radio(b.parentElement, b); gift.type = b.dataset.type;
        form.querySelector('.gift-amount').hidden = gift.type !== 'amount';
        form.querySelector('.gift-ritual').hidden = gift.type !== 'ritual';
        paint();
      }));
      if (sel) sel.addEventListener('change', () => { pickRitual(); paint(); });
      form.querySelectorAll('.gift-send [role=radio]').forEach(b => b.addEventListener('click', () => {
        radio(b.parentElement, b); gift.send = b.dataset.send;
        form.querySelector('.gift-them').hidden = gift.send !== 'them';
      }));
      form.querySelectorAll('[data-k]').forEach(i => i.addEventListener('input', paint));
      const d = form.querySelector('[data-k="Send on"]');
      if (d) d.min = new Date().toISOString().slice(0, 10);
      paint();
    }

    if (kind === 'job') {
      document.querySelectorAll('[data-apply-role]').forEach(a => a.addEventListener('click', () => {
        const sel = form.querySelector('[name=topic]');
        const opt = [...sel.options].find(o => o.value.toLowerCase() === a.dataset.applyRole.toLowerCase());
        if (opt) sel.value = opt.value;
      }));
    }

    /* ---------- validation and sending ---------- */
    const say = (msg, ok) => { status.textContent = msg; status.className = 'zf-status ' + (ok ? 'ok' : 'err'); };
    const bad = (el, msg) => { if (el) { el.setAttribute('aria-invalid', 'true'); el.focus(); } say(msg, false); return false; };

    function valid() {
      form.querySelectorAll('[aria-invalid]').forEach(e => e.removeAttribute('aria-invalid'));
      const f = n => form.querySelector(`[name="${n}"]`);
      if (kind === 'gift') {
        if (gift.type === 'amount' && (!gift.amount || gift.amount < 10)) return bad(form.querySelector('.gift-other input'), 'Please choose an amount (at least €10).');
        if (gift.send === 'them' && !/^\S+@\S+\.\S+$/.test(detail('Recipient email'))) return bad(form.querySelector('[data-k="Recipient email"]'), 'Please add their email address.');
      }
      if (!f('name').value.trim()) return bad(f('name'), 'Please add your name.');
      if (!/^\S+@\S+\.\S+$/.test(f('email').value.trim())) return bad(f('email'), 'Please check your email address.');
      if (kind === 'contact' && !f('message').value.trim()) return bad(f('message'), 'Please write your message.');
      if (kind === 'job') {
        const cv = f('cv') && f('cv').files[0];
        if (cv && cv.size > 5 * 1024 * 1024) return bad(f('cv'), 'The CV is larger than 5 MB. Please send a smaller file.');
        if (cv && !/\.(pdf|docx?)$/i.test(cv.name)) return bad(f('cv'), 'Please send your CV as a PDF or Word file.');
        if (!f('message').value.trim() && !cv) return bad(f('message'), 'Tell us a little about yourself, or attach your CV.');
      }
      if (!f('consent').checked) return bad(f('consent'), 'Please tick the box so we may keep your message to reply to you.');
      return true;
    }

    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!valid()) return;
      const btn = form.querySelector('button[type=submit]');
      const fd = new FormData(form);
      fd.append('action', 'zv_message');
      fd.append('kind', kind);
      fd.append('page', location.href);
      if (kind === 'job') ['Experience', 'Availability', 'Languages'].forEach(k => detail(k) && fd.append(`details[${k}]`, detail(k)));
      if (kind === 'contact') form.querySelectorAll('[data-k]').forEach(el => el.value.trim() && fd.append(`details[${el.dataset.k}]`, el.value.trim()));
      if (kind === 'gift') {
        fd.append('topic', 'Gift card');
        fd.append('details[Gift]', what());
        fd.append('details[Value]', eur(value()));
        ['To', 'From', 'Card message'].forEach(k => detail(k) && fd.append(`details[${k}]`, detail(k)));
        fd.append('details[Delivery]', gift.send === 'them' ? 'Email to the recipient' : 'Email to the buyer');
        if (gift.send === 'them') ['Recipient email', 'Send on'].forEach(k => detail(k) && fd.append(`details[${k}]`, detail(k)));
      }
      btn.disabled = true; say('Sending…', true);
      try {
        const r = await fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' });
        const j = await r.json();
        if (!j.success) {
          btn.disabled = false;
          const fld = j.data && j.data.field ? form.querySelector(`[name="${j.data.field}"]`) : null;
          return bad(fld, (j.data && j.data.message) || 'Sorry, something went wrong. Please try WhatsApp.');
        }
        const done = document.createElement('div');
        done.className = 'zf-done';
        done.setAttribute('role', 'status');
        done.innerHTML = '<span class="zf-tick">✓</span><h3>' + ({ gift: 'Request received', job: 'Application sent' }[kind] || 'Message sent') + '</h3><p></p>';
        done.querySelector('p').textContent = j.data.message;
        if (j.data.payUrl) {
          const a = document.createElement('a');
          a.className = 'btn btn-dark'; a.href = j.data.payUrl; a.target = '_blank'; a.rel = 'noopener';
          a.textContent = 'Continue to payment';
          done.appendChild(a);
        }
        form.replaceWith(done);
        done.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } catch (err) {
        btn.disabled = false;
        say('No connection. Please try again, or write to us on WhatsApp.', false);
      }
    });
  });
})();

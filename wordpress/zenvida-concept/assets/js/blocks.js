/* ZenVida blocks — editor UI. Front-end markup is rendered in PHP (inc/blocks.php). */
(function (wp) {
  const { registerBlockType } = wp.blocks;
  const { createElement: h, Fragment } = wp.element;
  const { RichText, InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps, useInnerBlocksProps, InnerBlocks } = wp.blockEditor;
  const { PanelBody, TextControl, TextareaControl, SelectControl, ToggleControl, Button, RangeControl, Notice } = wp.components;
  const SSR = wp.serverSideRender;
  const E = window.ZV_EDITOR || { icons: {}, img: {} };

  /* ---------- helpers ---------- */
  const upd = (p, k) => v => p.setAttributes({ [k]: v });
  const RT = (p, k, tag, cls, ph, plain) => h(RichText, {
    tagName: tag, className: cls, value: p.attributes[k], onChange: upd(p, k),
    placeholder: ph || 'Write…', allowedFormats: plain ? [] : ['core/bold', 'core/italic', 'core/link'],
  });
  const Icon = name => h('span', { className: 'zv-ic', dangerouslySetInnerHTML: { __html: E.icons[name] || E.icons.star || '' } });
  const Img = (p, k, cls, fallback) => h(MediaUploadCheck, { fallback: h('img', { src: p.attributes[k] || fallback, className: cls, alt: '' }) },
    h(MediaUpload, { onSelect: m => p.setAttributes({ [k]: m.url }), allowedTypes: ['image'],
      render: ({ open }) => p.attributes[k] || fallback
        ? h('img', { src: p.attributes[k] || fallback, className: cls, alt: '', onClick: open, title: 'Click to change the image', style: { cursor: 'pointer' } })
        : h(Button, { variant: 'secondary', onClick: open, className: 'zv-img-btn' }, 'Choose image') }));

  const F = {
    text: (p, k, label, help) => h(TextControl, { key: k, label, help, value: p.attributes[k] || '', onChange: upd(p, k) }),
    area: (p, k, label, help) => h(TextareaControl, { key: k, label, help, value: p.attributes[k] || '', onChange: upd(p, k) }),
    toggle: (p, k, label) => h(ToggleControl, { key: k, label, checked: !!p.attributes[k], onChange: upd(p, k) }),
    select: (p, k, label, opts) => h(SelectControl, { key: k, label, value: p.attributes[k], options: opts.map(o => ({ value: o[0], label: o[1] })), onChange: upd(p, k) }),
    num: (p, k, label, min, max) => h(RangeControl, { key: k, label, value: p.attributes[k], min, max, onChange: upd(p, k) }),
    img: (p, k, label) => h(MediaUploadCheck, { key: k }, h(MediaUpload, {
      onSelect: m => p.setAttributes({ [k]: m.url }), allowedTypes: ['image'],
      render: ({ open }) => h('div', { className: 'zv-side-img' },
        h('p', null, label),
        p.attributes[k] ? h('img', { src: p.attributes[k], alt: '' }) : null,
        h(Button, { variant: 'secondary', onClick: open }, p.attributes[k] ? 'Replace image' : 'Choose image'),
        p.attributes[k] ? h(Button, { variant: 'link', isDestructive: true, onClick: () => p.setAttributes({ [k]: '' }) }, 'Use default') : null),
    })),
    note: text => h(Notice, { status: 'info', isDismissible: false, className: 'zv-note' }, text),
  };
  const Side = (title, ...fields) => h(InspectorControls, null, h(PanelBody, { title, initialOpen: true }, ...fields.filter(Boolean)));

  const BTN = [['dark', 'Dark'], ['light', 'Light'], ['glass', 'Glass (on photos)'], ['ghost', 'Outline'], ['sand', 'Sand']];
  const ICONS = [['home', 'House'], ['heart', 'Heart'], ['leaf', 'Leaf'], ['wave', 'Waves'], ['moon', 'Moon'], ['walk', 'Walking'], ['star', 'Star'], ['pin', 'Location'], ['clock', 'Clock'], ['award', 'Award'], ['users', 'People'], ['check', 'Check']];
  const COLORS = [['#C9A97A', 'Sand'], ['#8E9A82', 'Sage'], ['#C79A8E', 'Rose'], ['#8d4a58', 'Plum'], ['#8FA3B0', 'Sky'], ['#4A443B', 'Ink']];

  const reg = (name, edit, inner) => registerBlockType('zv/' + name, {
    edit,
    save: inner ? () => h(InnerBlocks.Content) : () => null,
  });

  /* Server-rendered preview with fields in the sidebar */
  const ssrBlock = (name, sideTitle, fields, note) => reg(name, function (p) {
    const bp = useBlockProps({ className: 'zv-ssr' });
    return h(Fragment, null,
      Side(sideTitle, note ? F.note(note) : null, ...fields(p)),
      h('div', bp, h(SSR, { block: 'zv/' + name, attributes: p.attributes })));
  });

  /* Grid container whose children are one block type */
  const container = (name, cls, child, count, label) => reg(name, function () {
    const bp = useBlockProps({ className: cls });
    const ip = useInnerBlocksProps(bp, {
      allowedBlocks: [child], template: Array.from({ length: count }, () => [child]),
      renderAppender: () => h(InnerBlocks.ButtonBlockAppender),
    });
    return h('div', ip);
  }, true);

  /* ---------- page-level blocks ---------- */
  reg('hero', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'hero' });
    return h(Fragment, null,
      Side('Hero',
        F.img(p, 'image', 'Background image'),
        F.text(p, 'btn1Url', 'Button 1 link'), F.select(p, 'btn1Style', 'Button 1 style', BTN),
        F.text(p, 'btn2Url', 'Button 2 link', 'Empty = rituals page'), F.select(p, 'btn2Style', 'Button 2 style', BTN),
        F.toggle(p, 'showRating', 'Show rating'), F.toggle(p, 'showOpen', 'Show "open now"'),
        F.note('Rating, address and opening hours come from Appearance → ZenVida settings.')),
      h('section', bp,
        h('img', { src: a.image || E.img.hero, alt: '' }),
        h('div', { className: 'wrap' },
          RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true),
          RT(p, 'title', 'h1', null, 'Headline (Shift+Enter for a new line, italic for the gold word)'),
          RT(p, 'text', 'p', null, 'Intro text'),
          h('div', { className: 'hero-ctas' }, RT(p, 'btn1Label', 'span', 'btn btn-' + a.btn1Style, 'Button', true), RT(p, 'btn2Label', 'span', 'btn btn-' + a.btn2Style, 'Button', true)),
          h('div', { className: 'hero-trust' },
            a.showRating && h('span', null, h('span', { className: 'stars' }, '★★★★★'), h('b', null, E.rating), ' from ' + E.ratingCount),
            a.showOpen && h('span', { className: 'open' }, 'Open now'),
            RT(p, 'trust1', 'span', null, 'Trust point', true), RT(p, 'trust2', 'span', null, 'Trust point', true)))));
  });

  ssrBlock('quick-book', 'Quick booking bar', p => [
    F.text(p, 'buttonLabel', 'Button label'), F.text(p, 'note1', 'Note 1'), F.text(p, 'note2', 'Note 2'), F.text(p, 'note3', 'Note 3'),
  ], 'The "What" list fills itself: the 3 most popular rituals, every area, and the quiz.');

  reg('section', function (p) {
    const a = p.attributes;
    const bp = useBlockProps({ className: 'block bg-' + a.background + (a.padTop ? '' : ' pt0') });
    const ip = useInnerBlocksProps({ className: 'zv-section-inner' }, {});
    const hasHead = a.eyebrow || a.title || a.text || a.linkLabel;
    return h(Fragment, null,
      Side('Section',
        F.select(p, 'background', 'Background', [['paper', 'Light'], ['cream', 'Cream'], ['dark', 'Dark']]),
        F.toggle(p, 'padTop', 'Space above'),
        F.text(p, 'anchor', 'Anchor', 'Lets links like #offers jump here'),
        F.text(p, 'linkUrl', 'Header link URL'),
        F.select(p, 'linkStyle', 'Header link style', [['link', 'Text link'], ['button', 'Outline button']])),
      h('section', bp, h('div', { className: 'wrap' },
        (hasHead || p.isSelected) && h('div', { className: 'sec-head' },
          h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow (optional)', true), RT(p, 'title', 'h2', null, 'Section title (optional)'), RT(p, 'text', 'p', 'lead', 'Intro text (optional)')),
          RT(p, 'linkLabel', 'span', a.linkStyle === 'button' ? 'btn btn-ghost' : 'link', 'Link (optional)', true)),
        h('div', ip))));
  }, true);

  reg('ritual-hero', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'wrap' });
    return h(Fragment, null,
      Side('Rituals hero', F.img(p, 'image', 'Image'), F.text(p, 'featured', 'Featured ritual (slug)', 'Shown as "Most booked" on the photo, e.g. deluxe-reflexology'),
        F.note('"{count}" in a button is replaced by the number of rituals.')),
      h('div', bp, h('section', { className: 'hero-split' },
        h('div', null,
          RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), RT(p, 'title', 'h1', null, 'Headline'), RT(p, 'text', 'p', 'lead', 'Intro'),
          h('div', { className: 'hero-ctas' }, RT(p, 'quizLabel', 'span', 'btn btn-dark', 'Quiz button', true), RT(p, 'btn2Label', 'span', 'btn btn-ghost', 'Button', true))),
        h('div', { className: 'hero-img' }, h('img', { src: a.image || E.img.room, alt: '' })))));
  });

  ssrBlock('ritual-listing', 'Rituals list', p => [F.toggle(p, 'showTiles', 'Show area tiles above the filters')],
    'Rituals, prices and areas come from the Rituals menu. Filters and the quiz work on the live page.');

  /* ---------- section content ---------- */
  container('area-grid', 'areas', 'zv/area-tile', 4);
  reg('area-tile', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'area', style: { '--c': a.color } });
    return h(Fragment, null,
      Side('Area tile', F.img(p, 'image', 'Image'), F.text(p, 'url', 'Link'), F.select(p, 'color', 'Colour behind the photo', COLORS)),
      h('div', bp, a.image ? h('img', { src: a.image, alt: '' }) : null,
        h('span', { className: 't' }, h('span', null, RT(p, 'title', 'b', null, 'Title', true), RT(p, 'subtitle', 'small', null, 'Subtitle', true)), h('span', { className: 'go' }, Icon('arrow')))));
  });

  ssrBlock('need-chips', 'Need shortcuts', p => [F.text(p, 'label', 'Label')], 'Chips come from Rituals → Needs.');
  ssrBlock('signature', 'Signature rituals', p => [F.num(p, 'count', 'How many', 1, 12), F.text(p, 'cta', 'Card link label')],
    'Shows rituals ticked "Signature" in their Ritual details, most popular first.');

  reg('pillars', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'pillars' });
    const ip = useInnerBlocksProps({ className: 'pillar-list' }, { allowedBlocks: ['zv/pillar'], template: [['zv/pillar'], ['zv/pillar'], ['zv/pillar']], renderAppender: () => h(InnerBlocks.ButtonBlockAppender) });
    return h(Fragment, null,
      Side('Image + features', F.img(p, 'image', 'Image')),
      h('div', bp,
        h('div', { className: 'photo' }, h('img', { src: a.image || E.img.roomTall, alt: '' }),
          h('div', { className: 'badge' }, RT(p, 'badgeTitle', 'b', null, 'Badge', true), RT(p, 'badgeText', 'span', null, 'Badge text', true))),
        h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), RT(p, 'title', 'h2', null, 'Title'), h('div', ip))));
  }, true);
  reg('pillar', function (p) {
    const bp = useBlockProps({ className: 'pillar' });
    return h(Fragment, null, Side('Feature', F.select(p, 'icon', 'Icon', ICONS)),
      h('div', bp, h('span', { className: 'ic' }, Icon(p.attributes.icon)), h('div', null, RT(p, 'title', 'h3', null, 'Title', true), RT(p, 'text', 'p', null, 'Text'))));
  });

  container('steps', 'steps', 'zv/step', 4);
  reg('step', function (p) {
    return h('div', useBlockProps({ className: 'step' }), RT(p, 'title', 'h3', null, 'Step title', true), RT(p, 'text', 'p', null, 'What happens'));
  });

  reg('callout', function (p) {
    return h(Fragment, null, Side('Callout', F.text(p, 'icon', 'Icon (emoji or symbol)'), F.text(p, 'linkUrl', 'Link URL')),
      h('div', useBlockProps({ className: 'callout' }), h('span', { className: 'ic' }, p.attributes.icon),
        RT(p, 'text', 'p', null, 'Callout text'), RT(p, 'price', 'strong', 'price', 'Price (optional)', true), RT(p, 'linkLabel', 'span', 'link', 'Link (optional)', true)));
  });

  reg('reviews', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'reviews' });
    const ip = useInnerBlocksProps({ className: 'rev-list' }, { allowedBlocks: ['zv/review'], template: [['zv/review'], ['zv/review'], ['zv/review']], renderAppender: () => h(InnerBlocks.ButtonBlockAppender) });
    return h(Fragment, null,
      Side('Reviews', F.toggle(p, 'showSample', 'Show "sample quotes" label'), F.note('Score and guest count come from ZenVida settings.')),
      h('div', bp, h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), h('div', { className: 'score' }, E.rating), h('div', { className: 'stars' }, '★★★★★'),
        h('p', { style: { color: 'var(--muted)', fontSize: '14px', margin: '8px 0 0' } }, 'From ' + E.ratingCount),
        a.showSample && h('span', { className: 'sample' }, 'Sample quotes — connect real reviews')), h('div', ip)));
  }, true);
  reg('review', function (p) {
    return h(Fragment, null, Side('Review', F.num(p, 'stars', 'Stars', 1, 5)),
      h('div', useBlockProps({ className: 'rev' }), h('div', { className: 'stars' }, '★'.repeat(p.attributes.stars || 5)),
        RT(p, 'text', 'p', null, '"What the guest said"'), h('small', null, RT(p, 'author', 'span', null, 'Name', true), ' · ', RT(p, 'source', 'span', null, 'Source', true))));
  });

  container('offers', 'offers', 'zv/offer', 4);
  reg('offer', function (p) {
    const a = p.attributes;
    return h(Fragment, null, Side('Offer card', F.text(p, 'url', 'Link'), F.toggle(p, 'featured', 'Highlight this card')),
      h('div', useBlockProps({ className: 'offer' + (a.featured ? ' feature' : '') }),
        RT(p, 'kicker', 'span', 'k', 'Label', true), RT(p, 'title', 'b', null, 'Title', true), RT(p, 'text', 'p', null, 'Description'), RT(p, 'linkLabel', 'span', 'go', 'Link label', true)));
  });

  reg('newsletter', function (p) {
    return h('div', useBlockProps({ className: 'news' }),
      h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), RT(p, 'title', 'h2', null, 'Title'), RT(p, 'text', 'p', null, 'Text'), RT(p, 'smallprint', 'small', null, 'Small print', true)),
      h('div', { className: 'nform' },
        h('div', { className: 'seg' }, h('button', { type: 'button', 'aria-pressed': 'true' }, 'Email'), h('button', { type: 'button', 'aria-pressed': 'false' }, 'WhatsApp')),
        h('div', { className: 'nrow' }, h('input', { type: 'email', placeholder: 'you@example.com', disabled: true }), RT(p, 'buttonLabel', 'span', 'btn btn-dark', 'Button', true)),
        h('div', { className: 'consent' }, h('input', { type: 'checkbox', disabled: true }), RT(p, 'consent', 'span', null, 'Consent text'))));
  });

  ssrBlock('contact-form', 'Contact form', p => [F.area(p, 'topics', 'Topics', 'Comma separated. Leave empty to hide the "About" menu.'), F.text(p, 'buttonLabel', 'Button text'), F.toggle(p, 'showPhone', 'Ask for a phone number')], 'Messages arrive under Messages in the admin and by email (Site settings → Contact form).');
  ssrBlock('gift-builder', 'Gift card builder', p => [F.text(p, 'amounts', 'Amounts (€)', 'Comma separated, e.g. 25, 50, 75, 100'), F.text(p, 'btnLabel', 'Button text'), F.area(p, 'note', 'Small print under the button')], 'Rituals and prices come from Rituals automatically. Requests arrive under Messages.');
  container('packs', 'packs', 'zv/pack', 3);
  reg('pack', function (p) {
    const a = p.attributes;
    const items = (a.items || '').split('\n').filter(s => s.trim());
    return h(Fragment, null,
      Side('Pricing card',
        F.area(p, 'items', 'What is included', 'One line each'),
        F.text(p, 'btnUrl', 'Button link', 'Empty = WhatsApp message with the card name'),
        F.toggle(p, 'featured', 'Highlight this card')),
      h('div', useBlockProps({ className: 'pack' + (a.featured ? ' featured' : '') }),
        RT(p, 'save', 'span', 'pack-save', 'Badge, e.g. Save 20% (optional)', true),
        RT(p, 'kicker', 'span', 'k', 'Name, e.g. Zen Starter', true),
        RT(p, 'title', 'h3', null, 'Title, e.g. 5 sessions', true),
        h('div', { className: 'pack-price' }, RT(p, 'price', 'b', null, '€240', true), RT(p, 'was', 's', null, 'old price (optional)', true)),
        items.length ? h('ul', null, items.map((t, i) => h('li', { key: i }, Icon('check'), h('span', null, t)))) : F.note('Add what is included in the panel on the right →'),
        RT(p, 'btnLabel', 'span', 'btn ' + (a.featured ? 'btn-sand' : 'btn-dark'), 'Button', true),
        RT(p, 'note', 'small', null, 'Small print (optional)', true)));
  });
  ssrBlock('events', 'Upcoming events', p => [F.num(p, 'count', 'How many events', 1, 9), F.area(p, 'empty', 'Text when nothing is planned')], 'Shows the next events from Events in the admin. Past events disappear by themselves.');
  ssrBlock('journal', 'Latest journal posts', p => [F.num(p, 'count', 'How many posts', 1, 9)], 'Shows your latest Posts with their featured image.');

  reg('split', function () {
    const ip = useInnerBlocksProps(useBlockProps({ className: 'fv' }), {
      allowedBlocks: ['zv/faq', 'zv/visit', 'zv/contact-form'], template: [['zv/faq', { eyebrow: 'FAQ', title: 'Good to know' }, [['zv/faq-item'], ['zv/faq-item']]], ['zv/visit']],
    });
    return h('div', ip);
  }, true);

  reg('faq', function (p) {
    const a = p.attributes, bp = useBlockProps({ className: 'faq' });
    const ip = useInnerBlocksProps({ className: 'zv-faq-items' }, {
      allowedBlocks: ['zv/faq-item'], template: [['zv/faq-item'], ['zv/faq-item']],
      renderAppender: () => h(InnerBlocks.ButtonBlockAppender),
    });
    return h('div', bp,
      (a.eyebrow || a.title || p.isSelected) && h(Fragment, null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow (optional)', true), RT(p, 'title', 'h2', null, 'Title (optional)')),
      h('div', ip));
  }, true);
  reg('faq-item', function (p) {
    return h(Fragment, null, Side('Question', F.toggle(p, 'open', 'Open by default')),
      h('div', useBlockProps({ className: 'zv-faq-item' }), RT(p, 'question', 'div', 'q', 'Question', true), RT(p, 'answer', 'p', null, 'Answer')));
  });

  ssrBlock('visit', 'Visit us', p => [
    F.text(p, 'eyebrow', 'Eyebrow'), F.text(p, 'title', 'Title'),
    F.select(p, 'layout', 'Layout', [['card', 'Card (for two columns)'], ['wide', 'Wide']]),
    F.text(p, 'bookLabel', 'Book button'), F.text(p, 'bookUrl', 'Book link', 'Empty = rituals page'), F.text(p, 'waLabel', 'WhatsApp button'),
  ], 'Address, phone, email, WhatsApp and opening hours are edited once in Appearance → ZenVida settings.');

  reg('foot-map', function (p) {
    const zones = [1, 2, 3, 4].map(i => h('li', { key: i }, h('button', { type: 'button', 'aria-pressed': i === 1 ? 'true' : 'false' },
      h('span', { className: 'n' }, i), h('span', null, RT(p, 'z' + i + 't', 'b', null, 'Zone', true), RT(p, 'z' + i + 'd', 'small', null, 'Description', true)))));
    return h('div', useBlockProps({ className: 'map-grid' }),
      h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), RT(p, 'title', 'h2', null, 'Title'), RT(p, 'text', 'p', 'lead', 'Text'), h('ul', { className: 'zones' }, zones)),
      h('div', { className: 'foot-wrap', dangerouslySetInnerHTML: { __html: E.footSvg || '' } }));
  });

  container('benefits', 'benefits', 'zv/benefit', 4);
  reg('benefit', function (p) {
    return h(Fragment, null, Side('Benefit', F.select(p, 'icon', 'Icon', ICONS)),
      h('div', useBlockProps({ className: 'benefit' }), Icon(p.attributes.icon), RT(p, 'title', 'b', null, 'Benefit', true), RT(p, 'text', 'p', null, 'Short explanation')));
  });

  reg('compare', function (p) {
    const a = p.attributes;
    const list = s => h('ul', null, (s || '').split('\n').filter(x => x.trim()).map((x, i) => h('li', { key: i }, x)));
    return h(Fragment, null,
      Side('Comparison', F.area(p, 'leftItems', 'Left list', 'One item per line'), F.area(p, 'rightItems', 'Right list (dark card)', 'One item per line')),
      h('div', useBlockProps({ className: 'compare' }),
        h('div', { className: 'cmp' }, RT(p, 'leftTitle', 'h3', null, 'Title', true), RT(p, 'leftText', 'p', 'sub', 'Subtitle', true), list(a.leftItems)),
        h('div', { className: 'cmp us' }, RT(p, 'rightTitle', 'h3', null, 'Title', true), RT(p, 'rightText', 'p', 'sub', 'Subtitle', true), list(a.rightItems))));
  });

  reg('story', function (p) {
    const ip = useInnerBlocksProps({ className: 'prose' }, { allowedBlocks: ['core/paragraph'], template: [['core/paragraph', { placeholder: 'Write the story…' }]] });
    return h('div', useBlockProps({ className: 'story' }),
      h('div', null, RT(p, 'eyebrow', 'span', 'eyebrow', 'Eyebrow', true), RT(p, 'title', 'h2', null, 'Title'), RT(p, 'quote', 'blockquote', 'quote', 'Pull quote (optional)')),
      h('div', null, h('div', ip), h('p', { className: 'zv-hint' }, 'Visitors see the first two paragraphs, then "Read more".')));
  }, true);
})(window.wp);

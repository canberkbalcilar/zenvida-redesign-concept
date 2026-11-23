/* ZenVida admin helpers: photo pickers, add/remove rows, colour pickers,
   show/hide switch and drag-and-drop order in the rituals list. */
jQuery(function ($) {
  const A = Object.assign({}, window.ZV_ADMIN, window.ajaxurl ? { ajax: window.ajaxurl } : {});

  /* Photo picker */
  $(document).on('click', '[data-zv-media] [data-zv-pick]', function (e) {
    e.preventDefault();
    const box = $(this).closest('[data-zv-media]');
    const frame = wp.media({ title: 'Choose a photo', library: { type: 'image' }, button: { text: 'Use this photo' }, multiple: false });
    frame.on('select', () => {
      const m = frame.state().get('selection').first().toJSON();
      const url = (m.sizes && (m.sizes.large || m.sizes.full) || m).url;
      box.find('input[type=hidden]').val(url).trigger('change');
      box.find('.zv-media-preview').prop('hidden', false).find('img').attr('src', url);
      box.find('.zv-media-empty').prop('hidden', true);
      box.find('[data-zv-clear]').prop('hidden', false);
      box.find('[data-zv-pick]').text('Change photo');
    });
    frame.open();
  });
  $(document).on('click', '[data-zv-media] [data-zv-clear]', function (e) {
    e.preventDefault();
    const box = $(this).closest('[data-zv-media]');
    box.find('input[type=hidden]').val('').trigger('change');
    box.find('.zv-media-preview').prop('hidden', true);
    box.find('.zv-media-empty').prop('hidden', false);
    $(this).prop('hidden', true);
    box.find('[data-zv-pick]').text('Choose photo');
  });
  // Term "Add new" form is submitted by AJAX: reset the photo field afterwards.
  $(document).ajaxComplete((e, xhr, s) => {
    if (s && typeof s.data === 'string' && s.data.indexOf('action=add-tag') === 0) $('#addtag [data-zv-clear]').trigger('click');
  });

  /* Area screens: plain label for the description */
  $('.taxonomy-area .term-description-wrap label, .taxonomy-area .term-description-wrap th label').text('Short description (on the area tile)');

  /* Colour pickers */
  const colors = ctx => $('.zv-color', ctx).filter(function () { return !$(this).closest('.wp-picker-container').length; }).wpColorPicker();
  colors(document);

  /* Repeating rows: + Add / Remove */
  $(document).on('click', '[data-zv-add]', function (e) {
    e.preventDefault();
    const table = $(this).closest('p').prevAll('[data-zv-repeat]').first();
    const last = table.find('[data-zv-row]').last();
    const row = last.clone();
    row.find('.wp-picker-container').each(function () {
      const input = $(this).find('input.zv-color').clone().removeAttr('style').removeClass('wp-color-picker');
      $(this).replaceWith(input);
    });
    row.find('input').not('.zv-color').val('');
    last.after(row);
    colors(row);
    row.find('input').first().trigger('focus');
  });
  $(document).on('click', '[data-zv-del]', function (e) {
    e.preventDefault();
    const row = $(this).closest('[data-zv-row]');
    if (row.siblings('[data-zv-row]').length) row.remove();
    else row.find('input').not('.zv-color').val('');
  });

  /* Rituals list: show / hide switch */
  $(document).on('change', '[data-zv-visible]', function () {
    const box = $(this);
    box.prop('disabled', true);
    $.post(A.ajax, { action: 'zv_visible', _ajax_nonce: A.nonce, id: box.data('zv-visible'), on: box.is(':checked') ? 1 : 0 })
      .done(r => { if (!r.success) box.prop('checked', !box.is(':checked')); })
      .fail(() => { box.prop('checked', !box.is(':checked')); alert('Could not save. Please reload the page and try again.'); })
      .always(() => box.prop('disabled', false));
  });

  /* Popups list: on / off switch */
  $(document).on('change', '[data-zv-popup]', function () {
    const box = $(this), row = box.closest('tr');
    box.prop('disabled', true);
    $.post(A.ajax, { action: 'zv_popup_active', _ajax_nonce: A.nonce, id: box.data('zv-popup'), on: box.is(':checked') ? 1 : 0 })
      .done(r => {
        if (!r.success) { box.prop('checked', !box.is(':checked')); return; }
        row.find('.zvp-status').attr('class', 'zvp-status zvp-' + r.data.status).text(r.data.label);
      })
      .fail(() => { box.prop('checked', !box.is(':checked')); alert('Could not save. Please reload the page and try again.'); })
      .always(() => box.prop('disabled', false));
  });

  /* Popup form: show the extra fields that belong to the chosen option */
  const showIf = () => $('.zv-popup-form select').each(function () {
    const td = $(this).closest('td');
    td.find('[data-zv-show-if]').each(function () { $(this).toggle($(this).data('zv-show-if') === $(td.find('select')[0]).val()); });
  });
  $(document).on('change', '.zv-popup-form select', showIf);
  showIf();

  /* Rituals list: drag to reorder (only on the plain, unfiltered list) */
  const list = $('body.post-type-ritual.edit-php #the-list');
  const q = new URLSearchParams(location.search);
  const filtered = ['orderby', 's', 'area', 'need', 'm', 'post_status'].some(k => q.get(k));
  if (list.length && !filtered) {
    list.sortable({
      handle: '.zv-handle', axis: 'y', items: '> tr', cursor: 'grabbing',
      helper: (e, tr) => { tr.children().each(function () { $(this).width($(this).width()); }); return tr; },
      update: () => {
        const ids = list.children('tr').map(function () { return this.id.replace('post-', ''); }).get();
        $.post(A.ajax, { action: 'zv_sort', _ajax_nonce: A.nonce, ids })
          .done(() => { $('.zv-saved').remove(); $('<span class="zv-saved">Order saved ✓</span>').appendTo('.wp-heading-inline').delay(2000).fadeOut(); })
          .fail(() => alert('Could not save the order. Please reload the page and try again.'));
      }
    });
  } else if (list.length) {
    $('.zv-handle').addClass('off').attr('title', 'Clear filters and sorting to reorder');
  }
});

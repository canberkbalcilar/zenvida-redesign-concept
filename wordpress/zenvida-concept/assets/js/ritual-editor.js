/* Ritual editor: open "Ritual details" (price, photos, category) by default and
   add two plain buttons in the sidebar to switch between the form and the page sections. */
(() => {
  const { createElement: h } = wp.element;
  const { registerPlugin } = wp.plugins;
  const Panel = (wp.editor && wp.editor.PluginDocumentSettingPanel) || (wp.editPost && wp.editPost.PluginDocumentSettingPanel);
  const { Button } = wp.components;

  const toggle = () => document.querySelector('.edit-post-meta-boxes-main__presenter button');
  const setDetails = open => {
    const b = toggle();
    if (b && (b.getAttribute('aria-expanded') === 'true') !== open) b.click();
    if (!b && open) document.getElementById('zv_details')?.scrollIntoView({ behavior: 'smooth' });
  };

  const ed = () => wp.data.select('core/editor');
  const edit = () => wp.data.dispatch('core/editor');

  // Publish in one click (no "Are you ready to publish?" step).
  wp.domReady(() => {
    if (ed().isPublishSidebarEnabled && ed().isPublishSidebarEnabled()) edit().disablePublishSidebar();
    wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/document');
  });

  // "Ritual name" field in the details form ↔ the post title. No name, no publishing.
  let lastTitle = null;
  const syncName = () => {
    const input = document.getElementById('zv_title_sync');
    if (!input) return;
    if (!input.dataset.bound) {
      input.dataset.bound = '1';
      input.addEventListener('input', () => edit().editPost({ title: input.value }));
    }
    const title = ed().getEditedPostAttribute('title') || '';
    if (title !== lastTitle) {
      lastTitle = title;
      if (document.activeElement !== input && input.value !== title) input.value = title;
      if (title.trim()) edit().unlockPostSaving('zv-name'); else edit().lockPostSaving('zv-name');
      input.closest('.zv-name').classList.toggle('is-missing', !title.trim());
    }
  };
  wp.data.subscribe(syncName);

  // Open the details form once the editor has rendered.
  let tries = 0;
  const t = setInterval(() => {
    if (toggle() || ++tries > 50) {
      clearInterval(t); setDetails(true);
      const panel = 'zv-ritual-switch/zv-ritual-switch';
      const ed = wp.data.select('core/editor');
      if (ed.isEditorPanelOpened && !ed.isEditorPanelOpened(panel)) wp.data.dispatch('core/editor').toggleEditorPanelOpened(panel);
    }
  }, 200);

  if (!Panel) return;
  registerPlugin('zv-ritual-switch', {
    render: () => h(Panel, { name: 'zv-ritual-switch', title: 'What do you want to edit?', initialOpen: true },
      h('div', { style: { display: 'grid', gap: '8px' } },
        h(Button, { variant: 'primary', onClick: () => setDetails(true), style: { justifyContent: 'center' } }, 'Price, photos, category'),
        h(Button, { variant: 'secondary', onClick: () => setDetails(false), style: { justifyContent: 'center' } }, 'Page sections (steps, FAQ…)'),
        h('p', { style: { margin: '4px 0 0', color: '#757575', fontSize: '12px' } }, 'Click Save (top right) when you are done.')
      )
    )
  });
})();

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

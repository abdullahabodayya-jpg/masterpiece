import { h } from '../dom.js';

/**
 * Tabs({ items, active, onChange })
 * items: [{ id: 'active', label: 'Active (3)' }, ...]
 * The component keeps its own active state; onChange(id) tells you about changes.
 */
export function Tabs({ items = [], active, onChange } = {}) {
  let current = active ?? items[0]?.id;

  const buttons = items.map((item) =>
    h(
      'button',
      {
        class: 'tab' + (item.id === current ? ' on' : ''),
        type: 'button',
        role: 'tab',
        'aria-selected': String(item.id === current),
        onClick: () => select(item.id),
      },
      item.label
    )
  );

  const root = h('div', { class: 'tabs', role: 'tablist' }, ...buttons);

  function select(id) {
    if (id === current) return;
    current = id;
    buttons.forEach((btn, i) => {
      const on = items[i].id === current;
      btn.classList.toggle('on', on);
      btn.setAttribute('aria-selected', String(on));
    });
    onChange?.(id);
  }

  return root;
}

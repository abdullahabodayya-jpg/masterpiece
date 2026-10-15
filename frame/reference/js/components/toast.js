import { h } from '../dom.js';

/**
 * showToast(message, { duration })
 * Shows one toast at a time (a new one replaces the old one).
 */
let current;

export function showToast(message, { duration = 2200 } = {}) {
  current?.remove();
  const el = h('div', { class: 'toast', role: 'status' }, message);
  document.body.append(el);
  current = el;
  setTimeout(() => {
    if (current === el) current = null;
    el.remove();
  }, duration);
}

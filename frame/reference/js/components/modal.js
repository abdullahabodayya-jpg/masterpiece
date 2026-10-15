import { h } from '../dom.js';

/**
 * openModal({ title, content, actions, onClose })
 * content: string | Node      actions: array of Nodes (e.g. Buttons)
 * Returns { close } so you can close it from your own code.
 * Closes on backdrop click and the Escape key.
 */
export function openModal({ title, content, actions = [], onClose } = {}) {
  const previouslyFocused = document.activeElement;

  const close = () => {
    document.removeEventListener('keydown', onKey);
    backdrop.remove();
    previouslyFocused?.focus?.();
    onClose?.();
  };
  const onKey = (e) => e.key === 'Escape' && close();

  const dialog = h(
    'div',
    { class: 'modal', role: 'dialog', 'aria-modal': 'true', tabindex: '-1' },
    title && h('h2', {}, title),
    content,
    actions.length > 0 && h('div', { class: 'row wrap', style: { marginTop: '16px' } }, ...actions)
  );

  const backdrop = h(
    'div',
    { class: 'modal-backdrop', onClick: (e) => e.target === backdrop && close() },
    dialog
  );

  document.addEventListener('keydown', onKey);
  document.body.append(backdrop);
  dialog.focus();

  return { close };
}

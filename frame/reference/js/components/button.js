import { h } from '../dom.js';

/**
 * Button({ label, variant, size, disabled, type, onClick })
 * variant: 'primary' | 'ghost'      size: 'md' | 'sm'
 */
export function Button({
  label = '',
  variant = 'primary',
  size = 'md',
  disabled = false,
  type = 'button',
  onClick,
  children,
} = {}) {
  const cls = ['btn', variant === 'ghost' && 'ghost', size === 'sm' && 'sm']
    .filter(Boolean)
    .join(' ');

  return h('button', { class: cls, type, disabled, onClick }, children ?? label);
}

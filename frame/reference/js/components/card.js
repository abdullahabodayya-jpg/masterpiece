import { h } from '../dom.js';

/**
 * Card({ hover, className, style }, ...children)
 * hover: adds the subtle lift animation on hover.
 */
export function Card({ hover = false, className = '', style } = {}, ...children) {
  const cls = ['card', hover && 'hover-lift', className].filter(Boolean).join(' ');
  return h('div', { class: cls, style }, ...children);
}

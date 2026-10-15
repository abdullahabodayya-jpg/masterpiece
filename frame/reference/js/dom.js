

export function h(tag, props = {}, ...children) {
  const el = document.createElement(tag);

  for (const [key, val] of Object.entries(props || {})) {
    if (val === false || val == null) continue;
    if (key === "class") el.className = val;
    else if (key === "style" && typeof val === "object")
      Object.assign(el.style, val);
    else if (key.startsWith("on") && typeof val === "function") {
      el.addEventListener(key.slice(2).toLowerCase(), val);
    } else if (val === true) el.setAttribute(key, "");
    else el.setAttribute(key, val);
  }

  append(el, children);
  return el;
}

function append(el, children) {
  for (const child of children.flat(Infinity)) {
    if (child === false || child == null) continue;
    el.append(
      child instanceof Node ? child : document.createTextNode(String(child)),
    );
  }
}

// Replace the contents of a container with a component.
export function mount(container, node) {
  container.replaceChildren(node);
}

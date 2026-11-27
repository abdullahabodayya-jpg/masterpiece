export function node(tag, className = '', text = '') {
    const element = document.createElement(tag);
    element.className = className;
    element.textContent = text;
    return element;
}

export function button(label, className, callback) {
    const control = node('button', 'btn ' + className, label);
    control.type = 'button';
    control.addEventListener('click', callback);
    return control;
}

export const human = value => String(value ?? '').replaceAll('_', ' ').replace(/^\w/, letter => letter.toUpperCase());
export const count = value => new Intl.NumberFormat().format(value);
const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeZone: 'UTC' });
export function date(value) {
    const parsed = new Date(value);
    return value && !Number.isNaN(parsed.valueOf()) ? dateFormat.format(parsed) : '—';
}

export function money(amount, currency) {
    if (amount === null || amount === undefined) return '—';
    // The API sends decimal strings. Retain their precision and always show the currency code.
    const [whole, fraction = ''] = String(amount).split('.');
    if (!/^-?\d+$/.test(whole) || !/^\d*$/.test(fraction)) return '—';
    const formatted = new Intl.NumberFormat().format(BigInt(whole) === 0n && whole.startsWith('-') ? -0 : BigInt(whole));
    const decimal = new Intl.NumberFormat().formatToParts(1.1).find(part => part.type === 'decimal').value;
    return formatted + decimal + fraction.padEnd(2, '0') + (currency ? ' ' + currency : '');
}

const tones = {
    active: 'positive', approved: 'positive', accepted: 'positive', completed: 'positive',
    succeeded: 'positive', paid: 'positive', published: 'positive', open: 'positive',
    pending: 'warning', pending_verification: 'warning', pending_agreement: 'warning',
    paused: 'warning', under_review: 'warning', needs_changes: 'warning',
    processing: 'info', in_progress: 'info', in_review: 'info', shortlisted: 'info',
    failed: 'danger', suspended: 'danger', removed: 'danger', rejected: 'danger',
    disputed: 'danger', restricted: 'danger',
};

export function badge(status) {
    return node('span', 'status-badge tone-' + (Object.hasOwn(tones, status) ? tones[status] : 'neutral'), human(status));
}

export function notice(message, kind = 'success') {
    const element = document.querySelector('#notice');
    element.className = message ? 'alert alert-' + kind : '';
    element.textContent = message;
}

export function state(element, message, retry) {
    element.replaceChildren(node('p', 'mb-0', message));
    if (retry) element.append(button('Retry', 'btn-sm btn-outline-secondary mt-3', retry));
}

export function field({ name, label, type = 'text', options, value = '', max, min, required, placeholder, pattern, autocomplete }, prefix) {
    const wrapper = node('div', 'field');
    const id = prefix + '-' + name;
    const title = node('label', 'form-label', label);
    title.htmlFor = id;
    const input = node(options ? 'select' : type === 'textarea' ? 'textarea' : 'input',
        options ? 'form-select' : 'form-control');
    input.id = id;
    input.name = name;
    if (input.tagName === 'INPUT') input.type = type;
    if (type === 'textarea') input.rows = 4;
    for (const [key, text] of options ?? []) {
        const option = node('option', '', text);
        option.value = key;
        input.append(option);
    }
    input.value = value ?? '';
    if (max) input.maxLength = max;
    if (min) input.minLength = min;
    if (placeholder) input.placeholder = placeholder;
    if (pattern) input.pattern = pattern;
    if (autocomplete) input.autocomplete = autocomplete;
    input.required = Boolean(required);
    wrapper.append(title, input);
    return wrapper;
}

export function values(form) {
    return Object.fromEntries([...new FormData(form)].map(([key, value]) => [
        key, key.startsWith('password') ? value : value.trim(),
    ]));
}

export function errors(form, error, summary, override) {
    form.querySelectorAll('.invalid-feedback').forEach(element => element.remove());
    form.querySelectorAll('.is-invalid').forEach(input => {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
    });
    summary.textContent = override ?? error?.message ?? '';
    summary.hidden = !summary.textContent;
    for (const [name, messages] of Object.entries(error?.fields ?? {})) {
        const input = form.elements.namedItem(name);
        if (!input || !Array.isArray(messages)) continue;
        const feedback = node('div', 'invalid-feedback', messages.join(' '));
        feedback.id = input.id + '-error';
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', feedback.id);
        input.after(feedback);
    }
}

const modal = document.querySelector('#record-dialog');
const form = document.querySelector('#dialog-form');
const body = document.querySelector('#dialog-body');
const footer = document.querySelector('#dialog-footer');
let busy = false;
let focusReturn;

function modalInstance() {
    return bootstrap.Modal.getOrCreateInstance(modal);
}

modal.addEventListener('hide.bs.modal', event => {
    if (busy) event.preventDefault();
});
modal.addEventListener('hidden.bs.modal', () => {
    form.onsubmit = null;
    body.replaceChildren();
    footer.replaceChildren();
    if (focusReturn?.isConnected) focusReturn.focus();
    else document.querySelector('#main').focus();
    focusReturn = undefined;
});
modal.addEventListener('shown.bs.modal', () => {
    (body.querySelector('input, select, textarea') ?? modal.querySelector('.btn-close')).focus();
});

export function dialog(title, content, actions = [], dismiss = 'Close') {
    if (!modal.classList.contains('show')) focusReturn = document.activeElement;
    document.querySelector('#dialog-title').textContent = title;
    body.replaceChildren(...content);
    const close = button(dismiss, 'btn-outline-secondary', () => modalInstance().hide());
    footer.replaceChildren(close, ...actions);
    form.onsubmit = event => event.preventDefault();
    if (modal.classList.contains('show')) {
        (body.querySelector('input, select, textarea') ?? modal.querySelector('.btn-close')).focus();
    } else {
        modalInstance().show();
    }
}

export function editDialog({ title, context, fields = [], label, danger, submit, onDone, conflict, onBack }) {
    const summary = node('div', 'alert alert-danger');
    summary.setAttribute('role', 'alert');
    summary.hidden = true;
    const content = [summary];
    if (context) content.push(node('p', 'text-secondary', context));
    content.push(...fields.map(item => field(item, 'dialog')));
    const action = node('button', 'btn ' + (danger ? 'btn-danger' : 'btn-primary'), label);
    action.type = 'submit';
    const controls = onBack ? [button('Back', 'btn-outline-secondary', onBack), action] : [action];
    dialog(title, content, controls, 'Cancel');

    form.onsubmit = async event => {
        event.preventDefault();
        if (busy) return;
        const payload = values(form);
        busy = true;
        const buttons = [...form.querySelectorAll('button')];
        buttons.forEach(control => { control.disabled = true; });
        action.textContent = 'Saving…';
        errors(form, null, summary);
        try {
            await submit(payload);
        } catch (error) {
            errors(form, error, summary, error.status === 409 ? conflict : undefined);
            summary.tabIndex = -1;
            summary.focus();
            return;
        } finally {
            busy = false;
            buttons.forEach(control => { control.disabled = false; });
            action.textContent = label;
        }
        modalInstance().hide();
        onDone();
    };
}

export function detailList(entries) {
    const list = node('dl', 'detail-list mb-0');
    for (const [label, value] of entries) {
        const item = node('dd', 'detail-value');
        if (value instanceof Node) item.append(value);
        else item.textContent = value === null || value === undefined || value === '' ? '—' : value;
        list.append(node('dt', '', label), item);
    }
    return list;
}

import { api, pagination } from './api.js';
import { addRecord, recordActions } from './record-actions.js';
import { sections } from './sections.js';
import { button, detailList, dialog, errors, field, node, notice, state, values } from './ui.js';

const key = document.body.dataset.page;
const config = sections[key];
const endpoint = config.endpoint ?? '/admin/' + key;
const filterForm = document.querySelector('#filters');
const listState = document.querySelector('#list-state');
const table = document.querySelector('#table-wrap');
const thead = document.querySelector('#table-head');
const tbody = document.querySelector('#table-body');
const nav = document.querySelector('#pagination');
const description = document.querySelector('#page-description');
const create = document.querySelector('#create-record');
const filterError = node('div', 'alert alert-danger filter-error');
filterError.setAttribute('role', 'alert');
filterError.hidden = true;

description.textContent = config.description;
description.hidden = false;
let query = {};
let currentPage = 1;
let controller;
let loadingDetails = false;

for (const item of config.filters) filterForm.append(field(item, 'filter'));
const controls = node('div', 'filter-actions');
const apply = node('button', 'btn btn-primary', 'Apply filters');
apply.type = 'submit';
controls.append(apply, button('Reset', 'btn-outline-secondary', () => {
    filterForm.reset();
    filterForm.requestSubmit();
}));
filterForm.append(controls, filterError);
filterForm.addEventListener('submit', event => {
    event.preventDefault();
    notice('');
    query = Object.fromEntries(Object.entries(values(filterForm)).filter(([, value]) => value !== ''));
    load(1);
});

const initialFilters = new URLSearchParams(location.search);
for (const item of config.filters) {
    const value = initialFilters.get(item.name);
    if (value === null) continue;
    const input = filterForm.elements.namedItem(item.name);
    input.value = item.options?.some(([key]) => key === value) || !item.options ? value : input.value;
    if (!input.checkValidity() || (item.max && value.length > item.max)) input.value = '';
}
query = Object.fromEntries(Object.entries(values(filterForm)).filter(([, value]) => value !== ''));

const headings = node('tr');
for (const [name] of config.columns) {
    const column = node('th', '', name);
    column.scope = 'col';
    headings.append(column);
}
const actions = node('th', 'text-end', 'Details');
actions.scope = 'col';
headings.append(actions);
thead.append(headings);

if (['users', 'catalog'].includes(config.kind)) {
    create.textContent = 'Create ' + config.singular.toLowerCase();
    create.hidden = false;
    create.addEventListener('click', () => addRecord(config, endpoint, message => {
        notice(message);
        load(1);
    }));
}

function columnValue(row, accessor) {
    return typeof accessor === 'function' ? accessor(row) : row[accessor];
}

function renderRows(rows) {
    tbody.replaceChildren();
    for (const row of rows) {
        const line = node('tr');
        config.columns.forEach(([, key], index) => {
            const cell = node('td', index === 0 ? 'primary-cell' : '');
            const value = columnValue(row, key);
            if (value instanceof Node) cell.append(value);
            else cell.textContent = value ?? '—';
            line.append(cell);
        });
        const cell = node('td', 'text-end');
        const view = button('View', 'btn-sm btn-outline-secondary', () => showRecord(row.id, view));
        view.setAttribute('aria-label', 'View ' + config.singular.toLowerCase() + (row.name || row.title ? ' ' + (row.name ?? row.title) : ''));
        cell.append(view);
        line.append(cell);
        tbody.append(line);
    }
}

function renderPages(page, length) {
    const buttons = node('div', 'pagination-actions');
    const previous = button('Previous', 'btn-sm btn-outline-secondary', () => load(page.page - 1));
    const next = button('Next', 'btn-sm btn-outline-secondary', () => load(page.page + 1));
    previous.disabled = page.page <= 1;
    next.disabled = !page.hasMore;
    buttons.append(previous, next);
    const first = (page.page - 1) * page.perPage + 1;
    const total = page.total === undefined ? '' : ' of ' + page.total;
    const summary = length ? 'Page ' + page.page + ' · ' + first + '–' + (first + length - 1) + total : 'Page ' + page.page;
    nav.replaceChildren(node('span', 'text-secondary', summary), buttons);
    nav.hidden = !length && page.page <= 1;
}

async function load(page = currentPage) {
    controller?.abort();
    controller = new AbortController();
    const { signal } = controller;
    currentPage = page;
    errors(filterForm, null, filterError);
    table.hidden = true;
    nav.hidden = true;
    listState.hidden = false;
    state(listState, 'Loading records…');
    try {
        const response = await api(endpoint, { query: { ...query, page, per_page: 20 }, signal });
        if (signal.aborted) return;
        if (response.data.length === 0 && page > 1) {
            await load(page - 1);
            return;
        }
        renderRows(response.data);
        renderPages(pagination(response), response.data.length);
        table.hidden = !response.data.length;
        listState.hidden = Boolean(response.data.length);
        if (!response.data.length) state(listState, 'No records match the current filters.');
    } catch (error) {
        if (signal.aborted) return;
        if (Object.keys(error.fields ?? {}).length) errors(filterForm, error, filterError);
        state(listState, error.message, () => load(page));
    }
}

async function showRecord(recordId, trigger) {
    if (loadingDetails) return;
    loadingDetails = true;
    notice('');
    trigger.disabled = true;
    trigger.textContent = 'Loading…';
    try {
        const { data: row } = await api(endpoint + '/' + encodeURIComponent(recordId));
        const supplement = document.createElement('div');
        const feedback = node('p', 'alert alert-danger mt-3 mb-0');
        feedback.setAttribute('role', 'alert');
        feedback.hidden = true;
        const fields = [...config.columns, ...config.details]
            .filter(([label]) => label !== ({ posts: 'Post', comments: 'Comment', reviews: 'Review' })[key]);
        const show = () => dialog(config.singular + ' details',
            [detailList(fields.map(([label, accessor]) => [label, columnValue(row, accessor)])), supplement, feedback],
            recordActions({ config, endpoint, row, back: show, feedback, done: message => {
                notice(message);
                load();
            } }),
        );
        trigger.disabled = false;
        trigger.focus();
        show();
        void loadRelated(row, supplement);
    } catch (error) {
        notice(error.message, 'danger');
    } finally {
        loadingDetails = false;
        trigger.disabled = false;
        trigger.textContent = 'View';
    }
}

async function loadRelated(row, target) {
    const related = [];
    if (key === 'verification') related.push(['Applicant', 'users', row.editor_id]);
    if (key === 'posts' || key === 'comments') related.push(['Author', 'users', row.author_id]);
    if (key === 'applications') related.push(['Job', 'jobs', row.work_item_id], ['Editor', 'users', row.editor_id]);
    if (key === 'jobs' || key === 'service-requests' || key === 'projects') {
        related.push(['Creator', 'users', row.creator_id], ['Editor', 'users', row.editor_id]);
    }
    if (key === 'services') related.push(['Editor', 'users', row.editor_id]);
    if (key === 'reviews') related.push(['Project', 'projects', row.work_item_id]);
    const available = related.filter(([, , member]) => member);
    if (available.length === 0) return;
    state(target, 'Loading related records…');
    const entries = await Promise.all(available.map(async ([label, resource, member]) => {
        try {
            const { data } = await api('/admin/' + resource + '/' + encodeURIComponent(member));
            if (key === 'verification') return [[label, data.name], ['Applicant email', data.email]];
            if (key === 'applications' && resource === 'jobs') return [[label, data.title], ['Job currency', data.currency]];
            return [[label, data.name ?? data.title]];
        } catch (error) {
            return [[label, error.status === 404 ? 'Unavailable' : error.message]];
        }
    }));
    target.replaceChildren(detailList(entries.flat()));
}

load();

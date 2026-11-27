import { api } from './api.js';
import { badge, count, date, human, money, node, state } from './ui.js';

const overview = document.querySelector('#overview');
const status = document.querySelector('#overview-state');
const home = document.body.dataset.home.replace(/\/$/, '');
const metrics = [
    ['total_users', 'Total users', 'people', 'users', 'total_creators', 'total_editors'],
    ['open_jobs', 'Open jobs', 'briefcase', 'jobs'],
    ['active_projects', 'Active projects', 'kanban', 'projects'],
    ['pending_verifications', 'Pending verifications', 'patch-check', 'verification'],
];
const queues = [
    ['active_users', 'Active users', 'users', { status: 'active' }],
    ['suspended_users', 'Suspended users', 'users', { status: 'suspended' }],
    ['completed_projects', 'Completed projects', 'projects', { status: 'completed' }],
    ['successful_payments', 'Successful payments', 'payments', { status: 'succeeded' }],
    ['pending_refunds', 'Pending or processing refunds', 'refunds'],
    ['pending_payouts', 'Pending or processing payouts', 'payouts'],
    ['total_posts', 'Community posts', 'posts'],
    ['total_reviews', 'Reviews', 'reviews'],
];
const financial = [
    ['gross_collected', 'Gross collected'],
    ['confirmed_refunds', 'Confirmed refunds'],
    ['refund_adjusted_collected', 'Refund-adjusted collections'],
    ['eligible_commission', 'Eligible sandbox commission'],
    ['eligible_editor_earnings', 'Eligible editor earnings'],
    ['pending_simulated_payouts', 'Pending simulated payouts'],
    ['paid_simulated_payouts', 'Paid simulated payouts'],
];

function link(page, className, label = '', filters) {
    const anchor = node('a', className, label);
    anchor.href = home + '/' + page + (filters ? '?' + new URLSearchParams(filters) : '');
    return anchor;
}

function glyph(name, className) {
    const icon = node('i', 'bi bi-' + name + ' ' + className);
    icon.setAttribute('aria-hidden', 'true');
    return icon;
}

function cards(data) {
    const target = document.querySelector('#metrics');
    target.replaceChildren(...metrics.map(([key, name, icon, page, creators, editors]) => {
        const card = link(page, 'panel kpi');
        const heading = node('div', 'kpi-heading');
        heading.append(node('span', '', name), glyph(icon, 'kpi-icon'));
        card.append(heading, node('strong', 'kpi-value', count(data[key])));
        if (creators && editors) {
            const creatorLabel = data[creators] === 1 ? ' Creator' : ' Creators';
            const editorLabel = data[editors] === 1 ? ' Editor' : ' Editors';
            card.append(node('small', 'kpi-meta', count(data[creators]) + creatorLabel + ' · ' + count(data[editors]) + editorLabel));
        }
        return card;
    }));
    document.querySelector('#queues').replaceChildren(...queues.map(([key, label, route, filters]) => {
        const row = link(route, 'queue-row', '', filters);
        row.append(node('span', '', label), node('strong', '', count(data[key])));
        return row;
    }));
}

function draw(data) {
    const months = data.monthly_registrations;
    const labels = months.map(row => row.month);
    const totals = months.map(row => row.total);
    const summary = document.querySelector('#chart-values');
    summary.textContent = months.map(({ month, total }) => month + ' · ' + count(total)).join(', ');
    const canvas = document.querySelector('#registrations');
    if (!window.Chart || !totals.length) {
        canvas.hidden = true;
        summary.classList.remove('visually-hidden');
        if (!totals.length) summary.textContent = 'No registration history available.';
        return;
    }
    new window.Chart(canvas, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Registrations', data: totals, backgroundColor: '#c75322', borderRadius: 3, maxBarThickness: 38 }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false }, ticks: { maxRotation: 0 } },
                y: { beginAtZero: true, suggestedMax: 1, ticks: { precision: 0 }, grid: { color: '#e9ebef' } } },
        },
    });
}

function financialSummary(data) {
    const target = document.querySelector('#financial-summary');
    const summary = data.financial_summary;
    const currencies = [...new Set(financial.flatMap(([key]) => Object.keys(summary[key] ?? {})))].sort();
    if (!currencies.length) {
        target.replaceChildren(node('p', 'text-secondary mb-0', 'No financial activity recorded.'));
        return;
    }

    const table = node('table', 'table align-middle admin-table summary-table');
    const head = table.createTHead().insertRow();
    for (const heading of ['Metric', ...currencies]) {
        const cell = node('th', '', heading);
        cell.scope = 'col';
        head.append(cell);
    }
    const body = table.createTBody();
    for (const [key, label] of financial) {
        const row = body.insertRow();
        const name = node('th', '', label);
        name.scope = 'row';
        row.append(name);
        currencies.forEach(currency => row.append(node('td', '', money(summary[key]?.[currency] ?? '0', currency))));
    }
    target.replaceChildren(table);
}

function recent(data) {
    const groups = [
        ['Recent users', 'users', data.recent_users, row => row.name, row => human(row.role)],
        ['Recent payments', 'payments', data.recent_payments, row => money(row.amount, row.currency)],
        ['Recent verifications', 'verification', data.recent_verifications, () => 'Verification request'],
    ];
    const target = document.querySelector('#activity');
    target.replaceChildren(...groups.map(([label, page, rows, primary, secondary]) => {
        const card = node('section', 'panel panel-pad');
        const heading = node('div', 'activity-heading');
        heading.append(node('h2', '', label), link(page, 'text-link', 'View all'));
        card.append(heading);
        if (!rows.length) card.append(node('p', 'text-secondary mb-0', 'No recent records.'));
        for (const row of rows) {
            const item = node('div', 'activity-row');
            const meta = node('div');
            meta.append(node('p', 'mb-0 activity-primary', primary(row)),
                node('small', 'text-secondary', [secondary?.(row), date(row.created_at)].filter(Boolean).join(' · ')));
            item.append(meta, badge(row.status));
            card.append(item);
        }
        return card;
    }));
}

async function load() {
    state(status, 'Loading dashboard…');
    try {
        const data = await api('/admin/dashboard');
        cards(data);
        financialSummary(data);
        recent(data);
        overview.hidden = false;
        draw(data);
        status.hidden = true;
    } catch (error) {
        overview.hidden = true;
        state(status, error.message, load);
    }
}

load();

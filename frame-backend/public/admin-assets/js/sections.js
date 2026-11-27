import { badge, date, human, money } from './ui.js';

const status = ['Status', row => badge(row.status)];
const created = ['Created', row => date(row.created_at)];
const amount = ['Amount', row => money(row.amount, row.currency)];
const title = ['Title', 'title'];
const activeStatus = ['Status', row => badge(row.deleted_at ? 'archived' : 'active')];

const list = (name, label, values, all = 'All') => ({
    name, label, options: [['', all], ...values.map(value => [value, human(value)])],
});
const search = (placeholder, max = 100) => ({ name: 'search', label: 'Search', type: 'search', max, placeholder });
const states = (...values) => list('status', 'Status', values, 'All statuses');
const uuid = '[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}';
const reference = label => ({ name: 'reference_id', label, pattern: uuid, placeholder: 'UUID', max: 36 });
const currency = { name: 'currency', label: 'Currency', pattern: '[A-Z]{3}', placeholder: 'USD', max: 3 };
const workStatus = states('draft', 'open', 'paused', 'pending_agreement', 'in_progress', 'in_review', 'completed', 'disputed', 'cancelled', 'closed');
const financeNote = 'Academic finance · Stripe sandbox and simulated payouts.';

const workDetails = [
    ['Creator reference', 'creator_id'], ['Editor reference', 'editor_id'],
    ['Service reference', 'editor_service_id'], ['Content type reference', 'content_type_id'],
    ['Currency', 'currency'], ['Deadline', row => date(row.deadline)],
    ['Agreed', row => date(row.agreed_at)], ['Activated', row => date(row.started_at)],
    ['Completed', row => date(row.completed_at)],
];
const work = (singular, description) => ({
    singular, description,
    filters: [search('Work title'), workStatus, reference('Creator reference')],
    columns: [title, ['Type', row => human(row.type)], status, ['Deadline', row => date(row.deadline)], created],
    details: workDetails,
});
const catalog = singular => ({
    singular, kind: 'catalog', description: 'Maintain the shared marketplace catalog.',
    filters: [search('Catalog name'), { name: 'archived', label: 'Availability', options: [['0', 'Active'], ['1', 'Archived']] }],
    columns: [['Name', 'name'], activeStatus],
    details: [['Archived on', row => date(row.deleted_at)]],
});

export const sections = {
    users: {
        singular: 'User', kind: 'users',
        description: 'Manage Creator and Editor accounts and review account status.',
        filters: [
            search('Name or email', 255),
            list('role', 'Role', ['creator', 'editor', 'admin', 'support'], 'All roles'),
            states('pending_verification', 'active', 'restricted', 'suspended', 'closed'),
        ],
        columns: [['Name', 'name'], ['Email', 'email'], ['Role', row => human(row.role)], status, created],
        details: [['Last updated', row => date(row.updated_at)]],
    },
    verification: {
        singular: 'Verification request', kind: 'verification', endpoint: '/staff/verification-requests',
        description: 'Review professional editor credentials and supporting documents.',
        filters: [{ name: 'sort', label: 'Sort by submission', options: [['newest', 'Newest first'], ['oldest', 'Oldest first']] }],
        columns: [['Document type', row => human(row.document_type)], status, ['Submitted', row => date(row.submitted_at)],
            ['Decided', row => date(row.decided_at)]],
        details: [['Decision notes', 'decision_notes']],
    },
    jobs: work('Job', 'Monitor Creator job listings and their lifecycle.'),
    applications: {
        singular: 'Application',
        description: 'Monitor Editor applications and their proposed terms.',
        filters: [states('pending', 'shortlisted', 'accepted', 'rejected', 'withdrawn'), reference('Job reference')],
        columns: [['Proposed price', row => money(row.proposed_price)],
            ['Delivery estimate (days)', 'estimated_delivery_days'], status, created],
        details: [['Job reference', 'work_item_id'], ['Editor reference', 'editor_id']],
    },
    services: {
        singular: 'Service',
        description: 'Monitor Editor service listings and offering status.',
        filters: [search('Service title'), states('draft', 'active', 'paused'), reference('Editor reference')],
        columns: [title, ['Price', row => money(row.price, row.currency)], ['Delivery (days)', 'delivery_days'],
            ['Status', row => badge(row.deleted_at ? 'archived' : row.status)], created],
        details: [['Editor reference', 'editor_id'], ['Content type reference', 'content_type_id'], ['Archived on', row => date(row.deleted_at)]],
    },
    'service-requests': work('Service request', 'Monitor Creator requests for Editor services.'),
    projects: work('Project', 'Monitor activated projects and their delivery status.'),
    payments: {
        singular: 'Payment', description: financeNote,
        filters: [states('pending', 'processing', 'succeeded', 'failed', 'cancelled'), currency, reference('Project reference')],
        columns: [amount, status, ['Confirmed', row => date(row.paid_at)], created],
        details: [['Project reference', 'work_item_id'], ['Creator reference', 'creator_id'],
            ['Platform fee', row => money(row.platform_fee, row.currency)], ['Editor net amount', row => money(row.editor_net_amount, row.currency)]],
    },
    refunds: {
        singular: 'Refund', kind: 'refunds', description: financeNote,
        filters: [states('pending', 'processing', 'succeeded', 'failed', 'cancelled'), currency, reference('Payment reference')],
        columns: [amount, status, ['Completed', row => date(row.completed_at)], created],
        details: [['Payment reference', 'payment_id']],
    },
    payouts: {
        singular: 'Payout', kind: 'payouts', description: financeNote,
        filters: [states('pending', 'processing', 'paid', 'failed', 'cancelled'), currency, reference('Project reference')],
        columns: [amount, status, ['Simulated payment date', row => date(row.paid_at)], created],
        details: [['Project reference', 'work_item_id'], ['Payment reference', 'payment_id'], ['Editor reference', 'editor_id']],
    },
    posts: {
        singular: 'Post', kind: 'posts',
        description: 'Review post visibility and moderate published public content.',
        filters: [states('published', 'archived', 'removed'), reference('Author reference')],
        columns: [['Post', row => excerpt(row.body)], ['Visibility', row => human(row.visibility)], status, created],
        details: [['Post text', row => row.body ?? 'Restricted content']],
    },
    comments: {
        singular: 'Comment', kind: 'comments',
        description: 'Review comments and moderate published comments on public posts.',
        filters: [states('published', 'hidden', 'removed'), reference('Parent post reference')],
        columns: [['Comment', row => excerpt(row.body)], status, created],
        details: [['Comment text', row => row.body ?? 'Restricted content'], ['Parent post reference', 'post_id']],
    },
    reviews: {
        singular: 'Review', kind: 'reviews',
        description: 'Inspect project ratings and moderate visible reviews.',
        filters: [reference('Project reference')],
        columns: [['Rating', row => row.rating + ' / 5'], ['Review', row => excerpt(row.comment, 'No written review')],
            ['Visibility', row => badge(row.deleted_at ? 'hidden' : 'visible')], created],
        details: [['Review text', 'comment'], ['Project reference', 'work_item_id'],
            ['Reviewer reference', 'reviewer_user_id'], ['Reviewed account reference', 'reviewed_user_id'], ['Hidden on', row => date(row.deleted_at)]],
    },
    'content-types': catalog('Content type'),
    'editing-tools': catalog('Editing tool'),
};

function excerpt(value, fallback = 'Restricted content') {
    if (value === null || value === undefined) return fallback;
    return value.length > 92 ? value.slice(0, 89) + '…' : value;
}

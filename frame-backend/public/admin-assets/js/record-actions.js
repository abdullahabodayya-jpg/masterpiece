import { api } from './api.js';
import { button, editDialog, human, node, notice } from './ui.js';

const id = encodeURIComponent;
const nameField = { name: 'name', label: 'Name', max: 255, required: true };
const emailField = { name: 'email', label: 'Email', type: 'email', max: 255, required: true };
const reason = { name: 'reason', label: 'Reason', type: 'textarea', max: 1000, required: true };
const password = { name: 'password', label: 'Password', type: 'password', min: 8, required: true, autocomplete: 'new-password' };
const title = (verb, singular) => verb + ' ' + singular.toLowerCase();

function command(options, done) {
    const { label, context, fields, method, path, data, danger, conflict, success } = options;
    return editDialog({
        title: label, label, context, fields, danger, conflict, onBack: options.back,
        submit: payload => {
            const values = { ...payload, ...data };
            if (values.email) values.email = values.email.toLowerCase();
            return api(path, { method, data: Object.keys(values).length ? values : undefined });
        },
        onDone: () => done(success),
    });
}

export function addRecord(config, endpoint, done) {
    const isUser = config.kind === 'users';
    const label = title('Create', config.singular);
    command({
        label, path: endpoint, method: 'POST',
        fields: isUser ? [
            nameField, emailField,
            { name: 'role', label: 'Role', required: true, options: [['', 'Select a role'], ['creator', 'Creator'], ['editor', 'Editor']] },
            password, { ...password, name: 'password_confirmation', label: 'Confirm password' },
        ] : [{ ...nameField, max: 100 }],
        success: config.singular + ' created.',
    }, done);
}

export function recordActions({ config, endpoint, row, done, back, feedback }) {
    const path = endpoint + '/' + id(row.id);
    const actions = [];
    const add = options => actions.push(button(options.label, options.danger ? 'btn-outline-danger' : 'btn-outline-secondary',
        () => command({ method: 'POST', path, back, ...options }, done)));
    const change = (label, options) => add({ label, ...options });
    const canManageUser = ['creator', 'editor'].includes(row.role);

    if (config.kind === 'users' && canManageUser) {
        change('Edit user', {
            method: 'PATCH', fields: [{ ...nameField, value: row.name }, { ...emailField, value: row.email }],
            success: 'User updated.',
        });
        if (['active', 'suspended'].includes(row.status)) {
            const suspend = row.status === 'active';
            change(suspend ? 'Suspend user' : 'Reinstate user', {
                method: 'PATCH', path: path + '/status', fields: [reason], danger: suspend,
                context: suspend ? 'Suspend access for ' + row.name + '?' : 'Restore access for ' + row.name + '?',
                data: { status: suspend ? 'suspended' : 'active' }, success: suspend ? 'User suspended.' : 'User reinstated.',
            });
        }
    }

    if (config.kind === 'catalog' && !row.deleted_at) {
        change(title('Edit', config.singular), {
            method: 'PATCH', fields: [{ ...nameField, max: 100, value: row.name }],
            success: config.singular + ' updated.',
        });
        change(title('Archive', config.singular), {
            method: 'DELETE', danger: true, context: 'Archive “' + row.name + '” from the active catalog?',
            conflict: 'This entry may still be referenced by a marketplace record. Referenced entries cannot be archived.',
            success: config.singular + ' archived.',
        });
    }

    if (config.kind === 'verification') {
        const download = button('Download document', 'btn-outline-secondary', async () => {
            download.disabled = true;
            download.textContent = 'Downloading…';
            feedback.hidden = true;
            try {
                const { blob, extension } = await api(path + '/document', { download: true });
                const url = URL.createObjectURL(blob);
                const link = node('a');
                link.href = url;
                link.download = 'verification-document.' + extension;
                document.body.append(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
                notice('Verification document downloaded.');
            } catch (error) {
                feedback.textContent = error.message;
                feedback.hidden = false;
            } finally {
                download.disabled = false;
                download.textContent = 'Download document';
            }
        });
        actions.push(download);
        if (row.status === 'pending') {
            for (const decision of ['approve', 'reject']) {
                const reject = decision === 'reject';
                change(human(decision) + ' request', {
                    path: path + '/decisions', danger: reject, fields: [{ ...reason, max: 5000, required: reject }],
                    context: 'Record a professional verification decision for this Editor.',
                    data: { decision }, success: reject ? 'Verification rejected.' : 'Verification approved.',
                });
            }
        }
    }

    if (config.kind === 'refunds') {
        const staff = '/staff/refunds/' + id(row.id);
        if (row.status === 'pending') {
            change('Approve refund', {
                path: staff + '/approval', context: 'Submit this refund to Stripe sandbox for processing?',
                conflict: 'This refund is no longer eligible for approval. The decision window or payment state may have changed.',
                success: 'Refund approval processed. Check the updated refund status.',
            });
            change('Reject refund', {
                path: staff + '/rejection', danger: true, context: 'Cancel this pending refund request?',
                success: 'Refund request rejected.',
            });
        }
        if (row.status === 'processing') {
            change('Reconcile refund', {
                path: staff + '/approval', context: 'Check this unresolved refund through the existing Stripe sandbox workflow?',
                conflict: 'This refund cannot be reconciled in its current state. The safe provider retry window may have expired.',
                success: 'Reconciliation completed. Check the updated refund status.',
            });
        }
    }

    if (config.kind === 'payouts' && row.status === 'pending') {
        change('Simulate payout', {
            path: '/staff/payouts/' + id(row.id) + '/simulation',
            context: 'Record a simulated payout for this project? No funds are transferred.',
            conflict: 'This payout does not currently meet the project completion and funding requirements.',
            success: 'Simulated payout recorded.',
        });
    }

    const rules = {
        posts: ['Remove post', 'removal', row.visibility === 'public' && row.status === 'published', 'Post removed.'],
        comments: ['Hide comment', 'hide', row.body != null && row.status === 'published', 'Comment hidden.'],
        reviews: ['Hide review', 'hide', !row.deleted_at, 'Review hidden.'],
    };
    const moderation = rules[config.kind];
    if (moderation?.[2]) {
        const [label, action, , success] = moderation;
        change(label, {
            path: path + '/' + action, danger: true, fields: [reason], success,
            context: 'Confirm the change to this record’s visibility and enter a moderation reason.',
        });
    }
    return actions;
}

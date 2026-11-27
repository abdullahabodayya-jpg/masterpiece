const { api: base, page, login } = document.body.dataset;
const messages = {
    400: 'Check the request parameters.',
    401: 'Your session has ended. Sign in again.',
    403: 'Your account cannot perform this action.',
    404: 'This record is unavailable.',
    409: 'The current record state does not allow this action. Refresh the list and try again.',
    419: 'The security token expired. Refresh the page and try again.',
    422: 'Check the highlighted fields.',
    429: 'Too many requests. Wait a moment and try again.',
    503: 'This operation is temporarily unavailable. Try again later.',
};

export class ApiError extends Error {
    constructor(status, body = {}) {
        super(messages[status] ?? (status === 0
            ? 'The server could not be reached. Check your connection and try again.'
            : 'The server could not complete the request.'));
        this.status = status;
        this.fields = body?.error?.fields ?? body?.errors ?? {};
    }
}

async function send(path, options) {
    try {
        return await fetch(base + path, {
            credentials: 'same-origin',
            mode: 'same-origin',
            cache: 'no-store',
            ...options,
        });
    } catch (error) {
        if (error.name === 'AbortError') throw error;
        throw new ApiError(0);
    }
}

function xsrf() {
    const cookie = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

let csrfPending;
export function initializeCsrf() {
    csrfPending ??= send('/csrf-cookie', { headers: { Accept: 'application/json' } })
        .then(response => {
            if (!response.ok) throw new ApiError(response.status);
        })
        .finally(() => { csrfPending = undefined; });
    return csrfPending;
}

export async function api(path, { method = 'GET', data, query, signal, download = false } = {}) {
    if (query) {
        const params = new URLSearchParams(query).toString();
        if (params) path += '?' + params;
    }

    const headers = { Accept: 'application/json' };
    if (data !== undefined) headers['Content-Type'] = 'application/json';

    const write = !['GET', 'HEAD'].includes(method);
    if (write && !xsrf()) await initializeCsrf();

    const request = () => {
        if (write) headers['X-XSRF-TOKEN'] = xsrf();
        return send(path, { method, headers, body: JSON.stringify(data), signal });
    };

    let response = await request();
    if (write && response.status === 419) {
        await initializeCsrf();
        response = await request();
    }
    if (response.status === 401 && page !== 'login') {
        const target = new URL(login);
        target.searchParams.set('expired', '1');
        location.replace(target);
    }
    if (response.status === 204) return null;
    if (download && response.ok) {
        const disposition = response.headers.get('Content-Disposition') ?? '';
        const document = /filename="?verification-document\.(pdf|png|jpe?g)"?(?:;|$)/i.exec(disposition);
        if (!document) throw new ApiError(500);
        return { blob: await response.blob(), extension: document[1].toLowerCase() };
    }

    const json = response.headers.get('Content-Type')?.includes('application/json');
    const body = json ? await response.json().catch(() => null) : null;
    if (!response.ok) throw new ApiError(response.status, body);
    if (body === null) throw new ApiError(500);
    return body;
}

export function pagination(response) {
    const page = response.meta?.page ?? response.current_page;
    return {
        page,
        total: response.total,
        perPage: response.meta?.per_page ?? response.per_page,
        hasMore: response.meta?.has_more ?? page < response.last_page,
    };
}

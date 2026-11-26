// Small fetch wrapper for the /api routes (session cookie + CSRF, JSON in and out).

export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function request(method, path, body) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken(),
    };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    let response;

    try {
        response = await fetch(`/api${path}`, {
            method,
            headers,
            credentials: 'same-origin',
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new ApiError('Couldn’t reach the server. Check your connection and try again.', 0);
    }

    if (response.status === 401) {
        window.location.assign('/login');
        throw new ApiError('Your session has ended. Sign in again.', 401);
    }

    if (response.status === 419) {
        throw new ApiError('This page has expired. Refresh it and try again.', 419);
    }

    const payload = response.status === 204 ? null : await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            payload?.message || 'Something went wrong. Try again.',
            response.status,
            payload?.errors || {},
        );
    }

    return payload;
}

export const api = {
    get: (path) => request('GET', path),
    post: (path, body = {}) => request('POST', path, body),
    put: (path, body = {}) => request('PUT', path, body),
    delete: (path) => request('DELETE', path),
};
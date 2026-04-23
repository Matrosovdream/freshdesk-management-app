import axios from 'axios';

function readCookie(name) {
    if (typeof document === 'undefined') return null;
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${encodeURIComponent(name)}=`));
    if (!match) return null;
    return decodeURIComponent(match.split('=')[1]);
}

export const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    const token = readCookie('XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
    }
    return config;
});

let csrfPromise = null;

export function ensureCsrf() {
    if (!csrfPromise) {
        csrfPromise = http.get('/sanctum/csrf-cookie').catch((err) => {
            csrfPromise = null;
            throw err;
        });
    }
    return csrfPromise;
}

export function rateLimitRemainingFromHeaders(headers) {
    const remaining = headers?.['x-ratelimit-remaining'];
    const limit = headers?.['x-ratelimit-limit'];
    if (remaining == null) return null;
    return { remaining: Number(remaining), limit: limit != null ? Number(limit) : null };
}

let rateLimitHandler = null;
export function onRateLimit(cb) {
    rateLimitHandler = cb;
}

http.interceptors.response.use(
    (response) => {
        const rl = rateLimitRemainingFromHeaders(response.headers);
        if (rl && rateLimitHandler) rateLimitHandler(rl);
        return response;
    },
    async (error) => {
        const status = error?.response?.status;
        const config = error?.config || {};

        if (status === 419 && !config.__retriedCsrf) {
            config.__retriedCsrf = true;
            csrfPromise = null;
            await ensureCsrf();
            return http.request(config);
        }

        if (status === 401 && !config.__silent401) {
            if (typeof window !== 'undefined') {
                const path = window.location?.pathname || '';
                const isPortalAuthPage = /\/portal\/(login|register|forgot|reset|verify|magic)/.test(path);
                if (!isPortalAuthPage) {
                    queueMicrotask(() => {
                        const redirect = encodeURIComponent(window.location.pathname + window.location.search);
                        window.location.assign(`/portal/login?redirect=${redirect}`);
                    });
                }
            }
        }

        // 422 is left for the caller to handle — we normalize into error.validation
        if (status === 422 && error.response?.data?.errors) {
            error.validation = error.response.data.errors;
        }

        return Promise.reject(error);
    },
);

export default http;

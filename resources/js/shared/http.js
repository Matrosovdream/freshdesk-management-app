import axios from 'axios';

/**
 * Read a cookie value by name. Used to grab the XSRF-TOKEN cookie
 * that Laravel Sanctum sets so we can forward it as a header.
 */
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

// Attach the XSRF token manually too — some axios versions don't honour
// `withXSRFToken` for non-same-origin requests and we want the header set
// regardless.
http.interceptors.request.use((config) => {
    const token = readCookie('XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = token;
    }
    return config;
});

let csrfPromise = null;

/**
 * Hit Sanctum's CSRF endpoint once per page-load so subsequent mutating
 * requests carry a valid XSRF-TOKEN cookie.
 */
export function ensureCsrf() {
    if (!csrfPromise) {
        csrfPromise = http.get('/sanctum/csrf-cookie').catch((err) => {
            csrfPromise = null;
            throw err;
        });
    }
    return csrfPromise;
}

/**
 * 401 → not authenticated: clear local auth, redirect to login (except from
 *       the bootstrap call or the login page itself — those handle it inline).
 * 419 → CSRF token mismatch: refresh cookie and retry once.
 */
http.interceptors.response.use(
    (response) => response,
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
            // Let the auth store's getters flip to unauthenticated next tick.
            // Redirect is handled by the router guard the next time the user
            // navigates; pages that care can inspect error.response themselves.
            if (typeof window !== 'undefined') {
                const path = window.location?.pathname || '';
                const isAuthPage = /\/portal\/(login|register|forgot|reset|verify|magic)/.test(path);
                if (!isAuthPage) {
                    // Defer so the current call-stack can complete / log the error.
                    queueMicrotask(() => {
                        const redirect = encodeURIComponent(window.location.pathname + window.location.search);
                        window.location.assign(`/portal/login?redirect=${redirect}`);
                    });
                }
            }
        }

        return Promise.reject(error);
    },
);

export default http;

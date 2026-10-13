import axios from 'axios';

const TOKEN_KEY = 'erp.token';
const COMPANY_KEY = 'erp.company';

/**
 * Shared axios instance wired to the Laravel API.
 *
 * Responsibilities:
 *  - attach the bearer token,
 *  - attach the active company header,
 *  - drop the credentials when the session expires.
 */
const client = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

export function readToken() {
    return window.localStorage.getItem(TOKEN_KEY);
}

export function writeToken(token) {
    if (token) {
        window.localStorage.setItem(TOKEN_KEY, token);
    } else {
        window.localStorage.removeItem(TOKEN_KEY);
    }
}

export function readCompanyId() {
    const raw = window.localStorage.getItem(COMPANY_KEY);
    return raw ? Number(raw) : null;
}

export function writeCompanyId(companyId) {
    if (companyId) {
        window.localStorage.setItem(COMPANY_KEY, String(companyId));
    } else {
        window.localStorage.removeItem(COMPANY_KEY);
    }
}

client.interceptors.request.use((config) => {
    const token = readToken();

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const companyId = readCompanyId();

    if (companyId) {
        config.headers['X-Company-Id'] = String(companyId);
    }

    return config;
});

client.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const isAuthRoute = error.config?.url?.startsWith('/auth/');

        if (status === 401 && !isAuthRoute) {
            writeToken(null);
            writeCompanyId(null);

            if (!window.location.pathname.startsWith('/login')) {
                window.location.assign('/login');
            }
        }

        return Promise.reject(error);
    },
);

export default client;

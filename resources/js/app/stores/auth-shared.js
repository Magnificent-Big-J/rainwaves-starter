import { api } from '../utils/api';

export const AUTH_BASE = import.meta.env.VITE_AUTH_BASE || '/auth';
export const SESSION_BASE = `${AUTH_BASE}/session`;
export const PASSWORD_BASE = `${AUTH_BASE}/password`;

const PENDING_TWO_FACTOR_STORAGE_KEY = 'rw-starter.pending-2fa';

export const getXsrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match?.[1] ? decodeURIComponent(match[1]) : '';
};

export const csrfCookie = () => {
    return api(`${SESSION_BASE}/csrf-cookie`, {
        credentials: 'include',
    });
};

export const extractUserPayload = (response) => {
    return response?.user || response?.data?.user || response?.data || response || null;
};

export const normalizeErrorMessage = (error, fallback = 'Something went wrong.') => {
    return error?.data?.message
        || error?.data?.error
        || error?.message
        || fallback;
};

export const validationErrors = (error) => {
    return error?.data?.errors || {};
};

export const loadPendingTwoFactorState = () => {
    const raw = window.localStorage.getItem(PENDING_TWO_FACTOR_STORAGE_KEY);

    if (!raw) {
        return { required: false, channel: null };
    }

    try {
        return JSON.parse(raw);
    } catch (_error) {
        return { required: false, channel: null };
    }
};

export const persistPendingTwoFactorState = (required, channel = null) => {
    if (!required) {
        window.localStorage.removeItem(PENDING_TWO_FACTOR_STORAGE_KEY);

        return;
    }

    window.localStorage.setItem(PENDING_TWO_FACTOR_STORAGE_KEY, JSON.stringify({
        required,
        channel,
    }));
};

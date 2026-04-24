import { defineStore } from 'pinia';
import QRCode from 'qrcode';

import { api } from '../utils/api';
import { useSessionStore } from './session';
import { csrfCookie, getXsrfToken, SESSION_BASE } from './auth-shared';

const buildOtpAuthUri = ({ secret, account, issuer }) => {
    if (!secret || !account || !issuer) {
        return null;
    }

    const encodedIssuer = encodeURIComponent(issuer);
    const encodedAccount = encodeURIComponent(account);

    return `otpauth://totp/${encodedIssuer}:${encodedAccount}?secret=${encodeURIComponent(secret)}&issuer=${encodedIssuer}&period=30&digits=6&algorithm=SHA1`;
};

const extractSecret = (response) => {
    return response?.secret
        || response?.data?.secret
        || response?.two_factor?.secret
        || response?.totp?.secret
        || null;
};

export const useTwoFactorStore = defineStore('twoFactorAuth', {
    state: () => ({
        status: null,
        setup: {
            secret: null,
            otpauthUrl: null,
            qrCodeDataUrl: null,
        },
        loading: false,
    }),
    actions: {
        async verifyLoginCode(code) {
            this.loading = true;

            try {
                await csrfCookie();

                await api(`${SESSION_BASE}/2fa/verify-otp`, {
                    method: 'POST',
                    body: { code },
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });

                const session = useSessionStore();
                session.setPendingTwoFactor(false);
                await session.fetchUser();
            } finally {
                this.loading = false;
            }
        },
        async resendLoginCode() {
            this.loading = true;

            try {
                await csrfCookie();

                return await api(`${SESSION_BASE}/2fa/email`, {
                    method: 'POST',
                    body: {},
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });
            } finally {
                this.loading = false;
            }
        },
        async getStatus() {
            this.loading = true;

            try {
                const response = await api(`${SESSION_BASE}/2fa/status`);
                this.status = response;

                return response;
            } finally {
                this.loading = false;
            }
        },
        async enableTotp(password, account = '') {
            this.loading = true;

            try {
                await csrfCookie();

                const response = await api(`${SESSION_BASE}/2fa/totp/enable`, {
                    method: 'POST',
                    body: password ? { password } : undefined,
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });

                let secret = extractSecret(response);

                if (!secret) {
                    const status = await this.getStatus().catch(() => null);
                    secret = extractSecret(status);
                }

                const issuer = import.meta.env.VITE_APP_NAME || 'Rainwaves Starter';
                const otpauthUrl = buildOtpAuthUri({ secret, account, issuer });
                const qrCodeDataUrl = otpauthUrl
                    ? await QRCode.toDataURL(otpauthUrl, { width: 220, margin: 1 })
                    : null;

                this.setup = { secret, otpauthUrl, qrCodeDataUrl };

                return response;
            } finally {
                this.loading = false;
            }
        },
        async verifyTotp(code) {
            this.loading = true;

            try {
                await csrfCookie();

                return await api(`${SESSION_BASE}/2fa/totp/verify`, {
                    method: 'POST',
                    body: { code },
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });
            } finally {
                this.loading = false;
            }
        },
        async disable(password) {
            this.loading = true;

            try {
                await csrfCookie();

                await api(`${SESSION_BASE}/2fa/disable`, {
                    method: 'POST',
                    body: password ? { password } : undefined,
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });

                this.setup = {
                    secret: null,
                    otpauthUrl: null,
                    qrCodeDataUrl: null,
                };
            } finally {
                this.loading = false;
            }
        },
    },
});

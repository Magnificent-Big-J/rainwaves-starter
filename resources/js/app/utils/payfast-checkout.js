// Real PayFast checkout submission: POST to the web-layer initiate route (returns raw
// HTML — the PayFastClient-generated auto-submit form — not JSON), parse out the action
// + hidden fields, then build and submit a real <form> so the browser navigates to
// PayFast's hosted checkout. Same mechanism payfast-browser-test.vue uses for
// inspection; extracted here so the real checkout flow (BillingController page) and
// the dev tool don't hand-roll two copies of this DOM-parsing logic.

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function extractErrorMessage(body, status) {
    try {
        const parsed = JSON.parse(body);
        return parsed?.message || `PayFast checkout request failed with ${status}.`;
    } catch {
        return `PayFast checkout request failed with ${status}.`;
    }
}

/**
 * @param {'payment' | 'subscription'} mode
 * @param {Record<string, unknown>} payload
 * @returns {Promise<void>} resolves once the browser has been navigated to PayFast;
 *          never resolves on success in practice since the page unloads.
 */
export async function startPayFastCheckout(mode, payload) {
    const endpoint =
        mode === 'subscription' ? '/payments/payfast/subscriptions/initiate' : '/payments/payfast/initiate';

    const response = await fetch(endpoint, {
        method: 'POST',
        credentials: 'include',
        headers: {
            Accept: 'text/html',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const body = await response.text();

    if (!response.ok) {
        throw new Error(extractErrorMessage(body, response.status));
    }

    const doc = new DOMParser().parseFromString(body, 'text/html');
    const generatedForm = doc.querySelector('form');
    const action = generatedForm?.getAttribute('action');

    if (!action) {
        throw new Error('PayFast did not return a checkout form.');
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    doc.querySelectorAll('input[type="hidden"]').forEach((sourceInput) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = sourceInput.getAttribute('name') || '';
        input.value = sourceInput.getAttribute('value') || '';
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

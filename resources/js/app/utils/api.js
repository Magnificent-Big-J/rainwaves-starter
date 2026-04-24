import { ofetch } from 'ofetch';

export const api = ofetch.create({
    credentials: 'include',
    retry: 0,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

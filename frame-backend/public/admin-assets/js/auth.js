import { api, initializeCsrf } from './api.js';

const { page, home, login } = document.body.dataset;

async function signOut() {
    try {
        await api('/sessions/current', { method: 'DELETE' });
    } catch (error) {
        if (error.status !== 401) throw error;
    }
}

if (page === 'login') {
    const message = document.querySelector('#auth-message');
    const form = document.querySelector('#login-form');
    const switchAccount = document.querySelector('#switch-account');

    function showMessage(text) {
        message.textContent = text;
        message.hidden = !text;
    }

    if (new URL(location.href).searchParams.has('expired')) {
        showMessage('Your session has ended. Sign in again.');
    }

    switchAccount?.addEventListener('click', async () => {
        switchAccount.disabled = true;
        try {
            await signOut();
            location.replace(login);
        } catch (error) {
            showMessage(error.message);
            switchAccount.disabled = false;
        }
    });

    if (form) {
        const submit = form.querySelector('[type=submit]');
        const password = form.elements.password;
        const toggle = document.querySelector('#show-password');
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Hide' : 'Show';
            toggle.setAttribute('aria-pressed', String(show));
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            showMessage('');
            submit.disabled = true;
            submit.textContent = 'Signing in…';
            let signedIn = false;
            try {
                await initializeCsrf();
                await api('/sessions', {
                    method: 'POST',
                    data: { email: form.elements.email.value.trim(), password: password.value },
                });
                signedIn = true;
                const { data: user } = await api('/me');
                if (user.role !== 'admin' || user.status !== 'active') {
                    await signOut();
                    showMessage('An active Frame administrator account is required.');
                    return;
                }
                location.replace(home);
            } catch (error) {
                if (signedIn) {
                    try {
                        await signOut();
                    } catch {
                        location.replace(login);
                        return;
                    }
                }
                showMessage(error.fields?.email?.[0] ?? error.message);
            } finally {
                password.value = '';
                submit.disabled = false;
                submit.textContent = 'Sign in';
            }
        });
        submit.disabled = false;
    }
} else {
    const buttons = [...document.querySelectorAll('[data-logout]')];
    for (const button of buttons) {
        button.addEventListener('click', async () => {
            buttons.forEach(item => { item.disabled = true; });
            try {
                await signOut();
                location.replace(login);
            } catch (error) {
                const toast = document.querySelector('#notice');
                toast.textContent = error.message;
                toast.className = 'alert alert-danger';
                buttons.forEach(item => { item.disabled = false; });
            }
        });
    }
}

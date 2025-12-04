const baseInput = document.querySelector('#baseUrl');
const defaultProductInput = document.querySelector('#defaultProduct');
const issueForm = document.querySelector('#issueForm');
const validateForm = document.querySelector('#validateForm');
const activateForm = document.querySelector('#activateForm');
const loginForm = document.querySelector('#adminLoginForm');
const sessionStateEl = document.querySelector('#sessionState');
const sessionActionsEl = document.querySelector('#sessionActions');
const sessionLogout = document.querySelector('#sessionLogout');
const sessionRefresh = document.querySelector('#sessionRefresh');
const logContainer = document.querySelector('#log');
const template = document.querySelector('#logEntry');

const STORAGE_KEY = 'gd-license-portal';
const state = {
    baseUrl: '/api/licenses',
    defaultProduct: '',
};

let sessionProfile = null;

try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    Object.assign(state, saved);
} catch (error) {
    console.warn('Unable to parse saved state', error);
}

if (baseInput) baseInput.value = state.baseUrl;
if (state.defaultProduct && defaultProductInput) {
    defaultProductInput.value = state.defaultProduct;
}

defaultProductInput?.addEventListener('input', () => {
    state.defaultProduct = defaultProductInput.value.trim();
    persist();
    applyDefaultProduct();
});

baseInput?.addEventListener('input', () => {
    state.baseUrl = baseInput.value.trim() || '/api/licenses';
    persist();
});

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function applyDefaultProduct() {
    const value = state.defaultProduct;
    [issueForm, validateForm, activateForm].forEach((form) => {
        if (!form) return;
        const field = form.querySelector('input[name="product_code"]');
        if (field && !field.value) {
            field.value = value;
        }
    });
}

applyDefaultProduct();

loginForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = formToJSON(loginForm);
    try {
        await userApi('/login', {
            method: 'POST',
            body: payload,
        });
        await refreshSession();
        loginForm.reset();
        toast('Signed in.');
    } catch (error) {
        toast(error.message || 'Login failed', 'error');
    }
});

sessionLogout?.addEventListener('click', async () => {
    try {
        await userApi('/logout', { method: 'POST' });
    } catch (error) {
        console.warn(error);
    }
    sessionProfile = null;
    updateSessionUI();
    toast('Signed out.');
});

sessionRefresh?.addEventListener('click', async () => {
    await refreshSession();
    toast('Session refreshed.');
});

issueForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = withDefaults(formToJSON(issueForm));
    await callApi('issue', payload, true);
});

validateForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = withDefaults(formToJSON(validateForm));
    await callApi('validate', payload);
});

activateForm?.querySelectorAll('button[data-action]')?.forEach((button) => {
    button.addEventListener('click', async () => {
        const action = button.dataset.action;
        const payload = withDefaults(formToJSON(activateForm));
        await callApi(action, payload, true);
    });
});

function withDefaults(payload) {
    if (!payload.product_code && state.defaultProduct) {
        payload.product_code = state.defaultProduct;
    }
    return payload;
}

async function ensureAdminSession() {
    if (sessionProfile?.is_admin) {
        return true;
    }
    try {
        await refreshSession();
    } catch (error) {
        return false;
    }
    return !!sessionProfile?.is_admin;
}

async function refreshSession() {
    try {
        const data = await userApi('/me');
        if (!data?.profile?.is_admin) {
            throw new Error('Admin privileges required.');
        }
        sessionProfile = data.profile;
        updateSessionUI();
        return sessionProfile;
    } catch (error) {
        sessionProfile = null;
        updateSessionUI(error.message);
        throw error;
    }
}

function updateSessionUI(message) {
    if (sessionProfile?.full_name) {
        sessionStateEl.textContent = `Signed in as ${sessionProfile.full_name}`;
        sessionActionsEl.classList.remove('hidden');
        loginForm?.classList.add('hidden');
    } else {
        sessionStateEl.textContent = message || 'Not signed in';
        sessionActionsEl.classList.add('hidden');
        loginForm?.classList.remove('hidden');
    }
}

async function callApi(action, payload, needsAdmin = false) {
    const entry = createLogEntry(action, payload);
    const baseUrl = (state.baseUrl || '/api/licenses').replace(/\/$/, '');
    const url = `${baseUrl}/${action}`;

    if (needsAdmin) {
        const ok = await ensureAdminSession();
        if (!ok) {
            entry.classList.add('error');
            entry.querySelector('.entry-body').textContent = 'Admin login required.';
            entry.querySelector('.pill').style.background = 'var(--danger)';
            toast('Admin login required.', 'error');
            return;
        }
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'include',
        });

        const body = await response.json();
        if (!response.ok || body.error) {
            throw new Error(body.error || 'API error');
        }

        entry.classList.remove('error');
        entry.querySelector('.label').textContent = `${action.toUpperCase()} · ${response.status}`;
        entry.querySelector('.entry-body').textContent = JSON.stringify(body, null, 2);
        entry.querySelector('.pill').style.background = 'var(--accent)';
        return body;
    } catch (error) {
        entry.classList.add('error');
        entry.querySelector('.label').textContent = `${action.toUpperCase()} · ERROR`;
        entry.querySelector('.entry-body').textContent = error.message;
        entry.querySelector('.pill').style.background = 'var(--danger)';
        toast(error.message, 'error');
    }
}

function formToJSON(form) {
    return Array.from(new FormData(form).entries()).reduce((acc, [key, value]) => {
        if (value === '') return acc;
        acc[key] = value;
        return acc;
    }, {});
}

function createLogEntry(action, payload) {
    const node = template.content.firstElementChild.cloneNode(true);
    node.querySelector('.label').textContent = `${action.toUpperCase()} · …`;
    node.querySelector('.entry-body').textContent = JSON.stringify(payload, null, 2);
    node.querySelector('.time').textContent = new Date().toLocaleTimeString();
    logContainer.prepend(node);
    return node;
}

function toast(message, type = 'info') {
    const note = document.createElement('div');
    note.className = `toast ${type}`;
    note.textContent = message;
    document.body.appendChild(note);
    setTimeout(() => note.remove(), 3400);
}

async function userApi(path, options = {}) {
    const { headers: optionHeaders = {}, body, ...rest } = options;
    const config = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            ...optionHeaders,
        },
        credentials: 'include',
        ...rest,
    };

    if (body !== undefined) {
        config.body = typeof body === 'string' ? body : JSON.stringify(body);
    }

    const response = await fetch(`/api/users${path}`, config);

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.error) {
        throw new Error(payload.error || 'Request failed');
    }
    return payload.data;
}

const SAMPLE_MAP = {
    issue: (base) => `# Login first and store cookies:
# curl -c cookie.txt -X POST https://example.com/api/users/login \
#   -H "Content-Type: application/json" -d '{"email":"","password":""}'
curl -X POST "${base}/issue" \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"product_code":"APP_PRO"}'`,
    activate: (base) => `curl -X POST "${base}/activate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO","instance_id":"site-123"}'`,
    validate: (base) => `curl -X POST "${base}/validate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO"}'`,
    deactivate: (base) => `curl -X POST "${base}/deactivate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO","instance_id":"site-123"}'`,
};

document.querySelectorAll('.doc-card button.copy').forEach((button) => {
    button.addEventListener('click', () => {
        const parent = button.closest('.doc-card');
        const key = parent?.dataset.sample;
        const base = (state.baseUrl || '/api/licenses').replace(/\/$/, '');
        const builder = SAMPLE_MAP[key];
        const text = builder ? builder(base) : parent.querySelector('pre')?.innerText;
        if (!text) return;
        navigator.clipboard.writeText(text)
            .then(() => toast('Sample copied.'))
            .catch(() => toast('Copy failed', 'error'));
    });
});

refreshSession().catch(() => updateSessionUI());

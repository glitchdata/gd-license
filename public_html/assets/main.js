const baseInput = document.querySelector('#baseUrl');
const tokenInput = document.querySelector('#adminToken');
const defaultProductInput = document.querySelector('#defaultProduct');
const issueForm = document.querySelector('#issueForm');
const validateForm = document.querySelector('#validateForm');
const activateForm = document.querySelector('#activateForm');
const logContainer = document.querySelector('#log');
const template = document.querySelector('#logEntry');

const STORAGE_KEY = 'gd-license-portal';
const state = {
    baseUrl: '/api/licenses',
    adminToken: '',
    defaultProduct: '',
};

try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    Object.assign(state, saved);
} catch (error) {
    console.warn('Unable to parse saved state', error);
}

baseInput.value = state.baseUrl;
if (state.adminToken) tokenInput.value = state.adminToken;
if (state.defaultProduct) defaultProductInput.value = state.defaultProduct;

defaultProductInput.addEventListener('input', () => {
    state.defaultProduct = defaultProductInput.value.trim();
    persist();
    applyDefaultProduct();
});

baseInput.addEventListener('input', () => {
    state.baseUrl = baseInput.value.trim() || '/api/licenses';
    persist();
});

tokenInput.addEventListener('input', () => {
    state.adminToken = tokenInput.value;
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
        await callApi(action, payload);
    });
});

function withDefaults(payload) {
    if (!payload.product_code && state.defaultProduct) {
        payload.product_code = state.defaultProduct;
    }
    return payload;
}

async function callApi(action, payload, needsToken = false) {
    const entry = createLogEntry(action, payload);
    const baseUrl = (state.baseUrl || '/api/licenses').replace(/\/$/, '');
    const url = `${baseUrl}/${action}`;

    const headers = {
        'Content-Type': 'application/json',
    };

    if (needsToken) {
        if (!state.adminToken) {
            entry.classList.add('error');
            entry.querySelector('.entry-body').textContent = 'Admin token required.';
            toast('Admin token required.', 'error');
            return;
        }
        headers['Authorization'] = `Bearer ${state.adminToken}`;
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers,
            body: JSON.stringify(payload),
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

const SAMPLE_MAP = {
    issue: (base, token) => `curl -X POST "${base}/issue" \\\n  -H "Authorization: Bearer ${token || '$TOKEN'}" \\\n  -H "Content-Type: application/json" \\\n  -d '{"product_code":"APP_PRO"}'`,
    activate: (base) => `curl -X POST "${base}/activate" \\\n  -H "Content-Type: application/json" \\\n  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO","instance_id":"site-123"}'`,
    validate: (base) => `curl -X POST "${base}/validate" \\\n  -H "Content-Type: application/json" \\\n  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO"}'`,
    deactivate: (base) => `curl -X POST "${base}/deactivate" \\\n  -H "Content-Type: application/json" \\\n  -d '{"license_key":"XXXX-XXXX","product_code":"APP_PRO","instance_id":"site-123"}'`,
};

document.querySelectorAll('.doc-card button.copy').forEach((button) => {
    button.addEventListener('click', () => {
        const parent = button.closest('.doc-card');
        const key = parent?.dataset.sample;
        const base = (state.baseUrl || '/api/licenses').replace(/\/$/, '');
        const token = state.adminToken || '$TOKEN';
        const builder = SAMPLE_MAP[key];
        const text = builder ? builder(base, token) : parent.querySelector('pre')?.innerText;
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => toast('Sample copied.')).catch(() => toast('Copy failed', 'error'));
    });
});

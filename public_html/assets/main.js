curl -X POST "${base}/issue" \
const baseInput = document.querySelector('#baseUrl');
const defaultProductInput = document.querySelector('#defaultProduct');
const loginForm = document.querySelector('#adminLoginForm');
const sessionStateEl = document.querySelector('#sessionState');
const sessionActionsEl = document.querySelector('#sessionActions');
const sessionLogout = document.querySelector('#sessionLogout');
const sessionRefresh = document.querySelector('#sessionRefresh');
const dashboard = document.querySelector('#licenseDashboard');
const issueForm = document.querySelector('#issueForm');
const logContainer = document.querySelector('#log');
const template = document.querySelector('#logEntry');
const licenseTableBody = document.querySelector('#licenseTableBody');
const licenseEmptyState = document.querySelector('#licenseEmpty');
const licenseSearchInput = document.querySelector('#licenseSearch');
const licenseStatusFilter = document.querySelector('#licenseStatus');
const licenseRefreshButton = document.querySelector('#licenseRefreshButton');
const licenseDetailForm = document.querySelector('#licenseDetailForm');
const licenseDetailEmpty = document.querySelector('#licenseDetailEmpty');
const deleteLicenseButton = document.querySelector('#deleteLicenseButton');
const detailProductEl = document.querySelector('#detailProduct');
const detailUsageEl = document.querySelector('#detailUsage');

const STORAGE_KEY = 'gd-license-portal';
const state = {
    baseUrl: '/api/licenses',
    defaultProduct: '',
};

let sessionProfile = null;
let licenses = [];
let selectedLicenseKey = null;
let searchTimer = null;

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

deleteLicenseButton?.setAttribute('disabled', 'true');

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function applyDefaultProduct() {
    const value = state.defaultProduct;
    if (!issueForm) return;
    const field = issueForm.querySelector('input[name="product_code"]');
    if (field && !field.value) {
        field.value = value;
    }
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
    selectedLicenseKey = null;
    licenses = [];
    renderLicenseTable();
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
    const result = await callApi('issue', payload, true);
    if (result?.license) {
        toast('License issued.');
        issueForm.reset();
        applyDefaultProduct();
        await loadLicenses();
        selectLicense(result.license.license_key);
    }
});

licenseDetailForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!selectedLicenseKey) return;
    const payload = formToJSON(licenseDetailForm, { includeEmpty: true });
    payload.license_key = selectedLicenseKey;
    const result = await callApi('update', payload, true);
    if (result?.license) {
        toast('License updated.');
        await loadLicenses();
        selectLicense(result.license.license_key);
    }
});

deleteLicenseButton?.addEventListener('click', async () => {
    if (!selectedLicenseKey) return;
    if (!window.confirm('Delete this license? This action cannot be undone.')) return;
    const result = await callApi('delete', { license_key: selectedLicenseKey }, true);
    if (result?.deleted) {
        toast('License deleted.');
        selectedLicenseKey = null;
        await loadLicenses();
        clearDetailForm();
    }
});

licenseSearchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadLicenses(), 300);
});

licenseStatusFilter?.addEventListener('change', () => {
    loadLicenses();
});

licenseRefreshButton?.addEventListener('click', () => {
    loadLicenses();
});

function withDefaults(payload) {
    if (!payload.product_code && state.defaultProduct) {
        payload.product_code = state.defaultProduct;
    }
    return payload;
}

async function loadLicenses() {
    if (!sessionProfile?.is_admin) return;
    try {
        const params = new URLSearchParams();
        const search = licenseSearchInput?.value.trim();
        const status = licenseStatusFilter?.value.trim();
        if (search) params.set('search', search);
        if (status) params.set('status', status);
        const query = params.toString();
        const response = await fetch(`/api/licenses${query ? `?${query}` : ''}`, {
            method: 'GET',
            credentials: 'include',
        });
        const body = await response.json();
        if (!response.ok || body.error) {
            throw new Error(body.error || 'Failed to load licenses.');
        }
        licenses = body.data?.licenses ?? [];
        renderLicenseTable();
        if (selectedLicenseKey) {
            const found = licenses.find((item) => item.license_key === selectedLicenseKey);
            if (found) {
                populateDetailForm(found);
            } else {
                clearDetailForm();
            }
        }
    } catch (error) {
        toast(error.message, 'error');
    }
}

function renderLicenseTable() {
    if (!licenseTableBody) return;
    licenseTableBody.innerHTML = '';
    if (!licenses.length) {
        licenseEmptyState?.classList.remove('hidden');
        return;
    }
    licenseEmptyState?.classList.add('hidden');
    licenses.forEach((license) => {
        const row = document.createElement('tr');
        if (license.license_key === selectedLicenseKey) {
            row.classList.add('active');
        }
        row.innerHTML = `
            <td>
                <strong>${license.license_key}</strong>
                <div class="muted">${license.product?.name ?? 'Unlinked product'}</div>
            </td>
            <td>${license.product?.code ?? '—'}</td>
            <td><span class="status-badge ${license.status}">${license.status}</span></td>
            <td>${license.expires_at ?? '—'}</td>
            <td>${license.activations_in_use}/${license.max_activations}</td>
        `;
        row.addEventListener('click', () => selectLicense(license.license_key));
        licenseTableBody.appendChild(row);
    });
}

function selectLicense(licenseKey) {
    const license = licenses.find((item) => item.license_key === licenseKey);
    if (!license) {
        clearDetailForm();
        return;
    }
    selectedLicenseKey = licenseKey;
    populateDetailForm(license);
    renderLicenseTable();
}

function populateDetailForm(license) {
    if (!licenseDetailForm) return;
    licenseDetailForm.classList.remove('hidden');
    licenseDetailEmpty?.classList.add('hidden');
    deleteLicenseButton?.removeAttribute('disabled');

    setDetailField('license_key', license.license_key);
    setDetailField('status', license.status);
    setDetailField('expires_at', license.expires_at ?? '');
    setDetailField('max_activations', license.max_activations ?? '');
    setDetailField('notes', license.notes ?? '');

    if (detailProductEl) {
        const name = license.product?.name ?? 'Unknown product';
        const code = license.product?.code ? ` · ${license.product.code}` : '';
        detailProductEl.textContent = `${name}${code}`;
    }
    if (detailUsageEl) {
        detailUsageEl.textContent = `${license.activations_in_use} / ${license.max_activations} activations`;
    }
}

function setDetailField(name, value) {
    if (!licenseDetailForm) return;
    const field = licenseDetailForm.elements.namedItem(name);
    if (field) {
        field.value = value ?? '';
    }
}

function clearDetailForm() {
    selectedLicenseKey = null;
    licenseDetailForm?.classList.add('hidden');
    licenseDetailForm?.reset();
    licenseDetailEmpty?.classList.remove('hidden');
    deleteLicenseButton?.setAttribute('disabled', 'true');
    if (detailProductEl) detailProductEl.textContent = '—';
    if (detailUsageEl) detailUsageEl.textContent = '0 / 0 activations';
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
        await loadLicenses();
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
        if (sessionProfile.is_admin) {
            dashboard?.classList.remove('hidden');
        } else {
            dashboard?.classList.add('hidden');
        }
    } else {
        sessionStateEl.textContent = message || '';
        sessionActionsEl.classList.add('hidden');
        loginForm?.classList.remove('hidden');
        dashboard?.classList.add('hidden');
        licenses = [];
        clearDetailForm();
        renderLicenseTable();
    }
}

async function callApi(action, payload, needsAdmin = false) {
    const entry = createLogEntry(action, payload);
    const baseUrl = (state.baseUrl || '/api/licenses').replace(/\/$/, '');
    const url = `${baseUrl}/${action}`;

    if (needsAdmin) {
        const ok = await ensureAdminSession();
        if (!ok) {
            markLogError(entry, 'Admin login required.');
            toast('Admin login required.', 'error');
            return null;
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

        if (entry) {
            entry.classList.remove('error');
            entry.querySelector('.label').textContent = `${action.toUpperCase()} · ${response.status}`;
            entry.querySelector('.entry-body').textContent = JSON.stringify(body, null, 2);
            entry.querySelector('.pill').style.background = 'var(--accent)';
        }
        return body.data;
    } catch (error) {
        markLogError(entry, error.message);
        toast(error.message, 'error');
        return null;
    }
}

function markLogError(entry, message) {
    if (!entry) return;
    entry.classList.add('error');
    const label = entry.querySelector('.label');
    if (label) {
        const base = label.textContent.split(' · ')[0];
        label.textContent = `${base} · ERROR`;
    }
    const body = entry.querySelector('.entry-body');
    if (body) {
        body.textContent = message;
    }
    const pill = entry.querySelector('.pill');
    if (pill) {
        pill.style.background = 'var(--danger)';
    }
}

function formToJSON(form, options = {}) {
    const includeEmpty = options.includeEmpty ?? false;
    return Array.from(new FormData(form).entries()).reduce((acc, [key, value]) => {
        if (!includeEmpty && value === '') {
            return acc;
        }
        acc[key] = value;
        return acc;
    }, {});
}

function createLogEntry(action, payload) {
    if (!template || !logContainer) {
        return null;
    }
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

refreshSession().catch(() => updateSessionUI());

const sessionState = document.querySelector('#sessionState');
const sessionMeta = document.querySelector('#sessionMeta');
const sessionRefresh = document.querySelector('#sessionRefresh');
const sessionLogout = document.querySelector('#sessionLogout');
const licenseCountEl = document.querySelector('#licenseCount');
const licenseTableBody = document.querySelector('#licenseTableBody');
const licenseEmpty = document.querySelector('#licenseEmpty');
const licenseSearch = document.querySelector('#licenseSearch');
const licenseStatus = document.querySelector('#licenseStatus');
const licenseRefresh = document.querySelector('#licenseRefresh');
const clearFiltersButton = document.querySelector('#clearFilters');
const rowTemplate = document.querySelector('#licenseRow');
const issueForm = document.querySelector('#issueForm');
const detailForm = document.querySelector('#detailForm');
const detailEmpty = document.querySelector('#detailEmpty');
const deleteLicenseButton = document.querySelector('#deleteLicense');
const scrollIssueButton = document.querySelector('#scrollIssue');

const detailKey = document.querySelector('#detailKey');
const detailProduct = document.querySelector('#detailProduct');
const detailStatus = document.querySelector('#detailStatus');
const detailExpires = document.querySelector('#detailExpires');
const detailCreated = document.querySelector('#detailCreated');
const detailUpdated = document.querySelector('#detailUpdated');
const detailUsage = document.querySelector('#detailUsage');
const detailRemaining = document.querySelector('#detailRemaining');
const detailNotes = document.querySelector('#detailNotes');

let profile = null;
let licenses = [];
let selectedKey = null;
let searchTimer = null;

sessionRefresh?.addEventListener('click', () => {
    refreshSession().then(() => toast('Session refreshed.'));
});

sessionLogout?.addEventListener('click', async () => {
    try {
        await userApi('/logout', { method: 'POST' });
    } catch (error) {
        console.warn(error);
    }
    toast('Signed out.');
    window.location.assign('/');
});

licenseRefresh?.addEventListener('click', () => loadLicenses());

licenseSearch?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadLicenses(), 350);
});

licenseStatus?.addEventListener('change', () => loadLicenses());

clearFiltersButton?.addEventListener('click', () => {
    if (licenseSearch) licenseSearch.value = '';
    if (licenseStatus) licenseStatus.value = '';
    loadLicenses();
});

scrollIssueButton?.addEventListener('click', () => {
    document.querySelector('#issueCard')?.scrollIntoView({ behavior: 'smooth' });
});

issueForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = formToJSON(issueForm);
    try {
        const data = await callLicenseApi('issue', payload);
        if (data?.license) {
            toast('License issued.');
            issueForm.reset();
            selectedKey = data.license.license_key;
            await loadLicenses();
        }
    } catch (error) {
        toast(error.message || 'Issue failed', 'error');
    }
});

detailForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!selectedKey) {
        toast('Select a license first.', 'error');
        return;
    }
    const payload = formToJSON(detailForm, { includeEmpty: true });
    payload.license_key = selectedKey;
    try {
        const data = await callLicenseApi('update', payload);
        if (data?.license) {
            toast('License updated.');
            selectedKey = data.license.license_key;
            await loadLicenses();
        }
    } catch (error) {
        toast(error.message || 'Update failed', 'error');
    }
});

deleteLicenseButton?.addEventListener('click', async () => {
    if (!selectedKey) {
        toast('Select a license first.', 'error');
        return;
    }
    const confirmed = window.confirm('Delete this license? This cannot be undone.');
    if (!confirmed) return;
    try {
        const data = await callLicenseApi('delete', { license_key: selectedKey });
        if (data?.deleted) {
            toast('License deleted.');
            selectedKey = null;
            await loadLicenses();
            renderDetail(null);
        }
    } catch (error) {
        toast(error.message || 'Delete failed', 'error');
    }
});

async function refreshSession() {
    setSessionState('Checking session…', 'Attempting to load your admin profile.');
    try {
        const data = await userApi('/me');
        if (!data?.profile?.is_admin) {
            throw new Error('Admin privileges required.');
        }
        profile = data.profile;
        setSessionState(`Signed in as ${profile.full_name}`, profile.email);
        await loadLicenses();
    } catch (error) {
        profile = null;
        setSessionState('Sign in required', error.message || 'Please return to the portal and log in as an admin.');
        licenses = [];
        renderLicenseTable();
    }
}

async function loadLicenses() {
    if (!profile?.is_admin) {
        return;
    }

    try {
        const params = new URLSearchParams();
        const search = licenseSearch?.value.trim();
        const status = licenseStatus?.value.trim();
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
        licenseCountEl.textContent = licenses.length.toString();
        renderLicenseTable();
        if (selectedKey) {
            const active = licenses.find((item) => item.license_key === selectedKey);
            if (active) {
                renderDetail(active);
            } else {
                renderDetail(null);
            }
        } else {
            renderDetail(null);
        }
    } catch (error) {
        toast(error.message || 'Unable to load licenses.', 'error');
        licenses = [];
        renderLicenseTable();
    }
}

function renderLicenseTable() {
    if (!licenseTableBody || !rowTemplate) return;
    licenseTableBody.innerHTML = '';
    if (!licenses.length) {
        licenseEmpty?.classList.remove('hidden');
        return;
    }
    licenseEmpty?.classList.add('hidden');

    licenses.forEach((license) => {
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        node.dataset.key = license.license_key;
        node.querySelector('.license-key').textContent = license.license_key;
        node.querySelector('.subline').textContent = license.notes || '—';
        node.querySelector('.product-code').textContent = `${license.product.name} · ${license.product.code}`;
        const pill = node.querySelector('.status-pill');
        pill.textContent = license.status;
        pill.classList.add(license.status);
        node.querySelector('.expires').textContent = formatDate(license.expires_at) || '—';
        node.querySelector('.usage').textContent = `${license.activations_in_use}/${license.max_activations}`;
        if (license.license_key === selectedKey) {
            node.classList.add('active');
        }
        node.addEventListener('click', () => {
            selectedKey = license.license_key;
            renderDetail(license);
            renderLicenseTable();
        });
        licenseTableBody.appendChild(node);
    });
}

function renderDetail(license) {
    if (!license) {
        detailKey.textContent = 'Pick a license';
        detailProduct.textContent = 'Nothing selected';
        detailStatus.textContent = detailExpires.textContent = detailCreated.textContent = detailUpdated.textContent = detailUsage.textContent = detailRemaining.textContent = '—';
        detailNotes.textContent = '—';
        selectedKey = null;
        setDetailFormVisible(false);
        detailForm?.reset();
        return;
    }

    detailKey.textContent = license.license_key;
    detailProduct.textContent = `${license.product.name} · ${license.product.code}`;
    detailStatus.textContent = license.status;
    detailExpires.textContent = formatDate(license.expires_at) || 'None';
    detailCreated.textContent = formatDate(license.created_at);
    detailUpdated.textContent = formatDate(license.updated_at);
    detailUsage.textContent = `${license.activations_in_use} of ${license.max_activations}`;
    detailRemaining.textContent = license.activations_remaining ?? '—';
    detailNotes.textContent = license.notes || '—';
    populateDetailForm(license);
    setDetailFormVisible(true);
}

function setSessionState(title, meta) {
    if (sessionState) sessionState.textContent = title;
    if (sessionMeta) sessionMeta.textContent = meta;
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

function formatDate(value) {
    if (!value) return '';
    const date = new Date(value.replace(' ', 'T'));
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function toast(message, type) {
    const note = document.createElement('div');
    note.className = `toast ${type || ''}`;
    note.textContent = message;
    document.body.appendChild(note);
    setTimeout(() => note.remove(), 3200);
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

function populateDetailForm(license) {
    if (!detailForm) return;
    if (detailForm.elements.status) detailForm.elements.status.value = license.status;
    if (detailForm.elements.expires_at) {
        detailForm.elements.expires_at.value = license.expires_at ?? '';
    }
    if (detailForm.elements.max_activations) {
        detailForm.elements.max_activations.value = license.max_activations ?? '';
    }
    if (detailForm.elements.notes) {
        detailForm.elements.notes.value = license.notes ?? '';
    }
}

function setDetailFormVisible(isVisible) {
    if (detailForm) detailForm.classList.toggle('hidden', !isVisible);
    if (detailEmpty) detailEmpty.classList.toggle('hidden', isVisible);
}

async function callLicenseApi(action, payload) {
    const response = await fetch(`/api/licenses/${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.error) {
        throw new Error(body.error || 'API error');
    }
    return body.data;
}

document.head.insertAdjacentHTML(
    'beforeend',
    `<style>
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(5, 5, 10, 0.92);
            color: white;
            padding: 0.9rem 1.2rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 999;
            animation: fadeIn 0.25s ease, fadeOut 0.35s ease 2.7s forwards;
            font-family: 'Space Grotesk', sans-serif;
        }
        .toast.error { border-color: rgba(255, 111, 111, 0.6); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px);} to { opacity:1; transform: translateY(0);} }
        @keyframes fadeOut { to { opacity:0; transform: translateY(10px);} }
    </style>`
);

refreshSession();

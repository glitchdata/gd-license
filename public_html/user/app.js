const loginForm = document.querySelector('#loginForm');
const loginPanel = document.querySelector('#loginPanel');
const dashboard = document.querySelector('#dashboard');
const userName = document.querySelector('#userName');
const userEmail = document.querySelector('#userEmail');
const userCreated = document.querySelector('#userCreated');
const userLastLogin = document.querySelector('#userLastLogin');
const licenseTable = document.querySelector('#licenseTable');
const refreshBtn = document.querySelector('#refreshBtn');
const logoutBtn = document.querySelector('#logoutBtn');
const rowTemplate = document.querySelector('#licenseRow');

const formatter = new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(loginForm).entries());
    try {
        const data = await api('/login', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        renderDashboard(data);
        loginForm.reset();
        toast('Signed in.');
    } catch (error) {
        toast(error.message || 'Login failed', 'error');
    }
});

refreshBtn.addEventListener('click', async () => {
    await loadDashboard();
    toast('Refreshed');
});

logoutBtn.addEventListener('click', async () => {
    try {
        await api('/logout', { method: 'POST' });
    } catch (error) {
        console.warn(error);
    }
    togglePanels(false);
    toast('Signed out.');
});

async function loadDashboard() {
    try {
        const data = await api('/me');
        renderDashboard(data);
    } catch (error) {
        togglePanels(false);
    }
}

function renderDashboard(data) {
    if (!data || !data.profile) {
        throw new Error('Malformed response.');
    }
    const { profile, licenses = [] } = data;
    userName.textContent = profile.full_name;
    userEmail.textContent = profile.email;
    userCreated.textContent = formatDate(profile.created_at);
    userLastLogin.textContent = profile.last_login_at ? formatDate(profile.last_login_at) : '—';
    renderLicenses(licenses);
    togglePanels(true);
}

function renderLicenses(licenses) {
    licenseTable.innerHTML = '';
    if (!licenses.length) {
        const empty = document.createElement('p');
        empty.className = 'muted';
        empty.textContent = 'No licenses have been assigned yet.';
        licenseTable.appendChild(empty);
        return;
    }

    licenses.forEach((item) => {
        const node = rowTemplate.content.firstElementChild.cloneNode(true);
        node.querySelector('.product').textContent = `${item.product.name} · ${item.product.code}`;
        node.querySelector('.key').textContent = item.license_key;
        node.querySelector('.status').textContent = item.status;
        node.querySelector('.assigned').textContent = formatDate(item.assigned_at);
        node.querySelector('.expires').textContent = item.expires_at ? formatDate(item.expires_at) : 'Never';
        const max = item.max_activations ?? '∞';
        const remaining = item.activations_remaining ?? '∞';
        node.querySelector('.activations').textContent = `${item.activations_in_use} / ${max} (${remaining} left)`;
        licenseTable.appendChild(node);
    });
}

function togglePanels(authed) {
    if (authed) {
        loginPanel.classList.add('hidden');
        dashboard.classList.remove('hidden');
    } else {
        loginPanel.classList.remove('hidden');
        dashboard.classList.add('hidden');
    }
}

async function api(path, options = {}) {
    const response = await fetch(`/api/users${path}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
        credentials: 'include',
        ...options,
    });

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
    if (!value) return '—';
    const date = new Date(value.replace(' ', 'T'));
    return formatter.format(date);
}

function toast(message, type = 'info') {
    const node = document.createElement('div');
    node.className = `toast ${type}`;
    node.textContent = message;
    document.body.appendChild(node);
    setTimeout(() => node.remove(), 3200);
}

loadDashboard();

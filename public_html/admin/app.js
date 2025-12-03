const forms = {
    issue: document.querySelector('#issueForm'),
    validate: document.querySelector('#validateForm'),
    activate: document.querySelector('#activateForm'),
};

const log = document.querySelector('#log');
const template = document.querySelector('#logEntry');
const baseInput = document.querySelector('#baseUrl');
const adminTokenInput = document.querySelector('#adminToken');

const STORAGE_KEY = 'gd-license-console';
const persisted = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
if (persisted.baseUrl) baseInput.value = persisted.baseUrl;
if (persisted.adminToken) adminTokenInput.value = persisted.adminToken;

adminTokenInput.addEventListener('input', () => persist());
baseInput.addEventListener('input', () => persist());

function persist() {
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
            baseUrl: baseInput.value.trim() || '/api/licenses',
            adminToken: adminTokenInput.value,
        })
    );
}

forms.issue.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = formToJSON(event.target);
    await callApi('issue', payload, true);
});

forms.validate.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = formToJSON(event.target);
    await callApi('validate', payload);
});

forms.activate.querySelectorAll('button[data-action]').forEach((button) => {
    button.addEventListener('click', async () => {
        const action = button.dataset.action;
        const payload = formToJSON(forms.activate);
        await callApi(action, payload);
    });
});

async function callApi(action, payload, needsToken = false) {
    const baseUrl = baseInput.value.trim() || '/api/licenses';
    const url = `${baseUrl.replace(/\/$/, '')}/${action}`;

    const headers = {
        'Content-Type': 'application/json',
    };

    if (needsToken) {
        const token = adminTokenInput.value.trim();
        if (!token) {
            toast('Admin token required for issuing licenses.', 'error');
            return;
        }
        headers['Authorization'] = `Bearer ${token}`;
    }

    const entry = createLogEntry(action, payload);

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers,
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || data.error) {
            throw new Error(data.error || 'API error');
        }
        entry.classList.remove('error');
        entry.querySelector('.label').textContent = `${action.toUpperCase()} · ${response.status}`;
        entry.querySelector('.entry-body').textContent = JSON.stringify(data, null, 2);
        return data;
    } catch (error) {
        entry.classList.add('error');
        entry.querySelector('.label').textContent = `${action.toUpperCase()} · ERROR`;
        entry.querySelector('.entry-body').textContent = error.message;
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
    const clone = template.content.firstElementChild.cloneNode(true);
    clone.querySelector('.label').textContent = `${action.toUpperCase()} · …`;
    clone.querySelector('.entry-body').textContent = JSON.stringify(payload, null, 2);
    clone.querySelector('.time').textContent = new Date().toLocaleTimeString();
    log.prepend(clone);
    return clone;
}

function toast(message, type) {
    const note = document.createElement('div');
    note.className = `toast ${type}`;
    note.textContent = message;
    document.body.appendChild(note);
    setTimeout(() => note.remove(), 3200);
}

// Lightweight toast styles injected dynamically
document.head.insertAdjacentHTML(
    'beforeend',
    `<style>
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: rgba(5,5,10,0.9);
            color: white;
            padding: 0.9rem 1.2rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.2);
            z-index: 999;
            animation: fadeIn 0.3s ease, fadeOut 0.4s ease 2.8s forwards;
            font-family: 'Space Grotesk', sans-serif;
        }
        .toast.error { border-color: rgba(255, 111, 111, 0.6); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px);} to { opacity:1; transform: translateY(0);} }
        @keyframes fadeOut { to { opacity:0; transform: translateY(10px);} }
    </style>`
);

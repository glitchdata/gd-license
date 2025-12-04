<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GD License Server · Control Room</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/main.css">
</head>
<body>
<div class="bg"></div>
<main class="wrap">
    <header class="masthead">
        <div>
            <p class="eyebrow">Glitchdata · License Ops</p>
            <h1>Log into the control room.</h1>
            <p class="lede">Sign in with your admin credentials right on the front page, then issue licenses, simulate activations, and inspect the JSON API responses without leaving this view.</p>
            <div class="cta-row">
                <a class="cta" href="#authPanel">Go to Admin Login</a>
                <a class="cta ghost" href="/user/">Customer Portal</a>
            </div>
            <ul class="callouts">
                <li>Admins sign in with email + password using the panel on the right.</li>
                <li>Issue / activate / deactivate endpoints require an authenticated admin session.</li>
                <li>All traffic targets <code>/api/licenses/*</code> over HTTPS.</li>
                <li>Keep this console behind HTTP auth or an allow-listed VPN.</li>
                <li>Customers sign in at <a href="/user/">/user/</a> with their email + password.</li>
            </ul>
        </div>
        <div class="panel session-panel" id="authPanel">
            <div class="session-header">
                <div>
                    <p class="eyebrow">Admin Session</p>
                    <h2 id="sessionState">Not signed in</h2>
                    <p class="muted">Issue / activate / deactivate endpoints require a logged-in admin.</p>
                </div>
                <div class="session-actions hidden" id="sessionActions">
                    <button type="button" class="ghost" id="sessionRefresh">Refresh</button>
                    <button type="button" id="sessionLogout">Logout</button>
                </div>
            </div>

            <form id="adminLoginForm" class="stack" method="post">
                <label>Email
                    <input type="email" name="email" placeholder="you@glitchdata.com" autocomplete="email" required>
                </label>
                <label>Password
                    <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                </label>
                <button type="submit">Sign In</button>
            </form>

            <label>API Base URL
                <input type="url" id="baseUrl" value="/api/licenses" autocomplete="off">
                <small>E.g. <code>https://license.glitchdata.com/api/licenses</code></small>
            </label>
            <label>Preferred Product Code
                <input type="text" id="defaultProduct" placeholder="APP_PRO" autocomplete="off">
                <small>Pre-fills each form to speed up testing.</small>
            </label>
        </div>
    </header>

    <section class="grid" id="forms">
        <article class="card">
            <div class="card-head">
                <h3>Issue License</h3>
                <p>Generate a brand-new key or reuse an existing value.</p>
            </div>
            <form id="issueForm" class="stack">
                <label>Product Code
                    <input name="product_code" required>
                </label>
                <label>License Key (optional)
                    <input name="license_key" placeholder="Auto-generate when blank">
                </label>
                <label>Expires At
                    <input name="expires_at" placeholder="2025-12-31 or +1 year">
                </label>
                <label>Max Activations
                    <input name="max_activations" type="number" min="1" placeholder="Default to product allowance">
                </label>
                <label>Notes
                    <textarea name="notes" rows="2" placeholder="Internal note"></textarea>
                </label>
                <div class="button-row">
                    <button type="submit">Issue License</button>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="card-head">
                <h3>Validate</h3>
                <p>Check the health of any key, optionally tied to an instance.</p>
            </div>
            <form id="validateForm" class="stack">
                <label>Product Code
                    <input name="product_code" required>
                </label>
                <label>License Key
                    <input name="license_key" required>
                </label>
                <label>Instance ID (optional)
                    <input name="instance_id" placeholder="site-123">
                </label>
                <div class="button-row">
                    <button type="submit">Validate License</button>
                </div>
            </form>
        </article>

        <article class="card">
            <div class="card-head">
                <h3>Activate / Deactivate</h3>
                <p>Simulate a client claiming or freeing an activation slot.</p>
            </div>
            <form id="activateForm" class="stack">
                <label>Product Code
                    <input name="product_code" required>
                </label>
                <label>License Key
                    <input name="license_key" required>
                </label>
                <label>Instance ID
                    <input name="instance_id" required>
                </label>
                <label>Domain
                    <input name="domain" placeholder="client.com">
                </label>
                <label>User Agent
                    <input name="user_agent" placeholder="woocommerce/8.0">
                </label>
                <div class="button-row split">
                    <button type="button" data-action="activate">Activate</button>
                    <button type="button" data-action="deactivate" class="ghost">Deactivate</button>
                </div>
            </form>
        </article>
    </section>

    <section class="card log">
        <div class="card-head">
            <h3>Live Console</h3>
            <p>Every request and response captured for auditing.</p>
        </div>
        <div id="log"></div>
    </section>

    <section class="api-docs">
        <div class="card-head">
            <h3>Calling the API</h3>
            <p>Four POST endpoints, each returning JSON in the same envelope.</p>
        </div>
        <div class="doc-grid">
            <article class="doc-card" data-sample="issue">
                <header>
                    <span class="pill issue"></span>
                    <strong>POST /api/licenses/issue</strong>
                </header>
                <p>Admin-only endpoint for creating keys. Requires a logged-in admin session (cookie).</p>
                <ul>
                    <li>`product_code` (required)</li>
                    <li>`license_key`, `expires_at`, `max_activations`, `notes`, `status`</li>
                </ul>
                <pre><code># Login first: curl -c cookie.txt -X POST https://example.com/api/users/login \
#   -H "Content-Type: application/json" -d '{"email":"","password":""}'
curl -X POST "$BASE/issue" \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"product_code":"APP_PRO"}'</code></pre>
                <button type="button" class="copy">Copy Sample</button>
            </article>

            <article class="doc-card" data-sample="activate">
                <header>
                    <span class="pill activate"></span>
                    <strong>POST /api/licenses/activate</strong>
                </header>
                <p>Called by clients to reserve an activation slot (idempotent per instance).</p>
                <ul>
                    <li>`license_key`, `product_code`, `instance_id` (required)</li>
                    <li>`domain`, `user_agent` optional</li>
                </ul>
                <pre><code>curl -X POST "$BASE/activate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX","product_code":"APP_PRO","instance_id":"site-123"}'</code></pre>
                <button type="button" class="copy">Copy Sample</button>
            </article>

            <article class="doc-card" data-sample="validate">
                <header>
                    <span class="pill validate"></span>
                    <strong>POST /api/licenses/validate</strong>
                </header>
                <p>Heartbeat endpoint returning status, expiry, and activation counts.</p>
                <ul>
                    <li>`license_key`, `product_code` (required)</li>
                    <li>`instance_id` optional to refresh that activation's timestamp</li>
                </ul>
                <pre><code>curl -X POST "$BASE/validate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX","product_code":"APP_PRO"}'</code></pre>
                <button type="button" class="copy">Copy Sample</button>
            </article>

            <article class="doc-card" data-sample="deactivate">
                <header>
                    <span class="pill deactivate"></span>
                    <strong>POST /api/licenses/deactivate</strong>
                </header>
                <p>Removes an activation so another domain/device can take its place.</p>
                <ul>
                    <li>`license_key`, `product_code`, `instance_id` (required)</li>
                </ul>
                <pre><code>curl -X POST "$BASE/deactivate" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"XXXX","product_code":"APP_PRO","instance_id":"site-123"}'</code></pre>
                <button type="button" class="copy">Copy Sample</button>
            </article>
        </div>
    </section>
</main>

<template id="logEntry">
    <div class="entry">
        <div class="entry-head">
            <span class="pill"></span>
            <strong class="label"></strong>
            <span class="time"></span>
        </div>
        <div class="entry-body"></div>
    </div>
</template>

<script src="/assets/main.js" defer></script>
</body>
</html>

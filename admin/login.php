<?php
$pageTitle = 'Staff Sign In';
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FBF4EA">
    <title><?= htmlspecialchars($pageTitle) ?> | Sweet Spot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-visual">
            <a class="login-brand" href="<?= BASE_URL ?>/index.php">
                <img src="<?= BASE_URL ?>/assets/images/sweet-spot-logo.png" alt="Sweet Spot">
            </a>
            <div class="login-visual-copy">
                <span class="admin-kicker">THE SWEET SPOT WORKSPACE</span>
                <h1>A little care<br>goes into <em>everything.</em></h1>
                <p>Your behind-the-counter space to keep the good things organized.</p>
            </div>
            <div class="login-visual-bottom">
                <span>BAKED WITH CARE. MANAGED WITH EASE.</span>
                <span>© <?= date('Y') ?> SWEET SPOT</span>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-panel-top"><a href="<?= BASE_URL ?>/index.php">← Back to website</a><span>STAFF ACCESS</span></div>
            <div class="login-form-wrap">
                <div class="login-mark">✳</div>
                <p class="admin-kicker">WELCOME BACK</p>
                <h2>Sign in to<br>your workspace.</h2>
                <p class="login-subtitle">Enter your staff credentials to continue.</p>

                <form class="admin-login-form" id="demo-login-form">
                    <div class="admin-field">
                        <label for="username">Username or email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" required>
                    </div>
                    <div class="admin-field">
                        <div class="field-label-row"><label for="password">Password</label><a href="#" class="quiet-link" id="forgot-link">Forgot password?</a></div>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password">Show</button>
                        </div>
                    </div>
                    <button class="admin-primary-btn login-submit" type="submit">Sign in <span>↗</span></button>
                    <p class="login-feedback" id="login-feedback" role="status" aria-live="polite"></p>
                </form>
                <div class="preview-notice"><span>i</span><p><strong>Design preview</strong>This login screen is currently UI-only. Secure authentication will be connected in the backend phase.</p></div>
            </div>
            <div class="login-panel-footer"><span>NEED ACCESS?</span><span>Contact your system administrator.</span></div>
        </section>
    </main>
    <script>
    const passwordInput = document.querySelector('#password');
    const passwordToggle = document.querySelector('#password-toggle');
    passwordToggle.addEventListener('click', () => {
        const reveal = passwordInput.type === 'password';
        passwordInput.type = reveal ? 'text' : 'password';
        passwordToggle.textContent = reveal ? 'Hide' : 'Show';
        passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
    document.querySelector('#demo-login-form').addEventListener('submit', (event) => {
        event.preventDefault();
        document.querySelector('#login-feedback').textContent = 'Preview mode: authentication is not connected yet.';
    });
    document.querySelector('#forgot-link').addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelector('#login-feedback').textContent = 'Password recovery will be available after backend setup.';
    });
    </script>
</body>
</html>
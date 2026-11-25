<?php
$pageTitle = 'Staff Sign In';

require_once __DIR__ . '/../config/auth.php';

if (admin_is_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$loginError = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!admin_verify_csrf_token($csrfToken)) {
        $loginError = 'Your session expired. Please refresh the page and try again.';
    } elseif ($identifier === '' || $password === '') {
        $loginError = 'Please enter your username or email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, password
             FROM admins
             WHERE username = :username OR email = :email
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $identifier,
            'email' => $identifier,
        ]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['login_success'] = 'You have successfully signed in. Welcome back, ' . $admin['username'] . '!';

            if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE admins SET password = :password WHERE id = :id');
                $update->execute([
                    'password' => $newHash,
                    'id' => $admin['id'],
                ]);
            }

            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }

        $loginError = 'The username/email or password you entered is incorrect.';
    }
}

$csrfToken = admin_csrf_token();
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

                <form class="admin-login-form" id="admin-login-form" method="POST" action="<?= BASE_URL ?>/admin/login.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="admin-field">
                        <label for="username">Username or email</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" value="<?= htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="admin-field">
                        <div class="field-label-row"><label for="password">Password</label><span class="quiet-link">Staff account access</span></div>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="password-toggle" aria-label="Show password">Show</button>
                        </div>
                    </div>
                    <button class="admin-primary-btn login-submit" type="submit">Sign in <span>↗</span></button>
                </form>
                <div class="preview-notice"><span>i</span><p><strong>Authorized staff only</strong>Your sign-in is checked securely against the Sweet Spot admin database.</p></div>
            </div>
            <div class="login-panel-footer"><span>NEED ACCESS?</span><span>Contact your system administrator.</span></div>
        </section>
    </main>
    <?php if ($loginError !== ''): ?>
    <div class="auth-toast auth-toast-error" id="auth-toast" role="alert" aria-live="assertive">
        <span class="auth-toast-icon">!</span>
        <span><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" class="auth-toast-close" aria-label="Dismiss notification">×</button>
    </div>
    <?php endif; ?>
    <script>
    const authToast = document.querySelector('#auth-toast');
    if (authToast) {
        requestAnimationFrame(() => authToast.classList.add('is-visible'));
        authToast.querySelector('.auth-toast-close').addEventListener('click', () => authToast.classList.remove('is-visible'));
        window.setTimeout(() => authToast.classList.remove('is-visible'), 4500);
    }

    const passwordInput = document.querySelector('#password');
    const passwordToggle = document.querySelector('#password-toggle');
    passwordToggle.addEventListener('click', () => {
        const reveal = passwordInput.type === 'password';
        passwordInput.type = reveal ? 'text' : 'password';
        passwordToggle.textContent = reveal ? 'Hide' : 'Show';
        passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
    });
    </script>
</body>
</html>

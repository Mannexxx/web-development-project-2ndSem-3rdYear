<?php
/** @var ?string $error */
/** @var string $email */
/** @var string $next */
/** @var string $mode */
/** @var array $towns */
/** @var array $errors */
/** @var array $old */
?>
<section class="auth-page wrap" data-auth-login-title="<?= e('Sign in · ' . APP_NAME) ?>" data-auth-register-title="<?= e('Join the island community · ' . APP_NAME) ?>">
    <div class="auth-shell">
        <aside class="auth-intro">
            <p class="eyebrow">BANTAYAN ISLAND MARKETPLACE</p>
            <h1 data-auth-copy data-login="Welcome back." data-register="Join our community."><?= $mode === 'register' ? 'Join our community.' : 'Welcome back.' ?></h1>
            <p data-auth-copy data-login="Sign in to stay connected with local sellers, manage your listings and continue conversations." data-register="Create an account to connect with local buyers and sellers across Bantayan Island."><?= $mode === 'register' ? 'Create an account to connect with local buyers and sellers across Bantayan Island.' : 'Sign in to stay connected with local sellers, manage your listings and continue conversations.' ?></p>
            <ul class="auth-benefits">
                <li><strong>Message local sellers</strong><span>Ask questions and arrange safe meetups.</span></li>
                <li><strong>Manage your listings</strong><span>Post, edit and mark items as sold.</span></li>
                <li><strong>Build community trust</strong><span>Leave verified transaction ratings.</span></li>
            </ul>
        </aside>

        <div class="auth-card">
            <div class="auth-card-head">
                <p class="eyebrow" data-auth-copy data-login="MEMBER ACCESS" data-register="CREATE AN ACCOUNT"><?= $mode === 'register' ? 'CREATE AN ACCOUNT' : 'MEMBER ACCESS' ?></p>
                <h2 class="h2" data-auth-copy data-login="Sign in" data-register="Join Bantayan Marketplace"><?= $mode === 'register' ? 'Join Bantayan Marketplace' : 'Sign in' ?></h2>
                <p class="muted" data-auth-copy data-login="Enter your account details to continue." data-register="Join local buyers and sellers across Bantayan Island."><?= $mode === 'register' ? 'Join local buyers and sellers across Bantayan Island.' : 'Enter your account details to continue.' ?></p>
            </div>
            <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <?php if ($errors): ?>
                <div class="flash flash-error" role="alert"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <nav class="auth-mode-switch" aria-label="Account options">
                <a href="<?= e(url('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>" data-auth-mode="login"<?= $mode === 'login' ? ' aria-current="page"' : '' ?>>Sign in</a>
                <a href="<?= e(url('login.php?mode=register' . ($next !== '' ? '&next=' . rawurlencode($next) : ''))) ?>" data-auth-mode="register"<?= $mode === 'register' ? ' aria-current="page"' : '' ?>>Create account</a>
            </nav>
            <a class="google-auth-button" href="<?= e(url('google.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>">
                <svg aria-hidden="true" viewBox="0 0 18 18" width="18" height="18"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.26-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.58 2.68-3.9 2.68-6.62Z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.83.86-3.04.86-2.35 0-4.34-1.59-5.05-3.72H.96v2.34A9 9 0 0 0 9 18Z"/><path fill="#FBBC05" d="M3.95 10.7A5.41 5.41 0 0 1 3.67 9c0-.59.1-1.16.28-1.7V4.96H.96A9 9 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.99-2.34Z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A8.65 8.65 0 0 0 9 0 9 9 0 0 0 .96 4.96L3.95 7.3C4.66 5.17 6.65 3.58 9 3.58Z"/></svg>
                Continue with Google
            </a>
            <div class="auth-divider"><span>or</span></div>
            <form method="post" action="<?= e(url('login.php')) ?>" class="form auth-form" data-auth-panel="login"<?= $mode === 'register' ? ' hidden' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password_login">
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="field">
                    <label for="email">Username or Email</label>
                    <input id="email" name="email" autocomplete="username" value="<?= e($email) ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                </div>
                <button class="btn btn-primary btn-block auth-submit" type="submit">Sign in</button>
            </form>
            <form method="post" action="<?= e(url('register.php')) ?>" class="form auth-form" data-auth-panel="register" novalidate<?= $mode === 'login' ? ' hidden' : '' ?>>
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <div class="field-row">
                    <div class="field">
                        <label for="register-firstname">First name</label>
                        <input id="register-firstname" name="firstname" placeholder="First name" autocomplete="given-name" value="<?= old($old, 'firstname') ?>" required>
                    </div>
                    <div class="field">
                        <label for="register-lastname">Last name</label>
                        <input id="register-lastname" name="lastname" placeholder="Last name" autocomplete="family-name" value="<?= old($old, 'lastname') ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label for="register-email">Email address</label>
                    <input id="register-email" name="email" type="email" placeholder="Email" autocomplete="email" value="<?= old($old, 'email') ?>" required>
                </div>
                <div class="field">
                    <label for="register-username">Username</label>
                    <input id="register-username" name="username" placeholder="Username" autocomplete="username" value="<?= old($old, 'username') ?>" pattern="[A-Za-z0-9._]{3,40}" required>
                    <p class="hint">3-40 characters: letters, numbers, dots or underscores.</p>
                </div>
                <div class="field">
                    <label for="register-password">Password</label>
                    <div class="password-field">
                        <input id="register-password" name="password" type="password" placeholder="Create a secure password" autocomplete="new-password" required>
                        <button type="button" class="password-toggle" data-password-toggle="register-password" aria-label="Show password">Show</button>
                    </div>
                    <p class="hint">Use at least 8 characters, including a number and a symbol.</p>
                </div>
                <div class="field">
                    <label for="register-town">Town on Bantayan Island</label>
                    <select id="register-town" name="town_id" required>
                        <option value="">Select Bantayan / Santa Fe / Madridejos</option>
                        <?php foreach ($towns as $town): ?>
                            <option value="<?= (int) $town['id'] ?>"<?= (int) ($old['town_id'] ?? 0) === (int) $town['id'] ? ' selected' : '' ?>><?= e($town['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="check">
                    <input type="checkbox" name="agree" value="1"<?= !empty($old['agree']) ? ' checked' : '' ?> required>
                    <span>I agree to the <a class="link" href="<?= e(url('privacy.php#terms')) ?>">Terms</a>, <a class="link" href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>, and <a class="link" href="<?= e(url('guidelines.php')) ?>">Community Guidelines</a>.</span>
                </label>
                <button class="btn btn-primary btn-block auth-submit" type="submit">Create account &amp; continue</button>
            </form>
            <a class="auth-guest register-guest" href="<?= e(url('index.php')) ?>">Continue as a guest <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>

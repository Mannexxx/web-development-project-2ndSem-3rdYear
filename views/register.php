<?php
/** @var string $step 'create' | 'verify' */
/** @var array $towns */
/** @var array $errors */
/** @var array $old */
/** @var string $next */
/** @var ?array $me */
/** @var string $verificationChannel */
/** @var string $verificationDestination */
/** @var ?string $verificationError */
/** @var bool $verificationSent */
/** @var string $verificationCarrier */
?>
<section class="auth-page auth-register-page wrap">
    <div class="register-modern-shell">
        <div class="auth-card register-card register-modern-card">
            <div class="register-heading">
                <p class="eyebrow">JOIN THE ISLAND COMMUNITY</p>
                <h1 class="h1">Create your account</h1>
                <p class="muted">Join local buyers and sellers across Bantayan Island.</p>
            </div>
            <a class="google-auth-button" href="<?= e(url('google.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>">
                <svg aria-hidden="true" viewBox="0 0 18 18" width="18" height="18"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.26-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92c1.71-1.58 2.68-3.9 2.68-6.62Z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.8.54-1.83.86-3.04.86-2.35 0-4.34-1.59-5.05-3.72H.96v2.34A9 9 0 0 0 9 18Z"/><path fill="#FBBC05" d="M3.95 10.7A5.41 5.41 0 0 1 3.67 9c0-.59.1-1.16.28-1.7V4.96H.96A9 9 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.99-2.34Z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.5.45 3.44 1.35l2.58-2.58A8.65 8.65 0 0 0 9 0 9 9 0 0 0 .96 4.96L3.95 7.3C4.66 5.17 6.65 3.58 9 3.58Z"/></svg>
                Continue with Google
            </a>
            <div class="auth-divider"><span>or</span></div>
            <?php if ($step !== 'create' && $me): ?>
                <div class="done-box">
                    <p><strong>Account created.</strong></p>
                    <p class="muted"><?= e(full_name($me)) ?> · <?= e($me['town_name']) ?></p>
                </div>
                <?php if ($step === 'verify_contact'): ?>
                    <div class="contact-verification">
                        <p class="eyebrow"><?= $verificationChannel === 'email' ? 'EMAIL VERIFICATION' : 'SMS VERIFICATION' ?></p>
                        <?php if ($verificationChannel === 'email'): ?>
                            <p>We sent a 6-digit code to <strong><?= e($verificationDestination) ?></strong>.</p>
                        <?php else: ?>
                            <p>We sent a verification code to <strong><?= e($verificationDestination) ?></strong> through your selected carrier’s email-to-SMS gateway. Delivery depends on carrier gateway availability.</p>
                        <?php endif; ?>
                        <?php if ($verificationError): ?><div class="flash flash-error" role="alert"><?= e($verificationError) ?></div><?php endif; ?>
                        <form method="post" action="<?= e(url('register.php?step=verify_contact' . ($next !== '' ? '&next=' . rawurlencode($next) : ''))) ?>" class="form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="next" value="<?= e($next) ?>">
                            <input type="hidden" name="action" value="verify_code">
                            <div class="field">
                                <label for="verification-code">Verification code</label>
                                <input id="verification-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="6-digit code" required>
                            </div>
                            <button class="btn btn-primary" type="submit">Verify <?= $verificationChannel === 'email' ? 'email' : 'phone' ?></button>
                        </form>
                        <form method="post" action="<?= e(url('register.php?step=verify_contact' . ($next !== '' ? '&next=' . rawurlencode($next) : ''))) ?>" class="top-gap-sm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="next" value="<?= e($next) ?>">
                            <input type="hidden" name="action" value="send_code">
                            <button class="btn" type="submit">Resend code</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($errors): ?>
                    <div class="flash flash-error" role="alert"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('register.php')) ?>" class="form" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="next" value="<?= e($next) ?>">
                    <div class="field-row">
                        <div class="field">
                            <label for="firstname">First name</label>
                            <input id="firstname" name="firstname" placeholder="First name" autocomplete="given-name" value="<?= old($old, 'firstname') ?>" required>
                        </div>
                        <div class="field">
                            <label for="lastname">Last name</label>
                            <input id="lastname" name="lastname" placeholder="Last name" autocomplete="family-name" value="<?= old($old, 'lastname') ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" placeholder="Email" autocomplete="email" value="<?= old($old, 'email') ?>" required>
                    </div>
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" name="username" placeholder="Username" autocomplete="username" value="<?= old($old, 'username') ?>" pattern="[A-Za-z0-9._]{3,40}" required>
                        <p class="hint">3-40 characters: letters, numbers, dots or underscores.</p>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input id="password" name="password" type="password" placeholder="Create a secure password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">Show</button>
                        </div>
                        <p class="hint">Use at least 8 characters, including a number and a symbol.</p>
                    </div>
                    <div class="field">
                        <label for="town_id">Town on Bantayan Island</label>
                        <select id="town_id" name="town_id" required>
                            <option value="">Select Bantayan / Santa Fe / Madridejos</option>
                            <?php foreach ($towns as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"<?= (int) ($old['town_id'] ?? 0) === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="check">
                        <input type="checkbox" name="agree" value="1"<?= !empty($old['agree']) ? ' checked' : '' ?>>
                        <span>I agree to the <a class="link" href="<?= e(url('privacy.php#terms')) ?>">Terms</a>, <a class="link" href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>, and <a class="link" href="<?= e(url('guidelines.php')) ?>">Community Guidelines</a>.</span>
                    </label>
                    <button class="btn btn-primary" type="submit" data-confirm-click="Create your Bantayan Marketplace account with these details?">Create account &amp; continue</button>
                </form>
                <p class="small top-gap register-signin">Already a member? <a class="link" href="<?= e(url('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>">Sign in</a></p>
                <p class="register-guest"><a class="btn" href="<?= e(url('index.php')) ?>">Continue browsing as a guest</a></p>
            <?php endif; ?>
        </div>

    </div>
</section>

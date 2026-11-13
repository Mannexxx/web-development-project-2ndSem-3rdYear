<?php
/** @var string $step 'create' | 'verify' */
/** @var array $towns */
/** @var array $errors */
/** @var array $old */
/** @var string $next */
/** @var ?array $me */
?>
<section class="wrap page-head">
    <p class="eyebrow">JOIN THE ISLAND COMMUNITY</p>
    <h1 class="h1">A local marketplace built on trust.</h1>
    <p class="lead">Browse freely. Create a verified profile to message, buy, sell, and leave ratings.</p>
</section>

<section class="wrap pad-bottom">
    <div class="two-col register-grid">
        <div class="panel">
            <h2 class="h2"><span class="num">01</span> Create your profile</h2>
            <?php if ($step === 'verify' && $me): ?>
                <div class="done-box">
                    <p><strong>Account created.</strong></p>
                    <p class="muted"><?= e(full_name($me)) ?> · <?= e($me['email']) ?> · <?= e($me['town_name']) ?></p>
                </div>
                <p class="small muted">Finish the face verification on this page to message sellers, sell and leave ratings.</p>
                <p><a class="btn" href="<?= e(url('index.php')) ?>">Skip for now and keep browsing</a></p>
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
                            <input id="firstname" name="firstname" placeholder="Your first name" autocomplete="given-name" value="<?= old($old, 'firstname') ?>" required>
                        </div>
                        <div class="field">
                            <label for="lastname">Last name</label>
                            <input id="lastname" name="lastname" placeholder="Your last name" autocomplete="family-name" value="<?= old($old, 'lastname') ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" name="email" type="email" placeholder="you@example.com" autocomplete="email" value="<?= old($old, 'email') ?>" required>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Create a secure password" autocomplete="new-password" required>
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
                <p class="small top-gap">Already a member? <a class="link" href="<?= e(url('login.php' . ($next !== '' ? '?next=' . rawurlencode($next) : ''))) ?>">Sign in</a></p>
                <p><a class="btn" href="<?= e(url('index.php')) ?>">Continue browsing as a guest</a></p>
            <?php endif; ?>
        </div>

        <div class="panel panel-soft face-panel<?= $step === 'verify' ? '' : ' is-disabled' ?>" id="face-panel"
             data-endpoint="<?= e(url('api/face-verify.php')) ?>"
             data-next="<?= e(url($next !== '' ? $next : 'index.php')) ?>"
             data-enabled="<?= $step === 'verify' ? '1' : '0' ?>">
            <h2 class="h2"><span class="num">02</span> Verify it’s really you</h2>
            <p class="eyebrow">NEXT STEP AFTER ACCOUNT CREATION</p>
            <p class="muted">Face recognition helps authenticate your profile and reduce impersonation in the community.</p>

            <div class="face-stage">
                <div class="face-circle" id="face-circle">
                    <video id="face-video" playsinline muted></video>
                    <span id="face-label">FACE<br>CAPTURE</span>
                </div>
                <p class="face-caption" id="face-status" aria-live="polite">Position your face inside the guide</p>
            </div>

            <ol class="steps">
                <li>Allow camera access.</li>
                <li>Face the camera in good lighting.</li>
                <li>Follow the on-screen liveness instructions.</li>
            </ol>

            <label class="check">
                <input type="checkbox" id="face-consent"<?= $step === 'verify' ? '' : ' disabled' ?>>
                <span>I consent to face processing for profile authentication. <a class="link" href="<?= e(url('privacy.php#verification')) ?>">Learn how my data is handled.</a></span>
            </label>
            <div class="btn-row">
                <button type="button" class="btn btn-primary" id="face-start"<?= $step === 'verify' ? '' : ' disabled' ?>>Start face verification</button>
                <a class="btn" href="<?= e(url('support.php?topic=face_verification')) ?>">Need help?</a>
            </div>
            <p class="small muted">Camera unavailable or verification unsuccessful? Retry or contact admin support. You can still browse as a guest.</p>
        </div>
    </div>
</section>

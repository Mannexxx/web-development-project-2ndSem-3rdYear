<?php
/** @var ?array $application */
/** @var array $form */
/** @var array $errors */
/** @var array $idTypes */
/** @var bool $mayResubmit */
/** @var bool $diditConfigured */
?>
<section class="wrap page-head">
    <p class="eyebrow">SELLER ONBOARDING</p>
    <h1 class="h1">Apply to become a seller.</h1>
    <p class="lead">Complete identity verification and submit your details for admin review before creating listings.</p>
</section>
<section class="wrap pad-bottom page-narrow-left">
    <?php if ($errors): ?>
        <div class="flash flash-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if ($application !== null): ?>
        <div class="panel panel-soft top-gap">
            <h2 class="h2">Application status</h2>
            <p><strong><?= e(ucwords(str_replace('_', ' ', $application['status']))) ?></strong>
                · Identity check: <?= e($application['didit_status']) ?></p>
            <?php if ($application['review_note']): ?><p><?= nl2br(e($application['review_note'])) ?></p><?php endif; ?>
            <?php if ($application['status'] === 'approved' && $application['didit_status'] === 'Approved'): ?>
                <p>Your seller application is approved. You can now <a class="link" href="<?= e(url('sell.php')) ?>">create a listing</a>.</p>
            <?php elseif ($application['status'] === 'pending'): ?>
                <?php if ($application['didit_status'] === 'Approved'): ?>
                    <p>Didit verified your identity. Your seller application is waiting for admin approval.</p>
                <?php else: ?>
                    <p>Seller access stays locked until Didit verifies your identity and an admin approves your application.</p>
                <?php endif; ?>
                <?php if ($application['didit_status'] !== 'Approved'): ?>
                    <?php if ($diditConfigured): ?>
                        <form method="post" action="<?= e(url('seller-application.php')) ?>" class="top-gap-sm">
                            <?= csrf_field() ?><input type="hidden" name="action" value="start_verification">
                            <button class="btn btn-primary" type="submit">Continue face and ID verification</button>
                        </form>
                    <?php else: ?>
                        <p class="small muted">Identity verification is not configured yet. Contact the marketplace admin.</p>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($mayResubmit && !$diditConfigured): ?>
        <div class="flash flash-info top-gap">Seller applications are temporarily unavailable because Didit is not configured. Your ID documents will not be collected until verification is ready.</div>
    <?php endif; ?>

    <?php if ($mayResubmit && $diditConfigured): ?>
        <form class="panel form top-gap" method="post" enctype="multipart/form-data" action="<?= e(url('seller-application.php')) ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="submit_application">
            <h2 class="h2">Applicant details</h2>
            <div class="field-row">
                <div class="field"><label for="first_name">First name</label><input id="first_name" name="first_name" maxlength="80" required value="<?= e($form['first_name']) ?>"></div>
                <div class="field"><label for="middle_name">Middle name <span class="muted">(optional)</span></label><input id="middle_name" name="middle_name" maxlength="80" value="<?= e($form['middle_name']) ?>"></div>
                <div class="field"><label for="last_name">Last name</label><input id="last_name" name="last_name" maxlength="80" required value="<?= e($form['last_name']) ?>"></div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="sex">Sex</label>
                    <select id="sex" name="sex" required>
                        <option value="">Select</option>
                        <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $form['sex'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" name="date_of_birth" type="date" required value="<?= e($form['date_of_birth']) ?>"><span class="small muted">Applicants must be older than 18.</span></div>
            </div>

            <h3 class="h3 top-gap">Residential address</h3>
            <div class="field-row">
                <div class="field"><label for="house_number">House number</label><input id="house_number" name="house_number" maxlength="80" required value="<?= e($form['house_number']) ?>"></div>
                <div class="field"><label for="street_village">Street / Village</label><input id="street_village" name="street_village" maxlength="120" required value="<?= e($form['street_village']) ?>"></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="barangay">Barangay</label><input id="barangay" name="barangay" maxlength="120" required value="<?= e($form['barangay']) ?>"></div>
                <div class="field"><label for="municipality">Municipality</label><input id="municipality" name="municipality" maxlength="120" required value="<?= e($form['municipality']) ?>"></div>
            </div>

            <h3 class="h3 top-gap">Valid government ID</h3>
            <div class="field">
                <label for="id_type">ID type</label>
                <select id="id_type" name="id_type" required>
                    <option value="">Select an ID type</option>
                    <?php foreach ($idTypes as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= $form['id_type'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-row">
                <div class="field"><label for="id_front">ID front image</label><input id="id_front" name="id_front" type="file" accept="image/jpeg,image/png" required><span class="small muted">JPG or PNG, up to 5 MB.</span></div>
                <div class="field"><label for="id_back">ID back image</label><input id="id_back" name="id_back" type="file" accept="image/jpeg,image/png" required><span class="small muted">JPG or PNG, up to 5 MB.</span></div>
            </div>

            <h3 class="h3 top-gap">Agreements and verification consent</h3>
            <div class="checks">
                <label class="check"><input type="checkbox" name="terms" value="1" required><span>I have read and agree to the <a class="link" href="<?= e(url('privacy.php#terms')) ?>" target="_blank" rel="noopener">Terms of Use</a>.</span></label>
                <label class="check"><input type="checkbox" name="privacy" value="1" required><span>I have read the <a class="link" href="<?= e(url('privacy.php#seller-data')) ?>" target="_blank" rel="noopener">Privacy Policy and seller data notice</a>.</span></label>
                <label class="check"><input type="checkbox" name="guidelines" value="1" required><span>I have read and agree to the <a class="link" href="<?= e(url('guidelines.php')) ?>" target="_blank" rel="noopener">Community Guidelines</a>.</span></label>
                <label class="check"><input type="checkbox" name="biometric_consent" value="1" required><span>I explicitly consent to Didit processing my face image and biometric data for liveness and face-match identity verification. I understand the provider receives my identity details for this check.</span></label>
            </div>
            <p class="small muted">Your uploaded ID images are private and available only to admins reviewing your application. They are deleted 30 days after the decision. Didit-hosted verification is a separate check; its data handling is described in the linked privacy notice.</p>
            <button class="btn btn-primary" type="submit" data-confirm-click="Submit your seller application and start identity verification?">Submit application</button>
        </form>
    <?php endif; ?>
</section>

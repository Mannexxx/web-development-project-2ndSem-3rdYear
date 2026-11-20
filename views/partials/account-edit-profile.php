<?php
/** @var ?array $me */
/** @var array $towns */
/** @var array $profileErrors */
?>
<section class="wrap pad-bottom account-panel" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab" tabindex="0" hidden>
    <div class="panel">
        <?php if ($profileErrors): ?>
            <div class="flash flash-error" role="alert"><ul><?php foreach ($profileErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('account.php?section=edit-profile')) ?>" enctype="multipart/form-data" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="field seller-image-editor">
                <label for="seller_background">Seller profile background</label>
                <div class="seller-background-stage">
                    <?php if (!empty($me['seller_background'])): ?>
                        <div class="seller-background-preview" style="background-image: url('<?= e(url((string) $me['seller_background'])) ?>')"></div>
                    <?php else: ?>
                        <div class="seller-background-preview seller-background-preview-empty">No background image uploaded.</div>
                    <?php endif; ?>
                    <div class="profile-picture-field profile-picture-field-overlay">
                        <div class="profile-avatar profile-avatar-edit">
                            <?php if (!empty($me['profile_pic'])): ?>
                                <img src="<?= e(url((string) $me['profile_pic'])) ?>" alt="<?= e(full_name($me)) ?>">
                            <?php else: ?>
                                <span><?= e(mb_strtoupper(mb_substr((string) $me['firstname'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="seller-image-actions">
                    <div class="seller-image-action">
                        <label class="btn" for="profile_picture">
                            <span data-file-label>Change profile picture</span>
                            <input id="profile_picture" class="sr-only" name="profile_picture" type="file" accept="image/jpeg,image/png" aria-label="Change profile picture" data-file-input>
                        </label>
                        <p class="hint">JPG or PNG, up to 5 MB.</p>
                    </div>
                    <div class="seller-image-action">
                        <label class="btn" for="seller_background">
                            <span data-file-label>Change background image</span>
                            <input id="seller_background" class="sr-only" name="seller_background" type="file" accept="image/jpeg,image/png" aria-label="Change background image" data-file-input>
                        </label>
                        <p class="hint">JPG or PNG, up to 5 MB.</p>
                    </div>
                </div>
                <p class="hint">The background image appears behind your public seller profile.</p>
                <?php if (!empty($me['seller_background'])): ?>
                    <label class="check">
                        <input type="checkbox" name="remove_seller_background" value="1">
                        <span>Remove current background image</span>
                    </label>
                <?php endif; ?>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="firstname">First name</label>
                    <input id="firstname" name="firstname" value="<?= e((string) $me['firstname']) ?>" autocomplete="given-name" required>
                </div>
                <div class="field">
                    <label for="lastname">Last name</label>
                    <input id="lastname" name="lastname" value="<?= e((string) $me['lastname']) ?>" autocomplete="family-name" required>
                </div>
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" value="<?= e((string) ($me['username'] ?? '')) ?>" autocomplete="username" pattern="[A-Za-z0-9._]{3,40}" required>
                <p class="hint">Use this username or your email address to sign in.</p>
            </div>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= e((string) $me['email']) ?>" autocomplete="email" required>
                <p class="hint">Changing your email requires verification after saving.</p>
            </div>
            <div class="field">
                <label for="town_id">Town on Bantayan Island</label>
                <select id="town_id" name="town_id" required>
                    <?php foreach ($towns as $town): ?>
                        <option value="<?= (int) $town['id'] ?>"<?= (int) $me['town_id'] === (int) $town['id'] ? ' selected' : '' ?>><?= e(town_label($town['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
    </div>
</section>

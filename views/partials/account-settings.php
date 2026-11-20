<section class="wrap pad-bottom account-panel" id="settings" role="tabpanel" aria-labelledby="settings-tab" tabindex="0" hidden>
    <div class="panel">
        <h2 class="h2">Delete account</h2>
        <p class="small muted top-gap-sm">Deleting your account deactivates it and anonymizes your personal details. Your listings will be removed and their photos deleted. Completed deals, messages, reviews, reports, and support history will remain for marketplace records. You will be signed out, and this action cannot be undone.</p>
        <form method="post" action="<?= e(url('account.php?section=settings')) ?>" class="form" data-confirm="Delete your account? It will be deactivated, your personal details anonymized, your listings removed, and you will be signed out. This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="deactivate_account">
            <label class="check">
                <input type="checkbox" name="confirm_deactivation" value="1" required>
                <span>I understand that my account will be deactivated and cannot be restored.</span>
            </label>
            <button class="btn btn-danger" type="submit">Delete account</button>
        </form>
    </div>
</section>

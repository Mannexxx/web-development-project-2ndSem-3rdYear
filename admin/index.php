<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

use App\Database;
use App\Repository\AuditRepository;
use App\Repository\GuidelineRepository;
use App\Repository\ListingRepository;
use App\Repository\NotificationRepository;
use App\Repository\ReportRepository;
use App\Repository\SupportRepository;
use App\Repository\UserRepository;

$admin = require_admin();
$aid = (int) $admin['id'];

$listings = repo(ListingRepository::class);
$reports = repo(ReportRepository::class);
$support = repo(SupportRepository::class);
$guidelines = repo(GuidelineRepository::class);
$notes = repo(NotificationRepository::class);
$audit = repo(AuditRepository::class);
$users = repo(UserRepository::class);

$tabs = ['moderation', 'reports', 'support', 'guidelines'];
$tabRaw = (string) ($_GET['tab'] ?? $_POST['tab'] ?? '');
$tab = in_array($tabRaw, $tabs, true) ? $tabRaw : 'moderation';

function admin_url(string $tab, array $extra = []): string
{
    return 'admin/index.php?' . http_build_query(['tab' => $tab] + $extra);
}

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    /* ---- product moderation ------------------------------------------------ */
    if ($action === 'moderate') {
        $listing = $listings->find((int) ($_POST['listing_id'] ?? 0));
        $decision = (string) ($_POST['decision'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));
        $checks = [
            'original_photos' => !empty($_POST['original_photos']),
            'accurate_info' => !empty($_POST['accurate_info']),
            'permitted_item' => !empty($_POST['permitted_item']),
        ];
        $back = admin_url('moderation', ['review' => (int) ($_POST['listing_id'] ?? 0)]);

        if ($listing === null || $listing['status'] !== 'pending') {
            flash('error', 'That listing is not waiting for review any more.');
            redirect(admin_url('moderation'));
        }
        if (!in_array($decision, ['approve', 'changes', 'reject'], true)) {
            flash('error', 'Unknown decision.');
            redirect($back);
        }
        if ($decision === 'approve' && in_array(false, $checks, true)) {
            flash('error', 'Tick all three checks (original photos, accurate information, permitted item) before approving.');
            redirect($back);
        }
        if ($decision !== 'approve' && mb_strlen($note) < 3) {
            flash('error', 'Add a short reason so the seller knows what to fix.');
            redirect($back);
        }
        if (mb_strlen($note) > 1000) {
            flash('error', 'Please keep the note under 1000 characters.');
            redirect($back);
        }

        $sellerId = (int) $listing['seller_id'];
        $link = 'listing.php?id=' . (int) $listing['id'];
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            if ($decision === 'approve') {
                $listings->approve((int) $listing['id']);
                $db = 'approved';
            } elseif ($decision === 'changes') {
                $listings->setStatus((int) $listing['id'], 'changes_requested');
                $db = 'changes_requested';
            } else {
                $listings->setStatus((int) $listing['id'], 'rejected');
                $db = 'rejected';
            }
            $listings->addModeration((int) $listing['id'], $aid, $db, $checks, $note);
            $audit->record($aid, 'listing_' . $db, 'listing', (int) $listing['id'], $note);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        match ($db) {
            'approved' => $notes->create($sellerId, 'listing', 'Your listing is live', '"' . $listing['title'] . '" was approved.', $link),
            'changes_requested' => $notes->create($sellerId, 'listing', 'Changes requested', 'For "' . $listing['title'] . '": ' . $note, 'sell.php?id=' . (int) $listing['id']),
            default => $notes->create($sellerId, 'listing', 'Listing rejected', 'For "' . $listing['title'] . '": ' . $note, 'account.php'),
        };
        flash('success', 'Decision saved: ' . ($db === 'approved' ? 'listing approved' : ($db === 'rejected' ? 'listing rejected' : 'changes requested')) . '.');
        redirect(admin_url('moderation'));
    }

    /* ---- reports ------------------------------------------------------------- */
    if ($action === 'report_remove' || $action === 'report_dismiss') {
        $report = $reports->find((int) ($_POST['report_id'] ?? 0));
        $back = admin_url('reports');
        if ($report === null || $report['status'] !== 'open') {
            flash('error', 'That report was already handled.');
            redirect($back);
        }
        $note = trim((string) ($_POST['note'] ?? ''));
        if ($action === 'report_dismiss') {
            $reports->close((int) $report['id'], $aid, 'dismissed', null, $note);
            $audit->record($aid, 'report_dismissed', 'report', (int) $report['id'], $note);
            $notes->create((int) $report['reporter_id'], 'report', 'Report reviewed', 'Thanks for your report about "' . $report['listing_title'] . '". The admin team found no guideline violation.', 'listing.php?id=' . (int) $report['listing_id']);
            flash('success', 'Report dismissed. The reporter was notified.');
            redirect($back);
        }

        $guideline = $guidelines->find((int) ($_POST['guideline_id'] ?? 0));
        if ($guideline === null || !(int) $guideline['is_active']) {
            flash('error', 'Choose the community guideline that was broken before removing the listing.');
            redirect(admin_url('reports', ['report' => (int) $report['id']]));
        }
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            if ($report['listing_status'] !== 'removed') {
                $listings->setStatus((int) $report['listing_id'], 'removed');
                $users->addViolation((int) $report['seller_id']);
            }
            $reports->close((int) $report['id'], $aid, 'resolved', (int) $guideline['id'], $note);
            $others = $reports->closeOtherOpenForListing((int) $report['listing_id'], (int) $report['id'], $aid, (int) $guideline['id'], $note);
            $audit->record($aid, 'listing_removed', 'listing', (int) $report['listing_id'], 'Report #' . $report['id'] . ' · ' . $guideline['title']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $notes->create((int) $report['seller_id'], 'report', 'Your listing was removed', '"' . $report['listing_title'] . '" broke the guideline: ' . $guideline['title'] . '. Repeated violations may lead to account restrictions.', 'guidelines.php');
        foreach (array_unique(array_merge([(int) $report['reporter_id']], $others)) as $rid) {
            $notes->create($rid, 'report', 'Report resolved', 'The listing "' . $report['listing_title'] . '" was removed. Thank you for helping keep the marketplace safe.');
        }
        flash('success', 'Listing removed. The seller and reporter were notified.');
        redirect($back);
    }

    /* ---- customer service ----------------------------------------------------- */
    if (in_array($action, ['ticket_reply', 'ticket_close', 'ticket_verify'], true)) {
        $ticket = $support->find((int) ($_POST['ticket_id'] ?? 0));
        $back = admin_url('support', ['ticket' => (int) ($_POST['ticket_id'] ?? 0)]);
        if ($ticket === null) {
            flash('error', 'That request does not exist.');
            redirect(admin_url('support'));
        }
        if ($action === 'ticket_reply') {
            $body = trim((string) ($_POST['body'] ?? ''));
            if (mb_strlen($body) < 2 || mb_strlen($body) > 2000) {
                flash('error', 'Write a reply (up to 2000 characters).');
                redirect($back);
            }
            $support->addMessage((int) $ticket['id'], $aid, $body);
            $support->setStatus((int) $ticket['id'], 'answered');
            $audit->record($aid, 'ticket_reply', 'ticket', (int) $ticket['id']);
            $notes->create((int) $ticket['user_id'], 'support', 'Admin replied to #S-' . $ticket['id'], excerpt($body, 120), 'support.php?t=' . (int) $ticket['id']);
            flash('success', 'Response sent.');
            redirect(admin_url('support'));
        }
        if ($action === 'ticket_close') {
            $support->setStatus((int) $ticket['id'], 'closed');
            $audit->record($aid, 'ticket_closed', 'ticket', (int) $ticket['id']);
            flash('success', 'Request closed.');
            redirect(admin_url('support'));
        }
        // ticket_verify: manual face verification for people whose camera step failed
        if (!empty($ticket['user_verified_at'])) {
            flash('info', 'This member is already face verified.');
            redirect($back);
        }
        $users->markFaceVerified((int) $ticket['user_id']);
        $audit->record($aid, 'face_verified_manually', 'user', (int) $ticket['user_id'], 'From request #S-' . $ticket['id']);
        $notes->create((int) $ticket['user_id'], 'verification', 'Profile verified', 'The admin team verified your profile. You can now message sellers and sell products.', 'sell.php');
        flash('success', 'Member marked as face verified.');
        redirect($back);
    }

    /* ---- guidelines -------------------------------------------------------------- */
    if ($action === 'guideline_save') {
        $gid = (int) ($_POST['id'] ?? 0) ?: null;
        $code = strtolower(trim((string) ($_POST['code'] ?? '')));
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $audience = ($_POST['audience'] ?? '') === 'sellers' ? 'sellers' : 'everyone';
        $sort = max(0, min(999, (int) ($_POST['sort_order'] ?? 0)));
        $active = !empty($_POST['is_active']);
        $errs = [];
        if (!preg_match('/^[a-z0-9\-]{3,40}$/', $code)) {
            $errs[] = 'The code must be 3–40 characters: lowercase letters, numbers and dashes.';
        }
        if ($title === '' || mb_strlen($title) > 120) {
            $errs[] = 'Enter a title (up to 120 characters).';
        }
        if ($body === '') {
            $errs[] = 'Enter the guideline text.';
        }
        if ($errs === [] && $guidelines->codeExists($code, $gid)) {
            $errs[] = 'Another guideline already uses that code.';
        }
        if ($errs !== []) {
            flash('error', $errs);
            redirect(admin_url('guidelines', $gid ? ['edit' => $gid] : []));
        }
        $saved = $guidelines->save($gid, $code, $title, $body, $audience, $sort, $active);
        $audit->record($aid, $gid ? 'guideline_updated' : 'guideline_created', 'guideline', $saved, $title);
        flash('success', 'Guideline saved.');
        redirect(admin_url('guidelines'));
    }

    redirect(admin_url($tab));
}

/* ---- page data -------------------------------------------------------------------- */
$pending = $listings->pending();
$openReports = $reports->open();
$openTickets = $support->open();

$reviewId = (int) ($_GET['review'] ?? 0);
$review = null;
foreach ($pending as $p) {
    if ($reviewId === 0 || $reviewId === (int) $p['id']) {
        $review = $listings->find((int) $p['id']);
        break;
    }
}
$reviewPhotos = $review ? $listings->photos((int) $review['id']) : [];

$reportId = (int) ($_GET['report'] ?? 0);
$report = $reportId > 0 ? $reports->find($reportId) : ($openReports ? $reports->find((int) $openReports[0]['id']) : null);
$ticketId = (int) ($_GET['ticket'] ?? 0);
$ticket = $ticketId > 0 ? $support->find($ticketId) : ($openTickets ? $support->find((int) $openTickets[0]['id']) : null);

$editGuideline = isset($_GET['edit']) ? $guidelines->find((int) $_GET['edit']) : null;

render('admin', [
    'tab' => $tab,
    'pending' => $pending,
    'openReports' => $openReports,
    'handledReports' => $tab === 'reports' ? $reports->recentHandled(8) : [],
    'openTickets' => $openTickets,
    'handledTickets' => $tab === 'support' ? $support->recentHandled(8) : [],
    'review' => $review,
    'reviewPhotos' => $reviewPhotos,
    'reviewSeller' => $review ? $users->ratings((int) $review['seller_id']) : null,
    'report' => $report,
    'reportAttachments' => $report ? $reports->attachments((int) $report['id']) : [],
    'ticket' => $ticket,
    'ticketMessages' => $ticket ? $support->messages((int) $ticket['id']) : [],
    'guidelinesAll' => $guidelines->allIncludingInactive(),
    'activeGuidelines' => $guidelines->active(),
    'editGuideline' => $editGuideline,
], ['title' => 'Admin workspace · ' . APP_NAME, 'chrome' => 'admin']);

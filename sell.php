<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

use App\Repository\CategoryRepository;
use App\Repository\ListingRepository;
use App\Repository\TownRepository;
use App\Service\UploadService;

$me = require_verified('sell a product');
$uid = (int) $me['id'];
$listings = repo(ListingRepository::class);
$categories = repo(CategoryRepository::class);
$towns = repo(TownRepository::class);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$listing = null;
if ($id > 0) {
    $listing = $listings->find($id);
    if ($listing === null || (int) $listing['seller_id'] !== $uid) {
        abort(404, 'That listing does not exist.');
    }
}

// Step 3 confirmation screen
$submittedId = (int) ($_GET['submitted'] ?? 0);
if ($submittedId > 0) {
    $done = $listings->find($submittedId);
    if ($done === null || (int) $done['seller_id'] !== $uid) {
        abort(404, 'That listing does not exist.');
    }
    render('sell-done', ['listing' => $done], ['title' => 'Submitted for moderation · ' . APP_NAME, 'tab' => 'sell', 'footer' => sell_footer()]);
    exit;
}

function sell_footer(): array
{
    return ['text' => 'Bantayan Marketplace', 'slashes' => true, 'links' => [['Seller guidelines', 'guidelines.php#sellers'], ['Contact admin', 'support.php'], ['Privacy', 'privacy.php']]];
}

$editable = $listing === null || in_array($listing['status'], ['draft', 'changes_requested'], true);
$conditions = ['new', 'like_new', 'used'];
$leafIds = array_map(static fn($c) => (int) $c['id'], $categories->leaves());

/** Parse and clean the posted form. */
function sell_input(array $post, array $me): array
{
    $price = parse_price((string) ($post['price'] ?? ''));
    return [
        'title' => mb_substr(trim((string) ($post['title'] ?? '')), 0, 150),
        'category_id' => (int) ($post['category_id'] ?? 0),
        'item_condition' => (string) ($post['item_condition'] ?? ''),
        'description' => trim((string) ($post['description'] ?? '')),
        'price_raw' => (string) ($post['price'] ?? ''),
        'price' => $price,
        'town_id' => (int) ($post['town_id'] ?? $me['town_id']),
    ];
}

$errors = [];
$form = null;

if (is_post()) {
    verify_csrf();
    if (!$editable) {
        abort(403, 'This listing can no longer be edited.');
    }
    $action = (string) ($_POST['action'] ?? 'save_draft');
    $form = sell_input($_POST, $me);

    if (mb_strlen($form['title']) < 2) {
        $errors[] = 'Please enter a product title.';
    }
    if ($errors === []) {
        $townOk = $towns->find($form['town_id']) !== null;
        $data = [
            'category_id' => in_array($form['category_id'], $leafIds, true) ? $form['category_id'] : null,
            'town_id' => $townOk ? $form['town_id'] : (int) $me['town_id'],
            'title' => $form['title'],
            'description' => $form['description'],
            'price' => $form['price'] ?? 0,
            'item_condition' => in_array($form['item_condition'], $conditions, true) ? $form['item_condition'] : null,
        ];
        $listingId = $listings->save($listing ? (int) $listing['id'] : null, $uid, $data);
        $uploader = new UploadService();
        $problems = [];

        // Remove photos
        foreach ((array) ($_POST['remove'] ?? []) as $pid) {
            $photo = $listings->photo((int) $pid);
            if ($photo !== null && (int) $photo['listing_id'] === $listingId) {
                $listings->removePhoto((int) $photo['id']);
                $uploader->delete($photo['file_path']);
            }
        }
        // Replace photos
        if (!empty($_FILES['replace']['name']) && is_array($_FILES['replace']['name'])) {
            foreach ($_FILES['replace']['name'] as $pid => $name) {
                if (($_FILES['replace']['error'][$pid] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $photo = $listings->photo((int) $pid);
                if ($photo === null || (int) $photo['listing_id'] !== $listingId) {
                    continue;
                }
                try {
                    $path = $uploader->saveListingPhoto([
                        'name' => $name, 'type' => $_FILES['replace']['type'][$pid] ?? '',
                        'tmp_name' => $_FILES['replace']['tmp_name'][$pid] ?? '', 'error' => $_FILES['replace']['error'][$pid],
                        'size' => $_FILES['replace']['size'][$pid] ?? 0,
                    ]);
                    $listings->replacePhotoPath((int) $photo['id'], $path);
                    $uploader->delete($photo['file_path']);
                } catch (RuntimeException $e) {
                    $problems[] = $e->getMessage();
                }
            }
        }
        // New photos
        $room = MAX_PHOTOS - $listings->photoCount($listingId);
        foreach (UploadService::files('photos') as $file) {
            if ($room <= 0) {
                $problems[] = 'You can add up to ' . MAX_PHOTOS . ' photos. Extra photos were skipped.';
                break;
            }
            try {
                $listings->addPhoto($listingId, $uploader->saveListingPhoto($file));
                $room--;
            } catch (RuntimeException $e) {
                $problems[] = $e->getMessage();
            }
        }
        // Cover photo
        $cover = (int) ($_POST['cover'] ?? 0);
        if ($cover > 0) {
            $photo = $listings->photo($cover);
            if ($photo !== null && (int) $photo['listing_id'] === $listingId) {
                $listings->makeCover($listingId, $cover);
            }
        }

        if ($problems !== []) {
            flash('error', array_values(array_unique($problems)));
        }

        if ($action === 'submit') {
            $missing = [];
            if (mb_strlen($form['title']) < 5) {
                $missing[] = 'The title needs at least 5 characters.';
            }
            if ($data['category_id'] === null) {
                $missing[] = 'Choose a category.';
            }
            if ($data['item_condition'] === null) {
                $missing[] = 'Choose the condition.';
            }
            if (mb_strlen($form['description']) < 20) {
                $missing[] = 'Write a description of at least 20 characters. Mention any defects.';
            }
            if ($form['price'] === null || $form['price'] <= 0) {
                $missing[] = 'Enter a selling price greater than zero (numbers only, for example 12500).';
            }
            if ($listings->photoCount($listingId) < 1) {
                $missing[] = 'Add at least one photo of the actual item.';
            }
            if ($missing !== []) {
                flash('error', array_merge(['Your draft was saved, but it is not ready to submit yet:'], $missing));
                redirect('sell.php?id=' . $listingId);
            }
            $listings->submit($listingId);
            redirect('sell.php?submitted=' . $listingId);
        }

        flash('success', 'Draft saved. You can finish it any time from your account.');
        redirect('sell.php?id=' . $listingId);
    }
}

$photos = $listing ? $listings->photos((int) $listing['id']) : [];
if ($form === null) {
    $price = $listing ? (float) $listing['price'] : 0.0;
    $form = [
        'title' => $listing['title'] ?? '',
        'category_id' => (int) ($listing['category_id'] ?? 0),
        'item_condition' => (string) ($listing['item_condition'] ?? ''),
        'description' => $listing['description'] ?? '',
        'price_raw' => $price > 0 ? rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.') : '',
        'town_id' => (int) ($listing['town_id'] ?? $me['town_id']),
    ];
}
$moderation = $listing && $listing['status'] === 'changes_requested' ? $listings->latestModeration((int) $listing['id']) : null;

render('sell', [
    'listing' => $listing,
    'editable' => $editable,
    'photos' => $photos,
    'form' => $form,
    'errors' => $errors,
    'leaves' => $categories->leaves(),
    'towns' => $towns->all(),
    'moderation' => $moderation,
], [
    'title' => 'Sell a product · ' . APP_NAME,
    'tab' => 'sell',
    'scripts' => ['sell.js'],
    'footer' => sell_footer(),
]);

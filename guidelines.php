<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

render('guidelines', ['items' => repo(App\Repository\GuidelineRepository::class)->active()], ['title' => 'Community guidelines · ' . APP_NAME]);

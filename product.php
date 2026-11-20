<?php

declare(strict_types=1);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id = $id !== false && $id !== null && $id > 0 ? $id : 0;

header('Location: listing.php?id=' . $id, true, 302);
exit;

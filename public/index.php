<?php

use App\Kernel;

// Support legacy nested XAMPP URL prefix by normalizing REQUEST_URI.
$legacyPrefix = '/RLIFE1/RLIFE1/RLIFE/studyflow1/public';
if (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], $legacyPrefix)) {
    $normalized = substr($_SERVER['REQUEST_URI'], strlen($legacyPrefix));
    $_SERVER['REQUEST_URI'] = $normalized !== '' ? $normalized : '/';
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

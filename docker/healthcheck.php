<?php

declare(strict_types=1);

if (! is_file('/var/www/html/public/app/index.html')) {
    exit(1);
}

$context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
$response = @file_get_contents('http://127.0.0.1/up', false, $context);

exit($response !== false && isset($http_response_header[0]) && preg_match('/^HTTP\/\S+ 200\b/', $http_response_header[0]) === 1 ? 0 : 1);

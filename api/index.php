<?php
require_once __DIR__ . '/includes/headers.php';

echo json_encode([
    'project' => 'SUhousing API',
    'version' => '1.0',
    'status'  => 'running',
    'endpoints' => [
        'register'         => '/api/register.php',
        'login'            => '/api/login.php',
        'logout'           => '/api/logout.php',
        'listings'         => '/api/listings.php',
        'listing'          => '/api/listing.php?id={id}',
        'profiles'         => '/api/profiles.php',
        'feedback'         => '/api/feedback.php',
        'admin_listings'   => '/api/admin/listings.php',
        'admin_feedback'   => '/api/admin/feedback.php',
        'admin_dashboard'  => '/api/admin/dashboard.php',
    ]
]);
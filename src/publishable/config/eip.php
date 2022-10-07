<?php

return [
    // JWT 過期(秒)
    'eip_auth' => env('EIP_AUTH', false),
    'eip_url' => env('EIP_URL'),
    'JWT_EXP' => env('JWT_EXP', 900),
    'CLIENT_SECRET' => env('EIP_CLIENT_SECRET'),
    'COOKIE_DOMAIN' => env('COOKIE_DOMAIN', '.hwacom.com'),
];

<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],


    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:3000','https://api-quanlythietbi.onrender.com'], // thêm địa chỉ frontend của bạn tại đây

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // bắt buộc nếu dùng Sanctum
];

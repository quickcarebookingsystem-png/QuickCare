<?php
return [
    // Environment variables can override these values on the server.
    'secret_key' => getenv('TOYYIBPAY_SECRET_KEY') ?: 'qhicglbk-ouu9-54js-5xn2-vr9i5tx0ezbt',
    'category_code' => getenv('TOYYIBPAY_CATEGORY_CODE') ?: 'h213fqqz',
    'base_url' => getenv('TOYYIBPAY_BASE_URL') ?: 'https://toyyibpay.com',
];

<?php

return [
    'base_api_url' => env('PROVIDER_BASE_API_URL'),
    'base_ciam_url' => env('PROVIDER_BASE_CIAM_URL'),
    'base_crypto_url' => env('PROVIDER_BASE_CRYPTO_URL', 'https://me-crypto.mashu.lol/api/890'),
    'basic_auth'   => env('PROVIDER_BASIC_AUTH'),
    'device_id'    => env('PROVIDER_AX_DEVICE_ID'),
    'fp_key'       => env('PROVIDER_AX_FP_KEY'),
    'user_agent'   => env('PROVIDER_UA'),
    'api_key'      => env('PROVIDER_API_KEY'),
    'aes_key'      => env('PROVIDER_AES_KEY_ASCII'),
];

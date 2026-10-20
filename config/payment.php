<?php

return [

    /*
    | fake: ใช้ทดสอบระหว่างรอคีย์ ไม่เรียก gateway จริง
    | live: เรียก KGP (Thai QR) หรือ Omise (บัตร) ตามคีย์ด้านล่าง
    | แยกได้ต่อวิธีชำระ ถ้าไม่ตั้งใช้ PAYMENT_DRIVER
    */
    'drivers' => [
        'thai_qr' => env('PAYMENT_THAI_QR_DRIVER', env('PAYMENT_DRIVER', 'fake')),
        'credit_card' => env('PAYMENT_CARD_DRIVER', env('PAYMENT_DRIVER', 'fake')),
    ],

    'merchant_name' => env('PAYMENT_MERCHANT_NAME', 'Click Class Tutor'),

    'methods' => [
        'thai_qr' => (bool) env('PAYMENT_THAI_QR_ENABLED', true),
        'credit_card' => (bool) env('PAYMENT_CARD_ENABLED', true),
    ],

    'link_ttl_minutes' => (int) env('PAYMENT_LINK_TTL_MINUTES', 60),

    /*
    | ปลายทางหลังชำระเสร็จหรือกดกลับ ใช้ {order_id} และ {order_no} แทนค่าได้
    */
    'return_url' => env('PAYMENT_RETURN_URL'),

    'kgp' => [
        'base_url' => env('KGP_BASE_URL', 'https://openapi-uat.kgppayments.com'),
        'secret_key' => env('KGP_SECRET_KEY'),
        'public_key' => env('KGP_PUBLIC_KEY'),
        'qr_expire_seconds' => (int) env('KGP_QR_EXPIRE_SECONDS', 600),
        'timeout' => 20,
    ],

    'omise' => [
        'base_url' => env('OMISE_BASE_URL', 'https://api.omise.co'),
        'public_key' => env('OMISE_PUBLIC_KEY'),
        'secret_key' => env('OMISE_SECRET_KEY'),
        'timeout' => 30,
    ],

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Click Class Tutor Mail Branding
    |--------------------------------------------------------------------------
    */

    'support_email' => env('MAIL_SUPPORT_ADDRESS', env('MAIL_FROM_ADDRESS', 'hello@example.com')),

    'staff' => [
        'header_title' => 'ระบบหลังบ้าน',
        'footer_note' => 'อีเมลนี้ส่งถึงพนักงาน / ผู้ดูแลระบบเท่านั้น โปรดเก็บรักษาข้อมูลเป็นความลับ',
        'primary_color' => '#465fff',
        'bg_color' => '#f3f4f6',
    ],

    'customer' => [
        'header_title' => 'Click Class Tutor',
        'footer_note' => 'หากคุณไม่ได้เป็นผู้ร้องขออีเมลนี้ สามารถเพิกเฉยได้',
        'primary_color' => '#0f766e',
        'bg_color' => '#f0fdfa',
    ],

];

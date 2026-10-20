<?php

return [

    /*
    | ข้อมูลผู้ออกใบเสร็จที่พิมพ์บนหัวกระดาษ
    */
    'company_name' => env('RECEIPT_COMPANY_NAME', env('APP_NAME', 'Click Class Tutor')),
    'company_address' => env('RECEIPT_COMPANY_ADDRESS'),
    'tax_id' => env('RECEIPT_TAX_ID'),
    'phone' => env('RECEIPT_PHONE'),
    'email' => env('RECEIPT_EMAIL', env('MAIL_SUPPORT_ADDRESS')),

    /*
    | path ใน public/ เช่น images/logo/logo.png เว้นว่าง = ไม่แสดงโลโก้
    */
    'logo' => env('RECEIPT_LOGO', 'images/logo/logo.png'),

    'note' => env('RECEIPT_NOTE', 'ใบเสร็จนี้ออกโดยระบบอัตโนมัติ ใช้เป็นหลักฐานการชำระเงิน'),

    'download_ttl_minutes' => (int) env('RECEIPT_DOWNLOAD_TTL_MINUTES', 60),

    'timezone' => env('RECEIPT_TIMEZONE', 'Asia/Bangkok'),

];

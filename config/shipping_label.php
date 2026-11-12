<?php

return [

    /*
    | ผู้ส่งที่พิมพ์บนใบปะหน้าพัสดุ (A6) เว้นว่าง = ใช้ข้อมูลเดียวกับใบเสร็จ (config/receipt.php)
    */
    'sender_name' => env('SHIPPING_SENDER_NAME') ?: env('RECEIPT_COMPANY_NAME', env('APP_NAME', 'Click Class Tutor')),
    'sender_phone' => env('SHIPPING_SENDER_PHONE') ?: env('RECEIPT_PHONE'),
    'sender_address' => env('SHIPPING_SENDER_ADDRESS') ?: env('RECEIPT_COMPANY_ADDRESS'),

    /*
    | จำนวนรายการสินค้าที่พิมพ์บนใบ ที่เหลือแสดงเป็น "และอีก N รายการ" เพื่อให้จบในหน้าเดียว
    */
    'max_items' => (int) env('SHIPPING_LABEL_MAX_ITEMS', 4),

];

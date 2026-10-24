<?php

return [

    /*
    | อัปโหลดวิดีโอตรงจากเบราว์เซอร์ขึ้น R2 แบบ multipart (ไม่ผ่าน PHP)
    | R2 กำหนดให้ทุก part ยกเว้น part สุดท้ายมีขนาดเท่ากัน และไม่เกิน 10,000 part
    */
    'video_max_mb' => (int) env('MEDIA_VIDEO_MAX_MB', 5120),

    'video_part_mb' => (int) env('MEDIA_VIDEO_PART_MB', 32),

    'video_upload_ttl_hours' => (int) env('MEDIA_VIDEO_UPLOAD_TTL_HOURS', 24),

];

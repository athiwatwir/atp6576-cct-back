# ชำระเงินออนไลน์ (Thai QR PromptPay + บัตรเครดิต + e-Wallet)

| วิธีชำระ (`payments.payment_method`) | ผู้ให้บริการ (`payments.provider`) | เอกสาร |
|------|------|------|
| `thai_qr` QR พร้อมเพย์ | `kgp` KGP Payment Gateway (PGW) | `docs/omise/attachments/KGP-PGW [QR] API Specification v.1.2.1.pdf` |
| `credit_card` บัตรเครดิต / เดบิต | `omise` Omise (KPN) | [docs.omise.co/charging-cards](https://docs.omise.co/charging-cards) |
| `truemoney` TrueMoney Wallet | `omise` | [docs.omise.co/truemoney-wallet](https://docs.omise.co/truemoney-wallet) |
| `shopeepay` ShopeePay | `omise` | [docs.omise.co/shopeepay](https://docs.omise.co/shopeepay) |
| `rabbit_linepay` Rabbit LINE Pay | `omise` | [docs.omise.co/rabbit-linepay](https://docs.omise.co/rabbit-linepay) |
| `wechat_pay` WeChat Pay | `omise` | [docs.omise.co/wechat-pay](https://docs.omise.co/wechat-pay) |
| `bank_transfer` โอนเงินแล้วแนบสลิป | ไม่มี (แอดมินตรวจสลิป) | |

แต่ละวิธีเปิดหรือปิดได้ที่หน้า **วิธี/ค่าธรรมเนียมการชำระเงิน** ดูหัวข้อ [เปิดปิดวิธีชำระและค่าธรรมเนียม](#เปิดปิดวิธีชำระและค่าธรรมเนียม)

## e-Wallet (Omise Source)

`OnlinePayment::startWallet()` สร้าง charge แบบ source กับ Omise (`OmiseCardGateway::chargeSource()`) ด้วยยอด `payments.amount` และ `return_uri` เป็นหน้าออเดอร์ของ Nuxt ต่อท้าย `payment_return=wallet` (`OnlinePayment::walletReturnUrl()`)

| วิธี | `platform=desktop` (ค่าเริ่มต้น) | `platform=mobile` |
|------|------|------|
| TrueMoney | `truemoney_qr` แสดง QR (หมดอายุ 1 วัน) | `truemoney_jumpapp` เปิดแอป TrueMoney |
| ShopeePay | `shopeepay` หน้าเว็บ ShopeePay | `shopeepay_jumpapp` เปิดแอป Shopee |
| Rabbit LINE Pay | `rabbit_linepay` หน้าเว็บ (หมดอายุประมาณ 20 นาที) | เหมือนกัน |
| WeChat Pay | `wechat_pay_mpm` แสดง QR (หมดอายุ 1 วัน) | `wechat_pay` เปิดแอป WeChat |

- ผลที่ได้มี `authorize_uri` (ให้ redirect ไป) และสำหรับแบบ QR มี `qr_image` (ระบบดาวน์โหลดรูป QR จาก Omise แล้วเก็บเป็น data URI ใน `payments.metadata.qr_image`) เก็บ `source_type`, `platform`, `flow` ไว้ใน `payments.metadata`
- ยืนยันผลเหมือนบัตร: webhook ของ Omise และ `payment/status` ดึง charge จาก Omise ใหม่ (`expired` = ไม่สำเร็จ)
- กดชำระใหม่ รายการเดิมที่ยังรอถูกปิด (`superseded`) และสั่ง Omise expire charge เดิม กันลูกค้าจ่ายรายการเก่าซ้ำ
- ต้องใช้ `PAYMENT_CARD_DRIVER=live` และคีย์ Omise (test หรือ live) โหมด `fake` ไม่มี e-Wallet บัญชี Omise ต้องเปิดช่องทางนั้นด้วย หน้าตั้งค่าตรวจกับ Omise `GET /capability` (แคช 10 นาที) แล้วเตือนถ้ายังไม่เปิด
- Omise test mode: หน้า `authorize_uri` ให้กดจำลองสำเร็จหรือไม่สำเร็จได้ หรือใช้ `POST /charges/{id}/mark_as_paid` / `mark_as_failed` ของ Omise (เฉพาะคีย์ test)
- ตรวจกับบัญชี test เมื่อ 2 พ.ย. 2569: บัญชีเปิด TrueMoney, ShopeePay, Rabbit LINE Pay, WeChat Pay และผ่อนชำระ / Direct Debit (ยังไม่รองรับในระบบ) บัญชี live ต้องตรวจอีกครั้งเมื่อเปลี่ยนคีย์

## Driver ต่อวิธีชำระ

แต่ละวิธีเลือก driver แยกกันได้ ถ้าไม่ตั้งรายวิธี จะใช้ `PAYMENT_DRIVER` (ค่าเริ่มต้น `fake`)

| ตัวแปร | วิธีชำระ | `live` เรียก |
|------|------|------|
| `PAYMENT_THAI_QR_DRIVER` | QR พร้อมเพย์ | KGP |
| `PAYMENT_CARD_DRIVER` | บัตร | Omise |

สถานะตอนนี้ในเครื่อง dev: QR เป็น `fake` (รอคีย์ KGP) บัตรเป็น `live` กับ Omise test mode (`pkey_test_` / `skey_test_`) ซึ่งเป็นการตัดเงินทดสอบของ Omise ไม่มีเงินจริง

### `fake` (ไม่เรียก gateway)

`payments.provider` เป็น `fake`

- QR เป็นภาพจำลอง สแกนจ่ายไม่ได้ หน้าชำระเงินมีปุ่ม **จำลองชำระสำเร็จ** และ **จำลองไม่สำเร็จ**
- บัตรจำลอง: เลขลงท้าย `0002` ไม่ผ่าน, ลงท้าย `3220` ต้องยืนยัน OTP (มีหน้าจำลอง 3-D Secure), เลขอื่นสำเร็จ หน้าเว็บไม่ส่งเลขบัตรไปที่ไหน แค่เลือก token ทดสอบตามเลขท้ายบัตร
- หน้าออเดอร์ในแอดมินมีปุ่ม **จำลองชำระสำเร็จ** สำหรับรายการ `fake` ที่ยังรอชำระ

รายการที่สร้างตอนเป็น `fake` ยังเป็น `provider = fake` หลังเปลี่ยนเป็น `live` และไม่ไปเรียก gateway จริง

### Omise test mode

ใช้คีย์ `pkey_test_` / `skey_test_` กับ `PAYMENT_CARD_DRIVER=live` บัตรทดสอบจาก [Omise test cards](https://docs.omise.co/api-testing)

| เลขบัตร | ผล |
|------|------|
| `4242 4242 4242 4242` | สำเร็จ |
| `4111 1111 1114 0011` | ไม่ผ่าน `insufficient_fund` |

วันหมดอายุเป็นอนาคตและ CVV ใดก็ได้ หน้าชำระเงินแสดงกล่องโหมดทดสอบเมื่อ public key ขึ้นต้นด้วย `pkey_test_`

## ตั้งค่า `.env`

```env
PAYMENT_DRIVER=fake            # fake | live ค่าเริ่มต้นของทุกวิธี
PAYMENT_THAI_QR_DRIVER=fake    # ไม่ตั้ง = ใช้ PAYMENT_DRIVER
PAYMENT_CARD_DRIVER=live       # ไม่ตั้ง = ใช้ PAYMENT_DRIVER
PAYMENT_MERCHANT_NAME="Click Class Tutor"
PAYMENT_LINK_TTL_MINUTES=60
# ปลายทางหลังชำระ ใช้ {order_id}, {order_no}, {guest_token} ได้ ถ้าไม่ตั้งใช้ STUDENT_APP_URL/orders/{order_id}
# ออเดอร์ที่สั่งแบบไม่สมัครสมาชิกต่อท้าย ?guest_token=... ให้เอง ถ้ารูปแบบไม่มี {guest_token}
PAYMENT_RETURN_URL=

KGP_BASE_URL=https://openapi-uat.kgppayments.com   # production: https://openapi.kgppayments.com
KGP_SECRET_KEY=
KGP_PUBLIC_KEY=
KGP_QR_EXPIRE_SECONDS=600       # 60–86400

OMISE_PUBLIC_KEY=
OMISE_SECRET_KEY=
```

ค่าทั้งหมดอ่านผ่าน `config/payment.php` หลังแก้ `.env` ให้รัน `php artisan config:clear`

`PAYMENT_THAI_QR_ENABLED` / `PAYMENT_CARD_ENABLED` เลิกใช้แล้ว ใช้เป็นค่าเริ่มต้นตอนรัน migration `add_is_active_to_payment_fees_table` ครั้งแรกเท่านั้น หลังจากนั้นเปิดปิดที่หน้าแอดมิน

## ชำระในเว็บนักเรียน (Nuxt)

หน้าออเดอร์ของ Nuxt สร้าง QR ตัดบัตร และถามสถานะผ่าน Student API เอง ไม่ redirect มาหน้า Laravel (นอกจากหน้า 3-D Secure ของธนาคาร) endpoint อยู่ใต้ `/api/v1/orders/{id}/payment/...` สำหรับนักเรียนที่ล็อกอิน และ `/api/v1/guest/orders/{guest_token}/payment/...` สำหรับสั่งซื้อแบบไม่สมัครสมาชิก ดู `docs/student-api.md`

- บัตร: `return_uri` ที่ส่งให้ Omise คือหน้าออเดอร์ของ Nuxt (`OnlinePayment::cardReturnUrl()` = `PAYMENT_RETURN_URL` หรือ `{STUDENT_APP_URL}/orders/{order_id}` ต่อท้าย `payment_return=card`) หน้านั้นเรียก `payment/status` ซึ่งดึง charge จาก Omise ใหม่ ส่วนหน้า `/pay/{token}` ยังกลับผ่าน `/pay/return/{payment}` เหมือนเดิม
- e-Wallet: `POST .../payment/wallet` ส่ง `method` และ `platform` แล้ว redirect ไป `authorize_uri` หรือแสดง `qr_image` ลูกค้ากลับมาที่หน้าออเดอร์พร้อม `payment_return=wallet`
- โหมดทดสอบ: `POST .../payment/simulate` (`OnlinePayment::simulateLatest()`) แทนปุ่มจำลองในหน้า Laravel (ใช้กับรายการ `fake` เท่านั้น)
- `GET /api/v1/payment-methods` ไม่ต้องล็อกอิน ส่งวิธีที่เปิดอยู่ (`available`) และ `omise_public_key`

## หน้าชำระเงิน (`/pay/{token}`)

หน้าเว็บของ Laravel ที่ลูกค้าเปิดเพื่อจ่าย ไม่ต้องล็อกอิน ใช้ token จากตาราง `payment_links` (อายุตาม `PAYMENT_LINK_TTL_MINUTES`) เว็บนักเรียนไม่ใช้หน้านี้แล้ว ยังเปิดได้จาก

1. แอดมินกด **สร้างลิงก์ชำระเงินออนไลน์** ในหน้าออเดอร์ แล้วคัดลอกลิงก์ส่งให้ลูกค้า
2. `POST /api/v1/orders/{id}/payment-link` หรือ `payment_url` ของ `POST /api/v1/guest/orders` (ถ้าต้องการส่งลิงก์ให้จ่ายนอกเว็บ)

ในหน้ามีสรุปรายการ ให้เลือกวิธีชำระ แสดง QR หรือฟอร์มบัตร พอชำระสำเร็จจะมีปุ่มกลับไปที่ `PAYMENT_RETURN_URL` เมื่อออเดอร์ชำระแล้ว ลิงก์จะเปลี่ยนเป็น `used` แต่ยังเปิดดูหน้าชำระสำเร็จได้ ลิงก์ที่หมดอายุจะแสดงหน้าแจ้งให้กลับไปขอลิงก์ใหม่

### ส่วน QR ตามคู่มือ KGP (`KGP QR UXUI_Instruction_Guideline _v2_SA_20260713.pdf`)

| ข้อกำหนด | สิ่งที่หน้าแสดง |
|------|------|
| หัวสีน้ำเงิน `#00427A` พร้อมโลโก้ Thai QR Payment สีขาว | หัวสีน้ำเงิน ตอนนี้เป็นข้อความ `THAI QR PAYMENT` ถ้าวางไฟล์โลโก้ทางการที่ `public/images/payment/thai-qr-payment.png` หน้าจะใช้รูปแทน |
| โลโก้ PromptPay ใต้หัว | `public/images/payment/promptpay.png` จากไฟล์ที่ KGP ส่งมา |
| ข้อความ “Powered by KGP” | มี |
| โลโก้ Thai QR ในตัว QR | ใช้รูป QR ที่ KGP สร้างให้ (`image_with_base64`) |
| เวลานับถอยหลัง mm:ss หรือเวลาหมดอายุ | แสดงทั้งสองแบบ |
| ชื่อร้านค้า | `account_name` จาก KGP หรือ `PAYMENT_MERCHANT_NAME` |
| ยอดเงินพร้อมสกุลเงิน เช่น `555.00 THB` | มี |
| ปุ่ม Save QR | ปุ่ม “บันทึก QR (Save QR)” |
| ขั้นตอนการชำระ (ไม่บังคับ) | มี |
| คำเตือนมิจฉาชีพ TH/EN | มี |

หน้า QR ถามสถานะทุก 4 วินาทีผ่าน `GET /pay/{token}/status` เมื่อจ่ายแล้วจะเปลี่ยนเป็นหน้าชำระสำเร็จ QR ที่ยังไม่หมดอายุจะถูกใช้ซ้ำ ไม่สร้างใหม่ทุกครั้งที่กด ถ้า QR หมดอายุจะมีปุ่มสร้าง QR ใหม่

### ส่วนบัตร

โหมด live โหลด [Omise.js](https://docs.omise.co/omise-js) แล้วใช้หน้าต่างชำระของ Omise (`OmiseCard.configure()` / `OmiseCard.open()` ใน `resources/js/payment.js`)

- **หน้าต่าง:** เปิดเฉพาะบัตร (`otherPaymentMethods: []`) ภาษาไทย ยอดเงินคือยอดออเดอร์รวมค่าธรรมเนียมบัตร (สตางค์)
- **การกรอกบัตร:** ลูกค้ากรอกบัตรใน iframe ของ Omise (`cdn.omise.co/pay.html`) หน้าเว็บและ server ของเราไม่เห็นเลขบัตร (เราไม่มี PCI DSS)
- **token:** ได้ token จาก `onCreateTokenSuccess` แล้วส่งไปที่ `POST /pay/{token}/card`
- **ฟอร์มบัตรในหน้าเดิม:** ใช้เฉพาะเมื่อ driver บัตรเป็นตัวจำลอง (ต้องส่ง token จำลอง) หรือโหลด `OmiseCard` ไม่ได้
- **API ของ Nuxt ไม่เปลี่ยน:** ยังรับ token จาก `Omise.createToken()` ตาม `docs/student-api.md`

จากนั้น server ตัดเงินด้วย Charges API แบบ `capture=true` ถ้าธนาคารต้องยืนยัน 3-D Secure จะพาไปที่ `authorize_uri` แล้วกลับมาที่ `/pay/return/{payment}` (signed URL) ระบบตรวจผลกับ Omise แล้วพากลับไปหน้าชำระเงิน

`failure_code` ที่พบบ่อยของ Omise (`insufficient_fund`, `stolen_or_lost_card`, `invalid_security_code`, `payment_rejected`, `failed_fraud_check` ฯลฯ) ถูกแปลงเป็นข้อความภาษาไทยใน `failure_message` รหัสอื่นใช้ข้อความจาก Omise

## เปิดปิดวิธีชำระและค่าธรรมเนียม

ตั้งค่าที่ **การขาย → วิธี/ค่าธรรมเนียมการชำระเงิน** (`/settings/payment-fees`) หน้าเดียวแสดงทุกวิธี (`PaymentMethod::customerMethods()`): QR พร้อมเพย์, บัตรเครดิต, TrueMoney, ShopeePay, Rabbit LINE Pay, WeChat Pay, โอนเงิน เก็บที่ตาราง `payment_fees` แถวละวิธี

- **เปิดใช้งาน** (`payment_fees.is_active`) ลูกค้าเห็นและเลือกวิธีนี้ได้ ปิดแล้ว API ไม่แสดง และเริ่มชำระด้วยวิธีนั้นไม่ได้ (ตอบ `422` "ยังไม่เปิดใช้วิธีชำระนี้" / ส่งสลิปตอบ "ยังไม่เปิดรับชำระด้วยการโอนเงิน") รายการที่เริ่มไปแล้วยังจ่ายต่อได้
- ค่าเริ่มต้นหลัง migrate: QR และบัตรตาม `PAYMENT_*_ENABLED` เดิม (ปกติเปิด) โอนเงินเปิด e-Wallet ปิด
- วิธีที่เปิดแต่ระบบยังไม่พร้อม (เช่น e-Wallet ตอน `PAYMENT_CARD_DRIVER=fake`) ไม่แสดงให้ลูกค้า หน้าตั้งค่าขึ้นคำเตือน (`OnlinePayment::isAvailable()`)
- หน้าแสดงผู้ให้บริการ โหมดทดสอบ / คีย์ทดสอบ และคำเตือนเมื่อบัญชี Omise ยังไม่เปิดช่องทาง e-Wallet นั้น
- หน้า `/pay/{token}` ของ Laravel แสดงเฉพาะ QR พร้อมเพย์และบัตรที่เปิดอยู่

ค่าธรรมเนียมตั้งแยกต่อวิธี ค่าเริ่มต้นคือไม่เก็บ

| ช่อง | ความหมาย |
|------|------|
| เก็บจากลูกค้า | เปิดแล้วบวกค่าธรรมเนียมเข้ายอดที่ลูกค้าจ่าย |
| เปอร์เซ็นต์ | 0–100 คิดจากยอดออเดอร์ (`orders.total_amount` หลังส่วนลด) |
| ค่าคงที่ | บาทต่อรายการชำระ |
| ขั้นต่ำ / สูงสุด | ไม่บังคับ ปรับค่าธรรมเนียมให้อยู่ในช่วงนี้ ขั้นต่ำต้องไม่เกินสูงสุด |
| ข้อความที่ลูกค้าเห็น | เว้นว่างได้ ระบบแสดง "ค่าธรรมเนียมบัตรเครดิต (3.5% + 10.00 บาท)" |

สูตร: `ยอดออเดอร์ × เปอร์เซ็นต์ / 100 + ค่าคงที่` แล้วจำกัดด้วยขั้นต่ำ/สูงสุด ปัดเป็นทศนิยม 2 ตำแหน่ง ออเดอร์ยอด 0 บาทไม่มีค่าธรรมเนียม หน้าตั้งค่ามีช่องลองคำนวณจากยอดตัวอย่าง การบันทึกถูกเก็บใน Activity Log

- ค่าธรรมเนียมเก็บที่รายการชำระ: `payments.amount` = ยอดออเดอร์ + ค่าธรรมเนียม และ `payments.fee_amount` = ค่าธรรมเนียม ยอดออเดอร์ ส่วนลด และคูปองไม่เปลี่ยน
- คิดตอนเริ่มชำระแต่ละครั้ง (สร้าง QR, ตัดบัตร, เปิด e-Wallet, สร้างออเดอร์/ส่งสลิปโอนเงิน) และเก็บค่าตั้งที่ใช้ไว้ใน `payments.metadata.fee` แก้ค่าตั้งภายหลังไม่กระทบรายการที่สร้างแล้ว QR ที่ยังไม่หมดอายุจะถูกสร้างใหม่ถ้ายอดที่ต้องจ่ายเปลี่ยน
- ยอดที่ส่งให้ KGP และ Omise คือ `payments.amount` และการตรวจยอดตอนแจ้งผลเทียบกับยอดนี้
- หน้าชำระเงินแสดงค่าธรรมเนียมใต้แต่ละวิธี และแยกบรรทัดยอดออเดอร์ / ค่าธรรมเนียม / ยอดชำระเมื่อเลือกวิธีแล้ว
- แอดมินบันทึกการชำระให้นักเรียนเอง (หน้านักเรียน) ไม่คิดค่าธรรมเนียม
- หน้าออเดอร์แสดงค่าธรรมเนียมและยอดที่ลูกค้าจ่าย หน้าการเงินแสดงค่าธรรมเนียมที่เก็บได้ใต้รายได้ (ไม่รวมใน "รายได้ที่ชำระแล้ว" ซึ่งยังคิดจากยอดออเดอร์) และแสดงในแต่ละรายการชำระ

## การยืนยันผลชำระ

ระบบตัดออเดอร์เป็นชำระแล้วจากผลที่ได้จาก gateway เท่านั้น ใช้ `StudentPurchase::review()` ตัวเดียวกับที่แอดมินอนุมัติสลิป จึงเปิดสิทธิ์เรียน บันทึกการใช้คูปอง และตั้ง `paid_at` เหมือนกัน

| ช่องทาง | วิธี |
|------|------|
| KGP notify | KGP POST มาที่ `/api/v1/payments/kgp/notify` ระบบตรวจ `checksum` ก่อนเสมอ ไม่ตรงตอบ `400` |
| KGP inquiry | หน้า QR, API status และปุ่มในแอดมินเรียก `GET /api/v2/charge/{reference_order}` |
| Omise webhook | Omise POST มาที่ `/api/v1/payments/omise/webhook` ระบบไม่เชื่อข้อมูลใน body แต่ดึง charge จาก Omise ใหม่ทุกครั้ง |
| Omise return | หลัง 3-D Secure กลับมาที่ `/pay/return/{payment}` แล้วดึง charge ใหม่ |

สูตร checksum ของ KGP (หน้า 24 ของสเปก)

```text
sha256(charge_id + amount (ทศนิยม 4 ตำแหน่ง เช่น 200.5000) + currency + status + transaction_state + KGP_SECRET_KEY)
```

สเปกพิมพ์ฟิลด์ `status` ผิดเป็น `stauts` ในตัวอย่าง ระบบอ่านได้ทั้งสองชื่อ

ถ้ายอดที่ gateway แจ้งไม่ตรงกับ `payments.amount` ระบบไม่ตัดเป็นชำระแล้ว ตั้งรายการเป็น `failed` ด้วยรหัส `amount_mismatch` และเขียน log ระดับ error

## Webhook ของ Thai QR (KGP PGW)

ฝั่ง PGW ไม่มีหน้า dashboard ให้ตั้ง URL เอง ต้องส่ง URL ให้ทีม operation ของ KGP เป็นคนตั้ง (ผ่านฟอร์ม `ATP.xlsx` หรืออีเมล) UAT กับ production ตั้งแยกกัน URL ต้องเป็น HTTPS ที่เข้าจากอินเทอร์เน็ตได้ และไม่ต้องส่ง `X-Api-Key`

| Webhook | URL | สเปก | การตรวจ | ตอบกลับ |
|------|------|------|------|------|
| Notify Payment | `POST {APP_URL}/api/v1/payments/kgp/notify` | หัวข้อ 6 | `checksum` SHA-256 ต้องตรง และยอดต้องเท่า `payments.amount` | `200 {}` สำเร็จหรือไม่พบรายการ / `400` checksum ไม่ตรง (`failure_code: 1012`) |
| Refund Notification | `POST {APP_URL}/api/v1/payments/kgp/refund-notify` | หัวข้อ 8 | payload ไม่มี checksum ระบบเรียก Refund Inquiry `GET /api/v2/refund/{refund_id}` (หัวข้อ 9) และใช้ผลจาก KGP เท่านั้น `charge_id` ต้องตรง | `200 {}` บันทึกแล้วหรือไม่พบรายการ / `400` ไม่มี `refund_id` หรือ `charge_id` (`1018`) / `503` ยืนยันกับ KGP ไม่ได้ (`1011`) ให้ KGP ส่งซ้ำ |

ส่งซ้ำได้ ระบบไม่เปิดสิทธิ์ซ้ำและไม่คิดเงินซ้ำ ทุกครั้งที่รับ webhook บันทึกลง `payment_transactions` (`notify`, `refund.notify`, `refund.unverified`)

Refund Notification หาออเดอร์จาก `payments.metadata.charge_id` (ได้มาตอนชำระสำเร็จ) และเก็บผลไว้ที่ `payments.metadata.refunds.{refund_id}` (`status`, `refund_amount_type`, `refund_day_type`, `refund_ref_id`) เมื่อ KGP ยืนยันว่า `status = success` และเป็นการคืนเต็มจำนวน (`refund_amount_type = F`) ระบบตั้ง `payments.status` และ `orders.payment_status` เป็น `refunded` การคืนบางส่วน (`P`) หรือ `pending` เก็บประวัติไว้แต่ยังเป็นชำระแล้ว ระบบไม่ยกเลิกสิทธิ์เรียนให้เอง แอดมินปิดสิทธิ์ได้ที่หน้านักเรียน

## ข้อมูลที่เก็บ

ทุกครั้งที่สร้าง QR หรือตัดบัตร ระบบสร้างแถวใหม่ใน `payments` แถวเดิมที่ยังรอชำระผ่าน gateway จะถูกตั้งเป็น `failed` ด้วยรหัส `superseded` และ QR เดิมจะถูกยกเลิกกับ KGP เมื่อออเดอร์ชำระสำเร็จผ่าน gateway แถวอื่นของออเดอร์ที่ยัง `pending` และไม่มีสลิป (เช่นแถว `bank_transfer` ที่สร้างตอนสั่งซื้อ) จะถูกตั้งเป็น `failed` รหัส `superseded` ด้วย หน้าการเงินจะได้ไม่แสดงว่ายังรอชำระ

| คอลัมน์ | ความหมาย |
|------|------|
| `provider` | `kgp`, `omise` หรือ `fake` |
| `reference` | เลขอ้างอิงของเรา `CCT` + เลข payment 10 หลัก ส่งเป็น `reference_order` ให้ KGP |
| `transaction_id` | `qr_id` ของ KGP หรือ charge id ของ Omise |
| `expires_at` | เวลาหมดอายุของ QR |
| `failure_code`, `failure_message` | เหตุผลที่ไม่สำเร็จ |
| `metadata.qr_image` | รูป QR (data URI) |
| `metadata.charge_id` | charge id ของ KGP หลังจ่ายแล้ว |
| `metadata.card` | ยี่ห้อและเลข 4 ตัวท้ายของบัตรจาก Omise |

ทุกเหตุการณ์ (สร้าง QR, ตัดบัตร, inquiry, notify, webhook) บันทึกใน `payment_transactions` พร้อม payload ไม่มีเลขบัตรหรือ secret key

## แอดมิน

หน้าออเดอร์ (`orders.show`) ส่วน **การชำระเงิน**

- แสดงผู้ให้บริการ เลขอ้างอิง รหัสรายการ เวลา QR หมดอายุ และเหตุผลที่ไม่สำเร็จ
- **ตรวจสถานะกับผู้ให้บริการ** `POST /orders/{order}/payments/{payment}/refresh`
- **จำลองชำระสำเร็จ** (เฉพาะโหมดทดสอบ) `POST /orders/{order}/payments/{payment}/simulate`
- **สร้างลิงก์ชำระเงินออนไลน์** `POST /orders/{order}/payment-link`

หน้าการเงินรวมยอดตามวิธีชำระจาก `payments.payment_method` อยู่แล้ว จึงมี QR พร้อมเพย์และบัตรเครดิตแยกบรรทัดให้เอง

## ยังไม่ได้ทำ

- สั่งคืนเงินจากระบบ (KGP `POST /api/v2/refund/{charge_id}` กับ Omise Refunds API) ตอนนี้ยังต้องคืนผ่าน portal ของผู้ให้บริการ ฝั่ง Thai QR รับ Refund Notification จาก KGP ได้แล้ว ฝั่ง Omise ยังไม่รับ event คืนเงิน
- ผ่อนชำระ, TrueMoney, ShopeePay, LINE Pay, บันทึกบัตรไว้ใช้ครั้งหน้า
- ยังไม่ได้ทดสอบกับ UAT ของ KGP ต้องรอคีย์ ส่วนบัตรทดสอบกับ Omise test mode แล้ว (สำเร็จ / ไม่ผ่าน) ยังไม่ได้ทดสอบบัตรที่ต้องยืนยัน 3-D Secure และ webhook ของ Omise เพราะเครื่อง dev ไม่มี URL สาธารณะ

## สิ่งที่ต้องส่งให้ KGP / ทำเมื่อได้คีย์

1. กรอกฟอร์ม `docs/omise/attachments/ATP.xlsx` ช่อง **ThaiQr Notify URL** ใส่ `{APP_URL}/api/v1/payments/kgp/notify` และแจ้ง KGP ว่า Refund Notification ใช้ `{APP_URL}/api/v1/payments/kgp/refund-notify` (UAT ใช้โดเมนทดสอบหรือ ngrok ได้) ถามทีม KGP ด้วยว่าถ้าตอบไม่ใช่ 2xx จะส่งซ้ำกี่ครั้ง และมี IP ต้นทางสำหรับ allowlist หรือไม่
2. ตั้ง webhook ใน Omise dashboard เป็น `{APP_URL}/api/v1/payments/omise/webhook` (ไม่ตั้งก็ใช้ได้ เพราะหน้าชำระเงินตรวจผลกับ Omise เองตอนกลับจาก 3-D Secure)
3. ได้คีย์ KGP แล้วใส่ `KGP_SECRET_KEY`, `KGP_PUBLIC_KEY` ตั้ง `PAYMENT_THAI_QR_DRIVER=live` แล้ว `php artisan config:clear` ถ้ารัน `php artisan serve --no-reload` ต้องรีสตาร์ทด้วย
4. ทดสอบตาม UAT template ของ KGP แล้วขอคีย์ production เปลี่ยน `KGP_BASE_URL` เป็น production
5. ขึ้น production ให้เปลี่ยน Omise เป็นคีย์ `pkey_` / `skey_` ที่ไม่มี `_test_`

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | บทบาท |
|------|------|
| `config/payment.php` | ค่าตั้งต้นทั้งหมด |
| `app/Support/Payments/OnlinePayment.php` | สร้างรายการ, ตรวจผล, webhook, ลิงก์ชำระ |
| `app/Support/Payments/KgpQrGateway.php` | เรียก KGP Create QR / Inquiry / Cancel + checksum |
| `app/Support/Payments/OmiseCardGateway.php` | เรียก Omise Charges API |
| `app/Support/Payments/FakeGateway.php` | ตัวจำลองสำหรับโหมดทดสอบ |
| `app/Support/Payments/PaymentFees.php` | คำนวณค่าธรรมเนียม, ยอดต่อวิธีชำระ, กติกาสำหรับ API |
| `app/Models/PaymentFee.php` / `app/Http/Controllers/PaymentFeeController.php` | ค่าตั้งและหน้าตั้งค่าค่าธรรมเนียม |
| `resources/views/pages/settings/payment-fees.blade.php` | หน้าตั้งค่าค่าธรรมเนียม |
| `app/Http/Controllers/PaymentPageController.php` | หน้า `/pay/{token}` |
| `app/Http/Controllers/Api/PaymentController.php` | API ให้ Nuxt |
| `app/Http/Controllers/Api/PaymentWebhookController.php` | KGP notify, KGP refund notify, Omise webhook |
| `app/Http/Controllers/OrderPaymentController.php` | ปุ่มในหน้าออเดอร์แอดมิน |
| `resources/views/payment/*.blade.php` | หน้าชำระเงิน |
| `resources/js/payment.js` | Alpine component ของหน้าชำระเงิน (entry แยกจากแอดมิน) |
| `database/migrations/2026_10_17_090000_add_gateway_fields_to_payments_table.php` | คอลัมน์ `reference`, `expires_at`, `failure_*` |
| `docs/omise/attachments/` | ไฟล์แนบจากอีเมล KGP (สเปก, คู่มือ UX/UI, ฟอร์ม ATP.xlsx, โลโก้ PromptPay) |

@extends('payment.layout')

@push('head')
    @if (! $fakeCard && in_array('credit_card', $methods, true) && $omisePublicKey)
        <script src="https://cdn.omise.co/omise.js"></script>
    @endif
@endpush

@section('content')
    @php
        $thaiQrLogo = file_exists(public_path('images/payment/thai-qr-payment.png')) ? asset('images/payment/thai-qr-payment.png') : null;
        $config = [
            'state' => $state,
            'methods' => $methods,
            'quotes' => $quotes,
            'fakeCard' => $fakeCard,
            'omisePublicKey' => $omisePublicKey,
            'fakeTokens' => $fakeTokens,
            'urls' => [
                'thaiQr' => route('payment.thai-qr', $link->token),
                'card' => route('payment.card', $link->token),
                'status' => route('payment.status', $link->token),
                'simulate' => route('payment.simulate', $link->token),
            ],
        ];
    @endphp

    <div x-data="paymentPage(@js($config))" class="space-y-4">
        <header class="text-center">
            <p class="text-sm font-medium text-brand-600">{{ $merchantName }}</p>
            <h1 class="mt-1 text-xl font-semibold">ชำระเงินออเดอร์ {{ $order->order_no }}</h1>
        </header>

        @if ($fakeQr || $fakeCard || str_starts_with((string) $omisePublicKey, 'pkey_test_'))
            <div class="rounded-xl border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700">
                <p class="font-medium">โหมดทดสอบ ไม่มีการตัดเงินจริง</p>
                <ul class="mt-1 space-y-0.5 text-xs leading-5">
                    @if ($fakeQr)
                        <li>QR เป็นภาพจำลอง สแกนจ่ายไม่ได้ ใช้ปุ่มจำลองผลใต้ QR แทน</li>
                    @endif
                    @if ($fakeCard)
                        <li>บัตรทดสอบ: ลงท้าย 0002 = ไม่ผ่าน, ลงท้าย 3220 = ต้องยืนยัน OTP, เลขอื่น = สำเร็จ</li>
                    @elseif (str_starts_with((string) $omisePublicKey, 'pkey_test_'))
                        <li>บัตรใช้ Omise test mode: 4242 4242 4242 4242 = สำเร็จ, 4111 1111 1114 0011 = ไม่ผ่าน วันหมดอายุในอนาคตและ CVV ใดก็ได้</li>
                    @endif
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold">สรุปรายการ</h2>
            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-3 py-2">
                        <span class="text-gray-600">{{ $item->item_name }} @if ((int) $item->quantity > 1) <span class="text-gray-400">× {{ $item->quantity }}</span> @endif</span>
                        <span class="shrink-0 font-medium">{{ number_format((float) $item->total_price, 2) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-3 space-y-1 border-t border-gray-100 pt-3 text-sm">
                @if ((float) $order->discount_amount > 0)
                    <div class="flex justify-between text-gray-500"><dt>ส่วนลด</dt><dd>-{{ number_format((float) $order->discount_amount, 2) }}</dd></div>
                @endif
                @if ((float) $order->shipping_amount > 0)
                    <div class="flex justify-between text-gray-500"><dt>ค่าจัดส่ง</dt><dd>{{ number_format((float) $order->shipping_amount, 2) }}</dd></div>
                @endif
                <template x-if="currentQuote && currentQuote.fee_amount > 0 && !paid">
                    <div class="space-y-1">
                        <div class="flex justify-between text-gray-500"><dt>ยอดออเดอร์</dt><dd>{{ number_format((float) $order->total_amount, 2) }}</dd></div>
                        <div class="flex justify-between text-gray-500"><dt x-text="currentQuote.fee_label"></dt><dd x-text="'+' + number(currentQuote.fee_amount)"></dd></div>
                    </div>
                </template>
                <div class="flex justify-between text-base font-semibold"><dt>ยอดชำระ</dt><dd x-text="amountText(payableTotal)">{{ number_format((float) $order->total_amount, 2) }} THB</dd></div>
            </dl>
        </section>

        {{-- ชำระสำเร็จ --}}
        <section x-show="paid" x-cloak class="rounded-2xl border border-success-200 bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success-50 text-2xl text-success-600">✓</div>
            <h2 class="mt-4 text-lg font-semibold">ชำระเงินสำเร็จ</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $order->isBook() ? 'ได้รับชำระเงินแล้ว เราจะจัดส่งหนังสือให้โดยเร็ว' : 'ระบบเปิดสิทธิ์เรียนให้แล้ว ขอบคุณที่ใช้บริการ' }}</p>
            <p class="mt-3 text-xs text-gray-400" x-show="state.payment?.reference">เลขอ้างอิง <span x-text="state.payment?.reference"></span></p>
            <a href="{{ $returnUrl }}" class="bg-brand-500 hover:bg-brand-600 mt-6 inline-flex w-full items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white">กลับไปที่เว็บไซต์</a>
        </section>

        <template x-if="!paid">
            <div class="space-y-4">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold">เลือกวิธีชำระเงิน</h2>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @if (in_array('thai_qr', $methods, true))
                            <button type="button" @click="select('thai_qr')"
                                :class="method === 'thai_qr' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-gray-200 hover:border-gray-300'"
                                class="flex items-center gap-3 rounded-xl border bg-white p-4 text-left transition">
                                <img src="{{ asset('images/payment/promptpay.png') }}" alt="PromptPay" class="h-10 w-auto" />
                                <span>
                                    <span class="block text-sm font-medium">QR พร้อมเพย์</span>
                                    <span class="block text-xs text-gray-500">สแกนจ่ายผ่านแอปธนาคาร</span>
                                    @if (($quotes['thai_qr']['fee_amount'] ?? 0) > 0)
                                        <span class="mt-0.5 block text-xs text-warning-600">+ ค่าธรรมเนียม {{ number_format($quotes['thai_qr']['fee_amount'], 2) }} บาท</span>
                                    @endif
                                </span>
                            </button>
                        @endif
                        @if (in_array('credit_card', $methods, true))
                            <button type="button" @click="select('credit_card')"
                                :class="method === 'credit_card' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-gray-200 hover:border-gray-300'"
                                class="flex items-center gap-3 rounded-xl border bg-white p-4 text-left transition">
                                <span class="flex h-8 w-12 items-center justify-center rounded-md bg-gray-900 text-[10px] font-bold tracking-wider text-white">CARD</span>
                                <span>
                                    <span class="block text-sm font-medium">บัตรเครดิต / เดบิต</span>
                                    <span class="block text-xs text-gray-500">Visa, Mastercard, JCB</span>
                                    @if (($quotes['credit_card']['fee_amount'] ?? 0) > 0)
                                        <span class="mt-0.5 block text-xs text-warning-600">+ ค่าธรรมเนียม {{ number_format($quotes['credit_card']['fee_amount'], 2) }} บาท</span>
                                    @endif
                                </span>
                            </button>
                        @endif
                    </div>
                    @if ($methods === [])
                        <p class="mt-3 text-sm text-gray-500">ยังไม่เปิดช่องทางชำระออนไลน์ กรุณาติดต่อแอดมิน</p>
                    @endif
                </section>

                <p x-show="error" x-text="error" x-cloak class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600"></p>

                {{-- Thai QR ตาม KGP QR UX/UI guideline --}}
                <section x-show="method === 'thai_qr'" x-cloak class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-center gap-3 px-4 py-3 text-white" style="background-color: #00427A">
                        @if ($thaiQrLogo)
                            <img src="{{ $thaiQrLogo }}" alt="Thai QR Payment" class="h-8 w-auto" />
                        @else
                            <span class="text-base font-bold tracking-wide">THAI QR PAYMENT</span>
                        @endif
                    </div>

                    <div class="flex flex-col items-center px-5 pt-4 pb-5">
                        <img src="{{ asset('images/payment/promptpay.png') }}" alt="PromptPay" class="h-14 w-auto" />
                        <p class="mt-1 text-xs text-gray-500">Powered by KGP</p>

                        <template x-if="!qr">
                            <div class="w-full py-6 text-center">
                                <p class="text-sm text-gray-500">กดสร้าง QR แล้วสแกนด้วยแอปธนาคารเพื่อชำระ</p>
                                <button type="button" @click="createQr()" :disabled="busy"
                                    class="bg-brand-500 hover:bg-brand-600 mt-4 inline-flex w-full items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white disabled:opacity-60">
                                    <span x-text="busy ? 'กำลังสร้าง QR...' : 'สร้าง QR พร้อมเพย์'"></span>
                                </button>
                            </div>
                        </template>

                        <template x-if="qr">
                            <div class="flex w-full flex-col items-center">
                                <div class="relative mt-4">
                                    <img :src="qr.qr_image" alt="Thai QR" class="h-64 w-64 rounded-lg border border-gray-100 bg-white object-contain p-2" :class="qrExpired && 'opacity-20'" />
                                    <div x-show="qrExpired" class="absolute inset-0 flex flex-col items-center justify-center">
                                        <p class="text-sm font-semibold text-error-600">QR หมดอายุแล้ว</p>
                                        <button type="button" @click="createQr()" :disabled="busy" class="bg-brand-500 mt-3 rounded-lg px-4 py-2 text-xs font-medium text-white">สร้าง QR ใหม่</button>
                                    </div>
                                </div>

                                <p class="mt-4 text-sm text-gray-500">ชื่อร้านค้า</p>
                                <p class="text-base font-semibold" x-text="qr.merchant_name"></p>

                                <p class="mt-3 text-2xl font-bold" x-text="amountText(qr.amount)"></p>

                                <div class="mt-3 text-center" x-show="!qrExpired">
                                    <p class="text-sm text-gray-500">QR หมดอายุใน <span class="font-mono text-base font-semibold text-gray-800" x-text="countdown"></span></p>
                                    <p class="text-xs text-gray-400">ภายใน <span x-text="expiresAtText"></span></p>
                                </div>

                                <button type="button" @click="saveQr()" x-show="!qrExpired"
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    บันทึก QR (Save QR)
                                </button>

                                <p class="mt-3 flex items-center gap-2 text-xs text-gray-500" x-show="!qrExpired">
                                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-success-500"></span>
                                    รอการชำระเงิน ระบบจะอัปเดตให้อัตโนมัติ
                                </p>

                                @if ($fakeQr)
                                    <div class="mt-4 grid w-full grid-cols-2 gap-2">
                                        <button type="button" @click="simulate('failed')" :disabled="busy" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">จำลองไม่สำเร็จ</button>
                                        <button type="button" @click="simulate('paid')" :disabled="busy" class="rounded-lg bg-success-600 px-3 py-2 text-xs font-medium text-white">จำลองชำระสำเร็จ</button>
                                    </div>
                                @endif
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold">ขั้นตอนการชำระ</h3>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-gray-600">
                            <li>กดบันทึก QR หรือเปิดหน้านี้ค้างไว้</li>
                            <li>เปิดแอปธนาคาร เลือกสแกน QR หรือเลือกรูป QR จากคลังภาพ</li>
                            <li>ตรวจชื่อร้านค้าและยอดเงินให้ตรง แล้วยืนยันการชำระ</li>
                            <li>รอหน้านี้แสดงว่าชำระสำเร็จ</li>
                        </ol>
                    </div>

                    <div class="border-t border-error-100 bg-error-50 px-5 py-3 text-xs leading-5 text-error-700">
                        <p class="font-medium">โปรดระวังถูกมิจฉาชีพหลอกโอนเงิน! กรุณาตรวจสอบข้อมูลบัญชีผู้รับก่อนชำระเงิน</p>
                        <p>Beware of scammer! Please verify the recipient’s account information before paying.</p>
                    </div>
                </section>

                {{-- บัตรเครดิต / เดบิต ผ่าน Omise.js --}}
                <section x-show="method === 'credit_card'" x-cloak class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold">ข้อมูลบัตร</h2>
                    <p class="mt-1 text-xs text-gray-500">ข้อมูลบัตรส่งตรงไปยังผู้ให้บริการชำระเงิน ระบบของเราไม่เก็บเลขบัตร</p>
                    <form class="mt-4 space-y-4" @submit.prevent="payCard()" autocomplete="on">
                        <div>
                            <label for="card_name" class="mb-1.5 block text-sm font-medium text-gray-700">ชื่อบนบัตร</label>
                            <input id="card_name" x-model="card.name" type="text" autocomplete="cc-name" required
                                class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" />
                        </div>
                        <div>
                            <label for="card_number" class="mb-1.5 block text-sm font-medium text-gray-700">หมายเลขบัตร</label>
                            <input id="card_number" x-model="card.number" @input="formatNumber()" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" required
                                class="h-11 w-full rounded-lg border border-gray-300 px-4 font-mono text-sm tracking-wider focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="card_expiry" class="mb-1.5 block text-sm font-medium text-gray-700">วันหมดอายุ</label>
                                <input id="card_expiry" x-model="card.expiry" @input="formatExpiry()" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="ดด/ปป" required
                                    class="h-11 w-full rounded-lg border border-gray-300 px-4 font-mono text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" />
                            </div>
                            <div>
                                <label for="card_cvc" class="mb-1.5 block text-sm font-medium text-gray-700">CVV</label>
                                <input id="card_cvc" x-model="card.cvc" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="•••" required
                                    class="h-11 w-full rounded-lg border border-gray-300 px-4 font-mono text-sm focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden" />
                            </div>
                        </div>
                        <button type="submit" :disabled="busy"
                            class="bg-brand-500 hover:bg-brand-600 inline-flex w-full items-center justify-center rounded-lg px-5 py-3 text-sm font-medium text-white disabled:opacity-60">
                            <span x-text="busy ? 'กำลังชำระเงิน...' : 'ชำระ ' + amountText(quotes.credit_card?.total ?? orderTotal)"></span>
                        </button>
                        <p class="text-center text-xs text-gray-400">บัตรบางใบจะพาไปหน้ายืนยัน OTP ของธนาคาร (3-D Secure)</p>
                    </form>
                </section>

                <p class="text-center">
                    <a href="{{ $returnUrl }}" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">กลับไปหน้าออเดอร์</a>
                </p>
            </div>
        </template>
    </div>
@endsection

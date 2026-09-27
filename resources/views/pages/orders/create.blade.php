@extends('layouts.app')

@section('content')
@php
    $booksPayload = $books->map(fn ($book) => [
        'id' => $book->id,
        'name' => $book->name,
        'price' => (float) $book->effective_price,
        'stock' => $book->stock,
        'status' => $book->status,
        'thumbnail_url' => $book->thumbnail_url,
    ])->values();

    $oldItems = collect(old('items', []))
        ->filter(fn ($item) => filled($item['product_id'] ?? null))
        ->values()
        ->all();
@endphp

<style>[x-cloak] { display: none !important; }</style>

<x-common.page-breadcrumb pageTitle="สร้างออเดอร์" />

<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    x-data="orderForm(
        @js($booksPayload),
        @js($customersPayload),
        @js($oldItems),
        @js(old('customer_mode', 'existing')),
        @js((string) old('user_id', '')),
        {
            name: @js(old('customer_name', old('shipping_name', ''))),
            email: @js(old('customer_email', '')),
            phone: @js(old('customer_phone', old('shipping_phone', ''))),
        }
    )">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">สร้างคำสั่งซื้อหนังสือ</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">กรอกข้อมูลลูกค้า ที่อยู่จัดส่ง รายการหนังสือ และวิธีชำระเงิน</p>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" class="space-y-8 p-5 sm:p-6">
        @csrf
        <input type="hidden" name="copy_customer_to_shipping" value="1">
        <input type="hidden" name="shipping_name" :value="contactName">
        <input type="hidden" name="shipping_phone" :value="contactPhone">
        <input type="hidden" name="customer_name" :value="customerMode === 'manual' ? contactName : ''">
        <input type="hidden" name="customer_phone" :value="customerMode === 'manual' ? contactPhone : ''">

        <section>
            <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">ข้อมูลลูกค้าและที่อยู่จัดส่ง</h4>

            <div class="mb-5 flex flex-wrap gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="customer_mode" value="existing" class="peer sr-only" x-model="customerMode">
                    <span class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 dark:border-gray-700 dark:text-gray-300 dark:peer-checked:bg-brand-500/15 dark:peer-checked:text-brand-400">
                        เลือกลูกค้าที่มีอยู่
                    </span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="customer_mode" value="manual" class="peer sr-only" x-model="customerMode">
                    <span class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 dark:border-gray-700 dark:text-gray-300 dark:peer-checked:bg-brand-500/15 dark:peer-checked:text-brand-400">
                        กรอกข้อมูลลูกค้าใหม่
                    </span>
                </label>
            </div>
            @error('customer_mode')
                <p class="mb-3 text-sm text-error-500">{{ $message }}</p>
            @enderror

            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="sm:col-span-2 lg:col-span-3" x-show="customerMode === 'existing'" x-cloak>
                        <label for="user_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            ลูกค้า<span class="text-error-500">*</span>
                        </label>
                        <select id="user_id" name="user_id" x-model="selectedUserId" @change="applySelectedCustomer()"
                            :required="customerMode === 'existing'"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="">เลือกลูกค้า</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }} — {{ $customer->email }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            ชื่อผู้รับ<span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="contact_name" x-model="contactName" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('shipping_name')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                        @error('customer_name')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="contact_phone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            เบอร์โทร<span class="text-error-500">*</span>
                        </label>
                        <input type="text" id="contact_phone" x-model="contactPhone" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('shipping_phone')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                        @error('customer_phone')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="customerMode === 'manual'" x-cloak>
                        <label for="customer_email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            อีเมล
                        </label>
                        <input type="email" id="customer_email" name="customer_email" x-model="customerEmail"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        @error('customer_email')
                            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-common.thai-address
                    :address-line1="old('shipping_address_line1', '')"
                    :subdistrict="old('shipping_subdistrict', '')"
                    :district="old('shipping_district', '')"
                    :province="old('shipping_province', '')"
                    :postal-code="old('shipping_postal_code', '')"
                />

                <div>
                    <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเหตุ</label>
                    <textarea id="notes" name="notes" rows="2"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section>
            <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">การชำระเงิน</h4>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="payment_method" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        วิธีชำระเงิน<span class="text-error-500">*</span>
                    </label>
                    <select id="payment_method" name="payment_method" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', 'bank_transfer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')
                        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="shipping_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ค่าจัดส่ง (บาท)</label>
                    <input type="number" id="shipping_amount" name="shipping_amount" min="0" step="0.01"
                        x-model.number="shippingAmount"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('shipping_amount')
                        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="discount_amount" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">ส่วนลด (บาท)</label>
                    <input type="number" id="discount_amount" name="discount_amount" min="0" step="0.01"
                        x-model.number="discountAmount"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    @error('discount_amount')
                        <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายการหนังสือ</h4>
                <button type="button" @click="openBookModal()"
                    class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-3 py-2 text-xs font-medium text-white shadow-theme-xs">
                    + เพิ่มรายการ
                </button>
            </div>

            @error('items')
                <p class="mb-3 text-sm text-error-500">{{ $message }}</p>
            @enderror

            <div class="space-y-3" x-show="items.length > 0">
                <template x-for="(item, index) in items" :key="item.product_id">
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-3 sm:flex-row sm:items-center dark:border-gray-800">
                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                            <template x-if="bookOf(item.product_id)?.thumbnail_url">
                                <img :src="bookOf(item.product_id).thumbnail_url" :alt="bookOf(item.product_id)?.name" class="h-full w-full object-cover" />
                            </template>
                            <template x-if="!bookOf(item.product_id)?.thumbnail_url">
                                <div class="flex h-full w-full items-center justify-center text-[10px] text-gray-400">ไม่มีปก</div>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-800 dark:text-white/90" x-text="bookOf(item.product_id)?.name || '-'"></div>
                            <div class="mt-1 text-sm text-brand-600 dark:text-brand-400" x-text="`฿${Number(bookOf(item.product_id)?.price || 0).toFixed(2)}`"></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div>
                                <label class="mb-1 block text-xs text-gray-500">จำนวน</label>
                                <input type="number" min="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-20 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            </div>
                            <div class="pt-5 text-sm font-medium text-gray-800 dark:text-white/90" x-text="`฿${lineTotal(item).toFixed(2)}`"></div>
                            <button type="button" @click="removeItem(index)"
                                class="mt-5 inline-flex items-center rounded-lg border border-error-300 bg-error-50 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-100 dark:border-error-500/40 dark:bg-error-500/10 dark:text-error-400">
                                ลบ
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="items.length === 0" class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีรายการหนังสือ</p>
                <button type="button" @click="openBookModal()"
                    class="text-brand-600 hover:text-brand-700 mt-2 text-sm font-medium dark:text-brand-400">
                    เลือกหนังสือจากรายการ
                </button>
            </div>

            <div class="mt-4 rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-white/[0.03]">
                <div class="flex justify-between"><span class="text-gray-500">ยอดสินค้า</span><span class="font-medium text-gray-800 dark:text-white/90" x-text="`฿${subtotal.toFixed(2)}`"></span></div>
                <div class="mt-1 flex justify-between"><span class="text-gray-500">ค่าจัดส่ง</span><span class="font-medium text-gray-800 dark:text-white/90" x-text="`฿${Number(shippingAmount || 0).toFixed(2)}`"></span></div>
                <div class="mt-1 flex justify-between"><span class="text-gray-500">ส่วนลด</span><span class="font-medium text-gray-800 dark:text-white/90" x-text="`-฿${Number(discountAmount || 0).toFixed(2)}`"></span></div>
                <div class="mt-2 flex justify-between border-t border-gray-200 pt-2 dark:border-gray-700">
                    <span class="font-semibold text-gray-800 dark:text-white/90">ยอดรวม</span>
                    <span class="font-semibold text-brand-600 dark:text-brand-400" x-text="`฿${grandTotal.toFixed(2)}`"></span>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800">
            <a href="{{ route('orders.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                ยกเลิก
            </a>
            <button type="submit"
                class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">
                สร้างออเดอร์
            </button>
        </div>
    </form>

    <div x-show="showBookModal" x-cloak
        @keydown.escape.window="if (showBookModal) closeBookModal()"
        class="modal fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" data-modal>
        <div @click="closeBookModal()" class="fixed inset-0 h-full w-full bg-gray-900/40"></div>
        <div @click.stop class="relative w-full max-w-3xl rounded-3xl bg-white p-5 shadow-xl sm:p-6 dark:bg-gray-900">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">เลือกหนังสือ</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เลือกรายการเพื่อเพิ่มเข้าออเดอร์</p>
                </div>
                <button type="button" @click="closeBookModal()"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10">
                    ✕
                </button>
            </div>

            <div class="mb-4">
                <input type="text" x-model="bookSearch" placeholder="ค้นหาชื่อหนังสือ..."
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            <div class="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                <template x-for="book in filteredBooks" :key="book.id">
                    <button type="button" @click="selectBook(book)"
                        class="flex w-full items-center gap-3 rounded-xl border border-gray-200 p-3 text-left transition hover:border-brand-300 hover:bg-brand-50/50 dark:border-gray-800 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10"
                        :class="isSelected(book.id) ? 'border-brand-400 bg-brand-50/60 dark:border-brand-500/50 dark:bg-brand-500/10' : ''">
                        <div class="h-16 w-12 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            <template x-if="book.thumbnail_url">
                                <img :src="book.thumbnail_url" :alt="book.name" class="h-full w-full object-cover" />
                            </template>
                            <template x-if="!book.thumbnail_url">
                                <div class="flex h-full w-full items-center justify-center text-[10px] text-gray-400">ไม่มีปก</div>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-800 dark:text-white/90" x-text="book.name"></div>
                            <div class="mt-1 text-sm font-semibold text-brand-600 dark:text-brand-400" x-text="`฿${Number(book.price).toFixed(2)}`"></div>
                            <div class="mt-0.5 text-xs text-gray-400" x-show="book.stock !== null" x-text="`คงเหลือ ${book.stock}`"></div>
                        </div>
                        <span class="shrink-0 text-xs font-medium"
                            :class="isSelected(book.id) ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400'"
                            x-text="isSelected(book.id) ? 'เพิ่มแล้ว' : 'เลือก'"></span>
                    </button>
                </template>

                <div x-show="filteredBooks.length === 0" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                    ไม่พบหนังสือที่ตรงกับคำค้นหา
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="closeBookModal()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function orderForm(books, customers, initialItems, customerMode, selectedUserId, customerData) {
        return {
            books,
            customers,
            customerMode: customerMode || 'existing',
            selectedUserId: selectedUserId ? String(selectedUserId) : '',
            contactName: customerData?.name || '',
            contactPhone: customerData?.phone || '',
            customerEmail: customerData?.email || '',
            showBookModal: false,
            bookSearch: '',
            items: (initialItems && initialItems.length)
                ? initialItems.map(item => ({
                    product_id: String(item.product_id ?? ''),
                    quantity: Number(item.quantity || 1),
                }))
                : [],
            shippingAmount: Number(@json((float) old('shipping_amount', 0))),
            discountAmount: Number(@json((float) old('discount_amount', 0))),
            applySelectedCustomer() {
                const selected = this.customers.find(c => String(c.id) === String(this.selectedUserId));
                if (! selected) {
                    return;
                }

                this.contactName = selected.name || '';
                this.contactPhone = selected.phone || '';
            },
            openBookModal() {
                this.bookSearch = '';
                this.showBookModal = true;
                document.body.style.overflow = 'hidden';
            },
            closeBookModal() {
                this.showBookModal = false;
                document.body.style.overflow = '';
            },
            get filteredBooks() {
                const q = (this.bookSearch || '').trim().toLowerCase();
                if (! q) {
                    return this.books;
                }

                return this.books.filter(book => String(book.name || '').toLowerCase().includes(q));
            },
            isSelected(productId) {
                return this.items.some(item => String(item.product_id) === String(productId));
            },
            selectBook(book) {
                const existing = this.items.find(item => String(item.product_id) === String(book.id));
                if (existing) {
                    existing.quantity = Number(existing.quantity || 1) + 1;
                } else {
                    this.items.push({
                        product_id: String(book.id),
                        quantity: 1,
                    });
                }
                this.closeBookModal();
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            bookOf(productId) {
                return this.books.find(b => String(b.id) === String(productId));
            },
            bookPrice(productId) {
                return Number(this.bookOf(productId)?.price || 0);
            },
            lineTotal(item) {
                return this.bookPrice(item.product_id) * Number(item.quantity || 0);
            },
            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            get grandTotal() {
                return Math.max(0, this.subtotal + Number(this.shippingAmount || 0) - Number(this.discountAmount || 0));
            },
        };
    }
</script>
@endsection

@push('scripts')
    @include('partials.thai-address-scripts')
@endpush

@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="สร้างออเดอร์" />

    @include('pages.students._steps', ['step' => 2])

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-600 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
        x-data="{
            rows: {{ \Illuminate\Support\Js::from(old('items', [['type' => 'course', 'id' => '']])) }},
            catalog: {{ \Illuminate\Support\Js::from($catalog) }},
            options(type) {
                return this.catalog[type] || [];
            },
            price(row) {
                const item = this.options(row.type).find((entry) => String(entry.id) === String(row.id));
                return item ? Number(item.price) : 0;
            },
            total() {
                return this.rows.reduce((sum, row) => sum + this.price(row), 0);
            },
            add() {
                this.rows.push({ type: 'course', id: '' });
            },
            remove(index) {
                this.rows.splice(index, 1);
            },
            money(value) {
                return '฿' + Number(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">เลือกสินค้าให้ {{ $student->name }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($hasPaidOrder)
                    นักเรียนคนนี้เคยซื้อแล้ว อีเมลหลังชำระเงินจะมีเฉพาะผลการซื้อ
                @else
                    นี่คือการซื้อครั้งแรก อีเมลหลังชำระเงินจะมีผลการซื้อและข้อมูลสำหรับเข้าระบบ
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('students.orders.store', $student) }}" class="space-y-5 p-5 sm:p-6">
            @csrf
            <div class="space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                        <select class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-3" x-model="row.type" @change="row.id = ''" :name="'items[' + index + '][type]'">
                            <option value="course">คอร์ส</option>
                            <option value="curriculum">หลักสูตร</option>
                            <option value="exam">ข้อสอบ</option>
                        </select>
                        <select class="dark:bg-dark-900 h-11 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 md:col-span-6" x-model="row.id" :name="'items[' + index + '][id]'" required>
                            <option value="">เลือกรายการ</option>
                            <template x-for="item in options(row.type)" :key="row.type + '-' + item.id">
                                <option :value="item.id" x-text="item.name + ' · ฿' + Number(item.price).toLocaleString('th-TH')"></option>
                            </template>
                        </select>
                        <div class="flex items-center justify-between gap-3 md:col-span-3">
                            <span class="text-sm font-medium text-gray-800 dark:text-white/90" x-text="money(price(row))"></span>
                            <button type="button" class="text-sm text-error-600" @click="remove(index)" x-show="rows.length > 1">ลบ</button>
                        </div>
                    </div>
                </template>
            </div>

            <button type="button" @click="add()" class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                เพิ่มรายการ
            </button>

            <div>
                <label for="coupon_code" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">รหัสคูปอง</label>
                <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" maxlength="100" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm uppercase dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เว้นว่างได้ ส่วนลดจะถูกคำนวณตอนสร้างออเดอร์ และนับการใช้เมื่อชำระเงินสำเร็จ</p>
                @error('coupon_code')
                    <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">หมายเหตุ</label>
                <textarea id="notes" name="notes" rows="3" class="dark:bg-dark-900 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('notes') }}</textarea>
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    ยอดรวม <span class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="money(total())"></span>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('students.show', $student) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">ยกเลิก</a>
                    <button type="submit" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">ถัดไป: ชำระเงิน</button>
                </div>
            </div>
        </form>
    </div>
@endsection

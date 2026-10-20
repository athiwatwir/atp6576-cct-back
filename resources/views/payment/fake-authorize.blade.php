@extends('payment.layout')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-700">โหมดทดสอบ ไม่มีการตัดเงินจริง</p>
        <h1 class="mt-4 text-lg font-semibold">ยืนยันตัวตน 3-D Secure</h1>
        <p class="mt-2 text-sm leading-6 text-gray-500">
            หน้านี้จำลองหน้ากรอก OTP ของธนาคาร ยอด {{ number_format((float) $payment->amount, 2) }} THB
            เลขอ้างอิง {{ $payment->reference }}
        </p>
        <form method="POST" action="{{ $action }}" class="mt-6 grid grid-cols-2 gap-3">
            @csrf
            <button type="submit" name="result" value="decline" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700">ปฏิเสธ</button>
            <button type="submit" name="result" value="approve" class="bg-brand-500 hover:bg-brand-600 rounded-lg px-4 py-2.5 text-sm font-medium text-white">ยืนยัน OTP</button>
        </form>
    </div>
@endsection

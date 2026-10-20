@extends('payment.layout')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm sm:p-8">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-warning-50 text-2xl text-warning-600">!</div>
        <h1 class="mt-4 text-lg font-semibold">{{ $title }}</h1>
        <p class="mt-2 text-sm leading-6 text-gray-500">{{ $message }}</p>
        @if ($returnUrl)
            <a href="{{ $returnUrl }}" class="bg-brand-500 hover:bg-brand-600 mt-6 inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-medium text-white">กลับไปหน้าออเดอร์</a>
        @endif
    </div>
@endsection

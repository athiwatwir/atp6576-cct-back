@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $article->title }}" />

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @if ($article->image_url)
            <img src="{{ $article->image_url }}" alt="" class="max-h-80 w-full object-cover" />
        @endif
        <div class="space-y-4 p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $article->title }}</h2>
                        <x-common.status-badge :status="$article->status" />
                        @if ($article->isLive())
                            <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-400">กำลังแสดงบนเว็บ</span>
                        @endif
                    </div>
                    <p class="mt-2 font-mono text-xs text-gray-400">{{ $article->slug }}</p>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $article->published_at?->format('d/m/Y H:i') ?: 'ยังไม่กำหนดวันเผยแพร่' }}
                        @if ($article->expired_at)
                            – {{ $article->expired_at->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('articles.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">กลับ</a>
                    <a href="{{ route('articles.edit', $article) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">แก้ไข</a>
                </div>
            </div>
            @if ($article->excerpt)
                <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ $article->excerpt }}</p>
            @endif
            @php($articleHtml = \App\Support\ArticleHtml::clean($article->content))
            @if ($articleHtml)
                <div class="article-content">{!! $articleHtml !!}</div>
            @else
                <p class="text-sm text-gray-500">ยังไม่มีเนื้อหา</p>
            @endif
        </div>
    </div>
@endsection

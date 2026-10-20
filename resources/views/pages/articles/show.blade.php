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
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('articles.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">กลับ</a>
                    <a href="{{ route('articles.edit', $article) }}" class="bg-brand-500 hover:bg-brand-600 inline-flex items-center rounded-lg px-4 py-2.5 text-sm font-medium text-white">แก้ไข</a>
                </div>
            </div>
            @if ($article->excerpt)
                <p class="rounded-xl bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ $article->excerpt }}</p>
            @endif

            @if ($article->seo_title || $article->seo_description || $article->seo_keywords)
                <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
                    <p class="text-xs font-medium text-gray-500">SEO</p>
                    <dl class="mt-2 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                        @if ($article->seo_title)
                            <div>
                                <dt class="text-xs text-gray-400">title</dt>
                                <dd>{{ $article->seo_title }}</dd>
                            </div>
                        @endif
                        @if ($article->seo_description)
                            <div>
                                <dt class="text-xs text-gray-400">description</dt>
                                <dd>{{ $article->seo_description }}</dd>
                            </div>
                        @endif
                        @if ($article->seo_keywords)
                            <div>
                                <dt class="text-xs text-gray-400">keywords</dt>
                                <dd>{{ $article->seo_keywords }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            @php($articleHtml = \App\Support\ArticleHtml::clean($article->content))
            @if ($articleHtml)
                <div class="article-content">{!! $articleHtml !!}</div>
            @else
                <p class="text-sm text-gray-500">ยังไม่มีเนื้อหา</p>
            @endif

            @if ($article->courses->isNotEmpty() || $article->curriculums->isNotEmpty() || $article->assessments->isNotEmpty())
                <div class="space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">รายการที่เกี่ยวข้อง</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        @if ($article->courses->isNotEmpty())
                            <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                                <p class="text-xs font-medium text-gray-500">คอร์ส</p>
                                <ul class="mt-2 space-y-1 text-sm text-gray-800 dark:text-white/90">
                                    @foreach ($article->courses as $course)
                                        <li>{{ $course->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($article->curriculums->isNotEmpty())
                            <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                                <p class="text-xs font-medium text-gray-500">หลักสูตร</p>
                                <ul class="mt-2 space-y-1 text-sm text-gray-800 dark:text-white/90">
                                    @foreach ($article->curriculums as $curriculum)
                                        <li>{{ $curriculum->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($article->assessments->isNotEmpty())
                            <div class="rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                                <p class="text-xs font-medium text-gray-500">ข้อสอบ</p>
                                <ul class="mt-2 space-y-1 text-sm text-gray-800 dark:text-white/90">
                                    @foreach ($article->assessments as $exam)
                                        <li>{{ $exam->title }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

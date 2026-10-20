<?php

namespace App\Http\Controllers\Api;

use App\Enums\BannerPlacement;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Testimonial;
use App\Support\ArticleHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function banners(Request $request): JsonResponse
    {
        $placement = $request->string('placement')->toString();

        if ($placement !== '' && BannerPlacement::tryFrom($placement) === null) {
            return response()->json([
                'message' => 'ตำแหน่งแบนเนอร์ไม่ถูกต้อง',
                'errors' => ['placement' => ['ใช้ได้เฉพาะ home, courses, books']],
            ], 422);
        }

        $banners = Content::query()
            ->banners()
            ->visible()
            ->when($placement !== '', fn ($query) => $query->where('placement', $placement))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(30)
            ->get()
            ->map(fn (Content $banner) => $this->bannerPayload($banner))
            ->values();

        return response()->json([
            'data' => $banners,
        ]);
    }

    public function articles(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $perPage = min(50, max(1, (int) $request->integer('per_page', 12)));

        $articles = Content::query()
            ->articles()
            ->visible()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $articles->getCollection()->map(fn (Content $article) => $this->articlePayload($article))->values(),
            'links' => [
                'first' => $articles->url(1),
                'last' => $articles->url($articles->lastPage()),
                'prev' => $articles->previousPageUrl(),
                'next' => $articles->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    public function showArticle(string $slug): JsonResponse
    {
        $article = Content::query()
            ->articles()
            ->visible()
            ->with([
                'courses' => fn ($query) => $query
                    ->where('courses.status', 'published')
                    ->orderBy('courses.name')
                    ->select(['courses.id', 'courses.name', 'courses.slug', 'courses.thumbnail']),
                'curriculums' => fn ($query) => $query
                    ->where('curriculums.status', 'published')
                    ->orderBy('curriculums.name')
                    ->select(['curriculums.id', 'curriculums.name', 'curriculums.slug', 'curriculums.thumbnail']),
                'assessments' => fn ($query) => $query
                    ->forSale()
                    ->orderBy('assessments.title')
                    ->select(['assessments.id', 'assessments.title', 'assessments.slug', 'assessments.thumbnail']),
            ])
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            abort(404, 'ไม่พบบทความนี้');
        }

        return response()->json([
            'data' => $this->articlePayload($article, withContent: true),
        ]);
    }

    public function reviews(): JsonResponse
    {
        $reviews = Testimonial::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (Testimonial $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'content' => $review->content,
                'image_url' => $review->image_url,
            ])
            ->values();

        return response()->json([
            'data' => $reviews,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bannerPayload(Content $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'excerpt' => $banner->excerpt,
            'image_url' => $banner->image_url,
            'link_url' => $banner->link_url,
            'placement' => $banner->placement,
            'placement_label' => $banner->placement_label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function articlePayload(Content $article, bool $withContent = false): array
    {
        $payload = [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'image_url' => $article->image_url,
            'seo' => $this->articleSeoPayload($article),
        ];

        if ($withContent) {
            $payload['content'] = ArticleHtml::clean($article->content);
            $payload['related'] = [
                'courses' => $article->courses
                    ->map(fn ($course) => [
                        'id' => $course->id,
                        'slug' => $course->slug,
                        'name' => $course->name,
                        'thumbnail_url' => $course->thumbnail_url,
                    ])
                    ->values(),
                'curriculums' => $article->curriculums
                    ->map(fn ($curriculum) => [
                        'id' => $curriculum->id,
                        'slug' => $curriculum->slug,
                        'name' => $curriculum->name,
                        'thumbnail_url' => $curriculum->thumbnail_url,
                    ])
                    ->values(),
                'exams' => $article->assessments
                    ->map(fn ($exam) => [
                        'id' => $exam->id,
                        'slug' => $exam->slug,
                        'name' => $exam->title,
                        'thumbnail_url' => $exam->thumbnail_url,
                    ])
                    ->values(),
            ];
        }

        return $payload;
    }

    /**
     * @return array{title: string, description: ?string, keywords: ?string, image_url: ?string}
     */
    private function articleSeoPayload(Content $article): array
    {
        $title = filled($article->seo_title) ? (string) $article->seo_title : (string) $article->title;
        $description = filled($article->seo_description)
            ? (string) $article->seo_description
            : ($article->excerpt !== null && $article->excerpt !== '' ? (string) $article->excerpt : null);
        $keywords = filled($article->seo_keywords) ? (string) $article->seo_keywords : null;

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'image_url' => $article->image_url,
        ];
    }
}

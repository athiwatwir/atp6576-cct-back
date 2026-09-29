<?php

namespace App\Http\Controllers\Api;

use App\Enums\BannerPlacement;
use App\Http\Controllers\Controller;
use App\Models\Content;
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
            ->orderByDesc('published_at')
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
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            abort(404, 'ไม่พบบทความนี้');
        }

        return response()->json([
            'data' => $this->articlePayload($article, withContent: true),
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
            'published_at' => $article->published_at?->toIso8601String(),
        ];

        if ($withContent) {
            $payload['content'] = ArticleHtml::clean($article->content);
        }

        return $payload;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Articles\ArticleRequest;
use App\Models\Assessment;
use App\Models\Content;
use App\Models\Course;
use App\Models\Curriculum;
use App\Support\ContentAdmin;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private readonly ContentAdmin $contents) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $articles = Content::query()
            ->articles()
            ->withCount(['courses', 'curriculums', 'assessments'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.articles.index', [
            'title' => 'บทความ',
            'articles' => $articles,
            'filters' => compact('search', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('pages.articles.create', [
            'title' => 'เพิ่มบทความ',
            ...$this->formData(),
        ]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $article = $this->contents->create(
            'article',
            $request->validated(),
            $request->file('image'),
            $request->user()?->id,
        );

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'เพิ่มบทความเรียบร้อยแล้ว');
    }

    public function show(Content $article): View
    {
        $this->ensureArticle($article);
        $article->load([
            'courses:id,name,slug',
            'curriculums:id,name,slug',
            'assessments:id,title,slug',
        ]);

        return view('pages.articles.show', [
            'title' => $article->title,
            'article' => $article,
        ]);
    }

    public function edit(Content $article): View
    {
        $this->ensureArticle($article);
        $article->load(['courses:id', 'curriculums:id', 'assessments:id']);

        return view('pages.articles.edit', [
            'title' => 'แก้ไขบทความ',
            'article' => $article,
            ...$this->formData(),
        ]);
    }

    public function update(ArticleRequest $request, Content $article): RedirectResponse
    {
        $this->ensureArticle($article);

        $this->contents->update(
            $article,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
            $request->user()?->id,
        );

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'บันทึกบทความเรียบร้อยแล้ว');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $path = MediaStorage::storeImage($request->file('image'), 'articles/body');

        return response()->json([
            'url' => MediaStorage::url($path),
        ]);
    }

    public function destroy(Content $article): RedirectResponse
    {
        $this->ensureArticle($article);
        $this->contents->delete($article);

        return redirect()
            ->route('articles.index')
            ->with('success', 'ลบบทความเรียบร้อยแล้ว');
    }

    /**
     * @return array{courses: \Illuminate\Database\Eloquent\Collection<int, Course>, curriculums: \Illuminate\Database\Eloquent\Collection<int, Curriculum>, exams: \Illuminate\Database\Eloquent\Collection<int, Assessment>}
     */
    private function formData(): array
    {
        return [
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'curriculums' => Curriculum::query()->orderBy('name')->get(['id', 'name']),
            'exams' => Assessment::query()->where('type', 'primary_exam')->orderBy('title')->get(['id', 'title']),
        ];
    }

    private function ensureArticle(Content $article): void
    {
        abort_unless($article->type === 'article', 404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ContentStatus;
use App\Http\Requests\Curriculums\StoreCurriculumRequest;
use App\Http\Requests\Curriculums\UpdateCurriculumRequest;
use App\Models\Assessment;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Product;
use App\Models\Subject;
use App\Models\Video;
use App\Support\Audit;
use App\Support\CourseVideoUpload;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $curriculums = Curriculum::query()
            ->with('category')
            ->withCount([
                'courses',
                'chapters',
                'videos',
                'products as books_count',
                'assessments as exams_count' => fn ($query) => $query->whereIn('type', ['exam', 'primary_exam']),
                'assessments as exercises_count' => fn ($query) => $query->where('type', 'quiz'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.curriculums.index', [
            'title' => 'หลักสูตร',
            'curriculums' => $curriculums,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.curriculums.create', [
            'title' => 'เพิ่มหลักสูตร',
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreCurriculumRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['thumbnail']);

        $curriculum = Curriculum::query()->create([
            ...$this->payload($data),
            'slug' => $this->uniqueSlug($data['name']),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        if ($request->hasFile('thumbnail')) {
            $curriculum->update([
                'thumbnail' => MediaStorage::storeCurriculumThumbnail($request->file('thumbnail'), $curriculum->id),
            ]);
        }

        return redirect()
            ->route('curriculums.show', $curriculum)
            ->with('success', 'เพิ่มหลักสูตรเรียบร้อยแล้ว เลือกเนื้อหาที่ต้องการใส่แพ็กเกจได้เลย');
    }

    public function show(Curriculum $curriculum): View
    {
        $curriculum->load('category')->loadCount([
            'courses',
            'chapters',
            'videos',
            'products',
            'assessments as exams_count' => fn ($query) => $query->whereIn('assessments.type', ['exam', 'primary_exam']),
            'assessments as exercises_count' => fn ($query) => $query->where('assessments.type', 'quiz'),
        ]);

        return view('pages.curriculums.show', [
            'title' => $curriculum->name,
            'curriculum' => $curriculum,
            'catalog' => $this->simpleCatalog(),
            'attached' => [
                'book' => $curriculum->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all(),
            ],
        ]);
    }

    public function attached(Request $request, Curriculum $curriculum): JsonResponse
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(['course', 'chapter', 'video', 'exam', 'exercise', 'book'])],
        ])['type'];

        $items = match ($type) {
            'course' => $curriculum->courses()->with(['subject:id,name', 'instructor:id,name'])->withCount('chapters')->get()
                ->map(fn (Course $course) => $this->courseCard($course)),
            'chapter' => $curriculum->chapters()->with('course:id,name,thumbnail')->withCount('videos')->get()
                ->map(fn (Chapter $chapter) => $this->chapterCard($chapter)),
            'video' => $curriculum->videos()->with('chapters.course:id,name,thumbnail')->get()
                ->map(fn (Video $video) => $this->videoCard($video)),
            'exam' => $curriculum->assessments()->with(['courses:id,name', 'assessmentCourses.chapter:id,title'])->withCount('questions')->whereIn('assessments.type', ['exam', 'primary_exam'])->get()
                ->map(fn (Assessment $item) => $this->assessmentCard($item)),
            'exercise' => $curriculum->assessments()->with(['courses:id,name', 'assessmentCourses.chapter:id,title'])->withCount('questions')->where('assessments.type', 'quiz')->get()
                ->map(fn (Assessment $item) => $this->assessmentCard($item)),
            'book' => $curriculum->products()->get()
                ->map(fn (Product $book) => $this->bookCard($book)),
        };

        return response()->json([
            'data' => $items->values(),
        ]);
    }

    public function storeVideo(Request $request, Curriculum $curriculum): JsonResponse|RedirectResponse
    {
        if ($response = CourseVideoUpload::guard($request)) {
            return $response;
        }

        $data = $request->validate(CourseVideoUpload::rules(), CourseVideoUpload::messages());

        $video = CourseVideoUpload::storeStandalone($data, $request->file('video_file'));
        $sort = (int) $curriculum->videos()->max('curriculum_videos.sort_order');
        $curriculum->videos()->syncWithoutDetaching([
            $video->id => ['sort_order' => $sort + 1],
        ]);

        Audit::log(
            action: 'curriculum.items_attached',
            entity: $curriculum,
            new: ['type' => 'video', 'ids' => [$video->id]],
            description: 'อัปโหลดวิดีโอในหลักสูตร '.$curriculum->name,
        );

        return CourseVideoUpload::successResponse(
            $request,
            'อัปโหลดวิดีโอและเพิ่มในหลักสูตรเรียบร้อยแล้ว',
            route('curriculums.show', ['curriculum' => $curriculum, 'tab' => 'video']),
        );
    }

    public function destroyVideo(Curriculum $curriculum, Video $video): RedirectResponse
    {
        abort_unless($curriculum->videos()->where('videos.id', $video->id)->exists(), 404);

        if ($video->chapters()->exists()) {
            return redirect()
                ->route('curriculums.show', ['curriculum' => $curriculum, 'tab' => 'video'])
                ->with('error', 'ลบได้เฉพาะวิดีโอเดี่ยว วิดีโอที่อยู่ในคอร์สให้ใช้ปุ่มนำออก');
        }

        MediaStorage::delete($video->storage_key);
        MediaStorage::delete($video->thumbnail);
        $video->curriculums()->detach();
        $video->delete();

        Audit::log(
            action: 'curriculum.video_deleted',
            entity: $curriculum,
            old: ['id' => $video->id, 'title' => $video->title],
            description: 'ลบวิดีโอเดี่ยว '.$video->title,
        );

        return redirect()
            ->route('curriculums.show', ['curriculum' => $curriculum, 'tab' => 'video'])
            ->with('success', 'ลบวิดีโอเดี่ยวเรียบร้อยแล้ว');
    }

    public function catalog(Request $request, Curriculum $curriculum): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['course', 'chapter', 'video', 'exam', 'exercise'])],
            'search' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer'],
            'course_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'inactive'])],
            'exam_type' => ['nullable', Rule::in(['exam', 'primary_exam'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim((string) ($data['search'] ?? ''));
        $subjectId = isset($data['subject_id']) ? (int) $data['subject_id'] : null;
        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        $status = $data['status'] ?? null;
        $examType = $data['exam_type'] ?? null;

        if (in_array($data['type'], ['chapter', 'exam', 'exercise'], true) && ! $courseId) {
            return response()->json([
                'data' => [],
                'filters' => $this->catalogFilters(),
                'requires_course' => true,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 12,
                ],
            ]);
        }

        $paginator = match ($data['type']) {
            'course' => $this->courseCatalog($curriculum, $search, $subjectId, $status),
            'chapter' => $this->chapterCatalog($curriculum, $search, $courseId),
            'video' => $this->videoCatalog($curriculum, $search, $courseId),
            'exam' => $this->examCatalog($curriculum, $search, $courseId, $status, $examType),
            'exercise' => $this->exerciseCatalog($curriculum, $search, $courseId, $status),
        };

        return response()->json([
            'data' => $paginator->getCollection()->values(),
            'filters' => $this->catalogFilters(),
            'requires_course' => false,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function edit(Curriculum $curriculum): View
    {
        return view('pages.curriculums.edit', [
            'title' => 'แก้ไขหลักสูตร',
            'curriculum' => $curriculum,
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $data = $request->validated();
        unset($data['thumbnail'], $data['remove_thumbnail']);

        $payload = $this->payload($data);

        if ($curriculum->name !== $data['name']) {
            $payload['slug'] = $this->uniqueSlug($data['name'], $curriculum->id);
        }

        if ($data['status'] === 'published') {
            $payload['published_at'] = $curriculum->published_at ?? now();
        } elseif ($data['status'] === 'draft') {
            $payload['published_at'] = null;
        }

        if ($request->boolean('remove_thumbnail') && $curriculum->thumbnail) {
            MediaStorage::delete($curriculum->thumbnail);
            $payload['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $newPath = MediaStorage::curriculumThumbnailPath($curriculum->id);
            if ($curriculum->thumbnail && $curriculum->thumbnail !== $newPath) {
                MediaStorage::delete($curriculum->thumbnail);
            }
            $payload['thumbnail'] = MediaStorage::storeCurriculumThumbnail($request->file('thumbnail'), $curriculum->id);
        }

        $curriculum->update($payload);

        return redirect()
            ->route('curriculums.show', $curriculum)
            ->with('success', 'บันทึกหลักสูตรเรียบร้อยแล้ว');
    }

    public function destroy(Curriculum $curriculum): RedirectResponse
    {
        MediaStorage::delete($curriculum->thumbnail);
        $curriculum->delete();

        return redirect()
            ->route('curriculums.index')
            ->with('success', 'ลบหลักสูตรเรียบร้อยแล้ว');
    }

    public function attachItems(Request $request, Curriculum $curriculum): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['course', 'chapter', 'video', 'exam', 'exercise', 'book'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [], [
            'type' => 'ประเภทเนื้อหา',
            'ids' => 'รายการที่เลือก',
        ]);

        $ids = $this->validIds($data['type'], $data['ids']);

        if ($ids === []) {
            return back()->with('error', 'ไม่พบรายการที่เลือก');
        }

        $existingRelation = $this->relation($curriculum, $data['type']);
        $existing = $existingRelation
            ->pluck($existingRelation->getRelated()->getQualifiedKeyName())
            ->map(fn ($id) => (int) $id)
            ->all();
        $sortRelation = $this->relation($curriculum, $data['type']);
        $sort = (int) $sortRelation->max($sortRelation->getTable().'.sort_order');
        $attach = [];

        foreach ($ids as $id) {
            if (in_array($id, $existing, true)) {
                continue;
            }

            $sort++;
            $attach[$id] = ['sort_order' => $sort];
        }

        if ($attach !== []) {
            $this->relation($curriculum, $data['type'])->attach($attach);
            Audit::log(
                action: 'curriculum.items_attached',
                entity: $curriculum,
                new: ['type' => $data['type'], 'ids' => array_keys($attach)],
                description: 'เพิ่ม'.$this->typeLabel($data['type']).'ในหลักสูตร '.$curriculum->name,
            );
        }

        return redirect()
            ->route('curriculums.show', ['curriculum' => $curriculum, 'tab' => $data['type']])
            ->with('success', 'เพิ่มเนื้อหาเรียบร้อยแล้ว');
    }

    public function detachItem(Request $request, Curriculum $curriculum): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['course', 'chapter', 'video', 'exam', 'exercise', 'book'])],
            'id' => ['required', 'integer'],
        ]);

        $this->relation($curriculum, $data['type'])->detach($data['id']);

        Audit::log(
            action: 'curriculum.item_detached',
            entity: $curriculum,
            old: ['type' => $data['type'], 'id' => (int) $data['id']],
            description: 'นำ'.$this->typeLabel($data['type']).'ออกจากหลักสูตร '.$curriculum->name,
        );

        return redirect()
            ->route('curriculums.show', ['curriculum' => $curriculum, 'tab' => $data['type']])
            ->with('success', 'นำรายการออกจากหลักสูตรแล้ว');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'category_id' => $data['category_id'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'price' => $data['price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }

    private function relation(Curriculum $curriculum, string $type): BelongsToMany
    {
        return match ($type) {
            'course' => $curriculum->courses(),
            'chapter' => $curriculum->chapters(),
            'video' => $curriculum->videos(),
            'exam', 'exercise' => $curriculum->assessments(),
            'book' => $curriculum->products(),
        };
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function validIds(string $type, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $query = match ($type) {
            'course' => Course::query()->whereIn('id', $ids),
            'chapter' => Chapter::query()->whereIn('id', $ids),
            'video' => Video::query()->whereIn('id', $ids),
            'exam' => Assessment::query()->whereIn('id', $ids)->whereIn('type', ['exam', 'primary_exam']),
            'exercise' => Assessment::query()->whereIn('id', $ids)->where('type', 'quiz'),
            'book' => Product::query()->whereIn('id', $ids)->where('type', 'book'),
        };

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{subjects: \Illuminate\Support\Collection, courses: \Illuminate\Support\Collection, statuses: list<array{value: string, label: string}>}
     */
    private function catalogFilters(): array
    {
        return [
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => ContentStatus::filterOptions(['draft', 'published', 'inactive']),
            'exam_types' => [
                ['value' => 'primary_exam', 'label' => 'ข้อสอบขายแยก'],
                ['value' => 'exam', 'label' => 'ข้อสอบในคอร์ส'],
            ],
        ];
    }

    private function courseCatalog(Curriculum $curriculum, string $search, ?int $subjectId, ?string $status)
    {
        return Course::query()
            ->with(['subject:id,name', 'instructor:id,name'])
            ->withCount('chapters')
            ->whereNotIn('id', function ($query) use ($curriculum) {
                $query->select('course_id')->from('curriculum_courses')->where('curriculum_id', $curriculum->id);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%");
                });
            })
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->through(fn (Course $course) => $this->courseCard($course));
    }

    /**
     * @return array{id:int,title:string,code:?string,description:?string,thumbnail:?string,badges:list<?string>}
     */
    private function courseCard(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->name,
            'code' => $course->code,
            'description' => $this->previewText($course->short_description, $course->description),
            'thumbnail' => $course->thumbnail_url,
            'badges' => array_values(array_filter([
                $course->subject?->name,
                $course->instructor?->name,
                $this->courseStatusLabel($course->status),
                $course->chapters_count.' บท',
                $this->priceBadge($course),
            ])),
        ];
    }

    private function chapterCatalog(Curriculum $curriculum, string $search, int $courseId)
    {
        return Chapter::query()
            ->with('course:id,name,thumbnail')
            ->withCount('videos')
            ->whereNotIn('id', function ($query) use ($curriculum) {
                $query->select('chapter_id')->from('curriculum_chapters')->where('curriculum_id', $curriculum->id);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$search}%"));
                });
            })
            ->where('course_id', $courseId)
            ->orderBy('course_id')
            ->orderBy('seq')
            ->paginate(12)
            ->through(fn (Chapter $chapter) => $this->chapterCard($chapter));
    }

    /**
     * @return array{id:int,title:string,code:?string,description:?string,thumbnail:?string,badges:list<?string>}
     */
    private function chapterCard(Chapter $chapter): array
    {
        return [
            'id' => $chapter->id,
            'title' => $chapter->title,
            'code' => $chapter->course?->name,
            'description' => $this->previewText(null, $chapter->description),
            'thumbnail' => $chapter->course?->thumbnail_url,
            'badges' => array_values(array_filter([
                $chapter->seq ? 'บทที่ '.$chapter->seq : null,
                $chapter->videos_count.' วิดีโอ',
            ])),
        ];
    }

    private function videoCatalog(Curriculum $curriculum, string $search, ?int $courseId)
    {
        return Video::query()
            ->with('chapters.course:id,name,thumbnail')
            ->whereNotIn('id', function ($query) use ($curriculum) {
                $query->select('video_id')->from('curriculum_videos')->where('curriculum_id', $curriculum->id);
            })
            ->when($courseId, fn ($query) => $query->whereHas(
                'chapters',
                fn ($chapter) => $chapter->where('course_id', $courseId),
            ))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('title')
            ->paginate(12)
            ->through(fn (Video $video) => $this->videoCard($video));
    }

    /**
     * @return array{id:int,title:string,code:?string,description:?string,thumbnail:?string,badges:list<?string>}
     */
    private function videoCard(Video $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'code' => null,
            'description' => $this->previewText(null, $video->description),
            'thumbnail' => null,
            'url' => $video->url,
            'standalone' => $video->chapters->isEmpty(),
            'badges' => array_values(array_filter([
                $video->chapters->isEmpty() ? 'วิดีโอเดี่ยว' : 'มาจากคอร์ส',
                $this->durationLabel($video->duration_seconds),
                $video->is_free ? 'ดูฟรี' : null,
            ])),
        ];
    }

    private function previewText(?string $short, ?string $long): ?string
    {
        $text = trim(strip_tags((string) ($short ?: $long)));

        return $text === '' ? null : Str::limit($text, 160);
    }

    private function courseStatusLabel(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return ContentStatus::labelFor($status);
    }

    private function priceBadge(Course $course): string
    {
        if ($course->sale_price !== null && (float) $course->sale_price > 0) {
            return '฿'.number_format((float) $course->sale_price, 0).' จาก ฿'.number_format((float) $course->price, 0);
        }

        if ((float) $course->price > 0) {
            return '฿'.number_format((float) $course->price, 0);
        }

        return 'ฟรี';
    }

    private function durationLabel(?int $seconds): ?string
    {
        if (! $seconds) {
            return null;
        }

        $minutes = intdiv($seconds, 60);
        $remain = $seconds % 60;

        if ($minutes >= 60) {
            return intdiv($minutes, 60).' ชม. '.($minutes % 60).' นาที';
        }

        if ($minutes > 0) {
            return $remain > 0 ? $minutes.' นาที '.$remain.' วินาที' : $minutes.' นาที';
        }

        return $remain.' วินาที';
    }

    private function examCatalog(Curriculum $curriculum, string $search, int $courseId, ?string $status, ?string $examType)
    {
        return Assessment::query()
            ->with(['courses:id,name', 'assessmentCourses.chapter:id,title'])
            ->withCount('questions')
            ->whereNotIn('id', function ($query) use ($curriculum) {
                $query->select('assessment_id')->from('curriculum_assessments')->where('curriculum_id', $curriculum->id);
            })
            ->where(function ($query) use ($courseId, $examType) {
                if ($examType === 'primary_exam') {
                    $query->where('type', 'primary_exam');

                    return;
                }

                $query->where(function ($linked) use ($courseId) {
                    $linked->where('type', 'exam')
                        ->whereHas('courses', fn ($course) => $course->where('courses.id', $courseId));
                });

                if ($examType !== 'exam') {
                    $query->orWhere('type', 'primary_exam');
                }
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('courses', fn ($course) => $course->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('title')
            ->paginate(12)
            ->through(fn (Assessment $exam) => $this->assessmentCard($exam));
    }

    private function exerciseCatalog(Curriculum $curriculum, string $search, int $courseId, ?string $status)
    {
        return Assessment::query()
            ->with(['courses:id,name', 'assessmentCourses.chapter:id,title'])
            ->withCount('questions')
            ->where('type', 'quiz')
            ->whereNotIn('id', function ($query) use ($curriculum) {
                $query->select('assessment_id')->from('curriculum_assessments')->where('curriculum_id', $curriculum->id);
            })
            ->whereHas('courses', fn ($course) => $course->where('courses.id', $courseId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('courses', fn ($course) => $course->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('title')
            ->paginate(12)
            ->through(fn (Assessment $item) => $this->assessmentCard($item));
    }

    /**
     * @return array{id:int,title:string,code:?string,description:?string,thumbnail:?string,badges:list<string>}
     */
    private function assessmentCard(Assessment $item): array
    {
        $kind = match ($item->type) {
            'primary_exam' => 'ข้อสอบขายแยก',
            'quiz' => 'แบบฝึกหัด',
            default => 'ข้อสอบในคอร์ส',
        };

        return [
            'id' => $item->id,
            'title' => $item->title,
            'code' => $item->code,
            'description' => $this->previewText(null, $item->description),
            'thumbnail' => $item->thumbnail_url,
            'badges' => array_values(array_filter([
                $kind,
                $this->courseStatusLabel($item->status),
                $this->examPlace($item),
                $item->duration_minutes ? $item->duration_minutes.' นาที' : 'ไม่จำกัดเวลา',
                $item->questions_count.' คำถาม',
                $item->passing_score !== null ? 'เกณฑ์ผ่าน '.rtrim(rtrim(number_format((float) $item->passing_score, 2), '0'), '.') : null,
                $item->max_attempts ? 'ทำได้ '.$item->max_attempts.' ครั้ง' : 'ไม่จำกัดครั้ง',
                $item->type === 'primary_exam' ? $this->assessmentPriceBadge($item) : null,
            ])),
        ];
    }

    private function examPlace(Assessment $exam): ?string
    {
        $place = $exam->courses
            ->map(function ($course) use ($exam) {
                $chapter = $exam->assessmentCourses->firstWhere('course_id', $course->id)?->chapter?->title;

                return $chapter ? $course->name.' / '.$chapter : $course->name;
            })
            ->filter()
            ->unique()
            ->implode(', ');

        return $place !== '' ? $place : null;
    }

    /**
     * @return array{id:int,title:string,code:?string,description:?string,thumbnail:?string,badges:list<string>}
     */
    private function bookCard(Product $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->name,
            'code' => $book->code,
            'description' => $this->previewText(null, $book->description),
            'thumbnail' => $book->thumbnail_url,
            'badges' => array_values(array_filter([
                $book->sale_price !== null && (float) $book->sale_price > 0
                    ? '฿'.number_format((float) $book->sale_price, 0).' จาก ฿'.number_format((float) $book->price, 0)
                    : ((float) $book->price > 0 ? '฿'.number_format((float) $book->price, 0) : 'ฟรี'),
            ])),
        ];
    }

    private function assessmentPriceBadge(Assessment $exam): string
    {
        if ((float) $exam->price > 0) {
            return '฿'.number_format((float) $exam->price, 0);
        }

        return 'ฟรี';
    }

    /**
     * @return array<string, list<array{id:int,title:string,meta:?string}>>
     */
    private function simpleCatalog(): array
    {
        return [
            'book' => Product::query()->books()->orderBy('name')->get()
                ->map(fn (Product $book) => [
                    'id' => $book->id,
                    'title' => $book->name,
                    'meta' => $book->code,
                ])->all(),
        ];
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'course' => 'คอร์ส',
            'chapter' => 'บทเรียน',
            'video' => 'วิดีโอ',
            'exam' => 'ข้อสอบ',
            'exercise' => 'แบบฝึกหัด',
            'book' => 'หนังสือ',
            default => 'เนื้อหา',
        };
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'curriculum';
        $slug = $base;
        $counter = 1;

        while (
            Curriculum::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categories()
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }
}

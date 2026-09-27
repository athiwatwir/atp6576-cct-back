<?php

namespace App\Http\Controllers;

use App\Http\Requests\Instructors\StoreInstructorRequest;
use App\Http\Requests\Instructors\UpdateInstructorRequest;
use App\Models\Instructor;
use App\Models\User;
use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $instructors = Instructor::query()
            ->with('user')
            ->withCount('courses')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('bio', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('pages.instructors.index', [
            'title' => 'ครูผู้สอน',
            'instructors' => $instructors,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.instructors.create', [
            'title' => 'เพิ่มครูผู้สอน',
            'users' => $this->linkableUsers(),
        ]);
    }

    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['user_id'] = $data['user_id'] ?: null;

        unset($data['image'], $data['remove_image']);

        $instructor = Instructor::query()->create($data);

        if ($request->hasFile('image')) {
            $instructor->update([
                'image' => MediaStorage::storeInstructorImage(
                    $request->file('image'),
                    $instructor->id
                ),
            ]);
        }

        return redirect()
            ->route('instructors.index')
            ->with('success', 'เพิ่มครูผู้สอนเรียบร้อยแล้ว');
    }

    public function edit(Instructor $instructor): View
    {
        $instructor->load('user');

        return view('pages.instructors.edit', [
            'title' => 'แก้ไขครูผู้สอน',
            'instructor' => $instructor,
            'users' => $this->linkableUsers($instructor->user_id),
        ]);
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $data['user_id'] ?: null;

        if ($instructor->name !== $data['name']) {
            $data['slug'] = $this->uniqueSlug($data['name'], $instructor->id);
        }

        if ($request->boolean('remove_image') && $instructor->image) {
            MediaStorage::delete($instructor->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            $newPath = MediaStorage::instructorImagePath($instructor->id);

            if ($instructor->image && $instructor->image !== $newPath) {
                MediaStorage::delete($instructor->image);
            }

            $data['image'] = MediaStorage::storeInstructorImage(
                $request->file('image'),
                $instructor->id
            );
        }

        unset($data['remove_image']);

        $instructor->update($data);

        return redirect()
            ->route('instructors.index')
            ->with('success', 'บันทึกข้อมูลครูผู้สอนเรียบร้อยแล้ว');
    }

    public function destroy(Instructor $instructor): RedirectResponse
    {
        if ($instructor->courses()->exists()) {
            return redirect()
                ->route('instructors.index')
                ->with('error', 'ไม่สามารถลบครูผู้สอนที่มีคอร์สผูกอยู่ได้');
        }

        MediaStorage::delete($instructor->image);
        $instructor->delete();

        return redirect()
            ->route('instructors.index')
            ->with('success', 'ลบครูผู้สอนเรียบร้อยแล้ว');
    }

    /**
     * @return Collection<int, User>
     */
    private function linkableUsers(?int $currentUserId = null)
    {
        return User::query()
            ->where(function ($query) use ($currentUserId) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'instructor'));

                if ($currentUserId) {
                    $query->orWhere('id', $currentUserId);
                }
            })
            ->where(function ($query) use ($currentUserId) {
                $query->whereDoesntHave('instructor');

                if ($currentUserId) {
                    $query->orWhere('id', $currentUserId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'instructor';
        }

        $slug = $base;
        $counter = 1;

        while (
            Instructor::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}

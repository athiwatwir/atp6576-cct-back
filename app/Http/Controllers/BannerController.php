<?php

namespace App\Http\Controllers;

use App\Http\Requests\Banners\BannerRequest;
use App\Models\Content;
use App\Support\ContentAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private readonly ContentAdmin $contents) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $placement = $request->string('placement')->toString();

        $banners = Content::query()
            ->banners()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($placement !== '', fn ($query) => $query->where('placement', $placement))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('pages.banners.index', [
            'title' => 'แบนเนอร์',
            'banners' => $banners,
            'filters' => compact('search', 'status', 'placement'),
        ]);
    }

    public function create(): View
    {
        return view('pages.banners.create', [
            'title' => 'เพิ่มแบนเนอร์',
            'banner' => new Content([
                'placement' => 'home',
                'status' => 'draft',
                'sort_order' => ((int) Content::query()->banners()->max('sort_order')) + 1,
            ]),
        ]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $banner = $this->contents->create(
            'banner',
            $request->validated(),
            $request->file('image'),
            $request->user()?->id,
        );

        return redirect()
            ->route('banners.show', $banner)
            ->with('success', 'เพิ่มแบนเนอร์เรียบร้อยแล้ว');
    }

    public function show(Content $banner): View
    {
        $this->ensureBanner($banner);

        return view('pages.banners.show', [
            'title' => $banner->title,
            'banner' => $banner,
        ]);
    }

    public function edit(Content $banner): View
    {
        $this->ensureBanner($banner);

        return view('pages.banners.edit', [
            'title' => 'แก้ไขแบนเนอร์',
            'banner' => $banner,
        ]);
    }

    public function update(BannerRequest $request, Content $banner): RedirectResponse
    {
        $this->ensureBanner($banner);

        $this->contents->update(
            $banner,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
            $request->user()?->id,
        );

        return redirect()
            ->route('banners.show', $banner)
            ->with('success', 'บันทึกแบนเนอร์เรียบร้อยแล้ว');
    }

    public function destroy(Content $banner): RedirectResponse
    {
        $this->ensureBanner($banner);
        $this->contents->delete($banner);

        return redirect()
            ->route('banners.index')
            ->with('success', 'ลบแบนเนอร์เรียบร้อยแล้ว');
    }

    public function move(Request $request, Content $banner): RedirectResponse
    {
        $this->ensureBanner($banner);

        $direction = $request->string('direction')->toString();
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        $this->contents->moveBanner($banner, $direction);

        return redirect()
            ->route('banners.index', $request->only(['search', 'status', 'placement']))
            ->with('success', 'จัดลำดับแบนเนอร์แล้ว');
    }

    private function ensureBanner(Content $banner): void
    {
        abort_unless($banner->type === 'banner', 404);
    }
}

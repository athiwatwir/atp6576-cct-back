<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Support\ArticleHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * หน้าข้อความคงที่ของเว็บนักเรียน (เช่น ข้อกำหนดและเงื่อนไข) แก้ไขได้จากหลังบ้าน
 * เก็บในตาราง contents: type = page, placement = คีย์ของหน้า
 */
class SitePageController extends Controller
{
    /** คีย์ที่ใช้ใน URL และ API => ชื่อหน้า */
    public const PAGES = [
        'terms' => 'ข้อกำหนดและเงื่อนไข',
    ];

    public function edit(string $page): View
    {
        $title = self::PAGES[$page] ?? abort(404);

        return view('pages.settings.site-page', [
            'title' => $title,
            'pageKey' => $page,
            'page' => self::find($page),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $defaultTitle = self::PAGES[$page] ?? abort(404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'content' => ['nullable', 'string', 'max:200000'],
        ], [], [
            'title' => 'หัวข้อ',
            'status' => 'สถานะ',
            'content' => 'เนื้อหา',
        ]);

        $record = self::find($page) ?? new Content([
            'type' => 'page',
            'placement' => $page,
            'slug' => 'page-'.$page,
            'created_by' => $request->user()?->id,
        ]);

        $record->fill([
            'title' => trim($data['title']) ?: $defaultTitle,
            'status' => $data['status'],
            'content' => ArticleHtml::clean($data['content'] ?? null),
            'updated_by' => $request->user()?->id,
        ]);
        if ($record->status === 'published' && ! $record->published_at) {
            $record->published_at = now();
        }
        $record->save();

        return redirect()
            ->route('site-pages.edit', $page)
            ->with('success', 'บันทึก'.$defaultTitle.'เรียบร้อยแล้ว');
    }

    public static function find(string $page): ?Content
    {
        return Content::query()
            ->where('type', 'page')
            ->where('placement', $page)
            ->first();
    }
}

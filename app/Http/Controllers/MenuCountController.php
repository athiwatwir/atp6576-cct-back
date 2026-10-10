<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuCountController extends Controller
{
    public function teaching(): JsonResponse
    {
        $counts = Cache::remember('menu.teaching.active-counts', now()->addSeconds(20), function () {
            $row = DB::selectOne(<<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM subjects WHERE status = 'active' AND deleted_at IS NULL) AS subjects,
                    (SELECT COUNT(*) FROM courses WHERE status = 'published' AND deleted_at IS NULL) AS courses,
                    (SELECT COUNT(*) FROM curriculums WHERE status = 'published' AND deleted_at IS NULL) AS curriculums,
                    (SELECT COUNT(*) FROM assessments WHERE status = 'published' AND is_independent = 1 AND type = 'primary_exam' AND deleted_at IS NULL) AS exams,
                    (SELECT COUNT(*) FROM products WHERE type = 'book' AND status = 'active' AND deleted_at IS NULL) AS books
            SQL);

            return [
                'subjects' => (int) $row->subjects,
                'courses' => (int) $row->courses,
                'curriculums' => (int) $row->curriculums,
                'exams' => (int) $row->exams,
                'books' => (int) $row->books,
            ];
        });

        return response()->json($counts);
    }
}

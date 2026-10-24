<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Support\VideoDirectUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VideoUploadController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        if (! VideoDirectUpload::available()) {
            return response()->json(['direct' => false]);
        }

        $data = $request->validate([
            'scope' => ['required', Rule::in(['course', 'standalone'])],
            'course_id' => ['required_if:scope,course', 'nullable', 'integer'],
            'chapter_id' => ['required_if:scope,course', 'nullable', 'integer'],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ]);

        $target = ['scope' => $data['scope']];
        if ($data['scope'] === 'course') {
            $chapter = Chapter::query()->findOrFail($data['chapter_id']);
            abort_unless($chapter->course_id === (int) $data['course_id'], 404);
            $target += ['course_id' => $chapter->course_id, 'chapter_id' => $chapter->id];
        }

        $upload = VideoDirectUpload::start((int) $request->user()->id, $target, $data['filename'], (int) $data['size']);

        return response()->json([
            'direct' => true,
            ...$upload,
            'urls' => [
                'sign' => route('video-uploads.sign', $upload['token']),
                'complete' => route('video-uploads.complete', $upload['token']),
                'abort' => route('video-uploads.abort', $upload['token']),
            ],
        ]);
    }

    public function sign(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1', 'max:50'],
            'parts.*' => ['integer', 'min:1', 'max:10000'],
        ]);

        return response()->json([
            'urls' => VideoDirectUpload::sign($token, (int) $request->user()->id, array_map('intval', $data['parts'])),
        ]);
    }

    public function complete(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'parts' => ['required', 'array', 'min:1', 'max:10000'],
            'parts.*.part_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'parts.*.etag' => ['required', 'string', 'max:200'],
        ]);

        VideoDirectUpload::complete($token, (int) $request->user()->id, $data['parts']);

        return response()->json(['completed' => true]);
    }

    public function abort(Request $request, string $token): JsonResponse
    {
        VideoDirectUpload::abort($token, (int) $request->user()->id);

        return response()->json(['aborted' => true]);
    }
}

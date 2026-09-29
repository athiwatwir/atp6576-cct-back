<?php

namespace App\Support;

use App\Models\Content;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ContentAdmin
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $type, array $data, ?UploadedFile $image, ?int $userId): Content
    {
        $content = Content::query()->create([
            ...$this->attributes($type, $data),
            'slug' => $this->uniqueSlug($type, (string) $data['title']),
            'sort_order' => $type === 'banner'
                ? (int) ($data['sort_order'] ?? $this->nextSortOrder())
                : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($image) {
            $content->update([
                'image' => MediaStorage::store($image, $type.'s/'.$content->id),
            ]);
        }

        return $content->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Content $content, array $data, ?UploadedFile $image, bool $removeImage, ?int $userId): Content
    {
        $payload = [
            ...$this->attributes($content->type, $data),
            'updated_by' => $userId,
        ];

        if ($content->title !== $data['title']) {
            $payload['slug'] = $this->uniqueSlug($content->type, (string) $data['title'], $content->id);
        }

        if ($content->type === 'banner') {
            $payload['sort_order'] = (int) ($data['sort_order'] ?? $content->sort_order);
            $payload['placement'] = $data['placement'] ?? $content->placement;
        }

        if ($removeImage && $content->image) {
            MediaStorage::delete($content->image);
            $payload['image'] = null;
        }

        if ($image) {
            if ($content->image) {
                MediaStorage::delete($content->image);
            }

            $payload['image'] = MediaStorage::store($image, $content->type.'s/'.$content->id);
        }

        $content->update($payload);

        return $content->refresh();
    }

    public function delete(Content $content): void
    {
        MediaStorage::delete($content->image);
        $content->delete();
    }

    public function moveBanner(Content $banner, string $direction): void
    {
        $banners = Content::query()
            ->banners()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $index = $banners->search(fn (Content $item) => $item->id === $banner->id);

        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($banners[$swapWith])) {
            return;
        }

        $current = $banners->get($index);
        $banners->put($index, $banners->get($swapWith));
        $banners->put($swapWith, $current);

        foreach ($banners->values() as $position => $item) {
            $item->update(['sort_order' => $position + 1]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(string $type, array $data): array
    {
        $publishedAt = $this->date($data['published_at'] ?? null);
        $expiredAt = $this->date($data['expired_at'] ?? null);

        if (($data['status'] ?? null) === 'published' && $publishedAt === null) {
            $publishedAt = now();
        }

        return [
            'type' => $type,
            'placement' => $type === 'banner' ? ($data['placement'] ?? 'home') : null,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $type === 'article' ? ArticleHtml::clean($data['content'] ?? null) : null,
            'link_url' => $type === 'banner' ? ($data['link_url'] ?? null) : null,
            'status' => $data['status'],
            'published_at' => $publishedAt,
            'expired_at' => $expiredAt,
        ];
    }

    private function date(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    private function nextSortOrder(): int
    {
        return ((int) Content::query()->banners()->max('sort_order')) + 1;
    }

    private function uniqueSlug(string $type, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = $type;
        }

        $slug = $base;
        $counter = 1;

        while (
            Content::withTrashed()
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

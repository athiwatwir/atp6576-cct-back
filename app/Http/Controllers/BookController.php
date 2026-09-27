<?php

namespace App\Http\Controllers;

use App\Http\Requests\Books\StoreBookRequest;
use App\Http\Requests\Books\UpdateBookRequest;
use App\Models\Product;
use App\Support\DocumentSequence;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $books = Product::query()
            ->books()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.books.index', [
            'title' => 'หนังสือ',
            'books' => $books,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pages.books.create', [
            'title' => 'เพิ่มหนังสือ',
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        unset($data['thumbnail'], $data['remove_thumbnail']);

        $book = Product::query()->create([
            'code' => DocumentSequence::nextBook(),
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'type' => 'book',
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => $data['stock'] ?? null,
            'status' => $data['status'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $book->update([
                'thumbnail' => MediaStorage::storeBookThumbnail(
                    $request->file('thumbnail'),
                    $book->id
                ),
            ]);
        }

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'เพิ่มหนังสือเรียบร้อยแล้ว');
    }

    public function show(Product $product): View
    {
        $this->ensureBook($product);

        return view('pages.books.show', [
            'title' => $product->name,
            'book' => $product,
        ]);
    }

    public function edit(Product $product): View
    {
        $this->ensureBook($product);

        return view('pages.books.edit', [
            'title' => 'แก้ไขหนังสือ',
            'book' => $product,
        ]);
    }

    public function update(UpdateBookRequest $request, Product $product): RedirectResponse
    {
        $this->ensureBook($product);

        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'type' => 'book',
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => $data['stock'] ?? null,
            'status' => $data['status'],
        ];

        if ($product->name !== $data['name']) {
            $payload['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        if ($request->boolean('remove_thumbnail') && $product->thumbnail) {
            MediaStorage::delete($product->thumbnail);
            $payload['thumbnail'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $newPath = MediaStorage::bookThumbnailPath($product->id);

            if ($product->thumbnail && $product->thumbnail !== $newPath) {
                MediaStorage::delete($product->thumbnail);
            }

            $payload['thumbnail'] = MediaStorage::storeBookThumbnail(
                $request->file('thumbnail'),
                $product->id
            );
        }

        unset($data['thumbnail'], $data['remove_thumbnail']);

        $product->update($payload);

        return redirect()
            ->route('books.show', $product)
            ->with('success', 'บันทึกหนังสือเรียบร้อยแล้ว');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureBook($product);

        MediaStorage::delete($product->thumbnail);
        $product->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'ลบหนังสือเรียบร้อยแล้ว');
    }

    private function ensureBook(Product $product): void
    {
        abort_unless($product->type === 'book', 404);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'book';
        $slug = $base;
        $counter = 1;

        while (
            Product::withTrashed()
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

@php
    /** @var \App\Models\Product|null $book */
    $book = $book ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ชื่อหนังสือ<span class="text-error-500">*</span>
        </label>
        <input type="text" id="name" name="name" value="{{ old('name', $book?->name) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('name')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            สถานะ<span class="text-error-500">*</span>
        </label>
        <select id="status" name="status" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            @foreach (\App\Enums\ContentStatus::options(['draft', 'active', 'inactive']) as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $book?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="stock" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            จำนวนคงเหลือ
        </label>
        <input type="number" id="stock" name="stock" min="0" value="{{ old('stock', $book?->stock) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ว่าง = ไม่จำกัดสต็อก</p>
        @error('stock')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคาปกติ (บาท)<span class="text-error-500">*</span>
        </label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $book?->price ?? 0) }}" required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        @error('price')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sale_price" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ราคาลด (บาท)
        </label>
        <input type="number" id="sale_price" name="sale_price" min="0" step="0.01" value="{{ old('sale_price', $book?->sale_price) }}"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">ถ้าระบุ ต้องไม่สูงกว่าราคาปกติ</p>
        @error('sale_price')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="thumbnail" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            ปกหนังสือ
        </label>
        <input type="file" id="thumbnail" name="thumbnail" accept="image/*"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            รองรับ JPG/PNG/WebP/GIF ไม่เกิน 2MB — ระบบจะแปลงเป็น WebP และเก็บที่
            <code class="rounded bg-gray-100 px-1 dark:bg-white/10">books/{id}/{id}.webp</code>
        </p>
        @error('thumbnail')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($book?->thumbnail)
            <div class="mt-3 flex items-center gap-3">
                <img src="{{ $book->thumbnail_url }}" alt="{{ $book->name }}" class="h-24 w-16 rounded-xl border border-gray-200 object-cover dark:border-gray-700" />
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_thumbnail" value="1" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                    ลบปกปัจจุบัน
                </label>
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="images" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รูปภาพเพิ่มเติม
        </label>
        <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">เลือกได้หลายรูป JPG, PNG, WebP หรือ GIF รูปละไม่เกิน 4 MB รวมไม่เกิน 20 รูป</p>
        @error('images')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
        @error('images.*')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($book?->images?->isNotEmpty())
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($book->images as $image)
                    <label class="block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                        <img src="{{ $image->url }}" alt="" class="h-28 w-full object-cover" />
                        <span class="flex items-center gap-2 px-2 py-2 text-xs text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                            ลบรูปนี้
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="video" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            วิดีโอ
        </label>
        <input type="file" id="video" name="video" accept="video/mp4,video/webm,video/quicktime"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 block w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">MP4, WebM หรือ MOV ไม่เกิน 500 MB ใส่ได้หนึ่งไฟล์ อัปโหลดใหม่จะแทนที่ไฟล์เดิม</p>
        @error('video')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror

        @if ($book?->video)
            <div class="mt-3 max-w-xl">
                <video src="{{ $book->video->url }}" controls class="w-full rounded-xl border border-gray-200 bg-black dark:border-gray-700"></video>
                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_video" value="1" class="text-brand-500 focus:ring-brand-500/20 h-4 w-4 rounded border-gray-300 dark:border-gray-700" />
                    ลบวิดีโอปัจจุบัน
                </label>
            </div>
        @endif
    </div>

    <div class="md:col-span-2">
        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            รายละเอียด
        </label>
        <textarea id="description" name="description" rows="5"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description', $book?->description) }}</textarea>
        @error('description')
            <p class="mt-1.5 text-sm text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>

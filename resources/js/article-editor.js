import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';

export function initArticleEditors() {
    document.querySelectorAll('[data-article-editor]').forEach((root) => mountEditor(root));
}

function mountEditor(root) {
    const source = document.getElementById(root.dataset.source || 'content');
    const surface = root.querySelector('[data-editor-root]');
    const status = root.querySelector('[data-editor-status]');

    if (!source || !surface) {
        return;
    }

    const editor = new Editor({
        element: surface,
        content: source.value || '',
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                        target: '_blank',
                    },
                },
            }),
            Image.configure({
                HTMLAttributes: { class: 'article-inline-image' },
            }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Placeholder.configure({
                placeholder: 'เขียนเนื้อหาบทความที่นี่...',
            }),
        ],
        editorProps: {
            attributes: {
                class: 'article-editor-surface',
            },
        },
        onUpdate: ({ editor: current }) => {
            source.value = current.getHTML();
        },
        onSelectionUpdate: () => refreshToolbar(root, editor),
        onTransaction: () => refreshToolbar(root, editor),
    });

    source.value = editor.getHTML();
    source.hidden = true;
    root.hidden = false;

    source.form?.addEventListener('submit', () => {
        source.value = editor.getHTML();
    });

    root.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => runCommand(editor, button.dataset.editorCommand, root, status));
    });

    refreshToolbar(root, editor);
}

function runCommand(editor, command, root, status) {
    const chain = () => editor.chain().focus();

    const actions = {
        bold: () => chain().toggleBold().run(),
        italic: () => chain().toggleItalic().run(),
        underline: () => chain().toggleUnderline().run(),
        strike: () => chain().toggleStrike().run(),
        'heading-2': () => chain().toggleHeading({ level: 2 }).run(),
        'heading-3': () => chain().toggleHeading({ level: 3 }).run(),
        bullet: () => chain().toggleBulletList().run(),
        ordered: () => chain().toggleOrderedList().run(),
        quote: () => chain().toggleBlockquote().run(),
        code: () => chain().toggleCodeBlock().run(),
        'align-left': () => chain().setTextAlign('left').run(),
        'align-center': () => chain().setTextAlign('center').run(),
        'align-right': () => chain().setTextAlign('right').run(),
        undo: () => chain().undo().run(),
        redo: () => chain().redo().run(),
        link: () => setLink(editor),
        image: () => pickImage(editor, root, status),
    };

    actions[command]?.();
    refreshToolbar(root, editor);
}

function setLink(editor) {
    const previous = editor.getAttributes('link').href || '';
    const url = window.prompt('ใส่ลิงก์', previous || 'https://');

    if (url === null) {
        return;
    }

    if (url.trim() === '') {
        editor.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
}

function pickImage(editor, root, status) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,image/gif';
    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        if (!file) {
            return;
        }

        const body = new FormData();
        body.append('image', file);
        if (status) {
            status.textContent = 'กำลังอัปโหลดรูป...';
        }

        try {
            const response = await fetch(root.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body,
            });
            const payload = await response.json();
            if (!response.ok || !payload.url) {
                throw new Error(payload.message || 'upload failed');
            }
            editor.chain().focus().setImage({ src: payload.url, alt: '' }).run();
            if (status) {
                status.textContent = '';
            }
        } catch (error) {
            if (status) {
                status.textContent = 'อัปโหลดรูปไม่สำเร็จ';
            }
        }
    });
    input.click();
}

function refreshToolbar(root, editor) {
    const active = {
        bold: editor.isActive('bold'),
        italic: editor.isActive('italic'),
        underline: editor.isActive('underline'),
        strike: editor.isActive('strike'),
        'heading-2': editor.isActive('heading', { level: 2 }),
        'heading-3': editor.isActive('heading', { level: 3 }),
        bullet: editor.isActive('bulletList'),
        ordered: editor.isActive('orderedList'),
        quote: editor.isActive('blockquote'),
        code: editor.isActive('codeBlock'),
        'align-left': editor.isActive({ textAlign: 'left' }),
        'align-center': editor.isActive({ textAlign: 'center' }),
        'align-right': editor.isActive({ textAlign: 'right' }),
        link: editor.isActive('link'),
    };

    root.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.classList.toggle('is-active', Boolean(active[button.dataset.editorCommand]));
    });
}

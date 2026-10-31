const CONCURRENCY = 4;
const MAX_ATTEMPTS = 8;
const SIGN_BATCH = 20;
const PHP_FALLBACK_MAX_BYTES = 500 * 1024 * 1024;

const csrfToken = () => document.querySelector('meta[name=csrf-token]')?.content || '';

class UploadError extends Error {
    constructor(message, status = 0) {
        super(message);
        this.status = status;
    }
}

const parseJson = (text) => {
    try {
        return JSON.parse(text || '{}');
    } catch (error) {
        const json = (text || '').match(/\{[\s\S]*\}\s*$/);
        try {
            return json ? JSON.parse(json[0]) : null;
        } catch (innerError) {
            return null;
        }
    }
};

const errorMessage = (status, payload) => {
    if (status === 413 && !payload?.message) {
        return 'ไฟล์ใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์ ตรวจสอบ upload_max_filesize และ post_max_size ของ PHP';
    }
    if (status === 419) {
        return 'หน้าเว็บหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่';
    }
    if (payload?.errors) {
        return Object.values(payload.errors).flat().join(' ');
    }
    if (payload?.message) {
        return payload.message;
    }

    return `อัปโหลดวิดีโอไม่สำเร็จ (HTTP ${status})`;
};

async function api(url, method, body) {
    let response;
    try {
        response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch (error) {
        throw new UploadError('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ต');
    }

    const payload = parseJson(await response.text());
    if (!response.ok) {
        throw new UploadError(errorMessage(response.status, payload), response.status);
    }

    return payload || {};
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const waitOnline = () => new Promise((resolve) => {
    if (navigator.onLine !== false) {
        resolve();
        return;
    }
    window.addEventListener('online', () => resolve(), { once: true });
});

function putPart(url, blob, onProgress, xhrs) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhrs.add(xhr);
        xhr.open('PUT', url);
        xhr.upload.addEventListener('progress', (event) => onProgress(event.loaded));
        xhr.onload = () => {
            xhrs.delete(xhr);
            if (xhr.status >= 200 && xhr.status < 300) {
                const etag = xhr.getResponseHeader('ETag');
                if (!etag) {
                    reject(new UploadError('R2 ไม่ส่ง ETag กลับมา ตั้ง CORS ของ bucket ให้ ExposeHeaders มี ETag', -1));
                    return;
                }
                resolve(etag);
                return;
            }
            reject(new UploadError(`R2 ตอบ HTTP ${xhr.status}`, xhr.status));
        };
        xhr.onerror = () => {
            xhrs.delete(xhr);
            reject(new UploadError('network', 0));
        };
        xhr.onabort = () => {
            xhrs.delete(xhr);
            reject(new UploadError('aborted', -2));
        };
        xhr.send(blob);
    });
}

/**
 * อัปโหลดไฟล์ตรงขึ้น R2 แบบ multipart คืนค่า { token, abortUrl } หรือ null ถ้าเซิร์ฟเวอร์ไม่รองรับ (ไม่ได้ใช้ R2)
 */
async function directUpload(form, file, onProgress) {
    const init = await api(form.dataset.directUploadUrl, 'POST', {
        kind: form.dataset.uploadKind || 'video',
        scope: form.dataset.uploadScope,
        course_id: form.dataset.courseId ? Number(form.dataset.courseId) : null,
        chapter_id: form.dataset.chapterId ? Number(form.dataset.chapterId) : null,
        filename: file.name,
        size: file.size,
    });

    if (!init.direct) {
        return null;
    }

    const { token, part_size: partSize, part_count: partCount, urls } = init;
    const loaded = new Array(partCount + 1).fill(0);
    const etags = new Array(partCount + 1).fill(null);
    const signed = {};
    const batches = {};
    const xhrs = new Set();
    let failed = null;
    let everSucceeded = false;

    const report = () => onProgress(loaded.reduce((sum, value) => sum + value, 0) / file.size);

    const urlFor = async (part, force) => {
        if (force) {
            const result = await api(urls.sign, 'POST', { parts: [part] });
            Object.assign(signed, result.urls);
            return signed[part];
        }
        if (signed[part]) {
            return signed[part];
        }
        const batch = Math.floor((part - 1) / SIGN_BATCH);
        if (!batches[batch]) {
            const parts = [];
            for (let n = batch * SIGN_BATCH + 1; n <= Math.min(partCount, (batch + 1) * SIGN_BATCH); n++) {
                parts.push(n);
            }
            batches[batch] = api(urls.sign, 'POST', { parts }).then((result) => Object.assign(signed, result.urls));
        }
        await batches[batch];

        return signed[part];
    };

    const uploadPart = async (part) => {
        const blob = file.slice((part - 1) * partSize, Math.min(file.size, part * partSize));
        let lastStatus = null;

        for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
            if (failed) {
                return;
            }
            await waitOnline();
            try {
                const url = await urlFor(part, lastStatus === 403);
                etags[part] = await putPart(url, blob, (bytes) => {
                    loaded[part] = bytes;
                    report();
                }, xhrs);
                loaded[part] = blob.size;
                everSucceeded = true;
                report();
                return;
            } catch (error) {
                loaded[part] = 0;
                report();
                if (error.status === -1 || error.status === -2 || (error.status >= 400 && error.status < 500 && error.status !== 403 && error.status !== 408 && error.status !== 429)) {
                    throw error;
                }
                if (error.status === 0 && !everSucceeded && attempt >= 3) {
                    const unreachable = new UploadError('อัปโหลดขึ้น R2 ไม่ได้ ตรวจสอบอินเทอร์เน็ต หรือการตั้ง CORS ของ bucket ให้อนุญาตโดเมนนี้ (ดู docs/media-storage.md)');
                    unreachable.unreachable = true;
                    throw unreachable;
                }
                if (attempt === MAX_ATTEMPTS) {
                    throw new UploadError(error.status === 0
                        ? 'เครือข่ายขัดข้องระหว่างอัปโหลด ลองใหม่แล้ว ' + MAX_ATTEMPTS + ' ครั้ง กรุณาตรวจสอบอินเทอร์เน็ต'
                        : error.message, error.status);
                }
                lastStatus = error.status;
                await sleep(Math.min(30000, 1000 * 2 ** (attempt - 1)));
            }
        }
    };

    let next = 1;
    const worker = async () => {
        while (!failed && next <= partCount) {
            const part = next++;
            try {
                await uploadPart(part);
            } catch (error) {
                failed = failed || error;
                xhrs.forEach((xhr) => xhr.abort());
            }
        }
    };

    try {
        await Promise.all(Array.from({ length: Math.min(CONCURRENCY, partCount) }, worker));
        if (failed) {
            throw failed;
        }

        await api(urls.complete, 'POST', {
            parts: etags.slice(1).map((etag, index) => ({ part_number: index + 1, etag })),
        });
    } catch (error) {
        api(urls.abort, 'DELETE').catch(() => {});
        throw error;
    }

    return { token, abortUrl: urls.abort };
}

const warnBeforeLeave = (event) => {
    event.preventDefault();
    event.returnValue = '';
};

function setUploading(component, uploading) {
    component.uploadingVideo = uploading;
    if (uploading) {
        window.addEventListener('beforeunload', warnBeforeLeave);
    } else {
        window.removeEventListener('beforeunload', warnBeforeLeave);
    }
}

function fail(component, message, flash) {
    setUploading(component, false);
    component.uploadStage = 'idle';
    component.uploadProgress = 0;
    component.uploadVideoError = message;
    flash('error', message);
}

const fileInputOf = (form) => form.querySelector(`input[type=file][name="${form.dataset.fileInput || 'video_file'}"]`);

async function uploadFileFirst(component, form) {
    const file = fileInputOf(form)?.files?.[0];
    if (!file || !form.dataset.directUploadUrl) {
        return null;
    }

    try {
        return await directUpload(form, file, (ratio) => {
            component.uploadProgress = Math.min(99, Math.round(ratio * 100));
        });
    } catch (error) {
        if (error.unreachable && file.size <= PHP_FALLBACK_MAX_BYTES) {
            console.warn('Direct upload to R2 failed, falling back to PHP upload.', error);
            component.uploadProgress = 0;
            return null;
        }
        throw error;
    }
}

export async function uploadVideoForm(component, event) {
    event.preventDefault();
    if (component.uploadingVideo) {
        return;
    }

    const form = event.target;
    const formData = new FormData(form);
    const flash = (type, message) => {
        if (typeof component.showFlash === 'function') {
            component.showFlash(type, message);
        }
    };

    setUploading(component, true);
    component.uploadProgress = 0;
    component.uploadStage = 'uploading';
    component.uploadVideoError = null;

    let direct = null;
    try {
        direct = await uploadFileFirst(component, form);
    } catch (error) {
        fail(component, error.message, flash);
        return;
    }

    if (direct) {
        formData.delete(fileInputOf(form)?.name || 'video_file');
        formData.set('upload_token', direct.token);
        component.uploadProgress = 100;
        component.uploadStage = 'saving';
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());

    if (!direct) {
        xhr.upload.addEventListener('progress', (progress) => {
            if (progress.lengthComputable) {
                component.uploadProgress = Math.min(99, Math.round((progress.loaded / progress.total) * 100));
            }
        });
        xhr.upload.addEventListener('load', () => {
            component.uploadProgress = 100;
            component.uploadStage = 'saving';
        });
    }

    xhr.onload = () => {
        const payload = parseJson(xhr.responseText);

        if (xhr.status >= 200 && xhr.status < 300) {
            setUploading(component, false);
            component.uploadingVideo = true;
            component.uploadStage = 'done';
            flash('success', payload?.message || 'เพิ่มวิดีโอบทเรียนเรียบร้อยแล้ว');
            setTimeout(() => {
                window.location.href = payload?.redirect || window.location.href;
            }, 700);
            return;
        }

        if (direct) {
            api(direct.abortUrl, 'DELETE').catch(() => {});
        }
        fail(component, errorMessage(xhr.status, payload), flash);
    };

    xhr.onerror = () => {
        if (direct) {
            api(direct.abortUrl, 'DELETE').catch(() => {});
        }
        fail(component, 'เครือข่ายขัดข้อง กรุณาลองอีกครั้ง', flash);
    };

    xhr.send(formData);
}

/**
 * ฟอร์มที่ submit ปกติ (แก้ไขวิดีโอ เพิ่มเอกสาร): ถ้าเลือกไฟล์ อัปโหลดตรงขึ้น R2 ก่อน แล้วส่งฟอร์มพร้อม upload_token
 * ชื่อช่องไฟล์มาจาก data-file-input (ค่าเริ่มต้น video_file) ชนิดไฟล์มาจาก data-upload-kind (video / document)
 */
export async function submitVideoEditForm(component, event) {
    const form = event.target;
    const fileInput = fileInputOf(form);
    if (!fileInput?.files?.[0] || !form.dataset.directUploadUrl) {
        return;
    }

    event.preventDefault();
    if (component.uploadingVideo) {
        return;
    }

    const flash = (type, message) => {
        if (typeof component.showFlash === 'function') {
            component.showFlash(type, message);
        }
    };

    setUploading(component, true);
    component.uploadProgress = 0;
    component.uploadStage = 'uploading';
    component.uploadVideoError = null;

    let direct = null;
    try {
        direct = await uploadFileFirst(component, form);
    } catch (error) {
        fail(component, error.message, flash);
        return;
    }

    if (direct) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'upload_token';
        input.value = direct.token;
        form.appendChild(input);
        fileInput.disabled = true;
    }

    component.uploadProgress = 100;
    component.uploadStage = 'saving';
    setUploading(component, false);
    component.uploadingVideo = true;
    form.submit();
}

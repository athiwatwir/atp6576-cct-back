export function uploadVideoForm(component, event) {
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

    component.uploadingVideo = true;
    component.uploadProgress = 0;
    component.uploadStage = 'uploading';
    component.uploadVideoError = null;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    const token = document.querySelector('meta[name=csrf-token]')?.content;
    if (token) {
        xhr.setRequestHeader('X-CSRF-TOKEN', token);
    }

    xhr.upload.addEventListener('progress', (event) => {
        if (!event.lengthComputable) {
            return;
        }
        component.uploadProgress = Math.min(99, Math.round((event.loaded / event.total) * 100));
    });

    xhr.upload.addEventListener('load', () => {
        component.uploadProgress = 100;
        component.uploadStage = 'saving';
    });

    xhr.onload = () => {
        let payload = null;
        try {
            payload = JSON.parse(xhr.responseText || '{}');
        } catch (error) {
            const json = (xhr.responseText || '').match(/\{[\s\S]*\}\s*$/);
            try {
                payload = json ? JSON.parse(json[0]) : null;
            } catch (innerError) {
                payload = null;
            }
        }

        if (xhr.status >= 200 && xhr.status < 300) {
            component.uploadStage = 'done';
            flash('success', payload?.message || 'เพิ่มวิดีโอบทเรียนเรียบร้อยแล้ว');
            setTimeout(() => {
                window.location.href = payload?.redirect || window.location.href;
            }, 700);
            return;
        }

        let message = `อัปโหลดวิดีโอไม่สำเร็จ (HTTP ${xhr.status})`;
        if (xhr.status === 413 && !payload?.message) {
            message = 'ไฟล์ใหญ่เกินขีดจำกัดของเซิร์ฟเวอร์ ตรวจสอบ upload_max_filesize และ post_max_size ของ PHP';
        } else if (xhr.status === 419) {
            message = 'หน้าเว็บหมดอายุ กรุณารีเฟรชหน้าแล้วลองใหม่';
        } else if (payload?.message) {
            message = payload.message;
        } else if (payload?.errors) {
            message = Object.values(payload.errors).flat().join(' ');
        }

        component.uploadingVideo = false;
        component.uploadStage = 'idle';
        component.uploadProgress = 0;
        component.uploadVideoError = message;
        flash('error', message);
    };

    xhr.onerror = () => {
        component.uploadingVideo = false;
        component.uploadStage = 'idle';
        component.uploadProgress = 0;
        component.uploadVideoError = 'เครือข่ายขัดข้อง กรุณาลองอีกครั้ง';
        flash('error', component.uploadVideoError);
    };

    xhr.send(formData);
}

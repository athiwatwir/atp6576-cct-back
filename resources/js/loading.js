/**
 * Global page loading — configure once, used across the app.
 *
 * Opt-out: add data-no-loading on a form, link, or parent element.
 * Manual:  window.PageLoading.show('ข้อความ') / .hide()
 * Alpine:  $store.loading.show('ข้อความ') / .hide()
 */
export const loadingConfig = {
    enabled: true,
    forms: true,
    links: true,
    message: 'กำลังโหลด...',
    formMessage: 'กำลังบันทึก...',
    linkMessage: 'กำลังโหลดหน้า...',
    skipSelector: '[data-no-loading]',
};

function shouldSkip(el) {
    if (!el || !(el instanceof Element)) {
        return true;
    }

    return Boolean(el.closest(loadingConfig.skipSelector));
}

function createLoadingStore() {
    return {
        active: false,
        message: loadingConfig.message,

        show(message = loadingConfig.message) {
            if (!loadingConfig.enabled) {
                return;
            }

            this.message = message || loadingConfig.message;
            this.active = true;
        },

        hide() {
            this.active = false;
        },

        toggle(force, message) {
            if (typeof force === 'boolean') {
                force ? this.show(message) : this.hide();
                return;
            }

            this.active ? this.hide() : this.show(message);
        },
    };
}

function isSameOriginNavigableLink(anchor) {
    if (!(anchor instanceof HTMLAnchorElement)) {
        return false;
    }

    if (anchor.target && anchor.target !== '_self') {
        return false;
    }

    if (anchor.hasAttribute('download')) {
        return false;
    }

    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#') || href.toLowerCase().startsWith('javascript:')) {
        return false;
    }

    try {
        const url = new URL(anchor.href, window.location.href);
        if (url.origin !== window.location.origin) {
            return false;
        }

        if (url.pathname === window.location.pathname
            && url.search === window.location.search
            && url.hash !== '') {
            return false;
        }
    } catch {
        return false;
    }

    return true;
}

function bindAutoLoading(Alpine) {
    document.addEventListener('submit', (event) => {
        if (!loadingConfig.enabled || !loadingConfig.forms) {
            return;
        }

        const form = event.target;
        if (!(form instanceof HTMLFormElement) || shouldSkip(form)) {
            return;
        }

        // Alpine / JS that calls preventDefault (AJAX) should not lock the UI forever.
        if (event.defaultPrevented) {
            return;
        }

        Alpine.store('loading').show(
            form.dataset.loadingMessage || loadingConfig.formMessage
        );
    });

    document.addEventListener('click', (event) => {
        if (!loadingConfig.enabled || !loadingConfig.links) {
            return;
        }

        if (event.defaultPrevented
            || event.button !== 0
            || event.metaKey
            || event.ctrlKey
            || event.shiftKey
            || event.altKey) {
            return;
        }

        const anchor = event.target instanceof Element
            ? event.target.closest('a[href]')
            : null;

        if (!anchor || shouldSkip(anchor) || !isSameOriginNavigableLink(anchor)) {
            return;
        }

        Alpine.store('loading').show(
            anchor.dataset.loadingMessage || loadingConfig.linkMessage
        );
    });

    window.addEventListener('pageshow', () => {
        Alpine.store('loading')?.hide();
    });
}

export function registerPageLoading(Alpine) {
    Alpine.store('loading', createLoadingStore());

    window.PageLoading = {
        config: loadingConfig,
        show: (message) => Alpine.store('loading').show(message),
        hide: () => Alpine.store('loading').hide(),
        toggle: (force, message) => Alpine.store('loading').toggle(force, message),
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => bindAutoLoading(Alpine));
    } else {
        bindAutoLoading(Alpine);
    }
}

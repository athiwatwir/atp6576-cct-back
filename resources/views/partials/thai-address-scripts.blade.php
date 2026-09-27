<link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
<style>
    .twitter-typeahead { width: 100%; display: block !important; }
    .tt-menu {
        width: 100%;
        margin-top: 4px;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        overflow: hidden;
        z-index: 50;
    }
    .dark .tt-menu {
        border-color: #374151;
        background: #111827;
        color: #f3f4f6;
    }
    .tt-suggestion {
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        cursor: pointer;
    }
    .tt-suggestion:hover,
    .tt-cursor {
        background: #f3f4f6;
    }
    .dark .tt-suggestion:hover,
    .dark .tt-cursor {
        background: #1f2937;
    }
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
<script>
    (function () {
        if (! window.jQuery || ! jQuery.Thailand) {
            return;
        }

        document.querySelectorAll('[data-thai-address]').forEach(function (wrap) {
            const $wrap = jQuery(wrap);

            jQuery.Thailand({
                $district: $wrap.find('.js-thai-subdistrict'),
                $amphoe: $wrap.find('.js-thai-district'),
                $province: $wrap.find('.js-thai-province'),
                $zipcode: $wrap.find('.js-thai-zipcode'),
            });
        });
    })();
</script>

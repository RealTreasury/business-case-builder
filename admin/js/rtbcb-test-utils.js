(function($){
    'use strict';

    const utils = {
        showLoading(button, text) {
            const original = button.text();
            button.prop('disabled', true).text(text || original);
            return original;
        },
        hideLoading(button, original) {
            button.prop('disabled', false).text(original);
        },
        computeMeta(text, start) {
            const words = text ? text.trim().split(/\s+/).length : 0;
            const elapsed = start ? ((performance.now() - start) / 1000).toFixed(2) : '0.00';
            const generated = new Date().toLocaleString();
            return { words, elapsed, generated };
        },
        renderSuccess(container, text, start, meta = {}) {
            const stats = utils.computeMeta(text, start);
            const wordCount = meta.word_count || stats.words;
            const elapsed = meta.elapsed_time || meta.elapsed || stats.elapsed;
            const timestamp = meta.generated_at || meta.generated || stats.generated;
            const notice = $('<div class="notice notice-success" />');
            notice.append('<div class="rtbcb-result-text" />');
            notice.find('.rtbcb-result-text').text(text);
            notice.append('<p class="rtbcb-result-meta">Word count: ' + wordCount + ' | Generated: ' + timestamp + ' | Elapsed: ' + elapsed + 's</p>');
            container.html(notice);
            return notice;
        },
        renderError(container, message, retryCallback) {
            const notice = $('<div class="notice notice-error" />');
            const p = $('<p />').html('<strong>Error:</strong> ' + message + ' ');
            if (retryCallback) {
                const retry = $('<a href="#" class="rtbcb-retry">Retry</a>');
                retry.on('click', function(e){
                    e.preventDefault();
                    retryCallback();
                });
                p.append(retry);
            }
            notice.append(p);
            container.html(notice);
            return notice;
        },
        copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(text);
            }
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);
                return successful ? Promise.resolve() : Promise.reject(new Error('Copy failed'));
            } catch (err) {
                document.body.removeChild(textarea);
                return Promise.reject(err);
            }
        }
    };

    window.rtbcbTestUtils = utils;

})(jQuery);


(function($){
    'use strict';

    window.RTBCBTestUtils = {
        showLoading(button, message = 'Generating...') {
            const $btn = $(button);
            if (!$btn.data('original-text')) {
                $btn.data('original-text', $btn.text());
            }
            $btn.prop('disabled', true).text(message);
        },

        hideLoading(button) {
            const $btn = $(button);
            const original = $btn.data('original-text');
            if (original) {
                $btn.text(original);
            }
            $btn.prop('disabled', false);
        },

        renderNotice(container, type, message, retryCallback, extraContent) {
            const $container = $(container);
            const $notice = $('<div />', { class: 'notice notice-' + type });
            const $p = $('<p />').html(message);
            if (retryCallback) {
                const $retry = $('<a href="#" class="rtbcb-retry" />').text('Retry');
                $retry.on('click', function(e){
                    e.preventDefault();
                    retryCallback();
                });
                $p.append(' ').append($retry);
            }
            $notice.append($p);
            if (extraContent) {
                $notice.append(extraContent);
            }
            $container.html($notice);
        },

        computeMetrics(text, startTime) {
            const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
            const elapsed = ((performance.now() - startTime) / 1000).toFixed(2);
            const timestamp = new Date().toLocaleTimeString();
            return { wordCount, elapsed, timestamp };
        },

        renderMetrics(container, metrics) {
            const html = '<p><strong>Word count:</strong> ' + metrics.wordCount + '<br><strong>Generated at:</strong> ' + metrics.timestamp + '<br><strong>Elapsed time:</strong> ' + metrics.elapsed + 's</p>';
            $(container).html(html);
        },

        async copy(text) {
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch (err) {
                    // Fall back to execCommand below.
                }
            }
            const $textarea = $('<textarea>').val(text).css({ position: 'fixed', top: '-1000px' });
            $('body').append($textarea);
            $textarea.focus().select();
            let success = false;
            try {
                success = document.execCommand('copy');
            } catch (err) {
                success = false;
            }
            $textarea.remove();
            return success;
        }
    };
})(jQuery);

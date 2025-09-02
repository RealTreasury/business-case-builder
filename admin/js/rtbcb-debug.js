(function(){
    'use strict';

    function logSuccess(message){
        console.log('✅ ' + message);
    }

    function logError(message, remedy){
        console.error('❌ ' + message + (remedy ? ' — ' + remedy : ''));
    }

    function logInfo(message){
        console.log('ℹ️ ' + message);
    }

    function isValidAjaxUrl(url){
        try {
            var parsed = new URL(url, window.location.origin);
            return (parsed.protocol === 'http:' || parsed.protocol === 'https:') && /admin-ajax\.php$/.test(parsed.pathname);
        } catch (e) {
            return false;
        }
    }

    async function runFullDiagnostics(){
        console.group('RTBCB Debug Diagnostics');

        if (typeof window.rtbcb_ajax === 'undefined'){
            logError('rtbcb_ajax object is missing', 'Ensure wp_localize_script provides rtbcb_ajax');
            console.groupEnd();
            return;
        }
        logSuccess('rtbcb_ajax object is localized');

        var ajaxUrl = window.rtbcb_ajax.ajax_url;
        if (!isValidAjaxUrl(ajaxUrl)){
            logError('rtbcb_ajax.ajax_url is invalid: ' + ajaxUrl, 'Check admin-ajax.php URL and protocol');
        } else {
            logSuccess('rtbcb_ajax.ajax_url looks valid');
            try {
                var ping = await fetch(ajaxUrl, { method: 'GET' });
                if (ping.ok){
                    logSuccess('Fetch to admin-ajax.php succeeded');
                } else {
                    logError('Fetch to admin-ajax.php returned status ' + ping.status);
                }
            } catch (e){
                logError('Fetch to admin-ajax.php failed: ' + e.message, 'Verify server accessibility');
            }
        }

        try {
            var emergencyParams = new URLSearchParams({ action: 'rtbcb_emergency_debug', nonce: window.rtbcb_ajax.nonce || '' });
            var emergencyRes = await fetch(ajaxUrl, { method: 'POST', body: emergencyParams });
            var emergencyData = await emergencyRes.json().catch(function(){ return {}; });
            if (emergencyRes.ok && emergencyData.success){
                logSuccess('Emergency debug endpoint responded');
            } else {
                logError('Emergency debug endpoint failed', 'Ensure server handles rtbcb_emergency_debug');
            }
        } catch (e){
            logError('Emergency debug request error: ' + e.message);
        }

        try {
            var simpleParams = new URLSearchParams({ action: 'rtbcb_simple_test', rtbcb_nonce: window.rtbcb_ajax.nonce || '' });
            var simpleRes = await fetch(ajaxUrl, { method: 'POST', body: simpleParams });
            var simpleData = await simpleRes.json().catch(function(){ return {}; });
            if (simpleRes.ok && simpleData.success){
                logSuccess('OpenAI connectivity test passed');
            } else {
                logError('OpenAI connectivity test failed', 'Verify API key and server configuration');
            }
        } catch (e){
            logError('OpenAI connectivity request error: ' + e.message);
        }

        try {
            var formParams = new URLSearchParams({
                action: 'rtbcb_generate_case',
                rtbcb_nonce: window.rtbcb_ajax.nonce || '',
                email: 'debug@example.com',
                company_name: 'Debug Corp',
                company_size: '1-10',
                industry: 'Testing',
                hours_reconciliation: '1',
                hours_cash_positioning: '1',
                num_banks: '1',
                ftes: '1',
                business_objective: 'Diagnostics',
                implementation_timeline: '1-3 months',
                budget_range: '<10k',
                'pain_points[]': 'manual_processes',
                fast_mode: '1'
            });
            var formRes = await fetch(ajaxUrl, { method: 'POST', body: formParams });
            var formData = await formRes.json().catch(function(){ return {}; });
            if (formRes.ok && formData.success){
                logSuccess('Full form submission succeeded');
            } else {
                logError('Full form submission failed', 'Check required fields and server handling for rtbcb_generate_case');
            }
        } catch (e){
            logError('Full form submission error: ' + e.message);
        }

        logInfo('Diagnostics complete');
        console.groupEnd();
    }

    window.rtbcbDebug = {
        runFullDiagnostics: runFullDiagnostics
    };

    if (window.location.search.indexOf('rtbcb_debug=1') !== -1){
        runFullDiagnostics();
    }
})();


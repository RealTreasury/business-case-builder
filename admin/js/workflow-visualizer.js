jQuery(function($) {
function loadHistory() {
$.post(rtbcbWorkflow.ajax_url, {
action: 'rtbcb_get_workflow_history',
nonce: rtbcbWorkflow.nonce
}).done(function(response) {
    if (response.success) {
        var history = response.data.history || [];
        var html = '';
        history.forEach(function(item, index) {
            if (item.prompts && item.prompts.length) {
                html += '<h3>Execution ' + (index + 1) + '</h3><ul>';
                item.prompts.forEach(function(p) {
                    var txt = '';
                    if (p.instructions) {
                        txt += p.instructions + "\n";
                    }
                    txt += p.input;
                    html += '<li><pre>' + $('<div>').text(txt).html() + '</pre></li>';
                });
                html += '</ul>';
            }
        });
        if (!html) {
            html = '<p>' + rtbcbWorkflow.strings.no_history + '</p>';
        }
        $('#rtbcb-workflow-history-container').html(html);
        alert(rtbcbWorkflow.strings.refresh_success);
    } else {
        alert(rtbcbWorkflow.strings.error);
    }
}).fail(function() {
alert(rtbcbWorkflow.strings.error);
});
}

$('#rtbcb-refresh-workflow').on('click', function() {
loadHistory();
});

$('#rtbcb-clear-workflow').on('click', function() {
$.post(rtbcbWorkflow.ajax_url, {
action: 'rtbcb_clear_workflow_history',
nonce: rtbcbWorkflow.nonce
}).done(function(response) {
if (response.success) {
$('#rtbcb-workflow-history-container').empty();
alert(rtbcbWorkflow.strings.clear_success);
} else {
alert(rtbcbWorkflow.strings.error);
}
}).fail(function() {
alert(rtbcbWorkflow.strings.error);
});
});

loadHistory();
});

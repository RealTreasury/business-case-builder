jQuery(function($) {
function loadHistory() {
$.post(rtbcbWorkflow.ajax_url, {
action: 'rtbcb_get_workflow_history',
nonce: rtbcbWorkflow.nonce
}).done(function(response) {
if (response.success) {
$('#rtbcb-workflow-history-container').text(JSON.stringify(response.data.history));
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

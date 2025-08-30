jQuery(function($) {
function loadHistory() {
    $.post(rtbcbWorkflow.ajax_url, {
        action: 'rtbcb_get_workflow_history',
        nonce: rtbcbWorkflow.nonce
    }).done(function(response) {
        if (response.success) {
            var history = response.data.history || [];
            if (!history.length) {
                $('#rtbcb-workflow-history-container').html('<p>' + rtbcbWorkflow.strings.no_history + '</p>');
                alert(rtbcbWorkflow.strings.refresh_success);
                return;
            }
            var stepNames = [];
            history.forEach(function(item) {
                if (item.steps) {
                    item.steps.forEach(function(step) {
                        if (stepNames.indexOf(step.name) === -1) {
                            stepNames.push(step.name);
                        }
                    });
                }
            });
            var html = '<table><thead><tr><th>' + rtbcbWorkflow.strings.lead + '</th>';
            stepNames.forEach(function(name) {
                html += '<th>' + $('<div>').text(name).html() + '</th>';
            });
            html += '</tr></thead><tbody>';
            history.forEach(function(item) {
                var lead = item.email || (item.lead_id ? 'ID ' + item.lead_id : rtbcbWorkflow.strings.unknown_lead);
                html += '<tr><td>' + $('<div>').text(lead).html() + '</td>';
                stepNames.forEach(function(name) {
                    var status = rtbcbWorkflow.strings.not_run;
                    if (item.steps) {
                        item.steps.forEach(function(step) {
                            if (step.name === name) {
                                status = step.status;
                            }
                        });
                    }
                    html += '<td>' + $('<div>').text(status).html() + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
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

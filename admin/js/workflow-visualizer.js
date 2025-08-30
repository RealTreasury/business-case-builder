jQuery(function($) {
    function renderHistory(history) {
        var $container = $('#rtbcb-workflow-history-container');
        $container.empty();
        if (!history.length) {
            $container.append($('<p>').text(rtbcbWorkflow.strings.no_history));
            return;
        }
        var stepNames = [];
        history.forEach(function(item) {
            (item.steps || []).forEach(function(step) {
                if (stepNames.indexOf(step.name) === -1) {
                    stepNames.push(step.name);
                }
            });
        });
        var $table = $('<table></table>');
        var $headRow = $('<tr></tr>').append($('<th>').text(rtbcbWorkflow.strings.lead_label));
        stepNames.forEach(function(name) {
            $headRow.append($('<th>').text(name));
        });
        $table.append($('<thead></thead>').append($headRow));
        var $tbody = $('<tbody></tbody>');
        history.forEach(function(item) {
            var leadLabel = item.lead.email || item.lead.id || '';
            var $row = $('<tr></tr>').append($('<td>').text(leadLabel));
            stepNames.forEach(function(name) {
                var match = (item.steps || []).find(function(s) { return s.name === name; });
                var status = match ? match.status : '';
                $row.append($('<td>').text(status));
            });
            $tbody.append($row);
        });
        $table.append($tbody);
        $container.append($table);
    }

    function loadHistory() {
        $.post(rtbcbWorkflow.ajax_url, {
            action: 'rtbcb_get_workflow_history',
            nonce: rtbcbWorkflow.nonce
        }).done(function(response) {
            if (response.success) {
                var history = response.data.history || [];
                renderHistory(history);
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

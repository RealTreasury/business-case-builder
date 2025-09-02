jQuery(function ($) {
	function showMessage(message, type) {
		var $msg = $("#rtbcb-workflow-message");
		$msg
			.removeClass("notice-success notice-error")
			.addClass("notice-" + type)
			.text(message)
			.show();
	}

	function loadHistory(showNotice) {
		$.post(rtbcbWorkflow.ajax_url, {
			action: "rtbcb_get_workflow_history",
			nonce: rtbcbWorkflow.nonce,
		})
			.done(function (response) {
                                if (response.success) {
					var history = response.data.history || [];
					if (!history.length) {
						$("#rtbcb-workflow-history-container").html(
							"<p>" + rtbcbWorkflow.strings.no_history + "</p>",
						);
						if (showNotice) {
							showMessage(rtbcbWorkflow.strings.refresh_success, "success");
						}
						return;
					}
					var stepNames = [];
					history.forEach(function (item) {
						if (item.steps) {
							item.steps.forEach(function (step) {
								if (stepNames.indexOf(step.name) === -1) {
									stepNames.push(step.name);
								}
							});
						}
					});
                                       var html =
                                               '<div class="rtbcb-history-table-wrapper"><table class="widefat rtbcb-history-table"><thead><tr><th>' +
                                               rtbcbWorkflow.strings.lead +
                                               "</th><th>" +
                                               rtbcbWorkflow.strings.company +
                                               "</th><th>" +
                                               rtbcbWorkflow.strings.started +
                                               "</th>";
                                       stepNames.forEach(function (name) {
                                               html += "<th>" + $("<div>").text(name).html() + "</th>";
                                       });
                                       html += "</tr></thead><tbody>";
                                       history.forEach(function (item) {
                                               var lead =
                                                       item.email ||
                                                       (item.lead_id
                                                               ? "ID " + item.lead_id
                                                               : rtbcbWorkflow.strings.unknown_lead);
                                               var company = item.company || rtbcbWorkflow.strings.unknown_company;
                                               var started = item.started_at || rtbcbWorkflow.strings.unknown_start;
                                               html +=
                                                       "<tr><td>" +
                                                       $("<div>").text(lead).html() +
                                                       "</td><td>" +
                                                       $("<div>").text(company).html() +
                                                       "</td><td>" +
                                                       $("<div>").text(started).html() +
                                                       "</td>";
                                               stepNames.forEach(function (name) {
                                                        var status = rtbcbWorkflow.strings.not_run;
                                                        var time = "";
                                                        if (item.steps) {
                                                                item.steps.forEach(function (step) {
                                                                        if (step.name === name) {
                                                                                status = step.status;
                                                                                if (step.duration) {
                                                                                        time =
                                                                                                '<span class="rtbcb-step-time">' +
                                                                                                step.duration +
                                                                                                rtbcbWorkflow.strings.seconds;
                                                                                        if (step.elapsed) {
                                                                                                time +=
                                                                                                        ' (' +
                                                                                                        step.elapsed +
                                                                                                        rtbcbWorkflow.strings.elapsed_suffix +
                                                                                                        ')';
                                                                                        }
                                                                                        time += '</span>';
                                                                                }
                                                                        }
                                                                });
                                                        }
							html +=
								"<td><span>" +
								$("<div>").text(status).html() +
								"</span>" +
								time +
								"</td>";
						});
						html += "</tr>";
					});
					html += "</tbody></table></div>";
					$("#rtbcb-workflow-history-container").html(html);
					if (showNotice) {
						showMessage(rtbcbWorkflow.strings.refresh_success, "success");
					}
                                } else if (showNotice) {
                                        showMessage(rtbcbWorkflow.strings.error, "error");
                                }
                        })
                        .fail(function () {
                                if (showNotice) {
                                        showMessage(rtbcbWorkflow.strings.error, "error");
                                }
                        });
        }

	$("#rtbcb-refresh-workflow").on("click", function () {
		loadHistory(true);
	});

	$("#rtbcb-clear-workflow").on("click", function () {
		$.post(rtbcbWorkflow.ajax_url, {
			action: "rtbcb_clear_workflow_history",
			nonce: rtbcbWorkflow.nonce,
		})
			.done(function (response) {
				if (response.success) {
					$("#rtbcb-workflow-history-container").empty();
					showMessage(rtbcbWorkflow.strings.clear_success, "success");
				} else {
					showMessage(rtbcbWorkflow.strings.error, "error");
				}
			})
			.fail(function () {
				showMessage(rtbcbWorkflow.strings.error, "error");
			});
	});

	loadHistory(false);
});

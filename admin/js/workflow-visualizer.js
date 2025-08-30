jQuery(function($){
	function loadHistory(){
		$('#rtbcb-workflow-history-container').html('<div class="rtbcb-loading">'+rtbcbWorkflow.strings.refresh_success+'</div>');
		$.post(rtbcbWorkflow.ajax_url,{action:'rtbcb_get_workflow_history',nonce:rtbcbWorkflow.nonce},function(resp){
			if(resp.success){
				var html='';
				if(resp.data.history.length){
					html+='<ul>';
					resp.data.history.forEach(function(item){
						html+='<li>'+item.name+' - '+item.status+' ('+item.duration+'s)</li>';
					});
					html+='</ul>';
				}else{
					html='<p>No history.</p>';
				}
				$('#rtbcb-workflow-history-container').html(html);
			}else{
				$('#rtbcb-workflow-history-container').text(rtbcbWorkflow.strings.error);
			}
		});
	}

	$('#rtbcb-refresh-workflow').on('click',function(){
		loadHistory();
	});

	$('#rtbcb-clear-workflow').on('click',function(){
		$.post(rtbcbWorkflow.ajax_url,{action:'rtbcb_clear_workflow_history',nonce:rtbcbWorkflow.nonce},function(resp){
			if(resp.success){
				$('#rtbcb-workflow-history-container').html('<p>'+rtbcbWorkflow.strings.clear_success+'</p>');
			}else{
				alert(rtbcbWorkflow.strings.error);
			}
		});
	});

	$('#rtbcb-export-workflow').on('click',function(){
		$.post(rtbcbWorkflow.ajax_url,{action:'rtbcb_get_workflow_history',nonce:rtbcbWorkflow.nonce},function(resp){
			if(resp.success){
				var dataStr='data:text/json;charset=utf-8,'+encodeURIComponent(JSON.stringify(resp.data,null,2));
				var dl=document.createElement('a');
				dl.setAttribute('href',dataStr);
				dl.setAttribute('download','workflow-history.json');
				document.body.appendChild(dl);
				dl.click();
				document.body.removeChild(dl);
			}else{
				alert(rtbcbWorkflow.strings.error);
			}
		});
	});

	loadHistory();
});

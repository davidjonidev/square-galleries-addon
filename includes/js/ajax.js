jQuery(function($){
	$('#sync-galleries-btn').click(function() {
        let action = 'trigger_gal_sync'
        let ajaxurl = 'ajax.php',
        data =  {'action': action}
        $.post(ajax_object.ajax_url, data, function (response) {
            
            $('.sync-status-label').text(`Sync Status: ${response.alert}`)
            setTimeout(function() {
                location.reload();
            }, 5000);
        })
    })
})
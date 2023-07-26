<?php

add_action('admin_enqueue_scripts','ajax_script_localize');
function ajax_script_localize($hook) {
	if( 'settings_page_squaregalladdon' != $hook ) {
	// Only applies to dashboard panel
	return;
	}

	wp_enqueue_script( 'ajax-js', plugins_url( '/js/ajax.js', __FILE__ ), array('jquery') );

	// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'ajax-js', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

// add_action( 'wp_ajax_trigger_gal_sync', 'trigger_gal_sync_callback' );
// add_action( 'wp_ajax_nopriv_trigger_gal_sync', 'trigger_gal_sync_callback' );

add_action( 'wp_ajax_trigger_gal_sync', 'trigger_gal_sync_callback' );
function trigger_gal_sync_callback(){
	
	// add_action( 'sync_gal_from_square_event', 'setup_get_product_galleries_from_square' );
	wp_schedule_single_event( time() + 3, 'sync_gal_from_square_event' );
	
	update_option( 'square_gal_update_sync_status', 'Scheduled');
    $response['alert'] = __('Scheduled in' .  time() + 3 . ' - Wait for page to automatically reload');
    wp_send_json($response);
    wp_die();
}
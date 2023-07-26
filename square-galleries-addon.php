<?php
/* 

	Plugin Name: Square Galleries Addon
	Plugin URI: https://solidwebsites.uk
	Description: This plugin will load gallery images for all products imported using the plugin - WooCommerce Square
	Version: 1.1
	Author: Dash Media Ltd
	Author URI: https://solidwebsites.uk
	License: 
	Text Domain: squaregalladdon 

*/

include( plugin_dir_path( __FILE__ ) . "includes/square-curl.php" );
include( plugin_dir_path( __FILE__ ) . "includes/upload-images.php" );
include( plugin_dir_path( __FILE__ ) . "includes/update-log.php" );
include( plugin_dir_path( __FILE__ ) . "includes/ajax.php" );

add_action('admin_enqueue_scripts', 'square_add_on_scripts');

function square_add_on_scripts($hook){

	if( 'settings_page_squaregalladdon' != $hook ) {
	// Only applies to dashboard panel
	return;
	}

	wp_enqueue_style('square-add-on-admin-css', plugins_url('includes/css/admin.css',__FILE__ ));
}

// SETTINGS MENU

function squaregalladdon_register_settings() {
	add_option( 'square_gal_update_last_page', 0);
	add_option( 'square_gal_update_last_product_id', 0);
	add_option( 'square_gal_update_last_product_name', 0);
	add_option( 'square_gal_update_last_product_info', 0);
	add_option( 'square_gal_update_sync_status', '...');
	register_setting( 'squaregalladdon_options_group', 'square_gal_update_last_page', 'squaregalladdon_callback' );
	// register_setting( 'squaregalladdon_options_group', 'square_gal_update_last_product_id', 'squaregalladdon_callback' );
	// register_setting( 'squaregalladdon_options_group', 'square_gal_update_last_product_name', 'squaregalladdon_callback' );
	// register_setting( 'squaregalladdon_options_group', 'square_gal_update_last_product_info', 'squaregalladdon_callback' );
	// register_setting( 'squaregalladdon_options_group', 'square_gal_update_sync_status', 'squaregalladdon_callback' );
}

add_action( 'admin_init', 'squaregalladdon_register_settings' );

function squaregalladdon_register_options_page() {
	add_options_page('Square Gallery Sync Settings', 'Squery Gallery Sync Menu', 'manage_options', 'squaregalladdon', 'squaregalladdon_options_page');
}
add_action('admin_menu', 'squaregalladdon_register_options_page');

function squaregalladdon_options_page() { ?>
	<div>
	<?php screen_icon(); ?>
	<h2>Square Gallery Sync Settings</h2>
	<div class="last-synced-product">
		<label class="sync-status-label">Sync Status: <?php echo get_option('square_gal_update_sync_status'); ?></label>
		</br>
		</br>
		<label>ID: <?php echo get_option('square_gal_update_last_product_id'); ?></label>
		</br>
		<label>Name: <?php echo get_option('square_gal_update_last_product_name'); ?></label>
		</br>
		<label>Info: <?php echo get_option('square_gal_update_last_product_info'); ?></label>
	</div>
	<input type="button"  class="button button-primary" id="sync-galleries-btn" name="gal-sync" value="Sync Galleries" />
	<form method="post" action="options.php">
		<?php settings_fields( 'squaregalladdon_options_group' ); ?>
		<table>
			<tr valign="top">
				<th>
					<label for="square_gal_update_last_page">Last sync index</label>
				</th>
			</tr>
			<tr valign="top">
				<th>
					<input type="text" id="square_gal_update_last_page" name="square_gal_update_last_page" value="<?php echo get_option('square_gal_update_last_page'); ?>" />
				</th>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	</div>
<?php
}

// HELPER FUNCTIONS

function pr($data)
{
    echo "<pre>";
    print_r($data); // or var_dump($data);
    echo "</pre>";
}

// MAIN FUNCTIONS
function update_product_gallery($product_id, $related_objects, $main_image_id) {

	$gallery = array();
	foreach ( $related_objects as $object ) {
		if ( $object->type === 'IMAGE' ) {
			if ( $object->id !== $main_image_id ) {

				$image_id = upload_image_by_url($object->image_data->url, $object->image_data->name, $object->id);
				array_push($gallery, $image_id);
				
			}
		}
	}

	$result = update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery));

	return $result;

}


// FROM CURL
function check_for_update($product_id, $related_objects, $main_image_id) {

	$images_meta = array();
	foreach ( $related_objects as $object ) {
		if ( $object->type === 'IMAGE' ) {
			if ( $object->id !== $main_image_id ) {
				array_push($images_meta, $object->id);
			}
		}
	}

	return $images_meta;

}

// FROM WOOCOMMERCE
function current_product_gallery($product_id) {

	$product = wc_get_product( $product_id );
    
    // if $product is not an object, return 0
    if ( $product === false ) {
        return 'Product not found!';
    }
    
    // Get the product gallery attachment IDs
    $attachment_ids = $product->get_gallery_image_ids();

    // if there are no product gallery images, return 0
    if ( ! $attachment_ids ) {
        return 'Gallery empty';
    }

	$square_id_refs = array();
	foreach ( $attachment_ids as $attachment_id ) {
		$square_id_ref = get_post_meta($attachment_id, '_square_id_ref');
		if ($square_id_ref) {
			array_push($square_id_refs, $square_id_ref[0]);
		}
	}
    
    // Return the number of gallery images
    return $square_id_refs;
}

function setup_get_product_galleries_from_square() {

	$hasPosts = true;
	// $paged = 0;
	$paged = get_option( 'square_gal_update_last_page');

	if ( $paged !== 0 ) {
		$paged++;
	}

	// do {
	$query = new WP_Query([
		'post_type' => 'product',
		'post_status' => ['publish', 'draft'],
		'posts_per_page' => 50,
		'paged' => $paged,
		'fields' => 'ids'
	]);
	
	if ( $query->posts ) {
		update_option( 'square_gal_update_sync_status', 'Processing');
		$update_notes = array();
		foreach ( $query->posts as $post_id ) {
			$product_id = $post_id;
			$square_id = get_post_meta( $post_id, '_square_item_id' );
			$main_image_id = get_post_meta( $post_id, '_square_item_image_id' );
			if ( isset($main_image_id[0]) ) {

				$square_data = square_get_object_data($square_id[0]);
				$related_objects = $square_data->related_objects;

				$curl_meta = check_for_update($product_id, $related_objects, $main_image_id[0]);
				$woo_meta = current_product_gallery($product_id);

				if ( $woo_meta == 'Gallery empty' ) {
					$update_result = update_product_gallery($product_id, $related_objects, $main_image_id[0]);
					$update_notes[get_the_title($post_id)] = 'Gallery updated!';
					update_option( 'square_gal_update_last_product_info', 'Gallery updated!' );
				} else {
					if (count(array_diff(array_merge($curl_meta, $woo_meta), array_intersect($curl_meta, $woo_meta))) === 0) {
						$update_notes[get_the_title($post_id)] = 'No update needed!';
						square_gall_update_log( [get_the_title($post_id) => 'No update needed!' ] );
						update_option( 'square_gal_update_last_product_info', 'No update needed!' );
					} else {
						$update_result = update_product_gallery($product_id, $related_objects, $main_image_id[0]);
						$update_notes[get_the_title($post_id)] = 'Gallery updated!';
						square_gall_update_log( [get_the_title($post_id) => 'Gallery updated!' ] );
						update_option( 'square_gal_update_last_product_info', 'Gallery updated!' );
					}
				}
				
				update_option( 'square_gal_update_last_product_id', $product_id);
				update_option( 'square_gal_update_last_product_name', get_the_title($post_id));
				
			}
		}
		update_option( 'square_gal_update_sync_status', 'Done');
		update_option( 'square_gal_update_last_page', $paged);
	} else {
		update_option( 'square_gal_update_sync_status', 'Done');
		update_option( 'square_gal_update_last_page', 0);
		$hasPosts = false;
	}

	wp_reset_postdata();
	square_gall_update_log($update_notes);
	
}

//CRONS
add_action( 'sync_gal_from_square_event', 'setup_get_product_galleries_from_square' );
add_action( 'sync_gal_from_square_event_stop', 'get_product_galleries_from_square_stop' );
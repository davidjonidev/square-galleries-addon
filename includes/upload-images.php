<?php

function upload_image_by_url( $image_url, $image_name, $square_id ) {

	// CHECK IF IMAGE IS ALREADY IN MEDIA LIBRARY
	$args = array(
		'post_status'  => 'inherit',
		'post_type'    => array( 'attachment' ),
		'meta_key'     => '_square_id_ref',
		'meta_value'   => $square_id,
		'meta_compare' => 'LIKE',
	);
	$check_library = new WP_Query($args);

	if ( $check_library->posts ) {
		return $check_library->posts[0]->ID;
	}

	// it allows us to use download_url() and wp_handle_sideload() functions
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	
	if ( ! function_exists( 'download_url' ) ) {
		return "download_url missing!";
	}

	// download to temp dir
	$temp_file = download_url( $image_url );

	if( is_wp_error( $temp_file ) ) {
		return $false;
	}

	// move the temp file into the uploads directory
	$file = array(
		'name'     => $image_name,
		'type'     => mime_content_type( $temp_file ),
		'tmp_name' => $temp_file,
		'size'     => filesize( $temp_file ),
	);
	$sideload = wp_handle_sideload(
		$file,
		array(
			'test_form'   => false // no needs to check 'action' parameter
		)
	);

	if( ! empty( $sideload[ 'error' ] ) ) {
		// you may return error message if you want
		return false;
	}

	// it is time to add our uploaded image into WordPress media library
	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => $sideload[ 'url' ],
			'post_mime_type' => $sideload[ 'type' ],
			'post_title'     => basename( $sideload[ 'file' ] ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$sideload[ 'file' ]
	);

	if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return false;
	}

	// update medatata, regenerate image sizes
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
	);

	update_post_meta($attachment_id, '_square_id_ref', $square_id);

	return $attachment_id;

}
<?php

/**
 * Write an entry to a log file in the uploads directory.
 * 
 * @since x.x.x
 * 
 * @param mixed $entry String or array of the information to write to the log.
 * @param string $file Optional. The file basename for the .log file.
 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
 * @return boolean|int Number of bytes written to the lof file, false otherwise.
 */
if ( ! function_exists( 'square_gall_update_log' ) ) {
	function square_gall_update_log( $entry, $mode = 'a', $file = 'square-gall-update-log' ) { 
		// Get WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		// If the entry is array, json_encode.
		if ( is_array( $entry ) ) { 
			$entry = json_encode( $entry ); 
		} 
		// Write the log file.
		$file  = $upload_dir . '/' . $file . '.log';
		$file  = fopen( $file, $mode );
		$bytes = fwrite( $file, current_time( 'mysql' ) . "::" . $entry . "\n" ); 
		fclose( $file ); 
		return $bytes;
	}
}

// function that runs when shortcode is called
function setup_test_square_gall_log() { 
  
	square_gall_update_log( [get_the_title() => 'No update needed!' ] );

}
// register shortcode
add_shortcode('run_test_square_gall_log', 'setup_test_square_gall_log');
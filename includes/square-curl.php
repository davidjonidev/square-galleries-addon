<?php

function square_get_object_data($square_id) {
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://connect.squareup.com/v2/catalog/object/'. $square_id .'?include_related_objects=true',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer [INSERT API KEY HERE]',
			'Content-Type: application/json'
		),
	));

	$response = curl_exec($curl);
	$response = json_decode($response);

	curl_close($curl);
	return ($response);
}
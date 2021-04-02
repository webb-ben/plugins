<?php
/*
Plugin Name: Bulk API Import
Plugin URI: https://github.com/webb-ben/plugins/bulk-api-import
Description: Quickly shortens url and csv files of urls via api without default yourls checks.
Version: 1.0
Author: Ben Webb
Author URI: http://github.com/Webb-Ben
*/

// Define custom api action 'quick_shorten'
yourls_add_filter( 'api_action_shorten_quick', 'quick_shorten' );
// Quick Shorten
function quick_shorten() {
    // Retrieve vals from HTTP request
	$url = ( isset( $_REQUEST['url'] ) ? $_REQUEST['url'] : '' );
	$keyword = ( isset( $_REQUEST['keyword'] ) ? $_REQUEST['keyword'] : '' );
	$title = ( isset( $_REQUEST['title'] ) ? $_REQUEST['title'] : '' );

	if (yourls_insert_link_in_db( $url, $keyword, $title )){
    // If link added, populate response vals
        $return['url']      = array('keyword' => $keyword, 'url' => $url, 'title' => $title, 'date' => $timestamp, 'ip' => $ip );
        $return['status']   = 'success';
        $return['title']    = $title;
        $return['shorturl'] = yourls_link($keyword);
    } 
    else {
    // Couldnt store result
        $return['status']   = 'fail';
        $return['code']     = 'error:db';
        $return['message']  = yourls_s( 'Error saving url to database' );
    }

    // Return response
	return $return;
}

// Define custom api action 'csv_shorten'
yourls_add_filter( 'api_action_shorten_csv', 'csv_shorten' );
// CSV Shorten
function csv_shorten() {
    // Retrieve file from HTTP request
    $file = $_FILES['import'];
    $count = import_urls( $file );

    // Form response with count of urls added from csv
    $return['status'] = 'success';
    $return['request'] = $_REQUEST;
    $return['message'] = yourls_s( 'Added '.$count.' links to database');

    // Return response
    return $return;
}

// Adapted from from yourls-bulk-import-and-shorten
// https://github.com/vaughany/yourls-bulk-import-and-shorten).
// Import csv of urls
function import_urls( $file ) {
    // Check if file was uploaded
    if ( !is_uploaded_file( $file['tmp_name'] ) ) {
        yourls_add_notice('Not an uploaded file.');
    }

    // Only handle .csv files
    if ($file['type'] !== 'text/csv') {
        yourls_add_notice('Not a .csv file.');
        return 0;
    }

    global $ydb;

    ini_set( 'auto_detect_line_endings', true );
    $count  = 0;
    $fh     = fopen( $file['tmp_name'], 'r' );
    $table  = YOURLS_DB_TABLE_URL;

    // If the file handle is okay.
    if ( $fh ) {

        // Get each line in turn as an array, comma-separated.
        while ( $csv = fgetcsv( $fh, 1000, ',' ) ) {

            $url = $keyword = $title = '';

            $url = trim( $csv[0] );

            if ( isset( $csv[1] ) && !empty( $csv[1] ) ) {
                $keyword = trim( $csv[1] );
            }

            if ( isset( $csv[2] ) && !empty( $csv[2] ) ) {
                $title = trim( $csv[2] );
            }

            // If the requested keyword is free, shorten url.
            if ( yourls_keyword_is_free( $keyword ) ) {
                if (yourls_insert_link_in_db( $url, $keyword, $title )){
                    $count++;
                }
            }
        }

    } else {
        yourls_add_notice('File handle is bad.');
    }

    // Return count of urls added
    return $count;
}
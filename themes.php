<?php

/*
Plugin Name: Theme parser
Plugin URI: 
Description: 
Author: Dmitriy Zhdankin
Version: 1.0
Author URI: 
*/

error_reporting(E_ALL);
ini_set("display_errors","1");

//global $wpdb;
//$wpdb->show_errors();

/**
 * @todo move to autoload
 */
require_once('lib/simple_html_dom.php');
require_once('additional_function.php');
require_once('parsing_themes.php');
require_once('controller.php');


spl_autoload_register('autoloader');
function autoloader( $class ) {
    $filename = 'classes/' . $class . '.class.php';
    if( file_exists( $filename ) ) {
        require_once( $filename );
    } else {
        $filename = 'libs/' . $class . '.class.php';
        if( file_exists( $filename ) ) {
            require_once( $filename );
        }
    }
}



?>

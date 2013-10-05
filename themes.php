<?php

/*
Plugin Name: Theme parser
Plugin URI:
Description:
Author: Dmitriy Zhdankin
Version: 1.0
Author URI:
*/

//$wpdb->show_errors();

/**
 * @todo move to autoload
 */
require_once('lib/simple_html_dom.php');
add_action('init','init_controller');

spl_autoload_register('autoloader');
function autoloader( $class ) {
    $filename = 'classes/' . $class . '.class.php';
    $curent_dir = dirname(__FILE__).'/';

    if( file_exists( $curent_dir.$filename ) ) {
        require_once( $filename );
    } else {
        $filename = 'libs/' . $class . '.class.php';
        if( file_exists( $curent_dir.$filename ) ) {
            require_once( $filename );
        } else {
            $filename = 'source_sites/' . $class . '.php';
            if( file_exists( $curent_dir.$filename ) ) {
                require_once( $filename );
            }
        }
    }
}

function init_controller(){
    require_once('controller.php');
}

?>

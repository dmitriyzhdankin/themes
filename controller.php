<?php
$url = $_SERVER['REQUEST_URI'];
$url = trim( $url, '/');
if( $url ) {
    $path = explode('/', $url);
    if( $path && $path[0] == 'pars' && isset($path[1]) && $path[1] ) {

        error_reporting(E_ALL);
        ini_set("display_errors","1");

        switch( $path[1] ) {
            case 'download_theme' : {
                downloadTheme();
            }
        }
    }
}

function downloadTheme() {
    $Theme = new Theme();
    $Theme->getNotLoadedTheme();
    $source_site_name = $Theme->theme_options['site_name'];
    $tmp = eval('$parser = new '.$source_site_name."();");
    if( $parser ) {
        $parser->theme_options = $Theme->theme_options;
        $parser->createTheme();
        $parser->setThemeIsDownloaded();
        echo '<br>'.$parser->theme_options['theme_name'].' was loaded';
    }
    die;
}
?>
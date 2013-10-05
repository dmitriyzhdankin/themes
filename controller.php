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
    $theme = getThemeForDownload();
    createTheme($theme);
    setThemeIdDownloaded($theme['id']);
    die;
}
?>

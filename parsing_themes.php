<?php

$site_name = 'http://wordpress.org';
$to = '/themes/tag-filter/';
    
if( isset($_GET['parsing_fields']) ) {
    
    $fields = array();

    $page_info = get_web_page($site_name.$to);
    $page_content = $page_info['content'];
    $html = str_get_html($page_content);
    foreach($html->find('#tag-filter-form input[type=checkbox]') as $checkbox ) {
        $fields[$checkbox->name] = $checkbox->value;
    }
    
    insertFieldsList($fields);
    
} elseif( isset($_GET['parsing_fields']) ) {
    
    $fields = getFieldsList();
    if( $fields ) {
        foreach( $fields as $key => $value ) {
            echo '<br>';
            echo 'Start work with field '.$value;
            $page_info = get_web_page($site_name.$to,array($key=>$value));
            $page_content = $page_info['content'];
            fiendThemesOnPage($page_content);
            echo '<hr>';
            unset($fields[$key]);
            updateFieldsList($fields);
            die;
        }
    } else {
        echo '<br>Dont Found Filters';
    }
    
} elseif( isset($_GET['download_theme']) ) {
    
}

?>

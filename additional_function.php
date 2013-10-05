<?php
//error_reporting(E_ALL);
//ini_set("display_errors","1");
//$wpdb->show_errors();

function get_web_page( $url, $post_fields = array() ) {
    $uagent="Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.9";
    $ch = curl_init( $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    if( $post_fields ) {
        $post_fields_string = '';
        foreach($post_fields as $key=>$value) { $post_fields_string .= $key.'='.$value.'&'; }
        rtrim($post_fields_string, '&');
        curl_setopt($ch,CURLOPT_POST, count($post_fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $post_fields_string);
    }
    $content=curl_exec( $ch );
    $err=curl_errno( $ch );
    $errmsg=curl_error( $ch );
    $header=curl_getinfo( $ch );
    curl_close( $ch );
    $header['errno']=$err;
    $header['errmsg']=$errmsg;
    $header['content']=$content;
    return $header;
} 

function insertTheme($theme) {
    global $wpdb;
    $table_name = 'themes';
    $data = array(
        'name' => $theme['name'],
        'url' => $theme['url'],
        'zip_url' => $theme['zip'],
        'preview' => $theme['preview'],
        'screenshot' => $theme['screenshot'],
    );
    return $wpdb->insert($table_name,$data);
}

function fiendThemesOnPage($page_content) {
    //var_dump($page_content);die;
    $html = str_get_html($page_content);
    $count_themes_on_page = count($html->find('.available-theme'));
    $count_inserted_themes = 0;
    foreach($html->find('.available-theme') as $el) {

        $name_els = $el->find('a.activatelink[target=_blank]'); //name
        $zip_el = $el->find('span a.activatelink');             //url
        $preview_el = $el->find('a.previewlink');               //preview    
        $screenshot_el = $el->find('a.previewlink img');        //screenshot
        
        $site_name = 'http://wordpress.org';

        $theme_info = array(
            'name' => $name_els[0]->innertext,
            'url' => $site_name.$name_els[0]->href,
            'zip' => $site_name.$zip_el[0]->href,
            'preview' => $preview_el[0]->href,
            'screenshot' => $screenshot_el[0]->src,
        );

        if( insertTheme($theme_info) ) {
            $count_inserted_themes++;
        }
    } 
    echo '<br>';
    echo 'Count themes = '.$count_themes_on_page;
    echo '<br>';
    echo 'Count Inserted themes = '.$count_inserted_themes;
}

function insertFieldsList( $fileds ) {
    global $wpdb;
    $table_name = $wpdb->prefix.'options';
    if(is_array($fileds) && !empty($fileds)) {
        $wpdb->insert( $table_name,  array('option_name' => 'fields_list', 'option_value' => serialize($fileds) ) );
    } else {
        echo '<br>Fields aren\'t found';
        die;
    }
}
function getFieldsList() {
    global $wpdb;
    $table_name = $wpdb->prefix.'options';
    $query = 'SELECT option_value FROM '.$table_name .' WHERE option_name = "fields_list"';
    if( $option = $wpdb->get_var( $query ) ) {
        return unserialize($option);
    } else {
        echo '<br>Can\'t get Fields list';
        die;
    }
}

function updateFieldsList($fileds) {
    global $wpdb;
    $table_name = $wpdb->prefix.'options';
    if(is_array($fileds) && !empty($fileds)) {
        $wpdb->update( 
                $table_name,  
                array( 'option_value' => serialize($fileds) ), 
                array( 'option_name' => 'fields_list' ) );
    } else {
        echo '<br>Fields aren\'t found for update';
        die;
    }
}

function getThemeForDownload() {
    global $wpdb;
    $table_name = 'themes';
    
    $query = 'SELECT * FROM '.$table_name .' WHERE loaded = 0 LIMIT 0,1';
    return $wpdb->get_row($query,ARRAY_A);
}

function setThemeIdDownloaded($theme_id) {
    global $wpdb;
    $table_name = 'themes';

    $query = 'UPDATE '.$table_name .' SET loaded = 1 WHERE id = '.$theme_id;
    return $wpdb->get_row($query,ARRAY_A);
}

function getThemeOptions( $theme ) {
    $page = get_web_page( $theme['url'] );
    $page_content = $page['content'];
    $html = str_get_html($page_content);
    //description
    $el_description = $html->find('.block-content');
    $theme['description'] = $el_description[0]->text();
    //tags
    $theme['tags'] = array();
    foreach( $html->find('.block-content #plugin-tags a') as $tag ) {
        $theme['tags'][] = $tag->innertext;
    }
	//local zip
	$theme['local_zip'] = 'wp-content/uploads/'.basename($theme['zip_url']);
	file_put_contents(ABSPATH.$theme['local_zip'], fopen($theme['zip_url'], 'r'));
	//preview
	$theme['local_preview'] = $theme['preview'];

	$theme['local_thumbnail'] = $theme['screenshot'];

    return $theme;
}

function createAttachment( $post_id, $image_url ) {
	$upload_dir = wp_upload_dir();
	$image_data = file_get_contents($image_url);
	$filename = $post_id.'_'.basename($image_url);
	if(wp_mkdir_p($upload_dir['path']))
		$file = $upload_dir['path'] . '/' . $filename;
	else
		$file = $upload_dir['basedir'] . '/' . $filename;
	file_put_contents($file, $image_data);

	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => sanitize_file_name($filename),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	set_post_thumbnail( $post_id, $attach_id );
}

function createTheme( $theme ) {

	$theme = getThemeOptions($theme);

	$default_theme = array(
		'name' => false,
		'description' => false,
		'tags' => false,
		'local_zip' => false,
		'local_thumbnail' => false,
		'local_preview' => false,
	);
	$theme = array_merge( $default_theme, $theme );
      if(strpos($theme['description'],'Tags')) {
    	    $theme['description'] = substr($theme['description'],0,strpos($theme['description'],'Tags'));
        }
	$post = array(
		'post_type' => 'post',
		'post_title' => $theme['name'],
		'post_content' => $theme['description'],
		'post_status' => 'publish',
	);
	
	if( is_array($theme['tags']) && !empty($theme['tags']) ) {
		$post['tags_input'] = implode(',', $theme['tags']);
	}

	$post_ID = wp_insert_post( $post );
	if( $post_ID ) {

		createAttachment( $post_ID, $theme['local_thumbnail'] );

		add_post_meta($post_ID, 'preview', $theme['local_preview'], true) or
			update_post_meta($post_ID, 'preview', $theme['local_preview']);
			

		add_post_meta($post_ID, 'zip', $theme['local_zip'], true) or
			update_post_meta($post_ID, 'zip', $theme['local_zip']);

		add_post_meta($post_ID, 'thumbnail', $theme['local_thumbnail'], true) or
			update_post_meta($post_ID, 'thumbnail', $theme['local_thumbnail']);
		
		$zip_id = createLocalZip($theme);
		add_post_meta($post_ID, 'zip_id', $zip_id) or
		        update_post_meta($post_ID, 'zip_id', $zip_id);
		        
		return true;
	}

}

function createLocalZip($theme) {
    global $wpdb;
    $file_table = 'ahm_files';
        $file = array(
        'title' => $theme['name'],
        'description' => $theme['name'],
        'category' => 'N;',
        'file' => basename($theme['local_zip']),
        'access' => 'guest',
        'link_label' => 'Download',
	'icon' => '35.png',
    );
    $wpdb->insert($file_table,$file);
    return $wpdb->insert_id;    
}
?>

<?php
class Theme {
    public $source_site = false;
    public $site_name = false;

    public $page_content = false;

    protected $parsed_themes_table = 'themes';
    protected $local_zip_path = 'wp-content/uploads/';

    protected $db = false;
    protected $theme_page_html = false;
    public $theme_options = false;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->db->show_errors();

        if( !$this->source_site || !$this->site_name ) {
            return false;
        }
        return true;
    }

    public function getNotLoadedTheme() {
        $query = 'SELECT * FROM '.$this->parsed_themes_table .' WHERE loaded = 0 LIMIT 0,1';
        $this->theme_options = $this->db->get_row( $query, ARRAY_A );
    }

    protected function getThemeOptions() {
        if( !$this->theme_options ) {
            return false;
        }
        $page = $this->get_web_page( $this->theme_options['url'] );
        $page_content = $page['content'];
        $this->theme_page_html = str_get_html($page_content);

        $this->theme_options['theme_name'] = $this->getName();
        $this->theme_options['theme_description'] = $this->getDescription();
        $this->theme_options['theme_tags'] = $this->getTags();
        $this->theme_options['theme_local_zip'] = $this->getLocalZip();
        $this->theme_options['theme_local_preview'] = $this->getLocalPreview();
        $this->theme_options['theme_local_thumbnail'] = $this->getThumbnail();
    }

    public function createTheme() {
        $this->getThemeOptions();

        $default_theme = array(
            'theme_name' => false,
            'theme_description' => false,
            'theme_tags' => false,
            'theme_local_zip' => false,
            'theme_local_thumbnail' => false,
            'theme_local_preview' => false,
        );

        $theme = array_merge( $default_theme, $this->theme_options );

        $post = array(
            'post_type' => 'post',
            'post_title' => $theme['theme_name'],
            'post_content' => $theme['theme_description'],
            'post_status' => 'publish',
        );

        if( is_array($theme['theme_tags']) && !empty($theme['theme_tags']) ) {
            $post['tags_input'] = implode(',', $theme['theme_tags']);
        }

        $post_ID = wp_insert_post( $post );
        if( $post_ID ) {

            if( $theme['theme_local_thumbnail'] ) {
                $this->addThumbnailToPost( $post_ID, $theme['theme_local_thumbnail'] );
            }

            if( $theme['theme_local_preview'] ) {
                add_post_meta($post_ID, 'preview', $theme['theme_local_preview'], true) or
                    update_post_meta($post_ID, 'preview', $theme['theme_local_preview']);
            }

            if( $theme['theme_local_zip'] ) {
                add_post_meta($post_ID, 'zip', $theme['theme_local_zip'], true) or
                    update_post_meta($post_ID, 'zip', $theme['theme_local_zip']);
            }

            if( $theme['theme_local_thumbnail'] ) {
                add_post_meta($post_ID, 'thumbnail', $theme['theme_local_thumbnail'], true) or
                    update_post_meta($post_ID, 'thumbnail', $theme['theme_local_thumbnail']);
            }

            if( $theme['theme_local_zip'] ) {
                $zip_id = $this->createLocalZip($theme);
                add_post_meta($post_ID, 'zip_id', $zip_id) or
                    update_post_meta($post_ID, 'zip_id', $zip_id);
            }

            return true;
        }

    }

    protected function getName() {
        return $this->theme_options['name'];
    }

    protected function getDescription() {
        return '';
    }

    protected function getTags() {
        return array();
    }

    protected function getLocalZip() {
        return false;
    }

    protected function getLocalPreview() {
        return false;
    }

    protected function getThumbnail() {
        return false;
    }

    protected function addThumbnailToPost( $post_id, $image_url ) {
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

    //add local zip to table
    protected function createLocalZip($theme) {
        $file_table = 'ahm_files';
        $file = array(
            'title' => $theme['theme_name'],
            'description' => $theme['theme_name'],
            'category' => 'N;',
            'file' => basename($theme['theme_local_zip']),
            'access' => 'guest',
            'link_label' => 'Download',
            'icon' => '35.png',
        );
        $this->db->insert($file_table,$file);
        return $this->db->insert_id;
    }

    public function setThemeIsDownloaded() {
        $query = 'UPDATE '. $this->parsed_themes_table .' SET loaded = 1 WHERE id = '.$this->theme_options['id'];
        return $this->db->query($query);
    }

    public function get_web_page( $url, $post_fields = array() ) {
        $headers = array('Content-type: text/html; charset=utf-8');
        $uagent="Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.9";
        $ch = curl_init( $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

    protected function get_web_page_html( $url, $post_fields = array() ) {
        $page = $this->get_web_page( $url, $post_fields );
        $page_content = $page['content'];
        $this->theme_page_html = str_get_html($page_content);
    }

    protected function insertTheme($theme) {
        $data = array(
            'name' => $theme['name'],
            'url' => $theme['url'],
            'zip_url' => $theme['zip'],
            'preview' => $theme['preview'],
            'screenshot' => $theme['screenshot'],
            'site_name' => $theme['site_name'],
        );
        return $this->db->insert($this->parsed_themes_table,$data);
    }




    /*******************************************/
    /**
     * Pager
     */

    protected function setPagesList( $pages ) {
        $table_name = $this->db->prefix.'options';

        if(is_array($pages) && !empty($pages)) {
            if( $this->getPagesList() === null ) {
                $this->db->insert( $table_name,  array('option_name' => $this->site_name.'_pages_list', 'option_value' => serialize($pages) ) );
            } else {
                $this->updatePagesList($pages);
            }
        } else {
            echo '<br>Fields aren\'t found';die;
        }
    }

    protected function getPagesList() {
        $table_name = $this->db->prefix.'options';
        $query = 'SELECT option_value FROM '.$table_name .' WHERE option_name = "'.$this->site_name.'_pages_list"';
        $option = $this->db->get_var( $query );
        if( $option && is_serialized( $option )) {
            return unserialize($option); // not loaded pages
        } elseif( $option === null ) {
            return null; // any pages was not loaded
        } elseif( $option == 1 ) {
            return false; // all pages was loaded
        }
    }

    protected function createPagesList( $count_pages ) {
        $pages_list = array();
        for( $i=1; $i <= $count_pages; $i++ ) {
            $pages_list[$i] = $i;
        }
        return $pages_list;
    }

    protected function updatePagesList( $pages ) {
        $table_name = $this->db->prefix.'options';

        if(is_array($pages) && !empty($pages)) {
            $this->db->update( $table_name,  array( 'option_value' => serialize($pages) ), array( 'option_name' => $this->site_name.'_pages_list' ) );
        } else {
            $this->db->update( $table_name,  array( 'option_value' => 1 ), array( 'option_name' => $this->site_name.'_pages_list' ) );
        }
    }

    protected function loadPagesList() {
        $this->get_web_page_html( $this->source_site );

        $count_pages = $this->getCountPages();
        $pages_list = $this->createPagesList($count_pages);
        $this->setPagesList($pages_list);
        die('pages list was loaded');
    }

    protected function getCountPages() {
        return 0;
    }


}
?>

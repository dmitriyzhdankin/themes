<?php
class Smthemes extends Theme {
    public $source_site = 'http://smthemes.com';
    public $site_name = 'smthemes';

    public function getThemes() {

        $pages_list = $this->getPagesList();
        if( $pages_list === null ) {
            $this->loadPagesList();//need load pages list
        } elseif( $pages_list === false ) {
            die('all pages was loaded');// all pages was loaded // need stop script
        } elseif(is_array($pages_list) ) {
            $this->loadPageWithThemes($pages_list); // need load page
        }
        die('1');
    }

    private function loadPageWithThemes( $pages_list ) {
        foreach( $pages_list as $key => $page_number ) {
            echo $key;
            $this->get_web_page_html( $this->source_site.'/page/'.$page_number );
            $this->insertThemesFromPageHtml();
            unset($pages_list[$key]);
            $this->updatePagesList($pages_list);
            die;
        }
    }

    private function insertThemesFromPageHtml() {
        foreach( $this->theme_page_html->find('#main_content #catalog .item') as $theme ) {
            $theme_info = array(
                'name' => $theme->find('.caption h2', 0)->innertext,
                'url' => $theme->find('.caption a', 0)->href,
                'preview' => $this->source_site.$theme->find('.review .demo', 0)->href,
                'zip' => $this->source_site.$theme->find('.review .download', 0)->href,
                'screenshot' => $theme->find('.review a img', 0)->src,
                'site_name' => $this->site_name,
            );
            $this->insertTheme($theme_info);
        }
    }

    private function getThemeName( $theme_html ) {

    }

    private function loadPagesList() {
        $this->get_web_page_html( $this->source_site );

        $count_pages = $this->getCountPages();
        $pages_list = $this->createPagesList($count_pages);
        $this->setPagesList($pages_list);
        die('pages list was loaded');
    }

    private function getCountPages() {
        if( $this->theme_page_html ) {
            $num_page = '';
            foreach( $this->theme_page_html->find('#main_content .pagination a') as $page ) {
                $num_page = $page->innertext;
            }
            if( $num_page ) {
                return intval($num_page);
            }
        }
        return false;
    }

    private function createPagesList( $count_pages ) {
        $pages_list = array();
        for( $i=1; $i <= $count_pages; $i++ ) {
            $pages_list[$i] = $i;
        }
        return $pages_list;
    }

    private function getPagesList() {
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

    private function setPagesList( $pages ) {
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

    private function updatePagesList( $pages ) {
        $table_name = $this->db->prefix.'options';

        if(is_array($pages) && !empty($pages)) {
            $this->db->update( $table_name,  array( 'option_value' => serialize($pages) ), array( 'option_name' => $this->site_name.'_pages_list' ) );
        } else {
            $this->db->update( $table_name,  array( 'option_value' => 1 ), array( 'option_name' => $this->site_name.'_pages_list' ) );
        }
    }


    protected function getDescription() {
        return $this->theme_page_html->find('#upcontent .articles dd div div',0)->innertext;
    }

    protected function getTags() {
        $tags = array();
        foreach( $this->theme_page_html->find('.bigitem div a') as $tag ) {
            if( ( strpos($tag->innertext, 'Download') !== 0 ) && $tag->innertext != 'Demo' ) {
                $tags[] = $tag->innertext;
            }
        }
        return $tags;
    }

    protected function getLocalZip() {
        $local_zip =  $this->local_zip_path.basename($this->theme_options['zip_url']);
        file_put_contents(ABSPATH.$local_zip.'.zip', fopen('http://smthemes.com/getfile/'.basename($this->theme_options['zip_url']), 'r'));
        return $local_zip;
    }

    protected function getLocalPreview() {
        return $this->theme_options['preview'];
    }

    protected function getThumbnail() {
        return $this->theme_options['screenshot'];
    }


}
?>

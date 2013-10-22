<?php
class Rockkitty extends Theme {
    public $source_site = 'http://themes.rock-kitty.net/';
    public $site_name = 'rockkitty';

    /**
     *@todo moved to parent
     */
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
        ini_set("max_execution_time", "600");
        foreach( $pages_list as $key => $page_number ) {
            echo $key;
            $this->get_web_page_html( $this->source_site.'/page/'.$page_number );
            $this->insertThemesFromPageHtml();
            unset($pages_list[$key]);
            $this->updatePagesList($pages_list);
            //die;
        }
    }

    /**
     * @todo need move to parent. need create some functions like 'getName, getUrl, getPrevie ...'
     */
    private function insertThemesFromPageHtml() {

        foreach( $this->theme_page_html->find('.leftside .template-box') as $theme ) {
            $theme_info = array(
                'name' => $theme->find('h3 a', 0)->innertext,
                'url' => $theme->find('h3 a', 0)->href,
                'preview' => $theme->find('.notify .preview a', 0)->href,
                'zip' => $theme->find('.notify .download a', 0)->href,
                'screenshot' => substr($theme->find('.image a img', 0)->src, strpos($theme->find('.image a img', 0)->src, 'imgfile=') +  strlen('imgfile=') ),
                'site_name' => $this->site_name,
            );
            $this->insertTheme($theme_info);
        }
    }

    protected function getCountPages() {
        if( $this->theme_page_html ) {
            $href = trim( $this->theme_page_html->find('.wp-pagenavi a.last',0)->href, '/');
            return intval(substr($href, strrpos( $href, '/')+1 ));
        }
        return false;
    }

    protected function getDescription() {
        return $this->theme_page_html->find('.innerimage p',7)->innertext;
    }

    protected function getTags() {
        $tags = array();
        foreach( $this->theme_page_html->find('.template-box2 .postmeta a') as $tag ) {
            $tags[] = $tag->innertext;
        }
        $tags[] = trim(preg_replace("(<([a-z]+)>.*?</\\1>)is","",$this->theme_page_html->find('.innerimage p',3)->innertext));
        return $tags;
    }

    protected function getLocalZip() {
        $local_zip =  $this->local_zip_path.basename($this->theme_options['url']).'.zip';
        file_put_contents(ABSPATH.$local_zip, fopen($this->theme_options['zip_url'], 'r'));
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
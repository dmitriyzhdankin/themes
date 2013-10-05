<?php
class Wordpress extends Theme {
    public $source_site = 'http://wordpress.org';
    public $site_name = 'wordpress';

    protected function getDescription() {
        $el_description = $this->theme_page_html->find('.block-content');
        $description = '';
        if( $el_description ) {
            $description = $el_description[0]->text();
        }
        // remove Tags from descriptipon
        if(strpos($description,'Tags')) {
    	    $description = substr($description,0,strpos($description,'Tags'));
        }
        return $description;
    }

    protected function getTags() {
        $tags = array();
        foreach( $this->theme_page_html->find('.block-content #plugin-tags a') as $tag ) {
            $tags[] = $tag->innertext;
        }
        return $tags;
    }

    protected function getLocalZip() {
        $local_zip =  $this->local_zip_path.basename($this->theme_options['zip_url']);
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

<?php
/*
Plugin Name: Post Filtration
Description: It counts the total words of a post and display that at All Post Section. It also provides the scopes of filtration of posts based on Best Comments and Total Words of a post.
Author: Zakaria Binsaifullah
Author URI: https://wpquerist.com
Version: 1.1.0
Text Domain: post-filtration
License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Domain Path:  /languages
*/


if (!function_exists('add_action')) {
    echo "Don't try this illegal way.";
    exit();
}


class PSN_POST_FILTRATION
{
    public function __construct() {

        add_filter('manage_posts_columns', array($this, 'pfn_custom_column'));
        add_action('manage_posts_custom_column', array($this, 'pfn_post_words'), 10, 2);

        // filter
        add_action('restrict_manage_posts', array($this, 'pfn_words_filter'));
        add_action('pre_get_posts', array($this, 'pfn_words_filtration'));

        add_action('restrict_manage_posts', array($this, 'pfn_thumb_filter'));
        add_action('pre_get_posts', array($this, 'pfn_thumb_filtration'));

        // sortable
        add_filter('manage_edit-post_sortable_columns',array($this,'pfn_word_sortable_cols'));

        add_action('pre_get_posts',array($this,'pfn_word_sortable_data'));

        add_action('save_post',array($this,'pfn_sortable_post_save'));

        add_action('init',array($this,'pfn_set_word_count'));

    }

    function pfn_set_word_count() {
        $posts = get_posts( array(
            'posts_per_page' => - 1,
            'post_type'      => 'post',
            'post_status'    => 'any'
        ) );

        foreach ( $posts as $p ) {
            $content = $p->post_content;
            $wordn   = str_word_count( strip_tags( $content ) );
            update_post_meta( $p->ID, 'wordn', $wordn );
        }
    }

    // sortable

    public function pfn_word_sortable_cols($cols){
        $cols['words'] = 'wordn';
        return $cols;
    }

    public function pfn_word_sortable_data($query){
        if (! is_admin()){
            return;
        }

        $orderby = $query->get('orderby');
        if ('wordn' == $orderby){
            $query->set('meta_key','wordn');
            $query->set('orderby','meta_value_num');
        }

    }

    public function pfn_sortable_post_save($id){
        $p = get_post($id);
        $content = $p->post_content;
        $wordn = str_word_count(strip_tags($content));

        update_post_meta($id,'wordn',$wordn);
    }

    public function pfn_custom_column($cols) {
        $cols['thumb'] = __('Thumbnail', 'post-filtration');
        $cols['words'] = __('Words', 'post-filtration');
        return $cols;
    }

    public function pfn_post_words($cols, $id) {
        if ($cols == 'words') {
            $post         = get_post($id);
            $post_content = $post->post_content;
            $raw_post     = strip_tags($post_content);
            $total_words  = str_word_count($raw_post);
            echo $total_words;
        } elseif ($cols == 'thumb') {
            $thumbnail = get_the_post_thumbnail($id, array(50, 40));
            echo $thumbnail;
        }
    }

    public function pfn_words_filter() {
        $options = array(
            'select'  => __('Filter By Words', 'post-filtration'),
            'less_3'  => __('Less than 300', 'post-filtration'),
            '3_btn_9' => __('Between 300 to 900', 'post-filtration'),
            'more_9'  => __('More than 900', 'post-filtration'),
        );

        if (isset($_GET['post_type']) && $_GET['post_type'] != 'post') {
            return;
        }

        $querySelect = isset($_GET['wordsCount']) ? sanitize_text_field($_GET['wordsCount']) : '';

        ?>
        <select name="wordsCount">
            <?php
            foreach ($options as $key => $option) {

                $select = '';
                if ($querySelect == $key) {
                    $select = 'selected';
                }

                printf("<option value='%s' %s >%s</option>", $key, $select, $option);
            }
            ?>
        </select>
        <?php

    }

    public function pfn_thumb_filter() {
        $options = array(
            '0' => __('Filter By Thumbnail', 'post-filtration'),
            '1' => __('Has Thumbnail', 'post-filtration'),
            '2' => __('No Thumbnail', 'post-filtration'),
        );

        if (isset($_GET['post_type']) && $_GET['post_type'] != 'post') {
            return;
        }

        $querySelect = isset($_GET['thumbnail']) ? sanitize_text_field($_GET['thumbnail']) : '';

        ?>
        <select name="thumbnail">
            <?php
            foreach ($options as $key => $option) {

                $select = '';
                if ($querySelect == $key) {
                    $select = 'selected';
                }

                printf("<option value='%s' %s >%s</option>", $key, $select, $option);
            }
            ?>
        </select>
        <?php

    }

    public function pfn_words_filtration($query) {

        if (!is_admin()) {
            return;
        }

        $querySelect = isset($_GET['wordsCount']) ? sanitize_text_field($_GET['wordsCount']) : '';

        if ($querySelect == 'less_3') {
            $query->set('meta_query', array(
                'key'     => 'wordn',
                'value'   => 300,
                'compare' => '<=',
                'type'    => 'NUMERIC'
            ));
        } elseif ($querySelect == '3_btn_9') {
            $query->set('meta_query', array(
                'key'     => 'wordn',
                'value'   => array(300, 900),
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC'
            ));
        } elseif ($querySelect == 'more_9') {
            $query->set('meta_query', array(
                'key'     => 'wordn',
                'value'   => 900,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ));
        }

    }

    public function pfn_thumb_filtration($query) {

        if (!is_admin()) {
            return;
        }

        $querySelect = isset($_GET['thumbnail']) ? sanitize_text_field($_GET['thumbnail']) : '';

        if ($querySelect == '1') {
            $query->set('meta_query', array(
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ));
        }elseif ($querySelect == '2'){
            $query->set('meta_query', array(
                'key'     => '_thumbnail_id',
                'compare' => 'NOT EXISTS',
            ));
        }

    }

}

new PSN_POST_FILTRATION();
<?php
/**
 * Plugin Name: Spelfabet Cody
 * Plugin URI: http://www.cbdweb.net
 * Description: Assess texts using Phoneme-Grapheme correspondences
 * Version: 0.9
 * Author: Nik Dow, CBDWeb
 * License: GPL2
 */
require_once plugin_dir_path ( __FILE__ ) . 'options.php';

function spelfabet_cody_enqueue_scripts(){
    /*see otu-newsletter for examples*/
}
add_action( 'admin_enqueue_scripts', spelfabet_cody_enqueue_scripts() );

add_action( 'init', 'create_spelfabet_cody');
function create_spelfabet_cody(){
    $labels = array(
        'name' => _x('word-CGT', 'post type general name'),
        'singular_name' => _x('Newsletter', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Newsletter'),
        'edit_item' => __('Edit Newsletter'),
        'new_item' => __('New Newsletter'),
        'view_item' => __('View Newsletter'),
        'search_items' => __('Search Newsletter'),
        'not_found' =>  __('No newsletters found'),
        'not_found_in_trash' => __('No newsletters found in Trash'),
        'parent_item_colon' => '',
    );
    register_post_type( 'cbdweb_newsletter',
        array(
            'label'=>__('Newsletters'),
            'labels' => $labels,
            'description' => 'Each post is one newsletter.',
            'public' => true,
            'can_export' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'show_ui' => true,
            'capabilities' =>array(
                'edit_post'=>'otu_newsletter_edit',
                'edit_posts'=>'otu_newsletter_edit',
                'edit_others_posts'=>'otu_newsletter_edit',
                'publish_posts'=>'otu_newsletter_edit',
            ),
            'menu_icon' => "dashicons-megaphone",
            'hierarchical' => false,
            'rewrite' => false,
            'supports'=> array('title', 'editor' ) ,
            'show_in_nav_menus' => true,
        )
    );
}
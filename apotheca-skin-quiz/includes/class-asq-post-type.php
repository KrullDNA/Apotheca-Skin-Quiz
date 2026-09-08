<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Apotheca Skin Quiz custom post type.
 */
class ASQ_Post_Type {

    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        $labels = array(
            'name'               => __( 'Apotheca Skin Quizs', 'apotheca-skin-quiz' ),
            'singular_name'      => __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'add_new'            => __( 'Add New', 'apotheca-skin-quiz' ),
            'add_new_item'       => __( 'Add New Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'edit_item'          => __( 'Edit Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'new_item'           => __( 'New Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'view_item'          => __( 'View Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'search_items'       => __( 'Search Apotheca Skin Quizs', 'apotheca-skin-quiz' ),
            'not_found'          => __( 'No product finders found', 'apotheca-skin-quiz' ),
            'not_found_in_trash' => __( 'No product finders found in trash', 'apotheca-skin-quiz' ),
            'menu_name'          => __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-search',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array( 'title' ),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'show_in_rest'        => false,
        );

        register_post_type( 'apotheca_skin_quiz', $args );
    }
}

new ASQ_Post_Type();

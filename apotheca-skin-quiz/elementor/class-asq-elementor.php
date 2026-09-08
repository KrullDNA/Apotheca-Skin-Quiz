<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Apotheca Skin Quiz Elementor widget.
 */
class ASQ_Elementor {

    public function __construct() {
        add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
    }

    public function register_category( $elements_manager ) {
        $elements_manager->add_category( 'apotheca-skin-quiz', array(
            'title' => __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'icon'  => 'eicon-search',
        ) );
    }

    public function register_widget( $widgets_manager ) {
        require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-elementor-widget.php';
        $widgets_manager->register( new ASQ_Elementor_Widget() );
    }
}

new ASQ_Elementor();

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
        add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_frontend_scripts' ) );
    }

    public function register_frontend_scripts() {
        wp_register_script(
            'asq-add-to-cart',
            ASQ_PLUGIN_URL . 'frontend/js/asq-add-to-cart.js',
            array( 'jquery' ),
            ASQ_VERSION,
            true
        );
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

        require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-add-to-cart-widget.php';
        $widgets_manager->register( new ASQ_Add_To_Cart_Widget() );

        require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-shade-cart-widget.php';
        $widgets_manager->register( new ASQ_Shade_Cart_Widget() );

        require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-product-image-widget.php';
        $widgets_manager->register( new ASQ_Product_Image_Widget() );

        require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-category-label-widget.php';
        $widgets_manager->register( new ASQ_Category_Label_Widget() );
    }
}

new ASQ_Elementor();

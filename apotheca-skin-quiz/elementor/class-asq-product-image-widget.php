<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * PF Product Image – simple product image for CrocoBlock listings.
 *
 * Renders the product featured image without zoom / lightbox.
 * When inside a Apotheca Skin Quiz results listing, automatically
 * swaps to the matched variation image.
 */
class ASQ_Product_Image_Widget extends Widget_Base {

    public function get_name() {
        return 'asq_product_image';
    }

    public function get_title() {
        return __( 'PF Product Image', 'apotheca-skin-quiz' );
    }

    public function get_icon() {
        return 'eicon-image';
    }

    public function get_categories() {
        return array( 'apotheca-skin-quiz' );
    }

    public function get_keywords() {
        return array( 'image', 'product', 'photo', 'variation', 'listing' );
    }

    public function get_style_depends() {
        return array( 'asq-frontend' );
    }

    /* ═══════════════════════════════════════
       CONTROLS
       ═══════════════════════════════════════ */

    protected function register_controls() {

        /* ── Content ── */

        $this->start_controls_section( 'section_content', array(
            'label' => __( 'Image', 'apotheca-skin-quiz' ),
        ) );

        $this->add_control( 'image_size', array(
            'label'   => __( 'Image Size', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'woocommerce_single',
            'options' => array(
                'thumbnail'            => __( 'Thumbnail', 'apotheca-skin-quiz' ),
                'medium'               => __( 'Medium', 'apotheca-skin-quiz' ),
                'medium_large'         => __( 'Medium Large', 'apotheca-skin-quiz' ),
                'large'                => __( 'Large', 'apotheca-skin-quiz' ),
                'woocommerce_single'   => __( 'WooCommerce Single', 'apotheca-skin-quiz' ),
                'woocommerce_thumbnail'=> __( 'WooCommerce Thumbnail', 'apotheca-skin-quiz' ),
                'full'                 => __( 'Full', 'apotheca-skin-quiz' ),
            ),
        ) );

        $this->add_control( 'link_to_product', array(
            'label'        => __( 'Link to Product', 'apotheca-skin-quiz' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'label_on'     => __( 'Yes', 'apotheca-skin-quiz' ),
            'label_off'    => __( 'No', 'apotheca-skin-quiz' ),
        ) );

        $this->add_responsive_control( 'align', array(
            'label'   => __( 'Alignment', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ),   'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ),  'icon' => 'eicon-text-align-right' ),
            ),
            'default'   => 'center',
            'selectors' => array(
                '{{WRAPPER}} .asq-pi-wrap' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();

        /* ═══════════════════
           STYLE TAB
           ═══════════════════ */

        /* ── Style: Image ── */

        $this->start_controls_section( 'section_style_image', array(
            'label' => __( 'Image', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'image_width', array(
            'label'      => __( 'Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%' ),
            'range'      => array(
                'px' => array( 'min' => 50, 'max' => 1200 ),
                '%'  => array( 'min' => 10, 'max' => 100 ),
            ),
            'default'    => array( 'size' => 100, 'unit' => '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-pi-img' => 'width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'image_max_width', array(
            'label'      => __( 'Max Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%' ),
            'range'      => array(
                'px' => array( 'min' => 50, 'max' => 1200 ),
                '%'  => array( 'min' => 10, 'max' => 100 ),
            ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-pi-img' => 'max-width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'image_height', array(
            'label'      => __( 'Height', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'vh' ),
            'range'      => array(
                'px' => array( 'min' => 50, 'max' => 1000 ),
                'vh' => array( 'min' => 10, 'max' => 100 ),
            ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-pi-img' => 'height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'image_fit', array(
            'label'   => __( 'Object Fit', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'cover',
            'options' => array(
                'cover'   => __( 'Cover', 'apotheca-skin-quiz' ),
                'contain' => __( 'Contain', 'apotheca-skin-quiz' ),
                'fill'    => __( 'Fill', 'apotheca-skin-quiz' ),
                'none'    => __( 'None', 'apotheca-skin-quiz' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-pi-img' => 'object-fit: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'image_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-pi-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'image_border',
            'selector' => '{{WRAPPER}} .asq-pi-img',
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'image_shadow',
            'selector' => '{{WRAPPER}} .asq-pi-img',
        ) );

        $this->add_control( 'image_opacity', array(
            'label'   => __( 'Opacity', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::SLIDER,
            'range'   => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
            'selectors' => array(
                '{{WRAPPER}} .asq-pi-img' => 'opacity: {{SIZE}};',
            ),
        ) );

        $this->add_control( 'hover_opacity', array(
            'label'   => __( 'Hover Opacity', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::SLIDER,
            'range'   => array( 'px' => array( 'min' => 0, 'max' => 1, 'step' => 0.05 ) ),
            'selectors' => array(
                '{{WRAPPER}} .asq-pi-wrap:hover .asq-pi-img' => 'opacity: {{SIZE}};',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Spacing ── */

        $this->start_controls_section( 'section_style_spacing', array(
            'label' => __( 'Spacing', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'image_margin', array(
            'label'      => __( 'Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-pi-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ═══════════════════════════════════════
       RENDER
       ═══════════════════════════════════════ */

    protected function render() {
        global $post, $product;

        // Editor placeholder.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            $this->render_editor_placeholder();
            return;
        }

        // Resolve the product.
        $the_product = $product;
        if ( ! $the_product instanceof \WC_Product && $post ) {
            if ( function_exists( 'wc_get_product' ) ) {
                $the_product = wc_get_product( $post->ID );
            }
        }
        if ( ! $the_product instanceof \WC_Product ) {
            return;
        }

        $settings   = $this->get_settings_for_display();
        $product_id = $the_product->get_id();
        $img_size   = $settings['image_size'] ?: 'woocommerce_single';
        $link       = 'yes' === ( $settings['link_to_product'] ?? 'yes' );

        // Determine the image to show.
        $image_id = $the_product->get_image_id();

        // Check if Apotheca Skin Quiz matched a specific variation — use its image.
        if ( $the_product->is_type( 'variable' ) && class_exists( 'ASQ_Ajax' ) ) {
            $matched_vid = ASQ_Ajax::get_matched_variation( $product_id );
            if ( $matched_vid ) {
                $variation = wc_get_product( $matched_vid );
                if ( $variation && $variation->get_image_id() ) {
                    $image_id = $variation->get_image_id();
                }
            }
        }

        if ( ! $image_id ) {
            // Use WooCommerce placeholder.
            $image_url = wc_placeholder_img_src( $img_size );
            $img_tag   = '<img src="' . esc_url( $image_url ) . '" class="asq-pi-img" alt="' . esc_attr( $the_product->get_name() ) . '">';
        } else {
            $img_tag = wp_get_attachment_image( $image_id, $img_size, false, array(
                'class' => 'asq-pi-img',
                'alt'   => $the_product->get_name(),
            ) );
        }

        $permalink = $the_product->get_permalink();

        echo '<div class="asq-pi-wrap">';
        if ( $link && $permalink ) {
            printf( '<a href="%s" class="asq-pi-link">%s</a>', esc_url( $permalink ), $img_tag );
        } else {
            echo $img_tag;
        }
        echo '</div>';
    }

    /* ─────────── Editor placeholder ─────────── */

    private function render_editor_placeholder() {
        echo '<div class="asq-pi-wrap">';
        echo '<img src="' . esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ) . '" class="asq-pi-img" alt="Product Image">';
        echo '</div>';
    }

    /* ─────────── Live preview template ─────────── */

    protected function content_template() {
        ?>
        <div class="asq-pi-wrap">
            <img src="<?php echo esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ); ?>" class="asq-pi-img" alt="Product Image">
        </div>
        <?php
    }
}

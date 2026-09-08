<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

/**
 * PF Shade Cart – Elementor Widget for CrocoBlock listing templates.
 *
 * Renders a colour swatch circle + shade label, with an add-to-cart
 * button that includes the price inline (e.g. "$115.00 | ADD TO CART +").
 * Designed to sit inside a JetEngine listing item for variable products.
 */
class ASQ_Shade_Cart_Widget extends Widget_Base {

    public function get_name() {
        return 'asq_shade_cart';
    }

    public function get_title() {
        return __( 'PF Shade Cart', 'apotheca-skin-quiz' );
    }

    public function get_icon() {
        return 'eicon-circle';
    }

    public function get_categories() {
        return array( 'apotheca-skin-quiz' );
    }

    public function get_keywords() {
        return array( 'shade', 'swatch', 'color', 'cart', 'variation', 'beauty' );
    }

    public function get_script_depends() {
        return array( 'asq-add-to-cart' );
    }

    public function get_style_depends() {
        return array( 'asq-frontend' );
    }

    /* ═══════════════════════════════════════
       CONTROLS
       ═══════════════════════════════════════ */

    protected function register_controls() {

        /* ── Content: General ── */

        $this->start_controls_section( 'section_content', array(
            'label' => __( 'Content', 'apotheca-skin-quiz' ),
        ) );

        $this->add_control( 'shade_attribute', array(
            'label'       => __( 'Shade Attribute', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'pa_shade',
            'description' => __( 'The taxonomy slug of the shade / colour attribute (e.g. pa_shade, pa_color).', 'apotheca-skin-quiz' ),
            'label_block' => true,
        ) );

        $this->add_control( 'color_meta_key', array(
            'label'       => __( 'Colour Meta Key', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'fif_swatch_color',
            'description' => __( 'Term meta key where the hex colour is stored by your swatch plugin (FiF VSE uses fif_swatch_color).', 'apotheca-skin-quiz' ),
            'label_block' => true,
        ) );

        $this->add_control( 'image_meta_key', array(
            'label'       => __( 'Image Meta Key', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'fif_swatch_image_id',
            'description' => __( 'Term meta key where the swatch image URL or attachment ID is stored. Takes priority over hex colour (FiF VSE uses fif_swatch_image_id).', 'apotheca-skin-quiz' ),
            'label_block' => true,
        ) );

        $this->add_control( 'fallback_color', array(
            'label'   => __( 'Fallback Circle Colour', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::COLOR,
            'default' => '#cccccc',
            'description' => __( 'Used when no swatch colour meta is found.', 'apotheca-skin-quiz' ),
        ) );

        $this->add_control( 'button_text', array(
            'label'   => __( 'Button Text', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::TEXT,
            'default' => __( 'ADD TO CART', 'apotheca-skin-quiz' ),
        ) );

        $this->add_control( 'button_icon', array(
            'label'       => __( 'Button Icon', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::ICONS,
            'default'     => array(
                'value'   => 'fas fa-plus',
                'library' => 'fa-solid',
            ),
        ) );

        $this->add_control( 'price_separator', array(
            'label'   => __( 'Price / Text Separator', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::TEXT,
            'default' => '|',
        ) );

        $this->add_control( 'show_quantity', array(
            'label'        => __( 'Quantity Input', 'apotheca-skin-quiz' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Show', 'apotheca-skin-quiz' ),
            'label_off'    => __( 'Hide', 'apotheca-skin-quiz' ),
            'default'      => '',
        ) );

        $this->add_responsive_control( 'align', array(
            'label'   => __( 'Alignment', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::CHOOSE,
            'options' => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ),   'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ),  'icon' => 'eicon-text-align-right' ),
            ),
            'default'   => 'left',
            'selectors' => array(
                '{{WRAPPER}} .asq-sc-wrap' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();

        /* ═══════════════════
           STYLE TAB
           ═══════════════════ */

        /* ── Style: Shade Row ── */

        $this->start_controls_section( 'section_style_shade_row', array(
            'label' => __( 'Shade Row', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'shade_row_gap', array(
            'label'      => __( 'Gap (Circle / Label)', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array(
                'px' => array( 'min' => 0, 'max' => 40 ),
                'em' => array( 'min' => 0, 'max' => 3, 'step' => 0.1 ),
            ),
            'default'    => array( 'size' => 10, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-shade-row' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'shade_row_margin_bottom', array(
            'label'      => __( 'Bottom Spacing', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array(
                'px' => array( 'min' => 0, 'max' => 60 ),
            ),
            'default'    => array( 'size' => 12, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-shade-row' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Shade Circle ── */

        $this->start_controls_section( 'section_style_circle', array(
            'label' => __( 'Shade Circle', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'circle_size', array(
            'label'      => __( 'Size', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 8, 'max' => 80 ) ),
            'default'    => array( 'size' => 24, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-circle' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'circle_border',
            'selector' => '{{WRAPPER}} .asq-sc-circle',
        ) );

        $this->add_control( 'circle_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%' ),
            'range'      => array(
                'px' => array( 'min' => 0, 'max' => 100 ),
                '%'  => array( 'min' => 0, 'max' => 50 ),
            ),
            'default'    => array( 'size' => 50, 'unit' => '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-circle' => 'border-radius: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'circle_shadow',
            'selector' => '{{WRAPPER}} .asq-sc-circle',
        ) );

        $this->end_controls_section();

        /* ── Style: Shade Label ── */

        $this->start_controls_section( 'section_style_label', array(
            'label' => __( 'Shade Label', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'label_typography',
            'selector' => '{{WRAPPER}} .asq-sc-label',
        ) );

        $this->add_control( 'label_color', array(
            'label'     => __( 'Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-label' => 'color: {{VALUE}};' ),
        ) );

        $this->end_controls_section();

        /* ── Style: Button ── */

        $this->start_controls_section( 'section_style_button', array(
            'label' => __( 'Button', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'btn_typography',
            'selector' => '{{WRAPPER}} .asq-sc-btn',
        ) );

        $this->start_controls_tabs( 'btn_colors' );

        $this->start_controls_tab( 'btn_normal', array(
            'label' => __( 'Normal', 'apotheca-skin-quiz' ),
        ) );
        $this->add_control( 'btn_color', array(
            'label'     => __( 'Text Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-btn' => 'color: {{VALUE}};' ),
        ) );
        $this->add_control( 'btn_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-btn' => 'background-color: {{VALUE}};' ),
        ) );
        $this->end_controls_tab();

        $this->start_controls_tab( 'btn_hover', array(
            'label' => __( 'Hover', 'apotheca-skin-quiz' ),
        ) );
        $this->add_control( 'btn_color_hover', array(
            'label'     => __( 'Text Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-btn:hover' => 'color: {{VALUE}};' ),
        ) );
        $this->add_control( 'btn_bg_hover', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-btn:hover' => 'background-color: {{VALUE}};' ),
        ) );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control( 'btn_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'separator'  => 'before',
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'btn_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .asq-sc-btn',
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'btn_shadow',
            'selector' => '{{WRAPPER}} .asq-sc-btn',
        ) );

        $this->add_control( 'btn_full_width', array(
            'label'     => __( 'Full Width', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'selectors' => array(
                '{{WRAPPER}} .asq-sc-btn' => 'width: 100%; display: flex;',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Price (inside button) ── */

        $this->start_controls_section( 'section_style_price', array(
            'label' => __( 'Price (in Button)', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'price_typography',
            'selector' => '{{WRAPPER}} .asq-sc-btn-price, {{WRAPPER}} .asq-sc-btn-price *',
        ) );

        $this->add_control( 'price_color', array(
            'label'     => __( 'Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-sc-btn-price, {{WRAPPER}} .asq-sc-btn-price *' => 'color: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Separator ── */

        $this->start_controls_section( 'section_style_separator', array(
            'label' => __( 'Separator', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'sep_color', array(
            'label'     => __( 'Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-btn-sep' => 'color: {{VALUE}};' ),
        ) );

        $this->add_responsive_control( 'sep_spacing', array(
            'label'      => __( 'Spacing', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array(
                'px' => array( 'min' => 0, 'max' => 30 ),
            ),
            'default'    => array( 'size' => 10, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-btn-sep' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Icon ── */

        $this->start_controls_section( 'section_style_icon', array(
            'label' => __( 'Icon', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'icon_size', array(
            'label'      => __( 'Size', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array(
                'px' => array( 'min' => 6, 'max' => 40 ),
            ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-btn-icon'     => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .asq-sc-btn-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'icon_color', array(
            'label'     => __( 'Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-sc-btn-icon'     => 'color: {{VALUE}};',
                '{{WRAPPER}} .asq-sc-btn-icon svg' => 'fill: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'icon_gap', array(
            'label'      => __( 'Spacing', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array(
                'px' => array( 'min' => 0, 'max' => 30 ),
            ),
            'default'    => array( 'size' => 6, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-btn-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();

        /* ── Style: Quantity Input ── */

        $this->start_controls_section( 'section_style_quantity', array(
            'label'     => __( 'Quantity Input', 'apotheca-skin-quiz' ),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => array( 'show_quantity' => 'yes' ),
        ) );

        $this->add_responsive_control( 'qty_width', array(
            'label'      => __( 'Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 30, 'max' => 120 ) ),
            'default'    => array( 'size' => 60, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-qty' => 'width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'qty_height', array(
            'label'      => __( 'Height', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 20, 'max' => 80 ) ),
            'default'    => array( 'size' => 40, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-qty' => 'height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'qty_gap', array(
            'label'      => __( 'Gap (Qty / Button)', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
            'default'    => array( 'size' => 8, 'unit' => 'px' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-inner' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'qty_typography',
            'selector' => '{{WRAPPER}} .asq-sc-qty',
        ) );

        $this->add_control( 'qty_text_color', array(
            'label'     => __( 'Text Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-qty' => 'color: {{VALUE}};' ),
        ) );

        $this->add_control( 'qty_bg_color', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-qty' => 'background-color: {{VALUE}};' ),
        ) );

        $this->add_control( 'qty_border_color', array(
            'label'     => __( 'Border Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array( '{{WRAPPER}} .asq-sc-qty' => 'border-color: {{VALUE}};' ),
        ) );

        $this->add_control( 'qty_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-sc-qty' => 'border-radius: {{SIZE}}{{UNIT}};',
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

        wp_enqueue_script( 'wc-add-to-cart' );

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

        $settings       = $this->get_settings_for_display();
        $product_id     = $the_product->get_id();
        $shade_attr     = sanitize_title( $settings['shade_attribute'] ?: 'pa_shade' );
        $meta_key       = sanitize_key( $settings['color_meta_key'] ?: 'product_attribute_color' );
        $image_meta_key = $settings['image_meta_key'] ?: 'product_attribute_image';
        $fallback       = $settings['fallback_color'] ?: '#cccccc';
        $show_qty       = 'yes' === ( $settings['show_quantity'] ?? '' );

        // Resolve variation data.
        $variation      = null;
        $variation_id   = 0;
        $shade_slug     = '';
        $shade_label    = '';
        $shade_hex      = $fallback;
        $shade_image    = '';  // URL — takes priority over hex colour.
        $price_html     = $the_product->get_price_html();
        $variation_attrs = array();

        if ( $the_product->is_type( 'variable' ) ) {
            // Check if Apotheca Skin Quiz matched a specific variation for
            // this parent product (set during compute_results rendering).
            $matched_vid = 0;
            if ( class_exists( 'ASQ_Ajax' ) ) {
                $matched_vid = ASQ_Ajax::get_matched_variation( $product_id );
            }

            if ( $matched_vid ) {
                $variation = wc_get_product( $matched_vid );
                if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
                    $variation = null;
                }
            }

            // Fallback: pick the first in-stock variation.
            if ( ! $variation ) {
                $variations = $the_product->get_available_variations( 'objects' );
                if ( ! empty( $variations ) ) {
                    foreach ( $variations as $var ) {
                        if ( $var->is_in_stock() ) {
                            $variation = $var;
                            break;
                        }
                    }
                    if ( ! $variation ) {
                        $variation = $variations[0];
                    }
                }
            }

            if ( $variation ) {

                $variation_id    = $variation->get_id();
                $variation_attrs = $variation->get_attributes();
                $price_html      = $variation->get_price_html();

                // Debug: log available attributes so the user can
                // verify the correct Shade Attribute setting.
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[PF Shade Cart] Product #' . $product_id
                        . ' variation #' . $variation_id
                        . ' attrs: ' . wp_json_encode( $variation_attrs )
                        . ' | looking for shade_attr="' . $shade_attr . '"' );
                }

                // Find the shade attribute value.  Try the configured key
                // first, then fall back to searching all attributes for a
                // key that contains the configured slug (handles pa_ prefix
                // mismatches and 'attribute_' prefix variations).
                $shade_slug = '';
                $matched_attr_key = '';

                if ( isset( $variation_attrs[ $shade_attr ] ) && '' !== $variation_attrs[ $shade_attr ] ) {
                    // Exact match.
                    $shade_slug = $variation_attrs[ $shade_attr ];
                    $matched_attr_key = $shade_attr;
                } else {
                    // Fuzzy match: strip pa_ prefix and compare.
                    $shade_bare = preg_replace( '/^pa_/', '', $shade_attr );
                    foreach ( $variation_attrs as $attr_key => $attr_val ) {
                        if ( '' === $attr_val ) {
                            continue;
                        }
                        $key_bare = preg_replace( '/^pa_/', '', $attr_key );
                        if ( $key_bare === $shade_bare ) {
                            $shade_slug = $attr_val;
                            $matched_attr_key = $attr_key;
                            break;
                        }
                    }

                    // Last resort: if only one attribute exists, use it.
                    if ( ! $shade_slug && count( $variation_attrs ) === 1 ) {
                        $shade_slug = reset( $variation_attrs );
                        $matched_attr_key = key( $variation_attrs );
                    }
                }

                if ( $shade_slug ) {
                    // Determine the taxonomy to search for the term.
                    $taxonomy = $matched_attr_key;

                    // Get human-readable label.
                    if ( taxonomy_exists( $taxonomy ) ) {
                        $term = get_term_by( 'slug', $shade_slug, $taxonomy );
                        if ( $term && ! is_wp_error( $term ) ) {
                            $shade_label = $term->name;

                            // ── Image swatch (takes priority over hex) ──
                            // Check the configured image meta key first,
                            // then common FiF VSE / swatch plugin keys.
                            $image_keys = array_unique( array_filter( array(
                                $image_meta_key,
                                'fif_swatch_image_id',
                                'product_attribute_image',
                                'image',
                                '_image',
                                'swatch_image',
                                'attribute_swatch_image',
                            ) ) );
                            foreach ( $image_keys as $img_key ) {
                                $img_val = get_term_meta( $term->term_id, $img_key, true );
                                if ( ! $img_val ) {
                                    continue;
                                }
                                // Value can be an attachment ID or a URL.
                                if ( is_numeric( $img_val ) ) {
                                    $url = wp_get_attachment_image_url( (int) $img_val, 'thumbnail' );
                                    if ( $url ) {
                                        $shade_image = $url;
                                        break;
                                    }
                                } elseif ( filter_var( $img_val, FILTER_VALIDATE_URL ) ) {
                                    $shade_image = $img_val;
                                    break;
                                }
                            }

                            // ── Hex colour (fallback when no image) ──
                            if ( ! $shade_image ) {
                                $color = get_term_meta( $term->term_id, $meta_key, true );
                                if ( ! $color ) {
                                    foreach ( array( 'fif_swatch_color', 'product_attribute_color', 'color', '_color', 'attribute_swatch_color' ) as $alt_key ) {
                                        if ( $alt_key === $meta_key ) {
                                            continue;
                                        }
                                        $color = get_term_meta( $term->term_id, $alt_key, true );
                                        if ( $color ) {
                                            break;
                                        }
                                    }
                                }
                                // Try ALL term meta for any hex value.
                                if ( ! $color ) {
                                    $all_meta = get_term_meta( $term->term_id );
                                    foreach ( $all_meta as $mk => $mv ) {
                                        $val = is_array( $mv ) ? ( $mv[0] ?? '' ) : $mv;
                                        if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val ) ) {
                                            $color = $val;
                                            break;
                                        }
                                    }
                                }
                                if ( $color ) {
                                    $shade_hex = $color;
                                }
                            }
                        }
                    }

                    // If no label found from taxonomy, use the raw value.
                    if ( ! $shade_label ) {
                        $shade_label = ucwords( str_replace( array( '-', '_' ), ' ', $shade_slug ) );
                    }
                }
            }
        } elseif ( $the_product->is_type( 'simple' ) ) {
            // Simple product – no shade data; only show button.
            $shade_label = '';
        }

        $is_purchasable = $the_product->is_purchasable() && $the_product->is_in_stock();
        $button_text    = $settings['button_text'] ?: __( 'ADD TO CART', 'apotheca-skin-quiz' );
        $separator      = $settings['price_separator'] ?: '|';

        // Build icon HTML.
        $icon_html = '';
        if ( ! empty( $settings['button_icon']['value'] ) ) {
            ob_start();
            echo '<span class="asq-sc-btn-icon">';
            Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) );
            echo '</span>';
            $icon_html = ob_get_clean();
        }

        // ── Output ──

        echo '<div class="asq-sc-wrap">';

        // Debug comment visible in browser inspector.
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            printf(
                '<!-- PF-SC debug: pid=%d vid=%d shade_attr="%s" shade_slug="%s" shade_label="%s" shade_hex="%s" shade_image="%s" attrs=%s -->',
                $product_id,
                $variation_id,
                esc_html( $shade_attr ),
                esc_html( $shade_slug ),
                esc_html( $shade_label ),
                esc_html( $shade_hex ),
                esc_html( $shade_image ),
                esc_html( wp_json_encode( $variation_attrs ) )
            );
        }

        // Shade row (only if we have shade data).
        if ( $shade_label ) {
            echo '<div class="asq-sc-shade-row">';

            // Image swatch takes priority over hex colour.
            if ( $shade_image ) {
                printf(
                    '<span class="asq-sc-circle fif-vse-preview" style="background-image:url(%s);background-size:cover;background-position:center;"></span>',
                    esc_url( $shade_image )
                );
            } else {
                printf(
                    '<span class="asq-sc-circle" style="background-color:%s;"></span>',
                    esc_attr( $shade_hex )
                );
            }

            printf(
                '<span class="asq-sc-label">%s</span>',
                esc_html( $shade_label )
            );
            echo '</div>';
        }

        // Button row (with optional quantity input).
        if ( $is_purchasable ) {
            $btn_classes = 'asq-sc-btn asq-shade-atc-btn';

            // Data attributes for AJAX add-to-cart.
            $data_attrs = sprintf(
                'data-product_id="%d" data-quantity="1"',
                $product_id
            );

            if ( $variation_id ) {
                $data_attrs .= sprintf( ' data-variation_id="%d"', $variation_id );
                foreach ( $variation_attrs as $attr_key => $attr_val ) {
                    $data_attrs .= sprintf(
                        ' data-attribute_%s="%s"',
                        esc_attr( sanitize_title( $attr_key ) ),
                        esc_attr( $attr_val )
                    );
                }
            } else {
                $btn_classes .= ' add_to_cart_button ajax_add_to_cart';
                $data_attrs  .= sprintf(
                    ' data-product_sku="%s"',
                    esc_attr( $the_product->get_sku() )
                );
            }

            // Wrap qty + button in a flex row.
            echo '<div class="asq-sc-inner">';

            // Quantity input.
            if ( $show_qty ) {
                echo '<input type="number" class="asq-sc-qty" value="1" min="1" step="1" inputmode="numeric">';
            }

            printf(
                '<a href="%s" class="%s" %s rel="nofollow">',
                $variation_id ? '#' : esc_url( $the_product->add_to_cart_url() ),
                esc_attr( $btn_classes ),
                $data_attrs
            );

            echo '<span class="asq-sc-btn-price">' . $price_html . '</span>';
            printf( '<span class="asq-sc-btn-sep">%s</span>', esc_html( $separator ) );
            printf( '<span class="asq-sc-btn-text">%s</span>', esc_html( $button_text ) );
            echo $icon_html;
            echo '</a>';

            echo '</div>'; // .asq-sc-inner
        } else {
            echo '<span class="asq-sc-btn asq-sc-btn--disabled">';
            echo '<span class="asq-sc-btn-text">' . esc_html__( 'Out of Stock', 'apotheca-skin-quiz' ) . '</span>';
            echo '</span>';
        }

        echo '</div>';
    }

    /* ─────────── Editor placeholder ─────────── */

    private function render_editor_placeholder() {
        $settings    = $this->get_settings_for_display();
        $fallback    = $settings['fallback_color'] ?: '#cccccc';
        $button_text = $settings['button_text'] ?: __( 'ADD TO CART', 'apotheca-skin-quiz' );
        $separator   = $settings['price_separator'] ?: '|';

        $icon_html = '';
        if ( ! empty( $settings['button_icon']['value'] ) ) {
            ob_start();
            echo '<span class="asq-sc-btn-icon">';
            Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) );
            echo '</span>';
            $icon_html = ob_get_clean();
        }

        echo '<div class="asq-sc-wrap">';

        echo '<div class="asq-sc-shade-row">';
        printf( '<span class="asq-sc-circle" style="background-color:%s;"></span>', esc_attr( $fallback ) );
        echo '<span class="asq-sc-label">Shade Name</span>';
        echo '</div>';

        printf(
            '<a href="#" class="asq-sc-btn" onclick="return false;"><span class="asq-sc-btn-price">&pound;115.00</span><span class="asq-sc-btn-sep">%s</span><span class="asq-sc-btn-text">%s</span>%s</a>',
            esc_html( $separator ),
            esc_html( $button_text ),
            $icon_html
        );

        echo '</div>';
    }

    /* ─────────── Live preview template ─────────── */

    protected function content_template() {
        ?>
        <#
        var fallback  = settings.fallback_color || '#cccccc';
        var btnText   = settings.button_text   || 'ADD TO CART';
        var separator = settings.price_separator || '|';
        var iconHTML  = elementor.helpers.renderIcon( view, settings.button_icon, { 'aria-hidden': true }, 'i', 'object' );
        #>
        <div class="asq-sc-wrap">
            <div class="asq-sc-shade-row">
                <span class="asq-sc-circle" style="background-color:{{{ fallback }}};"></span>
                <span class="asq-sc-label">Shade Name</span>
            </div>
            <a href="#" class="asq-sc-btn" onclick="return false;">
                <span class="asq-sc-btn-price">&pound;115.00</span>
                <span class="asq-sc-btn-sep">{{{ separator }}}</span>
                <span class="asq-sc-btn-text">{{{ btnText }}}</span>
                <# if ( iconHTML && iconHTML.value ) { #>
                <span class="asq-sc-btn-icon">{{{ iconHTML.value }}}</span>
                <# } #>
            </a>
        </div>
        <?php
    }
}

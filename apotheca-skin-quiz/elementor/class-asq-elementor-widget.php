<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

/**
 * Apotheca Skin Quiz – Elementor Widget with comprehensive styling controls.
 */
class ASQ_Elementor_Widget extends Widget_Base {

    public function get_name() {
        return 'apotheca_skin_quiz';
    }

    public function get_title() {
        return __( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' );
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return array( 'apotheca-skin-quiz' );
    }

    public function get_keywords() {
        return array( 'quiz', 'skin', 'apotheca', 'skincare', 'finder' );
    }

    /* ═══════════════════════════════════════
       CONTROLS
       ═══════════════════════════════════════ */

    protected function register_controls() {
        $this->section_content();
        $this->section_style_container();
        $this->section_style_progress_bar();
        $this->section_style_question_text();
        $this->section_style_image_answers();
        $this->section_style_text_answers();
        $this->section_style_checkbox();
        $this->section_style_buttons();
        $this->section_style_email_screen();
        $this->section_style_loading_screen();
        $this->section_style_results();
        $this->section_style_result_cards();
        $this->section_style_reading();
        $this->section_style_gate_result();
        $this->section_style_read_next();
    }

    /* ─── Content ─── */

    private function section_content() {
        $this->start_controls_section( 'section_content', array(
            'label' => __( 'Content', 'apotheca-skin-quiz' ),
        ) );

        $finders = $this->get_finder_list();

        $this->add_control( 'finder_id', array(
            'label'   => __( 'Select Apotheca Skin Quiz', 'apotheca-skin-quiz' ),
            'type'    => Controls_Manager::SELECT2,
            'options' => $finders,
            'default' => '',
        ) );

        $this->add_control( 'loading_heading_text', array(
            'label'       => __( 'Loading Screen Heading', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Finding your perfect products…', 'apotheca-skin-quiz' ),
            'label_block' => true,
            'separator'   => 'before',
        ) );

        $this->add_control( 'loading_svg_icon', array(
            'label'       => __( 'Loading Screen SVG Icon', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::ICONS,
            'default'     => array(
                'value'   => '',
                'library' => '',
            ),
            'description' => __( 'Choose a custom SVG icon to replace the default loading spinner. Upload your own SVG or pick from the icon library.', 'apotheca-skin-quiz' ),
        ) );

        $this->add_control( 'results_heading_text', array(
            'label'       => __( 'Results Screen Heading', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'Your Recommended Products', 'apotheca-skin-quiz' ),
            'label_block' => true,
            'separator'   => 'before',
        ) );

        // The Ingredient List Decoder page. Native URL control, so it offers the
        // site's own pages rather than needing the address typed. Left empty by
        // default; when empty the F12 result renders without a link.
        $this->add_control( 'decoder_url', array(
            'label'       => __( 'Ingredient List Decoder page', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::URL,
            'placeholder' => __( 'Search for a page or paste a URL', 'apotheca-skin-quiz' ),
            'description' => __( 'Where the "not sure what is in your products" result sends people. It opens in a new tab. Leave empty to show that line without a link.', 'apotheca-skin-quiz' ),
            'options'     => false,
            'default'     => array( 'url' => '' ),
            'label_block' => true,
            'separator'   => 'before',
        ) );

        $this->add_control( 'rn_new_tab', array(
            'label'        => __( 'Open read-next articles in a new tab', 'apotheca-skin-quiz' ),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __( 'Yes', 'apotheca-skin-quiz' ),
            'label_off'    => __( 'No', 'apotheca-skin-quiz' ),
            'return_value' => 'yes',
            'default'      => '',
            'separator'    => 'before',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Container ─── */

    private function section_style_container() {
        $this->start_controls_section( 'section_style_container', array(
            'label' => __( 'Container', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'container_max_width', array(
            'label'      => __( 'Max Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%', 'vw' ),
            'range'      => array(
                'px' => array( 'min' => 300, 'max' => 2400 ),
                '%'  => array( 'min' => 10, 'max' => 100 ),
                'vw' => array( 'min' => 10, 'max' => 100 ),
            ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-finder' => 'max-width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'container_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-finder' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Background::get_type(), array(
            'name'     => 'container_background',
            'selector' => '{{WRAPPER}} .asq-finder',
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'container_border',
            'selector' => '{{WRAPPER}} .asq-finder',
        ) );

        $this->add_responsive_control( 'container_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-finder' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'container_shadow',
            'selector' => '{{WRAPPER}} .asq-finder',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Progress Bar ─── */

    private function section_style_progress_bar() {
        $this->start_controls_section( 'section_style_progress', array(
            'label' => __( 'Progress Bar', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'progress_height', array(
            'label'      => __( 'Bar Height', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 2, 'max' => 30 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'progress_bg_color', array(
            'label'     => __( 'Track Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-progress-bar' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'progress_fill_color', array(
            'label'     => __( 'Fill Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-progress-fill' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'progress_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-progress-bar, {{WRAPPER}} .asq-progress-fill' => 'border-radius: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'progress_margin_bottom', array(
            'label'      => __( 'Bottom Spacing', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-progress-bar-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'progress_text_color', array(
            'label'     => __( 'Percentage Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-progress-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'progress_text_typography',
            'label'    => __( 'Percentage Typography', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-progress-text',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Question Text ─── */

    private function section_style_question_text() {
        $this->start_controls_section( 'section_style_question', array(
            'label' => __( 'Question Text', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'question_color', array(
            'label'     => __( 'Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-question-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'question_typography',
            'selector' => '{{WRAPPER}} .asq-question-text',
        ) );

        $this->add_responsive_control( 'question_align', array(
            'label'     => __( 'Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-question-text' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'question_margin', array(
            'label'      => __( 'Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-question-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'instruction_heading', array(
            'label'     => __( 'Instruction Text', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'instruction_color', array(
            'label'     => __( 'Instruction Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-question-instruction, {{WRAPPER}} .asq-question-hint' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'instruction_typography',
            'label'    => __( 'Instruction Typography', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-question-instruction, {{WRAPPER}} .asq-question-hint',
        ) );

        $this->add_responsive_control( 'instruction_margin', array(
            'label'      => __( 'Instruction Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-question-instruction, {{WRAPPER}} .asq-question-hint' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'instruction_align', array(
            'label'     => __( 'Instruction Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-question-instruction, {{WRAPPER}} .asq-question-hint' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Image Answers ─── */

    private function section_style_image_answers() {
        $this->start_controls_section( 'section_style_image_answers', array(
            'label' => __( 'Image Answer Cards', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'img_grid_columns', array(
            'label'      => __( 'Columns', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'range'      => array( 'px' => array( 'min' => 1, 'max' => 6 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answers-grid--images' => 'grid-template-columns: repeat({{SIZE}}, 1fr);',
            ),
        ) );

        $this->add_responsive_control( 'img_grid_gap', array(
            'label'      => __( 'Gap', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answers-grid--images' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'img_card_bg', array(
            'label'     => __( 'Card Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'img_card_border',
            'selector' => '{{WRAPPER}} .asq-answer-option--image',
        ) );

        $this->add_responsive_control( 'img_card_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'img_card_shadow',
            'selector' => '{{WRAPPER}} .asq-answer-option--image',
        ) );

        // Selected state
        $this->add_control( 'img_selected_heading', array(
            'label'     => __( 'Selected State', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'img_selected_border_color', array(
            'label'     => __( 'Selected Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image.asq-selected' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'img_selected_shadow_color', array(
            'label'     => __( 'Selected Shadow Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image.asq-selected' => 'box-shadow: 0 0 0 3px {{VALUE}};',
            ),
        ) );

        $this->add_control( 'img_selected_bg', array(
            'label'     => __( 'Selected Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image.asq-selected' => 'background-color: {{VALUE}};',
            ),
        ) );

        // Hover state
        $this->add_control( 'img_hover_heading', array(
            'label'     => __( 'Hover State', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'img_hover_border_color', array(
            'label'     => __( 'Hover Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image:hover' => 'border-color: {{VALUE}};',
            ),
        ) );

        // Image
        $this->add_control( 'img_image_heading', array(
            'label'     => __( 'Image', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'img_aspect_ratio', array(
            'label'      => __( 'Aspect Ratio', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'range'      => array( 'px' => array( 'min' => 0.3, 'max' => 2, 'step' => 0.05 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-img-wrap' => 'aspect-ratio: {{SIZE}};',
            ),
        ) );

        $this->add_responsive_control( 'img_image_radius', array(
            'label'      => __( 'Image Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-img-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Answer text
        $this->add_control( 'img_text_heading', array(
            'label'     => __( 'Answer Label', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'img_text_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image .asq-answer-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'img_text_typography',
            'selector' => '{{WRAPPER}} .asq-answer-option--image .asq-answer-text',
        ) );

        $this->add_responsive_control( 'img_text_padding', array(
            'label'      => __( 'Text Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--image .asq-answer-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Answer description (image layout only)
        $this->add_control( 'img_desc_heading', array(
            'label'     => __( 'Answer Description', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'img_desc_color', array(
            'label'     => __( 'Description Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--image .asq-answer-desc' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'img_desc_typography',
            'selector' => '{{WRAPPER}} .asq-answer-option--image .asq-answer-desc',
        ) );

        $this->add_responsive_control( 'img_desc_padding', array(
            'label'      => __( 'Description Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--image .asq-answer-desc' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'img_desc_margin', array(
            'label'      => __( 'Description Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--image .asq-answer-desc' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Text Answers ─── */

    private function section_style_text_answers() {
        $this->start_controls_section( 'section_style_text_answers', array(
            'label' => __( 'Text Answer Options', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'text_answer_gap', array(
            'label'      => __( 'Gap Between Options', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answers-grid--text' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'text_answer_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'text_answer_border',
            'selector' => '{{WRAPPER}} .asq-answer-option--text',
        ) );

        $this->add_responsive_control( 'text_answer_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--text' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'text_answer_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-answer-option--text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'text_answer_shadow',
            'selector' => '{{WRAPPER}} .asq-answer-option--text',
        ) );

        $this->add_control( 'text_answer_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text .asq-answer-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'text_answer_typography',
            'selector' => '{{WRAPPER}} .asq-answer-option--text .asq-answer-text',
        ) );

        // Selected state
        $this->add_control( 'text_selected_heading', array(
            'label'     => __( 'Selected State', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'text_selected_bg', array(
            'label'     => __( 'Selected Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text.asq-selected::before' => 'background: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'text_selected_border_color', array(
            'label'     => __( 'Selected Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text.asq-selected' => 'border-color: {{VALUE}} !important;',
            ),
        ) );

        $this->add_control( 'text_selected_text_color', array(
            'label'     => __( 'Selected Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text.asq-selected .asq-answer-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'text_selected_shadow',
            'label'    => __( 'Selected Shadow', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-answer-option--text.asq-selected',
        ) );

        // Hover state
        $this->add_control( 'text_hover_heading', array(
            'label'     => __( 'Hover State', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'text_hover_bg', array(
            'label'     => __( 'Hover Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text' => '--asq-text-hover-bg: {{VALUE}};',
                '{{WRAPPER}} .asq-answer-option--text::before' => 'background: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'text_hover_border_color', array(
            'label'     => __( 'Hover Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-answer-option--text:hover' => 'border-color: {{VALUE}};',
            ),
        ) );

        // Two-column layout
        $this->add_control( 'text_layout_heading', array(
            'label'     => __( 'Two-Column Layout', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'text_layout_gap', array(
            'label'      => __( 'Column Gap', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-text-layout' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Checkbox ─── */

    private function section_style_checkbox() {
        $this->start_controls_section( 'section_style_checkbox', array(
            'label' => __( 'Checkbox Indicator', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'checkbox_size', array(
            'label'      => __( 'Size', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 14, 'max' => 40 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-checkbox' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'checkbox_border_width', array(
            'label'      => __( 'Border Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 6 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-checkbox' => 'border-width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'checkbox_border_color', array(
            'label'     => __( 'Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-checkbox' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'checkbox_border_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-checkbox' => 'border-radius: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'checkbox_checked_bg', array(
            'label'     => __( 'Checked Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-selected .asq-checkbox' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'checkbox_check_color', array(
            'label'     => __( 'Check Mark Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-selected .asq-check-icon' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Buttons ─── */

    private function section_style_buttons() {
        $this->start_controls_section( 'section_style_buttons', array(
            'label' => __( 'Buttons', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        // Primary button
        $this->add_control( 'btn_primary_heading', array(
            'label' => __( 'Primary Button (Continue / Send)', 'apotheca-skin-quiz' ),
            'type'  => Controls_Manager::HEADING,
        ) );

        $this->add_control( 'btn_primary_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-primary' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_primary_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-primary' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_primary_hover_bg', array(
            'label'     => __( 'Hover Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-primary:hover' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_primary_hover_color', array(
            'label'     => __( 'Hover Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-primary:hover' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'btn_primary_typography',
            'selector' => '{{WRAPPER}} .asq-btn-primary',
        ) );

        $this->add_responsive_control( 'btn_primary_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-btn-primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'btn_primary_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-btn-primary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'btn_primary_shadow',
            'selector' => '{{WRAPPER}} .asq-btn-primary',
        ) );

        // Secondary button
        $this->add_control( 'btn_secondary_heading', array(
            'label'     => __( 'Secondary Button (Back / Start Over)', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'btn_secondary_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-secondary' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_secondary_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-secondary' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_secondary_border_color', array(
            'label'     => __( 'Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-secondary' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_secondary_hover_bg', array(
            'label'     => __( 'Hover Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-secondary:hover' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_secondary_hover_color', array(
            'label'     => __( 'Hover Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-btn-secondary:hover' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'btn_secondary_typography',
            'selector' => '{{WRAPPER}} .asq-btn-secondary',
        ) );

        $this->add_responsive_control( 'btn_secondary_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-btn-secondary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'btn_secondary_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-btn-secondary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Skip / Link button – full styling
        $this->add_control( 'btn_skip_heading', array(
            'label'     => __( 'Skip & View Results Button', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'btn_skip_spacing_top', array(
            'label'      => __( 'Spacing Above', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-skip-email' => 'margin-top: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'btn_skip_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-skip-email' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_skip_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-skip-email' => 'color: {{VALUE}}; text-decoration: none;',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'btn_skip_border',
            'selector' => '{{WRAPPER}} .asq-skip-email',
        ) );

        $this->add_responsive_control( 'btn_skip_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-skip-email' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'btn_skip_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-skip-email' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'btn_skip_shadow',
            'selector' => '{{WRAPPER}} .asq-skip-email',
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'btn_skip_typography',
            'selector' => '{{WRAPPER}} .asq-skip-email',
        ) );

        // Skip button hover
        $this->add_control( 'btn_skip_hover_heading', array(
            'label'     => __( 'Skip Button Hover', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'btn_skip_hover_bg', array(
            'label'     => __( 'Hover Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-skip-email:hover' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_skip_hover_color', array(
            'label'     => __( 'Hover Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-skip-email:hover' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'btn_skip_hover_border_color', array(
            'label'     => __( 'Hover Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-skip-email:hover' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Email Screen ─── */

    private function section_style_email_screen() {
        $this->start_controls_section( 'section_style_email', array(
            'label' => __( 'Email Capture Screen', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'email_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-email-screen' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Background::get_type(), array(
            'name'     => 'email_background',
            'selector' => '{{WRAPPER}} .asq-email-screen',
        ) );

        // Title
        $this->add_control( 'email_title_heading', array(
            'label'     => __( 'Title', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'email_title_color', array(
            'label'     => __( 'Title Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-email-title' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'email_title_typography',
            'selector' => '{{WRAPPER}} .asq-email-title',
        ) );

        // Input
        $this->add_control( 'email_input_heading', array(
            'label'     => __( 'Input Field', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'email_input_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-email-input' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'email_input_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-email-input' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'email_input_placeholder_color', array(
            'label'     => __( 'Placeholder Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-email-input::placeholder' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'email_input_border',
            'selector' => '{{WRAPPER}} .asq-email-input',
        ) );

        $this->add_control( 'email_input_focus_border', array(
            'label'     => __( 'Focus Border Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-email-input:focus' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'email_input_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-email-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'email_input_typography',
            'selector' => '{{WRAPPER}} .asq-email-input',
        ) );

        // Message
        $this->add_control( 'email_msg_heading', array(
            'label'     => __( 'Status Message', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'email_success_color', array(
            'label'     => __( 'Success Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#00a32a',
        ) );

        $this->add_control( 'email_error_color', array(
            'label'     => __( 'Error Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#b32d2e',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Loading Screen ─── */

    private function section_style_loading_screen() {
        $this->start_controls_section( 'section_style_loading', array(
            'label' => __( 'Loading Screen', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_responsive_control( 'loading_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-loading-screen' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_control( 'loading_icon_color', array(
            'label'     => __( 'Spinner Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-loading-icon' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'loading_icon_size', array(
            'label'      => __( 'Spinner Size', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px' ),
            'range'      => array( 'px' => array( 'min' => 20, 'max' => 120 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-loading-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ),
        ) );

        // Heading
        $this->add_control( 'loading_text_heading', array(
            'label'     => __( 'Heading', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'loading_text_color', array(
            'label'     => __( 'Text Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-loading-text' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'loading_text_typography',
            'selector' => '{{WRAPPER}} .asq-loading-text',
        ) );

        $this->add_responsive_control( 'loading_text_align', array(
            'label'     => __( 'Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-loading-text' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'loading_text_margin', array(
            'label'      => __( 'Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-loading-text' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Results Screen ─── */

    private function section_style_results() {
        $this->start_controls_section( 'section_style_results', array(
            'label' => __( 'Results Screen', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        // The box around the reading text (background, border, padding, margin).
        $this->add_control( 'results_box_h', array(
            'label' => __( 'Results box', 'apotheca-skin-quiz' ),
            'type'  => Controls_Manager::HEADING,
        ) );

        $this->add_control( 'results_box_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-results-container' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'results_box_border',
            'selector' => '{{WRAPPER}} .asq-results-container',
        ) );

        $this->add_responsive_control( 'results_box_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-results-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'results_box_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-results-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'results_box_margin', array(
            'label'      => __( 'Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-results-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'results_box_shadow',
            'selector' => '{{WRAPPER}} .asq-results-container',
        ) );

        // Title
        $this->add_control( 'results_title_h', array(
            'label'     => __( 'Title', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'results_title_color', array(
            'label'     => __( 'Title Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-results-title' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'results_title_typography',
            'selector' => '{{WRAPPER}} .asq-results-title',
        ) );

        $this->add_responsive_control( 'results_title_align', array(
            'label'     => __( 'Title Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-results-title' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->add_responsive_control( 'results_title_margin', array(
            'label'      => __( 'Title Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-results-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Grid
        $this->add_control( 'results_grid_heading', array(
            'label'     => __( 'Results Grid', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'results_grid_gap', array(
            'label'      => __( 'Gap', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-results-grid' => 'gap: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .asq-results-container .jet-listing-grid__items' => 'gap: {{SIZE}}{{UNIT}} !important;',
                '{{WRAPPER}} .asq-results-container .jet-listing-grid' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Day / Night Tabs ─── */

    /* ─── Style: Result Cards ─── */

    private function section_style_result_cards() {
        $this->start_controls_section( 'section_style_result_cards', array(
            'label' => __( 'Result Product Cards', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'card_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-result-card' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .asq-result-card',
        ) );

        $this->add_responsive_control( 'card_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-result-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .asq-result-card',
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'card_hover_shadow',
            'label'    => __( 'Hover Shadow', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-result-card:hover',
        ) );

        // Image
        $this->add_control( 'card_img_heading', array(
            'label'     => __( 'Product Image', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'card_img_aspect', array(
            'label'      => __( 'Aspect Ratio', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'range'      => array( 'px' => array( 'min' => 0.3, 'max' => 2, 'step' => 0.05 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-result-img-link' => 'aspect-ratio: {{SIZE}};',
            ),
        ) );

        $this->add_responsive_control( 'card_img_radius', array(
            'label'      => __( 'Image Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-result-img-link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Info
        $this->add_control( 'card_info_heading', array(
            'label'     => __( 'Product Info', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'card_info_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-result-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Name
        $this->add_control( 'card_name_color', array(
            'label'     => __( 'Name Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-result-name a' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'card_name_hover_color', array(
            'label'     => __( 'Name Hover Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-result-name a:hover' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'card_name_typography',
            'selector' => '{{WRAPPER}} .asq-result-name',
        ) );

        // Price
        $this->add_control( 'card_price_heading', array(
            'label'     => __( 'Price', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'card_price_color', array(
            'label'     => __( 'Price Color', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-result-price' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'card_price_typography',
            'selector' => '{{WRAPPER}} .asq-result-price',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: The reading (written result) ─── */

    /**
     * Controls for the written reading that replaces the product grid:
     * the four fixed sections (each with a small heading and body copy),
     * the accent used on emphasised reframe phrases, and the vertical
     * rhythm between sections. Typography groups are responsive by
     * design; spacing uses responsive controls so a one-finding reading
     * and a five-finding reading both stay intentional at every breakpoint.
     */
    private function section_style_reading() {
        $this->start_controls_section( 'section_style_reading', array(
            'label' => __( 'Result Reading', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        // Overall reading block
        $this->add_responsive_control( 'reading_max_width', array(
            'label'      => __( 'Reading Max Width', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', '%' ),
            'range'      => array(
                'px' => array( 'min' => 360, 'max' => 900 ),
                '%'  => array( 'min' => 40, 'max' => 100 ),
            ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading' => 'max-width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'reading_align', array(
            'label'     => __( 'Text Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-reading' => 'text-align: {{VALUE}};',
            ),
        ) );

        // Spacing between the four sections
        $this->add_responsive_control( 'reading_section_gap', array(
            'label'      => __( 'Space Between Sections', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading-section' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .asq-reading-section:last-child' => 'margin-bottom: 0;',
            ),
        ) );

        // Section heading
        $this->add_control( 'reading_heading_h', array(
            'label'     => __( 'Section Heading', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'reading_heading_color', array(
            'label'     => __( 'Heading Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-reading-heading' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'reading_heading_typography',
            'selector' => '{{WRAPPER}} .asq-reading-heading',
        ) );

        $this->add_responsive_control( 'reading_heading_spacing', array(
            'label'      => __( 'Heading Spacing (below)', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading-heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ),
        ) );

        // Body copy
        $this->add_control( 'reading_body_h', array(
            'label'     => __( 'Body Copy', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'reading_body_color', array(
            'label'     => __( 'Body Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-reading-p' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'reading_body_typography',
            'selector' => '{{WRAPPER}} .asq-reading-p',
        ) );

        $this->add_responsive_control( 'reading_body_spacing', array(
            'label'      => __( 'Paragraph Spacing', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading-p' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .asq-reading-p:last-child' => 'margin-bottom: 0;',
            ),
        ) );

        // Emphasis accent
        $this->add_control( 'reading_accent_h', array(
            'label'     => __( 'Emphasised Phrases', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'reading_accent_color', array(
            'label'       => __( 'Accent Colour', 'apotheca-skin-quiz' ),
            'description' => __( 'Used on the emphasised reframe phrases in the reading, and on the read-next link label.', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::COLOR,
            'selectors'   => array(
                '{{WRAPPER}} .asq-reading' => '--asq-reading-accent: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'reading_accent_typography',
            'label'    => __( 'Emphasis Typography', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-reading-em',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Medical gate result ─── */

    /**
     * Separate container styling for the safety gate reading. Styled
     * calmly rather than as an alert: it reuses the reading container but
     * exposes its own background, border, radius, padding and heading so
     * the gate can be given a quiet, distinct treatment without shouting.
     */
    private function section_style_gate_result() {
        $this->start_controls_section( 'section_style_gate_result', array(
            'label' => __( 'Medical Gate Result', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        $this->add_control( 'gate_note', array(
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __( 'Shown only when an answer trips the safety gate. Keep it calm, not alarming.', 'apotheca-skin-quiz' ),
            'content_classes' => 'elementor-descriptor',
        ) );

        $this->add_control( 'gate_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-reading--gate' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'gate_border',
            'selector' => '{{WRAPPER}} .asq-reading--gate',
        ) );

        $this->add_responsive_control( 'gate_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading--gate' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'gate_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-reading--gate' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        // Gate heading
        $this->add_control( 'gate_heading_h', array(
            'label'     => __( 'Gate Heading', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'gate_heading_color', array(
            'label'     => __( 'Heading Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-reading--gate .asq-reading-heading--gate' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'gate_heading_typography',
            'selector' => '{{WRAPPER}} .asq-reading--gate .asq-reading-heading--gate',
        ) );

        // Gate body
        $this->add_control( 'gate_body_h', array(
            'label'     => __( 'Gate Body', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'gate_body_color', array(
            'label'     => __( 'Body Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-reading--gate .asq-reading-p' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'gate_body_typography',
            'selector' => '{{WRAPPER}} .asq-reading--gate .asq-reading-p',
        ) );

        $this->end_controls_section();
    }

    /* ─── Style: Read-next articles ─── */

    /**
     * Read-next block controls, matched to the Ingredient List Decoder's
     * article cards so the two tools read as one family: intro line, card
     * container (background, border, radius, gap, padding), thumbnail
     * size, title and excerpt typography and colour, and the "read more"
     * label. All spacing controls are responsive.
     */
    private function section_style_read_next() {
        $this->start_controls_section( 'section_style_read_next', array(
            'label' => __( 'Read-Next Articles', 'apotheca-skin-quiz' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ) );

        // The full-width box the whole read-next block sits in.
        $this->add_control( 'rn_box_h', array(
            'label' => __( 'Read-next box', 'apotheca-skin-quiz' ),
            'type'  => Controls_Manager::HEADING,
        ) );

        $this->add_control( 'rn_box_bg', array(
            'label'     => __( 'Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-wrap' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'rn_box_border',
            'selector' => '{{WRAPPER}} .asq-readnext-wrap',
        ) );

        $this->add_responsive_control( 'rn_box_radius', array(
            'label'      => __( 'Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'rn_box_padding', array(
            'label'      => __( 'Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'rn_box_margin', array(
            'label'      => __( 'Margin', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-wrap' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'rn_box_shadow',
            'selector' => '{{WRAPPER}} .asq-readnext-wrap',
        ) );

        // The "Read next" heading on the box.
        $this->add_control( 'rn_heading_h', array(
            'label'     => __( '"Read next" heading', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'rn_heading_color', array(
            'label'     => __( 'Heading Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-wrap .asq-reading-heading' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'rn_heading_typography',
            'selector' => '{{WRAPPER}} .asq-readnext-wrap .asq-reading-heading',
        ) );

        // Intro line
        $this->add_control( 'rn_intro_line_h', array(
            'label'     => __( 'Intro line', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'rn_intro_color', array(
            'label'     => __( 'Intro Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-intro' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'rn_intro_typography',
            'selector' => '{{WRAPPER}} .asq-readnext-intro',
        ) );

        $this->add_responsive_control( 'rn_intro_spacing', array(
            'label'      => __( 'Intro Spacing (below)', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-intro' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ),
        ) );

        // Cards
        $this->add_control( 'rn_cards_h', array(
            'label'     => __( 'Cards', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        // Columns per device for the read-next grid. Defaults keep the current
        // single-column list; raise it for a 2 or 3 across grid.
        $this->add_responsive_control( 'rn_columns', array(
            'label'          => __( 'Columns', 'apotheca-skin-quiz' ),
            'type'           => Controls_Manager::SLIDER,
            'range'          => array( 'px' => array( 'min' => 1, 'max' => 4, 'step' => 1 ) ),
            'default'        => array( 'size' => 1 ),
            'tablet_default' => array( 'size' => 2 ),
            'mobile_default' => array( 'size' => 1 ),
            'selectors'      => array(
                '{{WRAPPER}} .asq-readnext-cards' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
            ),
        ) );

        $this->add_responsive_control( 'rn_cards_gap', array(
            'label'      => __( 'Gap Between Cards', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => array( 'px', 'em' ),
            'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-cards' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ) );

        // Card layout: the default row (image beside the text) or stacked
        // (image on top), which suits a 2 or 3 column grid.
        $this->add_control( 'rn_card_layout', array(
            'label'        => __( 'Card Layout', 'apotheca-skin-quiz' ),
            'type'         => Controls_Manager::SELECT,
            'default'      => 'row',
            'options'      => array(
                'row'     => __( 'Row (image left)', 'apotheca-skin-quiz' ),
                'stacked' => __( 'Stacked (image on top)', 'apotheca-skin-quiz' ),
            ),
            'prefix_class' => 'asq-rn-layout-',
        ) );

        $this->add_responsive_control( 'rn_card_padding', array(
            'label'      => __( 'Card Padding', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', 'em' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'rn_content_align', array(
            'label'     => __( 'Content Alignment', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => array(
                'left'   => array( 'title' => __( 'Left', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-left' ),
                'center' => array( 'title' => __( 'Center', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-center' ),
                'right'  => array( 'title' => __( 'Right', 'apotheca-skin-quiz' ), 'icon' => 'eicon-text-align-right' ),
            ),
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-body' => 'text-align: {{VALUE}};',
            ),
        ) );

        $this->add_control( 'rn_card_bg', array(
            'label'     => __( 'Card Background', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-card' => 'background-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Border::get_type(), array(
            'name'     => 'rn_card_border',
            'selector' => '{{WRAPPER}} .asq-readnext-card',
        ) );

        $this->add_responsive_control( 'rn_card_radius', array(
            'label'      => __( 'Card Border Radius', 'apotheca-skin-quiz' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => array( 'px', '%' ),
            'selectors'  => array(
                '{{WRAPPER}} .asq-readnext-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
            ),
        ) );

        $this->add_control( 'rn_card_hover_border', array(
            'label'     => __( 'Hover Border Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-card:hover' => 'border-color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
            'name'     => 'rn_card_hover_shadow',
            'label'    => __( 'Hover Shadow', 'apotheca-skin-quiz' ),
            'selector' => '{{WRAPPER}} .asq-readnext-card:hover',
        ) );

        // Thumbnail
        $this->add_control( 'rn_thumb_h', array(
            'label'     => __( 'Thumbnail', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_responsive_control( 'rn_thumb_width', array(
            'label'       => __( 'Thumbnail Width (row layout)', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => array( 'px' ),
            'range'       => array( 'px' => array( 'min' => 48, 'max' => 200 ) ),
            'selectors'   => array(
                '{{WRAPPER}}:not(.asq-rn-layout-stacked) .asq-readnext-thumb' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
                '{{WRAPPER}}:not(.asq-rn-layout-stacked) .asq-readnext-thumb img' => 'width: {{SIZE}}{{UNIT}};',
            ),
        ) );

        $this->add_responsive_control( 'rn_thumb_height', array(
            'label'       => __( 'Thumbnail Height (stacked layout)', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => array( 'px' ),
            'range'       => array( 'px' => array( 'min' => 80, 'max' => 360 ) ),
            'selectors'   => array(
                '{{WRAPPER}}.asq-rn-layout-stacked .asq-readnext-thumb img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
            ),
        ) );

        // Title
        $this->add_control( 'rn_title_h', array(
            'label'     => __( 'Title', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'rn_title_color', array(
            'label'     => __( 'Title Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-title' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'rn_title_typography',
            'selector' => '{{WRAPPER}} .asq-readnext-title',
        ) );

        // Excerpt
        $this->add_control( 'rn_excerpt_h', array(
            'label'     => __( 'Excerpt', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'rn_excerpt_color', array(
            'label'     => __( 'Excerpt Colour', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => array(
                '{{WRAPPER}} .asq-readnext-excerpt' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'rn_excerpt_typography',
            'selector' => '{{WRAPPER}} .asq-readnext-excerpt',
        ) );

        // Read more label
        $this->add_control( 'rn_more_h', array(
            'label'     => __( 'Read-More Label', 'apotheca-skin-quiz' ),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ) );

        $this->add_control( 'rn_more_color', array(
            'label'       => __( 'Label Colour', 'apotheca-skin-quiz' ),
            'description' => __( 'Leave empty to inherit the reading accent colour.', 'apotheca-skin-quiz' ),
            'type'        => Controls_Manager::COLOR,
            'selectors'   => array(
                '{{WRAPPER}} .asq-readnext-more' => 'color: {{VALUE}};',
            ),
        ) );

        $this->add_group_control( Group_Control_Typography::get_type(), array(
            'name'     => 'rn_more_typography',
            'selector' => '{{WRAPPER}} .asq-readnext-more',
        ) );

        $this->end_controls_section();
    }

    /* ═══════════════════════════════════════
       RENDER
       ═══════════════════════════════════════ */

    /**
     * A static, styleable preview for the Elementor editor: a sample question
     * and a short sample reading, in the real markup so the style controls in
     * every panel apply. Never shown on the front end.
     */
    private function render_editor_preview( $finder_id ) {
        if ( ! class_exists( 'ASQ_Config' ) ) {
            return '';
        }

        // Make sure the frontend stylesheet is present in the editor iframe.
        if ( ! wp_style_is( 'asq-frontend', 'registered' ) ) {
            wp_register_style( 'asq-frontend', ASQ_PLUGIN_URL . 'frontend/css/asq-frontend.css', array(), ASQ_VERSION );
        }
        wp_enqueue_style( 'asq-frontend' );

        $questions = ASQ_Config::questions();
        $q         = isset( $questions[0] ) ? $questions[0] : array( 'text' => 'Question', 'answers' => array() );
        $instr     = ! empty( $q['instruction'] ) ? $q['instruction'] : __( 'Select one option', 'apotheca-skin-quiz' );
        $answers   = array_slice( isset( $q['answers'] ) ? $q['answers'] : array(), 0, 4 );

        // A neutral placeholder thumbnail so read-next cards preview with an image.
        $ph  = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22200%22%20height%3D%22200%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23e5e7eb%22/%3E%3C/svg%3E';
        $cap = 'style="display:block;margin:26px 0 6px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#b9b9c0;"';

        ob_start();
        ?>
        <div class="asq-finder" id="asq-finder-<?php echo esc_attr( $finder_id ); ?>">
            <div style="margin:0 0 14px;padding:8px 12px;background:#f6f4fb;border:1px dashed #cdb9f0;border-radius:6px;font-size:12px;color:#6b5aa0;">
                <?php esc_html_e( 'Editor preview, for styling only. Every screen is shown stacked so you can style each part. The live quiz is interactive on the published page.', 'apotheca-skin-quiz' ); ?>
            </div>

            <span <?php echo $cap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Question screen', 'apotheca-skin-quiz' ); ?></span>

            <div class="asq-progress-bar-wrap">
                <div class="asq-progress-bar"><div class="asq-progress-fill" style="width:40%;"></div></div>
                <span class="asq-progress-text">40%</span>
            </div>

            <div class="asq-questions-container">
                <div class="asq-question-slide asq-slide-in">
                    <div class="asq-text-layout">
                        <div class="asq-text-left">
                            <h2 class="asq-question-text"><?php echo ASQ_Config::kses_copy( $q['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised inline HTML ?></h2>
                            <p class="asq-question-instruction"><?php echo ASQ_Config::kses_copy( $instr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised inline HTML ?></p>
                        </div>
                        <div class="asq-text-right">
                            <div class="asq-answers-grid asq-answers-grid--text">
                                <?php foreach ( $answers as $idx => $a ) : ?>
                                    <div class="asq-answer-option asq-answer-option--text<?php echo 0 === $idx ? ' asq-selected' : ''; ?>">
                                        <span class="asq-answer-text"><?php echo ASQ_Config::kses_copy( $a['text'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitised inline HTML ?></span>
                                        <span class="asq-checkbox"><span class="asq-check-icon"></span></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="asq-nav-buttons">
                        <div class="asq-nav-left"><button type="button" class="asq-btn asq-btn-secondary asq-btn-back"><?php esc_html_e( 'Back', 'apotheca-skin-quiz' ); ?></button></div>
                        <div class="asq-nav-center"><button type="button" class="asq-btn asq-btn-secondary asq-restart"><?php esc_html_e( 'Start again', 'apotheca-skin-quiz' ); ?></button></div>
                        <div class="asq-nav-right"><button type="button" class="asq-btn asq-btn-primary asq-btn-continue"><?php esc_html_e( 'Continue', 'apotheca-skin-quiz' ); ?></button></div>
                    </div>
                </div>
            </div>

            <span <?php echo $cap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Loading screen', 'apotheca-skin-quiz' ); ?></span>
            <div class="asq-loading-screen" style="display:block;">
                <div class="asq-loading-inner">
                    <svg class="asq-loading-icon" viewBox="0 0 50 50" width="60" height="60"><circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="90, 150"/></svg>
                    <p class="asq-loading-text"><?php esc_html_e( 'Working out your results…', 'apotheca-skin-quiz' ); ?></p>
                </div>
            </div>

            <span <?php echo $cap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Email-copy form', 'apotheca-skin-quiz' ); ?></span>
            <div class="asq-gate asq-emailcopy">
                <p class="asq-gate-lead"><?php esc_html_e( 'Want your results by email?', 'apotheca-skin-quiz' ); ?></p>
                <p class="asq-emailcopy-sub"><?php esc_html_e( "We'll send you a copy to keep.", 'apotheca-skin-quiz' ); ?></p>
                <div class="asq-gate-form">
                    <input type="email" class="asq-email-input" placeholder="<?php esc_attr_e( 'Enter your email address', 'apotheca-skin-quiz' ); ?>">
                    <label class="asq-consent-label"><input type="checkbox" class="asq-consent-checkbox" checked><span><?php esc_html_e( 'Yes, email me my result and send me skincare thinking and news from Apotheca®.', 'apotheca-skin-quiz' ); ?></span></label>
                    <button type="button" class="asq-btn asq-btn-primary asq-send-email"><?php esc_html_e( 'Email me a copy', 'apotheca-skin-quiz' ); ?></button>
                </div>
            </div>

            <span <?php echo $cap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Results screen', 'apotheca-skin-quiz' ); ?></span>

            <div class="asq-results-screen" style="display:block;">
                <h3 class="asq-results-title"><?php esc_html_e( 'Your results', 'apotheca-skin-quiz' ); ?></h3>
                <div class="asq-results-container">
                    <div class="asq-reading">
                        <section class="asq-reading-section asq-reading-section--describing">
                            <h3 class="asq-reading-heading"><?php esc_html_e( "What you're describing", 'apotheca-skin-quiz' ); ?></h3>
                            <p class="asq-reading-p"><?php echo esc_html__( 'A sample paragraph so you can style the reading, with an ', 'apotheca-skin-quiz' ); ?><span class="asq-reading-em"><?php esc_html_e( 'emphasised phrase', 'apotheca-skin-quiz' ); ?></span><?php esc_html_e( ' shown in your accent colour.', 'apotheca-skin-quiz' ); ?></p>
                        </section>
                        <section class="asq-reading-section asq-reading-section--probably_not">
                            <h3 class="asq-reading-heading"><?php esc_html_e( "What it probably isn't", 'apotheca-skin-quiz' ); ?></h3>
                            <p class="asq-reading-p"><?php esc_html_e( 'A second sample paragraph, so spacing between sections is visible.', 'apotheca-skin-quiz' ); ?></p>
                        </section>
                        <section class="asq-reading-section asq-reading-section--worth_trying">
                            <h3 class="asq-reading-heading"><?php esc_html_e( 'One or two things worth trying', 'apotheca-skin-quiz' ); ?></h3>
                            <p class="asq-reading-p"><?php esc_html_e( 'A third sample paragraph.', 'apotheca-skin-quiz' ); ?></p>
                        </section>
                    </div>
                </div>
                <div class="asq-results-actions">
                    <button type="button" class="asq-btn asq-btn-secondary asq-start-over"><?php esc_html_e( 'Start over', 'apotheca-skin-quiz' ); ?></button>
                </div>
                <div class="asq-readnext-wrap">
                    <section class="asq-reading-section asq-reading-section--read_next">
                        <h3 class="asq-reading-heading"><?php esc_html_e( 'Read next', 'apotheca-skin-quiz' ); ?></h3>
                        <p class="asq-reading-p asq-readnext-intro"><?php esc_html_e( 'A few things worth reading next.', 'apotheca-skin-quiz' ); ?></p>
                        <div class="asq-readnext-cards">
                            <?php for ( $c = 1; $c <= 3; $c++ ) : ?>
                                <a class="asq-readnext-card" href="#" onclick="return false;">
                                    <span class="asq-readnext-thumb"><img src="<?php echo esc_attr( $ph ); ?>" alt=""></span>
                                    <span class="asq-readnext-body">
                                        <span class="asq-readnext-title"><?php esc_html_e( 'Sample article title', 'apotheca-skin-quiz' ); ?></span>
                                        <span class="asq-readnext-excerpt"><?php esc_html_e( 'A short description of the article shows here.', 'apotheca-skin-quiz' ); ?></span>
                                        <span class="asq-readnext-more"><?php esc_html_e( 'Read more', 'apotheca-skin-quiz' ); ?></span>
                                    </span>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function render() {
        $settings  = $this->get_settings_for_display();
        $finder_id = absint( $settings['finder_id'] ?? 0 );

        if ( ! $finder_id ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="padding:40px;text-align:center;background:#f7f7f7;border:2px dashed #ccc;border-radius:8px;">';
                echo '<p style="font-size:16px;color:#666;">Apotheca Skin Quiz Widget</p>';
                echo '<p style="color:#999;">Please select a Apotheca Skin Quiz from the content settings.</p>';
                echo '</div>';
            }
            return;
        }

        // In the Elementor editor the live quiz is JS-driven and does not run
        // inside the editor iframe, so it shows as blank and can't be styled.
        // Render a static, styleable preview of the question and the reading
        // instead, using the real classes so every style control applies. On
        // the front end this branch is skipped and the live quiz renders.
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            echo $this->render_editor_preview( $finder_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_* below.
            return;
        }

        // Build shortcode with optional custom heading overrides.
        $shortcode_atts = 'id="' . $finder_id . '"';

        $loading_heading = trim( $settings['loading_heading_text'] ?? '' );
        if ( $loading_heading ) {
            $shortcode_atts .= ' loading_heading="' . esc_attr( $loading_heading ) . '"';
        }

        $results_heading = trim( $settings['results_heading_text'] ?? '' );
        if ( $results_heading ) {
            $shortcode_atts .= ' results_heading="' . esc_attr( $results_heading ) . '"';
        }

        // The Ingredient List Decoder page for the F12 result link.
        $decoder_url = '';
        if ( isset( $settings['decoder_url']['url'] ) && is_string( $settings['decoder_url']['url'] ) ) {
            $decoder_url = trim( $settings['decoder_url']['url'] );
        }
        if ( $decoder_url ) {
            $shortcode_atts .= ' decoder_url="' . esc_url( $decoder_url ) . '"';
        }

        if ( ! empty( $settings['rn_new_tab'] ) && 'yes' === $settings['rn_new_tab'] ) {
            $shortcode_atts .= ' rn_new_tab="1"';
        }

        // Read DN labels from finder post meta instead of Elementor settings
        $dn_meta = $finder_id ? get_post_meta( $finder_id, '_asq_dn_styles', true ) : array();
        $dn_meta = is_array( $dn_meta ) ? $dn_meta : array();

        $dn_day_label = trim( $dn_meta['day_label'] ?? '' );
        if ( $dn_day_label && 'Day' !== $dn_day_label ) {
            $shortcode_atts .= ' tab_day_label="' . esc_attr( $dn_day_label ) . '"';
        }

        $dn_night_label = trim( $dn_meta['night_label'] ?? '' );
        if ( $dn_night_label && 'Night' !== $dn_night_label ) {
            $shortcode_atts .= ' tab_night_label="' . esc_attr( $dn_night_label ) . '"';
        }

        // Render the shortcode
        echo do_shortcode( '[apotheca_skin_quiz ' . $shortcode_atts . ']' );

        // Output Day/Night tab styles inline so they apply to the
        // dynamically injected tabs regardless of Elementor CSS file
        // caching (e.g. WP Rocket).  Elementor's normal CSS generation
        // writes to an external file or <style> block at page-save time,
        // but caching plugins may serve a stale version.
        $this->render_dn_tab_inline_styles( $settings );

        // If a custom SVG icon was chosen, inject it to replace the default loading icon
        $icon_settings = $settings['loading_svg_icon'] ?? array();
        if ( ! empty( $icon_settings['value'] ) ) {
            $icon_html = '';

            if ( is_array( $icon_settings['value'] ) && ! empty( $icon_settings['value']['url'] ) ) {
                // SVG upload – render as <img> tag
                $icon_html = '<img src="' . esc_url( $icon_settings['value']['url'] ) . '" alt="" class="asq-custom-loading-img">';
            } else {
                // Icon library (Font Awesome, etc.) – render via Elementor helper
                ob_start();
                \Elementor\Icons_Manager::render_icon( $icon_settings, array( 'aria-hidden' => 'true', 'class' => 'asq-custom-loading-i' ) );
                $icon_html = ob_get_clean();
            }

            if ( $icon_html ) {
                ?>
                <script>
                (function(){
                    var wrap = document.getElementById('asq-finder-<?php echo esc_js( $finder_id ); ?>');
                    if (!wrap) return;
                    var oldIcon = wrap.querySelector('.asq-loading-icon');
                    if (!oldIcon) return;
                    var tmp = document.createElement('div');
                    tmp.innerHTML = <?php echo wp_json_encode( $icon_html ); ?>;
                    var newIcon = tmp.firstElementChild;
                    if (newIcon) {
                        newIcon.classList.add('asq-loading-icon');
                        oldIcon.parentNode.replaceChild(newIcon, oldIcon);
                    }
                })();
                </script>
                <?php
            }
        }
    }

    protected function content_template() {
        ?>
        <# if ( ! settings.finder_id ) { #>
        <div style="padding:40px;text-align:center;background:#f7f7f7;border:2px dashed #ccc;border-radius:8px;">
            <p style="font-size:16px;color:#666;">Apotheca Skin Quiz Widget</p>
            <p style="color:#999;">Please select a Apotheca Skin Quiz from the content settings.</p>
        </div>
        <# } #>
        <?php
    }

    /* ═══════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════ */

    /**
     * Output inline <style> for Day/Night tab controls.
     *
     * Dynamically created elements (.asq-dn-tab) can miss Elementor's
     * pre-generated CSS when a caching plugin serves a stale file.
     * This method writes the styles directly into the page HTML.
     */
    private function render_dn_tab_inline_styles( $settings ) {
        $finder_id = absint( $settings['finder_id'] ?? 0 );
        if ( ! $finder_id ) {
            return;
        }

        $dn = get_post_meta( $finder_id, '_asq_dn_styles', true );
        if ( ! is_array( $dn ) || empty( $dn ) ) {
            echo "\n<!-- PF DN v6: no _asq_dn_styles for finder $finder_id -->\n";
            return;
        }

        $w     = '.elementor-element-' . $this->get_id();
        $rules = array();

        // Typography
        if ( ! empty( $dn['font_family'] ) ) {
            $rules[] = "$w .asq-dn-tab{font-family:" . esc_attr( $dn['font_family'] ) . '}';
        }
        if ( ! empty( $dn['font_size'] ) ) {
            $rules[] = "$w .asq-dn-tab{font-size:" . absint( $dn['font_size'] ) . 'px}';
        }
        if ( ! empty( $dn['font_weight'] ) ) {
            $rules[] = "$w .asq-dn-tab{font-weight:" . esc_attr( $dn['font_weight'] ) . '}';
        }

        // Tab padding / gap / radius / margin (CSS shorthand values)
        if ( ! empty( $dn['tab_padding'] ) ) {
            $rules[] = "$w .asq-dn-tab{padding:" . esc_attr( $dn['tab_padding'] ) . '}';
        }
        if ( ! empty( $dn['tab_gap'] ) ) {
            $rules[] = "$w .asq-dn-tabs{gap:" . absint( $dn['tab_gap'] ) . 'px}';
        }
        if ( ! empty( $dn['tab_radius'] ) ) {
            $rules[] = "$w .asq-dn-tab{border-radius:" . esc_attr( $dn['tab_radius'] ) . '}';
        }
        if ( ! empty( $dn['tabs_margin'] ) ) {
            $rules[] = "$w .asq-dn-tabs{margin:" . esc_attr( $dn['tabs_margin'] ) . '}';
        }
        if ( ! empty( $dn['tabs_align'] ) ) {
            $rules[] = "$w .asq-dn-tabs{justify-content:" . esc_attr( $dn['tabs_align'] ) . '}';
        }

        // Normal state
        if ( ! empty( $dn['tab_color'] ) ) {
            $rules[] = "$w .asq-dn-tab{color:" . esc_attr( $dn['tab_color'] ) . '}';
        }
        if ( ! empty( $dn['tab_bg'] ) ) {
            $rules[] = "$w .asq-dn-tab{background-color:" . esc_attr( $dn['tab_bg'] ) . '}';
        }

        // Hover state
        if ( ! empty( $dn['tab_hover_color'] ) ) {
            $rules[] = "$w .asq-dn-tab:hover{color:" . esc_attr( $dn['tab_hover_color'] ) . '}';
        }
        if ( ! empty( $dn['tab_hover_bg'] ) ) {
            $rules[] = "$w .asq-dn-tab:hover{background-color:" . esc_attr( $dn['tab_hover_bg'] ) . '}';
        }

        // Active state
        if ( ! empty( $dn['tab_active_color'] ) ) {
            $rules[] = "$w .asq-dn-tab.asq-dn-tab--active{color:" . esc_attr( $dn['tab_active_color'] ) . '}';
        }
        if ( ! empty( $dn['tab_active_bg'] ) ) {
            $rules[] = "$w .asq-dn-tab.asq-dn-tab--active{background-color:" . esc_attr( $dn['tab_active_bg'] ) . '}';
        }

        // Bottom line
        if ( ! empty( $dn['line_color'] ) ) {
            $rules[] = "$w .asq-dn-tabs{border-bottom-color:" . esc_attr( $dn['line_color'] ) . '}';
        }
        if ( ! empty( $dn['line_width'] ) ) {
            $s = absint( $dn['line_width'] ) . 'px';
            $rules[] = "$w .asq-dn-tabs{border-bottom-width:$s}";
            $rules[] = "$w .asq-dn-tab--active::after{height:$s;bottom:calc(-1 * $s)}";
        }
        if ( ! empty( $dn['active_line_color'] ) ) {
            $rules[] = "$w .asq-dn-tab--active::after{background:" . esc_attr( $dn['active_line_color'] ) . '}';
        }

        echo "\n<!-- PF DN v6: finder=$finder_id | rules=" . count( $rules ) . " -->\n";

        if ( ! empty( $rules ) ) {
            echo '<style>' . implode( '', $rules ) . '</style>';
        }
    }

    private function get_finder_list() {
        $finders = get_posts( array(
            'post_type'      => 'apotheca_skin_quiz',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft' ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $list = array();
        foreach ( $finders as $f ) {
            $list[ $f->ID ] = $f->post_title;
        }

        return $list;
    }
}

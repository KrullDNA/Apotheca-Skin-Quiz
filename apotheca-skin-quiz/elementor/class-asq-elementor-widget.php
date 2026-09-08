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

        // Title
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

    /* ═══════════════════════════════════════
       RENDER
       ═══════════════════════════════════════ */

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

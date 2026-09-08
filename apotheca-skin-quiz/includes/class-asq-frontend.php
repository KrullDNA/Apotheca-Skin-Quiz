<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend shortcode and asset loading for Apotheca Skin Quiz.
 */
class ASQ_Frontend {

    public function __construct() {
        add_shortcode( 'apotheca_skin_quiz', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
    }

    public function register_assets() {
        wp_register_style(
            'asq-frontend',
            ASQ_PLUGIN_URL . 'frontend/css/asq-frontend.css',
            array(),
            ASQ_VERSION
        );

        wp_register_script(
            'asq-frontend',
            ASQ_PLUGIN_URL . 'frontend/js/asq-frontend.js',
            array( 'jquery' ),
            ASQ_VERSION,
            true
        );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'               => 0,
            'loading_heading'  => '',
            'results_heading'  => '',
            'tab_day_label'    => '',
            'tab_night_label'  => '',
        ), $atts, 'apotheca_skin_quiz' );

        $finder_id = absint( $atts['id'] );
        if ( ! $finder_id ) {
            return '<p>' . esc_html__( 'Apotheca Skin Quiz: invalid ID.', 'apotheca-skin-quiz' ) . '</p>';
        }

        $questions = get_post_meta( $finder_id, '_asq_questions', true );
        if ( ! is_array( $questions ) || empty( $questions ) ) {
            return '<p>' . esc_html__( 'Apotheca Skin Quiz: no questions configured.', 'apotheca-skin-quiz' ) . '</p>';
        }

        $options = get_post_meta( $finder_id, '_asq_options', true );
        $options = wp_parse_args( (array) $options, array(
            'enable_consent' => 1,
            'consent_text'   => '',
        ) );

        $consent_text = ! empty( $options['consent_text'] )
            ? $options['consent_text']
            : __( "I'd like to receive news and offers", 'apotheca-skin-quiz' );

        wp_enqueue_style( 'asq-frontend' );
        wp_enqueue_script( 'asq-frontend' );

        // Check if we have a results session token in the URL.
        $results_token = isset( $_GET['asq_results'] ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $_GET['asq_results'] ) : '';

        // Build the base page URL (without query params) for constructing results URLs.
        $page_url = get_permalink();
        if ( ! $page_url ) {
            global $wp;
            $page_url = home_url( add_query_arg( array(), $wp->request ) );
        }

        wp_localize_script( 'asq-frontend', 'asqFrontend', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'asq_frontend_nonce' ),
            'finder_id'     => $finder_id,
            'page_url'      => $page_url,
            'results_token' => $results_token,
            'i18n'          => array(
                'next'         => __( 'Continue', 'apotheca-skin-quiz' ),
                'back'         => __( 'Back', 'apotheca-skin-quiz' ),
                'skip_email'   => __( 'Skip & View Results', 'apotheca-skin-quiz' ),
                'send_results' => __( 'Send Results', 'apotheca-skin-quiz' ),
                'view_results' => __( 'View Results', 'apotheca-skin-quiz' ),
                'loading'      => __( 'Reading your answers…', 'apotheca-skin-quiz' ),
                'email_label'  => __( 'Get your results sent to your inbox', 'apotheca-skin-quiz' ),
                'email_placeholder' => __( 'Enter your email address', 'apotheca-skin-quiz' ),
                'email_success'=> __( 'Results sent!', 'apotheca-skin-quiz' ),
                'email_fail'   => __( 'Failed to send. Please try again.', 'apotheca-skin-quiz' ),
                'your_results' => __( 'Your responses', 'apotheca-skin-quiz' ),
                'start_over'   => __( 'Start Over', 'apotheca-skin-quiz' ),
                'complete'     => __( 'Complete', 'apotheca-skin-quiz' ),
            ),
        ) );

        // Build inline data for the finder so we don't need an extra AJAX call
        $inline_data = array();
        foreach ( $questions as $q ) {
            $q_data = array(
                'text'        => $q['text'],
                'instruction' => $q['instruction'] ?? '',
                'multiple'    => (bool) $q['multiple'],
                'answers'     => array(),
            );
            foreach ( $q['answers'] as $a ) {
                $image_url = ! empty( $a['image_id'] ) ? wp_get_attachment_image_url( $a['image_id'], 'large' ) : '';
                $a_data = array(
                    'text'        => $a['text'],
                    'description' => $a['description'] ?? '',
                    'image'       => $image_url,
                );

                // Include follow-up question data if present
                if ( ! empty( $a['follow_up'] ) && ! empty( $a['follow_up']['text'] ) ) {
                    $fu = $a['follow_up'];
                    $fu_data = array(
                        'text'        => $fu['text'],
                        'instruction' => $fu['instruction'] ?? '',
                        'multiple'    => ! empty( $fu['multiple'] ),
                        'answers'     => array(),
                    );
                    if ( ! empty( $fu['answers'] ) ) {
                        foreach ( $fu['answers'] as $fa ) {
                            $fa_image = ! empty( $fa['image_id'] ) ? wp_get_attachment_image_url( $fa['image_id'], 'large' ) : '';
                            $fu_data['answers'][] = array(
                                'text'        => $fa['text'] ?? '',
                                'description' => $fa['description'] ?? '',
                                'image'       => $fa_image,
                            );
                        }
                    }
                    $a_data['follow_up'] = $fu_data;
                }

                $q_data['answers'][] = $a_data;
            }
            $inline_data[] = $q_data;
        }

        // Admin-only settings must not end up in the public markup.
        $public_options = $options;
        unset( $public_options['notify_email'] );

        ob_start();
        ?>
        <div class="asq-finder" id="asq-finder-<?php echo esc_attr( $finder_id ); ?>" data-finder-id="<?php echo esc_attr( $finder_id ); ?>" data-questions="<?php echo esc_attr( wp_json_encode( $inline_data ) ); ?>" data-options="<?php echo esc_attr( wp_json_encode( $public_options ) ); ?>"<?php
            if ( ! empty( $atts['loading_heading'] ) ) {
                echo ' data-loading-heading="' . esc_attr( $atts['loading_heading'] ) . '"';
            }
            if ( ! empty( $atts['results_heading'] ) ) {
                echo ' data-results-heading="' . esc_attr( $atts['results_heading'] ) . '"';
            }
            if ( ! empty( $atts['tab_day_label'] ) ) {
                echo ' data-tab-day-label="' . esc_attr( $atts['tab_day_label'] ) . '"';
            }
            if ( ! empty( $atts['tab_night_label'] ) ) {
                echo ' data-tab-night-label="' . esc_attr( $atts['tab_night_label'] ) . '"';
            }
        ?>>

            <!-- Progress bar -->
            <div class="asq-progress-bar-wrap">
                <div class="asq-progress-bar">
                    <div class="asq-progress-fill" style="width:0%"></div>
                </div>
                <span class="asq-progress-text">0%</span>
            </div>

            <!-- Questions container -->
            <div class="asq-questions-container"></div>

            <!-- Email capture screen -->
            <div class="asq-email-screen" style="display:none;">
                <div class="asq-email-inner">
                    <h3 class="asq-email-title"></h3>
                    <p class="asq-email-desc"></p>
                    <div class="asq-email-form">
                        <input type="email" class="asq-email-input" placeholder="">
                        <button type="button" class="asq-btn asq-btn-primary asq-send-email"></button>
                    </div>
                    <?php if ( ! empty( $options['enable_consent'] ) ) : ?>
                        <label class="asq-consent-label">
                            <input type="checkbox" class="asq-consent-checkbox" value="1">
                            <span><?php echo esc_html( $consent_text ); ?></span>
                        </label>
                    <?php endif; ?>
                    <button type="button" class="asq-btn asq-btn-link asq-skip-email"></button>
                    <div class="asq-email-message" style="display:none;"></div>
                </div>
            </div>

            <!-- Loading screen -->
            <div class="asq-loading-screen" style="display:none;">
                <div class="asq-loading-inner">
                    <svg class="asq-loading-icon" viewBox="0 0 50 50" width="60" height="60">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="90, 150" stroke-dashoffset="0"/>
                    </svg>
                    <p class="asq-loading-text"></p>
                </div>
            </div>

            <!-- Results screen -->
            <div class="asq-results-screen" style="display:none;">
                <h3 class="asq-results-title"></h3>
                <div class="asq-results-container"></div>
                <div class="asq-results-actions">
                    <button type="button" class="asq-btn asq-btn-secondary asq-start-over"></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new ASQ_Frontend();

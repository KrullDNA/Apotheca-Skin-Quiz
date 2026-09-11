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
            'decoder_url'      => '',
            'rn_new_tab'       => '',
        ), $atts, 'apotheca_skin_quiz' );

        $finder_id = absint( $atts['id'] );
        if ( ! $finder_id ) {
            return '<p>' . esc_html__( 'Apotheca Skin Quiz: invalid ID.', 'apotheca-skin-quiz' ) . '</p>';
        }

        // The questions are defined in one place, the config array, and
        // shared across every quiz (front door). See asq-quiz-config.php.
        $questions = ASQ_Config::questions();
        if ( empty( $questions ) ) {
            return '<p>' . esc_html__( 'Apotheca Skin Quiz: no questions configured.', 'apotheca-skin-quiz' ) . '</p>';
        }

        $options = get_post_meta( $finder_id, '_asq_options', true );
        $options = wp_parse_args( (array) $options, array(
            'enable_consent' => 1,
            'consent_text'   => '',
            'exchange_text'  => '',
        ) );

        // Consent wording matches the Ingredient List Decoder so a person who
        // meets both tools is asked in the same words.
        $consent_text = ! empty( $options['consent_text'] )
            ? $options['consent_text']
            : __( 'Yes, email me my result and send me skincare thinking and news from Apotheca®.', 'apotheca-skin-quiz' );

        // The exchange used to sit above the first question. It now lives on the
        // email-copy box at the end instead, so this is empty by default and the
        // banner does not show. A site can still set its own text in the options.
        $exchange_text = ! empty( $options['exchange_text'] )
            ? $options['exchange_text']
            : '';

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
            'source_id'     => (int) get_the_ID(),
            'results_token' => $results_token,
            'gate'          => ASQ_Config::gate_meta(),
            'consent_enabled' => ! empty( $options['enable_consent'] ),
            'consent_text'    => $consent_text,
            'exchange_text'   => $exchange_text,
            'cookie_days'     => 180,
            'i18n'          => array(
                'next'              => __( 'Continue', 'apotheca-skin-quiz' ),
                'back'              => __( 'Back', 'apotheca-skin-quiz' ),
                'loading'           => __( 'Working out your results…', 'apotheca-skin-quiz' ),
                'email_placeholder' => __( 'Enter your email address', 'apotheca-skin-quiz' ),
                'email_fail'        => __( 'Failed to send. Please try again.', 'apotheca-skin-quiz' ),
                'email_gate_lead'   => __( 'Want your results by email?', 'apotheca-skin-quiz' ),
                'email_copy_sub'    => __( "If you'd like a copy by email, add your address here. One tick covers it, and you can leave any time.", 'apotheca-skin-quiz' ),
                'send_reading'      => __( 'Email me a copy', 'apotheca-skin-quiz' ),
                'consent_hint'      => __( 'Tick the box so we can send it.', 'apotheca-skin-quiz' ),
                'sent_confirm'      => __( 'Sent. Check your inbox for your copy.', 'apotheca-skin-quiz' ),
                'your_results'      => __( 'Your results', 'apotheca-skin-quiz' ),
                'start_over'        => __( 'Start over', 'apotheca-skin-quiz' ),
                'start_again'       => __( 'Start again', 'apotheca-skin-quiz' ),
                'restart_confirm'   => __( 'Start the quiz again? Your answers so far will be cleared.', 'apotheca-skin-quiz' ),
                'complete'          => __( 'Complete', 'apotheca-skin-quiz' ),
            ),
        ) );

        // Build inline data for the quiz so we don't need an extra AJAX call.
        // Text, instruction, multiple and the answer labels only; the finding
        // mappings stay server-side.
        $inline_data = ASQ_Config::frontend_questions();

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
            // The Ingredient List Decoder page, for the F12 link. Per widget, so
            // it rides on the wrapper rather than the shared localised object.
            if ( ! empty( $atts['decoder_url'] ) ) {
                echo ' data-decoder-url="' . esc_url( $atts['decoder_url'] ) . '"';
            }
            if ( ! empty( $atts['rn_new_tab'] ) ) {
                echo ' data-rn-new-tab="1"';
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
                <h3 class="asq-results-title" tabindex="-1"></h3>
                <div class="asq-results-container" role="region" aria-live="polite" aria-atomic="false" aria-label="<?php esc_attr_e( 'Your results', 'apotheca-skin-quiz' ); ?>"></div>
                <div class="asq-results-actions">
                    <button type="button" class="asq-btn asq-btn-secondary asq-start-over"></button>
                </div>
                <!-- Read-next sits full width below the reading and the button. -->
                <div class="asq-readnext-wrap" hidden></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new ASQ_Frontend();

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX result handler for Apotheca Skin Quiz.
 *
 * Stage 3 placeholder engine. The product recommendation logic, the
 * WooCommerce scoring and the CrocoBlock listing rendering have all been
 * removed. For now this simply resolves the visitor's answers into
 * readable question/answer text and hands them back, so the whole flow
 * (questions -> email gate -> loading -> result) can be tested end to end
 * before the findings engine is built in a later stage.
 *
 * There is no WooCommerce dependency of any kind in this class.
 */
class ASQ_Ajax {

    public function __construct() {
        // Frontend: compute the result from the answers.
        add_action( 'wp_ajax_asq_compute_results', array( $this, 'compute_results' ) );
        add_action( 'wp_ajax_nopriv_asq_compute_results', array( $this, 'compute_results' ) );

        // Frontend: record only that the medical gate fired (no PII, no option).
        add_action( 'wp_ajax_asq_record_gate', array( $this, 'record_gate' ) );
        add_action( 'wp_ajax_nopriv_asq_record_gate', array( $this, 'record_gate' ) );
    }

    /**
     * Store nothing but the fact that the gate fired.
     */
    public function record_gate() {
        check_ajax_referer( 'asq_frontend_nonce', 'nonce' );
        $finder_id = absint( $_POST['finder_id'] ?? 0 );
        if ( ! $finder_id ) {
            wp_send_json_error();
        }
        ASQ_Leads::record_gate( $finder_id );
        wp_send_json_success();
    }

    /**
     * Resolve the answer set and return it as the (placeholder) result.
     */
    public function compute_results() {
        check_ajax_referer( 'asq_frontend_nonce', 'nonce' );

        $finder_id        = absint( $_POST['finder_id'] ?? 0 );
        $answers          = json_decode( stripslashes( $_POST['answers'] ?? '[]' ), true );
        $followup_answers = json_decode( stripslashes( $_POST['followup_answers'] ?? '{}' ), true );
        if ( ! is_array( $followup_answers ) ) {
            $followup_answers = array();
        }

        // If a results_token is provided, re-hydrate answers from the stored
        // session (this powers the shareable results URL).
        $results_token = sanitize_text_field( $_POST['results_token'] ?? '' );
        if ( $results_token ) {
            $session = ASQ_Email::get_session_data( $results_token );
            if ( $session && ! empty( $session['finder_id'] ) ) {
                $finder_id        = absint( $session['finder_id'] );
                $answers          = json_decode( stripslashes( $session['answers'] ?? '[]' ), true );
                $followup_answers = json_decode( stripslashes( $session['followup_answers'] ?? '{}' ), true );
                if ( ! is_array( $answers ) ) {
                    $answers = array();
                }
                if ( ! is_array( $followup_answers ) ) {
                    $followup_answers = array();
                }
            } else {
                wp_send_json_error( array( 'message' => __( 'Results session expired or invalid.', 'apotheca-skin-quiz' ) ) );
            }
        }

        if ( ! $finder_id || ! is_array( $answers ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data.', 'apotheca-skin-quiz' ) ) );
        }

        $options = get_post_meta( $finder_id, '_asq_options', true );
        $options = wp_parse_args( (array) $options, array(
            'enable_consent' => 1,
            'consent_text'   => '',
        ) );
        // Never expose the owner's notification address to visitors.
        unset( $options['notify_email'] );

        // The page the quiz sits on, so read-next can exclude it.
        $source_id = absint( $_POST['source_id'] ?? 0 );

        // Run the findings engine.
        $findings = ASQ_Engine::evaluate( (array) $answers );
        $is_gate  = ( 1 === count( $findings ) && isset( $findings[0]['id'] ) && 'F11' === $findings[0]['id'] );

        // The medical gate replaces the reading. It may offer at most one
        // general article. Return only what the screen needs to show it,
        // never the answers or findings behind it.
        if ( $is_gate ) {
            $articles     = ASQ_Read_Next::for_gate( $source_id );
            $reading_html = ASQ_Presenter::render( $findings, (array) $answers, $articles );
            wp_send_json_success( array(
                'is_gate'      => true,
                'reading_html' => $reading_html,
                'options'      => $options,
            ) );
        }

        // Normal reading, with up to three read-next articles, split so the
        // first section shows and the rest sits behind the email gate.
        $articles = ASQ_Read_Next::for_findings( $findings, $source_id );
        $split    = ASQ_Presenter::render_split( $findings, (array) $answers, $articles );

        wp_send_json_success( array(
            'is_gate'           => false,
            'reading_intro_html' => $split['intro'],
            'reading_rest_html'  => $split['rest'],
            'options'            => $options,
        ) );
    }
}

new ASQ_Ajax();

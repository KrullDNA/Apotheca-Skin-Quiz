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

        $questions = get_post_meta( $finder_id, '_asq_questions', true );
        if ( ! is_array( $questions ) ) {
            wp_send_json_error();
        }

        $options = get_post_meta( $finder_id, '_asq_options', true );
        $options = wp_parse_args( (array) $options, array(
            'enable_consent' => 1,
            'consent_text'   => '',
        ) );
        // Never expose the owner's notification address to visitors.
        unset( $options['notify_email'] );

        // Turn the raw answer indices into readable question/answer text.
        $readable = ASQ_Leads::resolve_answers( $finder_id, (array) $answers, (array) $followup_answers );

        wp_send_json_success( array(
            'answers' => $readable,
            'options' => $options,
        ) );
    }
}

new ASQ_Ajax();

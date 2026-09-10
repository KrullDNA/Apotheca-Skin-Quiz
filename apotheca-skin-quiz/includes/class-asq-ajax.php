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

        // Frontend: anonymous funnel beacon (reached a question / completed).
        add_action( 'wp_ajax_asq_track', array( $this, 'track' ) );
        add_action( 'wp_ajax_nopriv_asq_track', array( $this, 'track' ) );
    }

    /**
     * Anonymous drop-off beacon. Records only a per-day count for a quiz: which
     * question index was reached, or that the quiz was completed. No personal
     * data, no answers. Fire-and-forget, so the response is deliberately tiny.
     */
    public function track() {
        check_ajax_referer( 'asq_frontend_nonce', 'nonce' );

        $finder_id = absint( $_POST['finder_id'] ?? 0 );
        $event     = ( isset( $_POST['event'] ) && 'complete' === $_POST['event'] ) ? 'complete' : 'reach';
        $q         = absint( $_POST['q'] ?? 0 );

        if ( $finder_id ) {
            ASQ_Leads::record_progress( $finder_id, $event, $q );
        }

        wp_send_json_success();
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

        // The Ingredient List Decoder page, set on the Elementor widget and
        // passed through so the F12 reading can link to it.
        $decoder_url = isset( $_POST['decoder_url'] ) ? esc_url_raw( wp_unslash( $_POST['decoder_url'] ) ) : '';

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

        // Normal reading, with up to three read-next articles. Read-next also
        // draws on findings that were suppressed from the copy but are still
        // true, so their articles can still appear.
        $rn_findings = ASQ_Engine::read_next_findings( $findings );
        $articles    = ASQ_Read_Next::for_findings( $rn_findings, $source_id );
        $split       = ASQ_Presenter::render_split( $findings, (array) $answers, $articles, $decoder_url );

        wp_send_json_success( array(
            'is_gate'           => false,
            'reading_intro_html' => $split['intro'],
            'reading_rest_html'  => $split['rest'],
            'options'            => $options,
        ) );
    }
}

new ASQ_Ajax();

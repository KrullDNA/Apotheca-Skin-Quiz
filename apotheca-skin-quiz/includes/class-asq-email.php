<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Email sending and shareable results URLs for Apotheca Skin Quiz.
 *
 * The email system is inherited from Product Finder: same branded layout,
 * the same per-quiz styling controls (logo, header image, accent colour,
 * heading, sub-heading, subject and footer) and the same admin test send.
 * What changed in Stage 3 is only the CONTENT it renders. Instead of a list
 * of recommended products it now renders the visitor's answer set as a
 * placeholder, until the written reading is built in a later stage.
 *
 * There is no WooCommerce dependency of any kind in this class.
 */
class ASQ_Email {

    public function __construct() {
        add_action( 'wp_ajax_asq_send_results_email', array( $this, 'send_results_email' ) );
        add_action( 'wp_ajax_nopriv_asq_send_results_email', array( $this, 'send_results_email' ) );

        // Save results to a session for a shareable URL.
        add_action( 'wp_ajax_asq_save_results_session', array( $this, 'save_results_session' ) );
        add_action( 'wp_ajax_nopriv_asq_save_results_session', array( $this, 'save_results_session' ) );

        // Admin: send a test email so the styling can be previewed.
        add_action( 'wp_ajax_asq_send_test_email', array( $this, 'send_test_email' ) );
    }

    /* ────────── Rate limiting ──────── */

    /**
     * Max results emails a single IP may trigger per hour. The endpoint is
     * open to visitors, so without a cap it could be scripted to spam
     * arbitrary inboxes with the site's branded email.
     */
    const RATE_LIMIT = 5;

    /**
     * Returns true if the current IP is within its hourly send allowance
     * (and consumes one slot); false if the limit is exhausted.
     */
    private function check_rate_limit() {
        $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
        if ( ! $ip ) {
            return false;
        }

        $key   = 'asq_email_rl_' . md5( $ip );
        $count = (int) get_transient( $key );

        if ( $count >= self::RATE_LIMIT ) {
            return false;
        }

        set_transient( $key, $count + 1, HOUR_IN_SECONDS );
        return true;
    }

    /**
     * Generate a unique session token and store the answers in a transient.
     */
    public function save_results_session() {
        check_ajax_referer( 'asq_frontend_nonce', 'nonce' );

        $finder_id = absint( $_POST['finder_id'] ?? 0 );
        $answers   = $_POST['answers'] ?? '[]';
        $followup  = $_POST['followup_answers'] ?? '{}';

        if ( ! $finder_id ) {
            wp_send_json_error( array( 'message' => 'Invalid quiz.' ) );
        }

        // Only store valid JSON – the token URL is public, so never let
        // arbitrary strings into the transient.
        if ( null === json_decode( stripslashes( $answers ), true ) ) {
            $answers = '[]';
        }
        if ( null === json_decode( stripslashes( $followup ), true ) ) {
            $followup = '{}';
        }

        $token = wp_generate_password( 16, false );

        $session_data = array(
            'finder_id'        => $finder_id,
            'answers'          => $answers,
            'followup_answers' => $followup,
            'created'          => time(),
        );

        // Store for 30 days.
        set_transient( 'asq_results_' . $token, $session_data, 30 * DAY_IN_SECONDS );

        wp_send_json_success( array( 'token' => $token ) );
    }

    /**
     * Get stored session data by token.
     */
    public static function get_session_data( $token ) {
        $token = preg_replace( '/[^a-zA-Z0-9]/', '', $token );
        return get_transient( 'asq_results_' . $token );
    }

    /* ────────── Send the result email ──────── */

    public function send_results_email() {
        check_ajax_referer( 'asq_frontend_nonce', 'nonce' );

        $email     = sanitize_email( $_POST['email'] ?? '' );
        $finder_id = absint( $_POST['finder_id'] ?? 0 );
        $results_url = esc_url_raw( $_POST['results_url'] ?? '' );
        $consent   = ! empty( $_POST['consent'] );

        if ( ! is_email( $email ) || ! $finder_id ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'apotheca-skin-quiz' ) ) );
        }

        if ( ! $this->check_rate_limit() ) {
            wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'apotheca-skin-quiz' ) ) );
        }

        // Resolve the answers into readable question/answer text.
        $answers  = json_decode( stripslashes( $_POST['answers'] ?? '[]' ), true );
        $readable = ASQ_Config::resolve_answers( (array) $answers );

        $email_styles = $this->get_email_styles( $finder_id );
        $finder_title = get_the_title( $finder_id );
        $subject      = ! empty( $email_styles['email_subject'] )
            ? $email_styles['email_subject']
            : sprintf( __( 'Your %s Results', 'apotheca-skin-quiz' ), $finder_title );

        $body = $this->build_email_body( $finder_id, $finder_title, $readable, $results_url, $email_styles );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        // Record the lead before sending – the visitor completed the quiz and
        // gave their address regardless of whether the mail server cooperates.
        $this->record_lead( $finder_id, $email, $consent, $readable );

        $sent = wp_mail( $email, $subject, $body, $headers );

        $this->notify_owner( $finder_id, $finder_title, $email, $consent );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Results sent to your email!', 'apotheca-skin-quiz' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send email. Please try again.', 'apotheca-skin-quiz' ) ) );
        }
    }

    /**
     * Read and default the per-quiz email styling options.
     */
    private function get_email_styles( $finder_id ) {
        $styles = get_post_meta( $finder_id, '_asq_email_styles', true );
        return wp_parse_args( (array) $styles, array(
            'logo_id'         => 0,
            'header_image_id' => 0,
            'accent_color'    => '#000000',
            'heading'         => '',
            'sub_heading'     => '',
            'email_subject'   => '',
            'footer_text'     => '',
        ) );
    }

    /**
     * Store the submission in the leads table with readable answers.
     * Products are gone, so the products column is stored empty.
     */
    private function record_lead( $finder_id, $email, $consent, $readable_answers ) {
        if ( ! class_exists( 'ASQ_Leads' ) ) {
            return false;
        }
        return ASQ_Leads::add_lead( $finder_id, $email, $consent, $readable_answers, array() );
    }

    /**
     * Send an instant lead alert to the quiz's notification address, if set.
     */
    private function notify_owner( $finder_id, $finder_title, $email, $consent ) {
        $options      = get_post_meta( $finder_id, '_asq_options', true );
        $notify_email = sanitize_email( is_array( $options ) ? ( $options['notify_email'] ?? '' ) : '' );

        if ( ! is_email( $notify_email ) ) {
            return;
        }

        $submissions_url = admin_url( 'edit.php?post_type=apotheca_skin_quiz&page=asq-submissions&finder=' . $finder_id );

        /* translators: %s: quiz title */
        $subject = sprintf( __( 'New Apotheca Skin Quiz submission: %s', 'apotheca-skin-quiz' ), $finder_title );

        $lines   = array();
        /* translators: %s: quiz title */
        $lines[] = sprintf( __( 'Someone just completed "%s".', 'apotheca-skin-quiz' ), $finder_title );
        $lines[] = '';
        $lines[] = __( 'Email:', 'apotheca-skin-quiz' ) . ' ' . $email;
        $lines[] = __( 'Marketing consent:', 'apotheca-skin-quiz' ) . ' ' . ( $consent ? __( 'Yes', 'apotheca-skin-quiz' ) : __( 'No', 'apotheca-skin-quiz' ) );
        $lines[] = '';
        $lines[] = __( 'View all submissions:', 'apotheca-skin-quiz' );
        $lines[] = $submissions_url;

        wp_mail( $notify_email, $subject, implode( "\n", $lines ) );
    }

    /* ────────── Admin: test email ──────── */

    /**
     * Send a sample email (placeholder answers) to the current admin user so
     * the Email Styling settings can be previewed quickly. No WooCommerce.
     */
    public function send_test_email() {
        check_ajax_referer( 'asq_admin_nonce', 'nonce' );

        $finder_id = absint( $_POST['finder_id'] ?? 0 );
        if ( ! $finder_id || ! current_user_can( 'edit_post', $finder_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'apotheca-skin-quiz' ) ) );
        }

        $user = wp_get_current_user();
        $to   = $user && is_email( $user->user_email ) ? $user->user_email : get_option( 'admin_email' );

        // A short sample answer set makes the preview representative.
        $sample = array(
            array(
                'question' => __( 'What brought you here today?', 'apotheca-skin-quiz' ),
                'answers'  => array( __( 'Something has changed and I don\'t know why', 'apotheca-skin-quiz' ) ),
            ),
            array(
                'question' => __( 'How does your skin feel twenty minutes after you cleanse?', 'apotheca-skin-quiz' ),
                'answers'  => array( __( 'Tight, like it needs something urgently', 'apotheca-skin-quiz' ) ),
            ),
        );

        $email_styles = $this->get_email_styles( $finder_id );
        $finder_title = get_the_title( $finder_id );
        $subject      = ! empty( $email_styles['email_subject'] )
            ? $email_styles['email_subject']
            : sprintf( __( 'Your %s Results', 'apotheca-skin-quiz' ), $finder_title );
        $subject      = '[' . __( 'TEST', 'apotheca-skin-quiz' ) . '] ' . $subject;

        $body = $this->build_email_body( $finder_id, $finder_title, $sample, home_url( '/' ), $email_styles );

        $sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

        if ( $sent ) {
            /* translators: %s: email address */
            wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent to %s.', 'apotheca-skin-quiz' ), $to ) ) );
        }
        wp_send_json_error( array( 'message' => __( 'Failed to send test email. Check your mail configuration.', 'apotheca-skin-quiz' ) ) );
    }

    /* ────────── Email body ──────── */

    /**
     * Build the branded HTML email. Keeps the Product Finder look (top accent
     * strip, logo, heading block, footer) and is responsive: a single fluid
     * column with a small media query so it stacks cleanly on a phone.
     *
     * @param int    $finder_id
     * @param string $finder_title
     * @param array  $readable      [ [ 'question' => str, 'answers' => [str,…] ], … ]
     * @param string $results_url
     * @param array  $email_styles
     * @return string
     */
    private function build_email_body( $finder_id, $finder_title, $readable, $results_url = '', $email_styles = array() ) {
        $accent   = ! empty( $email_styles['accent_color'] ) ? $email_styles['accent_color'] : '#000000';
        $logo_url = '';
        if ( ! empty( $email_styles['logo_id'] ) ) {
            $logo_url = wp_get_attachment_image_url( absint( $email_styles['logo_id'] ), 'medium' );
        }
        $heading     = ! empty( $email_styles['heading'] ) ? $email_styles['heading'] : $finder_title . ', your results';
        $sub_heading = ! empty( $email_styles['sub_heading'] ) ? $email_styles['sub_heading'] : __( 'Here is what you told us. Your full reading will appear here once the quiz is complete.', 'apotheca-skin-quiz' );
        $footer_text = ! empty( $email_styles['footer_text'] ) ? $email_styles['footer_text'] : __( 'This email was generated by the Apotheca Skin Quiz.', 'apotheca-skin-quiz' );
        $header_image_url = '';
        if ( ! empty( $email_styles['header_image_id'] ) ) {
            $header_image_url = wp_get_attachment_image_url( absint( $email_styles['header_image_id'] ), 'full' );
        }

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">';
        // Responsive rules: honoured by clients that support <style> + media queries.
        $html .= '<style>';
        $html .= 'body{margin:0;padding:0;}img{border:0;outline:none;text-decoration:none;}table{border-collapse:collapse;}';
        $html .= '@media only screen and (max-width:600px){';
        $html .= '.asq-e-container{width:100% !important;}';
        $html .= '.asq-e-pad{padding-left:20px !important;padding-right:20px !important;}';
        $html .= '.asq-e-h1{font-size:22px !important;}';
        $html .= '}';
        $html .= '</style>';
        $html .= '</head><body style="margin:0;padding:0;background:#ffffff;font-family:\'Montserrat\',Verdana,Arial,Helvetica,sans-serif;">';

        // Container.
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:0 16px;"><table role="presentation" class="asq-e-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">';

        // Top accent strip.
        $html .= '<tr><td style="background:' . esc_attr( $accent ) . ';height:20px;font-size:0;line-height:0;">&nbsp;</td></tr>';

        // Logo.
        if ( $logo_url ) {
            $html .= '<tr><td style="text-align:center;padding:30px 0 16px;">';
            $html .= '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $finder_title ) . '" style="max-width:200px;max-height:80px;height:auto;" />';
            $html .= '</td></tr>';
        }

        // Heading + sub-heading (or header image).
        if ( $header_image_url ) {
            $alt_text = $heading . ', ' . wp_strip_all_tags( $sub_heading );
            $html .= '<tr><td style="text-align:center;padding:16px 0 24px;">';
            $html .= '<img src="' . esc_url( $header_image_url ) . '" alt="' . esc_attr( $alt_text ) . '" style="max-width:100%;height:auto;display:block;margin:0 auto;" />';
            $html .= '</td></tr>';
        } else {
            $html .= '<tr><td class="asq-e-pad" style="padding:16px 0 24px;">';
            $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#f5f5f5;border-radius:8px;text-align:center;padding:28px 24px;">';
            $html .= '<h1 class="asq-e-h1" style="margin:0 0 8px;font-size:24px;font-weight:700;color:#000;">' . esc_html( $heading ) . '</h1>';
            $html .= '<div style="margin:0;font-size:14px;color:#666;line-height:1.5;">' . wp_kses_post( $sub_heading ) . '</div>';
            $html .= '</td></tr></table>';
            $html .= '</td></tr>';
        }

        // Answer-set placeholder body.
        $html .= '<tr><td class="asq-e-pad" style="padding:8px 0 8px;">';
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
        if ( ! empty( $readable ) && is_array( $readable ) ) {
            foreach ( $readable as $row ) {
                $question = isset( $row['question'] ) ? $row['question'] : '';
                $ans      = isset( $row['answers'] ) ? (array) $row['answers'] : array();
                $html .= '<tr><td style="padding:12px 0;border-bottom:1px solid #eee;">';
                $html .= '<div style="font-size:13px;font-weight:600;color:#000;margin-bottom:4px;">' . esc_html( $question ) . '</div>';
                $html .= '<div style="font-size:15px;color:#333;line-height:1.5;">' . esc_html( implode( ', ', $ans ) ) . '</div>';
                $html .= '</td></tr>';
            }
        } else {
            $html .= '<tr><td style="padding:12px 0;font-size:15px;color:#333;">' . esc_html__( 'No answers were recorded.', 'apotheca-skin-quiz' ) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</td></tr>';

        // View results button (the shareable results URL).
        if ( $results_url ) {
            $html .= '<tr><td style="text-align:center;padding:24px 0 8px;">';
            $html .= '<a href="' . esc_url( $results_url ) . '" style="display:inline-block;background:' . esc_attr( $accent ) . ';color:#ffffff;text-decoration:none;padding:14px 36px;font-size:14px;font-weight:300;letter-spacing:0.08em;text-transform:uppercase;">';
            $html .= esc_html__( 'VIEW MY RESULTS', 'apotheca-skin-quiz' );
            $html .= '</a>';
            $html .= '</td></tr>';
        }

        // Footer.
        $html .= '<tr><td class="asq-e-pad" style="text-align:center;padding:20px 0 30px;border-top:1px solid #e0e0e0;">';
        $html .= '<p style="margin:0;font-size:12px;color:#999;">' . esc_html( $footer_text ) . '</p>';
        $html .= '</td></tr>';

        // Close container.
        $html .= '</table></td></tr></table>';
        $html .= '</body></html>';

        return $html;
    }
}

new ASQ_Email();

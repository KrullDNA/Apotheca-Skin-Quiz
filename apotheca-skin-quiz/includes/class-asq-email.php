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

        $answers = json_decode( stripslashes( $_POST['answers'] ?? '[]' ), true );
        if ( ! is_array( $answers ) ) {
            $answers = array();
        }

        $email_styles = $this->get_email_styles( $finder_id );
        $finder_title = get_the_title( $finder_id );
        $subject      = ! empty( $email_styles['email_subject'] )
            ? $email_styles['email_subject']
            : sprintf( __( 'Your %s Results', 'apotheca-skin-quiz' ), $finder_title );
        $headers      = array( 'Content-Type: text/html; charset=UTF-8' );

        // Medical gate: never store answers or findings, never join a lead,
        // never push to a connector. Record only that the gate fired, and send
        // a plain acknowledgement containing no findings.
        if ( ASQ_Engine::is_gate( $answers ) ) {
            ASQ_Leads::record_gate( $finder_id );
            $ack  = $this->build_ack_body( $finder_title, $email_styles );
            $sent = wp_mail( $email, $subject, $ack, $headers );
            if ( $sent ) {
                wp_send_json_success( array( 'message' => __( 'Sent to your email.', 'apotheca-skin-quiz' ) ) );
            }
            wp_send_json_error( array( 'message' => __( 'Failed to send email. Please try again.', 'apotheca-skin-quiz' ) ) );
        }

        // Normal path. The one consent covers the emailed reading and
        // marketing, so without it we store nothing and send nothing.
        if ( ! $consent ) {
            wp_send_json_error( array( 'message' => __( 'Please tick the box so we can send your reading.', 'apotheca-skin-quiz' ) ) );
        }

        // The exact consent wording shown, and the page she came from.
        $consent_text = isset( $_POST['consent_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['consent_text'] ) ) : '';
        $source_id    = absint( $_POST['source_id'] ?? 0 );
        $source       = $source_id ? get_permalink( $source_id ) : $results_url;

        // Recompute the findings on the server, so what is stored and pushed is
        // trustworthy, then resolve the answers to readable text.
        $findings = ASQ_Engine::evaluate( $answers );
        $readable = ASQ_Config::resolve_answers( $answers );

        // Store the submission, joined to the lead by email. This fires
        // asq_lead_recorded, which queues the connector push (findings as
        // fields) for a consented lead.
        $ids     = ASQ_Leads::capture( array(
            'finder_id'    => $finder_id,
            'email'        => $email,
            'consent'      => $consent,
            'consent_text' => $consent_text,
            'source'       => $source,
            'answers'      => $readable,
            'findings'     => $findings,
        ) );
        $lead_id = is_array( $ids ) && ! empty( $ids['lead_id'] ) ? $ids['lead_id'] : 0;

        // Build the reading and email it: the four sections flat, read-next
        // with thumbnails, a working unsubscribe, and the consent wording.
        $articles = ASQ_Read_Next::for_findings( $findings, 0 );
        $reading  = ASQ_Presenter::build_reading( $findings, $answers, $articles );
        $body     = $this->build_email_body( $finder_title, $reading, array(
            'results_url'     => $results_url,
            'unsubscribe_url' => ASQ_Leads::unsubscribe_url( $lead_id ),
            'consent_text'    => $consent_text,
            'styles'          => $email_styles,
        ) );
        $sent = wp_mail( $email, $subject, $body, $headers );

        $this->notify_owner( $finder_id, $finder_title, $email, $consent );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Results sent to your email!', 'apotheca-skin-quiz' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send email. Please try again.', 'apotheca-skin-quiz' ) ) );
        }
    }

    /**
     * A plain acknowledgement for a gated response: the same branded chrome,
     * a calm note, and no findings of any kind.
     */
    private function build_ack_body( $finder_title, $email_styles ) {
        $phrasing = ASQ_Presenter::phrasing();
        $accent   = ! empty( $email_styles['accent_color'] ) ? $email_styles['accent_color'] : '#000000';
        $logo_url = ! empty( $email_styles['logo_id'] ) ? wp_get_attachment_image_url( absint( $email_styles['logo_id'] ), 'medium' ) : '';
        $note     = isset( $phrasing['gate']['email_ack'] ) ? $phrasing['gate']['email_ack'] : '';
        $footer   = ! empty( $email_styles['footer_text'] ) ? $email_styles['footer_text'] : __( 'This email was generated by the Apotheca Skin Quiz.', 'apotheca-skin-quiz' );

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700&display=swap" rel="stylesheet">';
        $html .= '</head><body style="margin:0;padding:0;background:#ffffff;font-family:\'Montserrat\',Verdana,Arial,Helvetica,sans-serif;">';
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:0 16px;"><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">';
        $html .= '<tr><td style="background:' . esc_attr( $accent ) . ';height:20px;font-size:0;line-height:0;">&nbsp;</td></tr>';
        if ( $logo_url ) {
            $html .= '<tr><td style="text-align:center;padding:30px 0 8px;"><img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $finder_title ) . '" style="max-width:200px;max-height:80px;height:auto;" /></td></tr>';
        }
        $html .= '<tr><td style="padding:24px 8px 24px;font-size:17px;line-height:1.6;color:#333;">' . esc_html( $note ) . '</td></tr>';
        $html .= '<tr><td style="text-align:center;padding:20px 8px 30px;border-top:1px solid #e0e0e0;"><p style="margin:0;font-size:12px;color:#999;">' . esc_html( $footer ) . '</p></td></tr>';
        $html .= '</table></td></tr></table></body></html>';

        return $html;
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

        // A representative sample answer set (several findings), run through
        // the real engine so the preview shows the true reading layout.
        $sample_answers = array( 0 => array( 3 ), 1 => array( 0 ), 2 => array( 0 ), 3 => array( 3 ), 4 => array( 3 ), 5 => array( 0 ), 6 => array( 1 ), 7 => array( 0 ), 8 => array( 1 ), 9 => array( 4 ) );
        $findings = ASQ_Engine::evaluate( $sample_answers );
        $articles = ASQ_Read_Next::for_findings( $findings, 0 );
        $reading  = ASQ_Presenter::build_reading( $findings, $sample_answers, $articles );

        $email_styles = $this->get_email_styles( $finder_id );
        $finder_title = get_the_title( $finder_id );
        $subject      = ! empty( $email_styles['email_subject'] )
            ? $email_styles['email_subject']
            : sprintf( __( 'Your %s Results', 'apotheca-skin-quiz' ), $finder_title );
        $subject      = '[' . __( 'TEST', 'apotheca-skin-quiz' ) . '] ' . $subject;

        $body = $this->build_email_body( $finder_title, $reading, array(
            'results_url'     => home_url( '/' ),
            'unsubscribe_url' => home_url( '/' ),
            'consent_text'    => __( 'Yes, email me my result and send me skincare thinking and news from Apotheca®.', 'apotheca-skin-quiz' ),
            'styles'          => $email_styles,
        ) );

        $sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );

        if ( $sent ) {
            /* translators: %s: email address */
            wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent to %s.', 'apotheca-skin-quiz' ), $to ) ) );
        }
        wp_send_json_error( array( 'message' => __( 'Failed to send test email. Check your mail configuration.', 'apotheca-skin-quiz' ) ) );
    }

    /* ────────── Email body ──────── */

    /**
     * Build the branded HTML email carrying the reading. Keeps the inherited
     * layout (top accent strip, logo, heading block, footer) and styling
     * controls, and is responsive. The reading renders flat: the four sections
     * in on-screen order, everything expanded, read-next with thumbnails.
     *
     * @param string $finder_title
     * @param array  $reading  From ASQ_Presenter::build_reading() (sections).
     * @param array  $args     results_url, unsubscribe_url, consent_text, styles.
     * @return string
     */
    private function build_email_body( $finder_title, $reading, $args = array() ) {
        $email_styles = isset( $args['styles'] ) ? (array) $args['styles'] : array();
        $results_url  = isset( $args['results_url'] ) ? $args['results_url'] : '';
        $unsub_url    = isset( $args['unsubscribe_url'] ) ? $args['unsubscribe_url'] : '';
        $consent_text = isset( $args['consent_text'] ) ? $args['consent_text'] : '';

        $accent   = ! empty( $email_styles['accent_color'] ) ? $email_styles['accent_color'] : '#000000';
        $logo_url = ! empty( $email_styles['logo_id'] ) ? wp_get_attachment_image_url( absint( $email_styles['logo_id'] ), 'medium' ) : '';
        $heading     = ! empty( $email_styles['heading'] ) ? $email_styles['heading'] : $finder_title . ', your reading';
        $sub_heading = ! empty( $email_styles['sub_heading'] ) ? $email_styles['sub_heading'] : __( 'Here is your reading, in full.', 'apotheca-skin-quiz' );
        $footer_text = ! empty( $email_styles['footer_text'] ) ? $email_styles['footer_text'] : __( 'This email was generated by the Apotheca Skin Quiz.', 'apotheca-skin-quiz' );
        $header_image_url = ! empty( $email_styles['header_image_id'] ) ? wp_get_attachment_image_url( absint( $email_styles['header_image_id'] ), 'full' ) : '';

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">';
        $html .= '<style>';
        $html .= 'body{margin:0;padding:0;}img{border:0;outline:none;text-decoration:none;}table{border-collapse:collapse;}';
        $html .= '@media only screen and (max-width:600px){.asq-e-container{width:100% !important;}.asq-e-pad{padding-left:20px !important;padding-right:20px !important;}.asq-e-h1{font-size:22px !important;}.asq-e-rn-thumb{display:none !important;}}';
        $html .= '</style>';
        $html .= '</head><body style="margin:0;padding:0;background:#ffffff;font-family:\'Montserrat\',Verdana,Arial,Helvetica,sans-serif;">';

        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding:0 16px;"><table role="presentation" class="asq-e-container" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">';

        // Top accent strip.
        $html .= '<tr><td style="background:' . esc_attr( $accent ) . ';height:20px;font-size:0;line-height:0;">&nbsp;</td></tr>';

        // Logo.
        if ( $logo_url ) {
            $html .= '<tr><td style="text-align:center;padding:30px 0 16px;"><img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $finder_title ) . '" style="max-width:200px;max-height:80px;height:auto;" /></td></tr>';
        }

        // Heading + sub-heading (or header image).
        if ( $header_image_url ) {
            $alt_text = $heading . ', ' . wp_strip_all_tags( $sub_heading );
            $html .= '<tr><td style="text-align:center;padding:16px 0 24px;"><img src="' . esc_url( $header_image_url ) . '" alt="' . esc_attr( $alt_text ) . '" style="max-width:100%;height:auto;display:block;margin:0 auto;" /></td></tr>';
        } else {
            $html .= '<tr><td class="asq-e-pad" style="padding:16px 0 24px;">';
            $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#f5f5f5;border-radius:8px;text-align:center;padding:28px 24px;">';
            $html .= '<h1 class="asq-e-h1" style="margin:0 0 8px;font-size:24px;font-weight:700;color:#000;">' . esc_html( $heading ) . '</h1>';
            $html .= '<div style="margin:0;font-size:14px;color:#666;line-height:1.5;">' . wp_kses_post( $sub_heading ) . '</div>';
            $html .= '</td></tr></table></td></tr>';
        }

        // The reading, flat.
        $html .= '<tr><td class="asq-e-pad" style="padding:8px 0 8px;">';
        $sections = isset( $reading['sections'] ) ? (array) $reading['sections'] : array();
        foreach ( $sections as $section ) {
            $html .= '<h2 style="margin:24px 0 10px;font-size:15px;font-weight:600;color:#666;">' . esc_html( $this->texturize( $section['heading'] ) ) . '</h2>';

            if ( isset( $section['key'] ) && 'read_next' === $section['key'] ) {
                if ( ! empty( $section['intro'] ) ) {
                    $html .= '<p style="margin:0 0 12px;font-size:14px;color:#666;line-height:1.5;">' . esc_html( $this->texturize( $section['intro'] ) ) . '</p>';
                }
                $html .= $this->build_readnext( isset( $section['articles'] ) ? $section['articles'] : array(), $accent );
            } else {
                foreach ( (array) $section['paragraphs'] as $para ) {
                    $html .= '<p style="margin:0 0 14px;font-size:16px;line-height:1.6;color:#333;">' . $this->email_inline( $para ) . '</p>';
                }
            }
        }
        $html .= '</td></tr>';

        // See it online button (the shareable results URL).
        if ( $results_url ) {
            $html .= '<tr><td style="text-align:center;padding:16px 0 8px;">';
            $html .= '<a href="' . esc_url( $results_url ) . '" style="display:inline-block;background:' . esc_attr( $accent ) . ';color:#ffffff;text-decoration:none;padding:14px 36px;font-size:14px;font-weight:300;letter-spacing:0.08em;text-transform:uppercase;">';
            $html .= esc_html__( 'SEE IT ONLINE', 'apotheca-skin-quiz' );
            $html .= '</a></td></tr>';
        }

        // Footer: the editable line, the consent wording, and a working unsubscribe.
        $html .= '<tr><td class="asq-e-pad" style="text-align:center;padding:20px 0 30px;border-top:1px solid #e0e0e0;">';
        $html .= '<p style="margin:0 0 8px;font-size:12px;color:#999;">' . esc_html( $footer_text ) . '</p>';
        if ( $consent_text ) {
            $html .= '<p style="margin:0 0 8px;font-size:11px;color:#aaa;line-height:1.5;">' . esc_html( $this->texturize( $consent_text ) ) . '</p>';
        }
        if ( $unsub_url ) {
            $html .= '<p style="margin:0;font-size:11px;color:#999;">' . esc_html__( 'Not for you?', 'apotheca-skin-quiz' ) . ' <a href="' . esc_url( $unsub_url ) . '" style="color:#999;">' . esc_html__( 'Unsubscribe', 'apotheca-skin-quiz' ) . '</a>.</p>';
        }
        $html .= '</td></tr>';

        $html .= '</table></td></tr></table></body></html>';

        return $html;
    }

    /**
     * Read-next article cards for the email, with thumbnails, table-based so
     * they hold up across mail clients.
     */
    private function build_readnext( $articles, $accent ) {
        if ( empty( $articles ) ) {
            return '';
        }
        $out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
        foreach ( (array) $articles as $card ) {
            $url = isset( $card['url'] ) ? $card['url'] : '';
            if ( '' === $url ) {
                continue;
            }
            $out .= '<tr><td style="padding:8px 0;">';
            $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
            if ( ! empty( $card['thumb'] ) ) {
                $out .= '<td class="asq-e-rn-thumb" width="120" valign="top" style="width:120px;"><a href="' . esc_url( $url ) . '"><img src="' . esc_url( $card['thumb'] ) . '" width="120" alt="" style="width:120px;height:auto;border-radius:6px;display:block;" /></a></td>';
            }
            $out .= '<td valign="top" style="padding-left:' . ( empty( $card['thumb'] ) ? '0' : '14px' ) . ';">';
            $out .= '<a href="' . esc_url( $url ) . '" style="font-size:16px;font-weight:600;color:#000;text-decoration:none;line-height:1.35;">' . esc_html( $this->texturize( isset( $card['title'] ) ? $card['title'] : '' ) ) . '</a>';
            if ( ! empty( $card['excerpt'] ) ) {
                $out .= '<div style="font-size:14px;color:#666;line-height:1.5;margin:4px 0 6px;">' . esc_html( $this->texturize( $card['excerpt'] ) ) . '</div>';
            }
            $out .= '<a href="' . esc_url( $url ) . '" style="font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:' . esc_attr( $accent ) . ';text-decoration:none;">' . esc_html__( 'Read more', 'apotheca-skin-quiz' ) . '</a>';
            $out .= '</td></tr></table>';
            $out .= '</td></tr>';
        }
        $out .= '</table>';
        return $out;
    }

    /**
     * A reading paragraph is trusted, authored HTML with our emphasis span.
     * Swap the class-based span for an inline style so it shows in email.
     */
    private function email_inline( $para ) {
        return str_replace(
            '<span class="asq-reading-em">',
            '<span style="font-weight:600;">',
            (string) $para
        );
    }

    /**
     * Texturise for house-style quotes, if WordPress is loaded.
     */
    private function texturize( $str ) {
        return function_exists( 'wptexturize' ) ? wptexturize( $str ) : $str;
    }
}

new ASQ_Email();

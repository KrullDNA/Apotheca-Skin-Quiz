<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Leads and submissions for Apotheca Skin Quiz.
 *
 * Mirrors the decoder's shape: a LEAD is a person, deduplicated by email, and
 * each quiz completion is a SUBMISSION joined to that lead by lead id, so one
 * person accumulates a history. Every submission stores the full snapshot the
 * brief asks for: email, timestamp, consent state, the exact consent wording
 * shown, the source page, the whole answer set, and the findings that fired.
 *
 * A medical-gate response is never stored here; see record_gate(), which keeps
 * only a count.
 */
class ASQ_Leads {

    /** Bump when the schema changes, triggers dbDelta on admin_init. */
    const DB_VERSION = '3';

    const PER_PAGE = 20;

    public function __construct() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_install' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_asq_export_leads', array( $this, 'export_csv' ) );

        // The unsubscribe link works for logged-out visitors too.
        add_action( 'admin_post_nopriv_asq_unsubscribe', array( $this, 'handle_unsubscribe' ) );
        add_action( 'admin_post_asq_unsubscribe', array( $this, 'handle_unsubscribe' ) );
    }

    public static function capability() {
        return apply_filters( 'asq_leads_capability', 'manage_options' );
    }

    public static function leads_table() {
        global $wpdb;
        return $wpdb->prefix . 'asq_leads';
    }

    public static function submissions_table() {
        global $wpdb;
        return $wpdb->prefix . 'asq_submissions';
    }

    /* ────────── Schema ────────── */

    public static function maybe_install() {
        if ( get_option( 'asq_leads_db_version' ) !== self::DB_VERSION ) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $leads   = self::leads_table();
        $subs    = self::submissions_table();

        // The lead: one row per person, keyed by email.
        dbDelta( "CREATE TABLE {$leads} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL DEFAULT '',
            consent TINYINT(1) NOT NULL DEFAULT 0,
            consent_text TEXT NULL,
            source VARCHAR(255) NULL,
            unsubscribed DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY email (email)
        ) {$charset};" );

        // The submission: one row per quiz completion, joined by lead_id.
        dbDelta( "CREATE TABLE {$subs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            finder_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            email VARCHAR(190) NOT NULL DEFAULT '',
            consent TINYINT(1) NOT NULL DEFAULT 0,
            consent_text TEXT NULL,
            source VARCHAR(255) NULL,
            answers LONGTEXT NULL,
            findings LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY lead_id (lead_id),
            KEY finder_id (finder_id),
            KEY email (email)
        ) {$charset};" );

        update_option( 'asq_leads_db_version', self::DB_VERSION );
    }

    /* ────────── Recording ────────── */

    /**
     * Find or create the lead for an email, then store this submission against
     * it. Fires asq_lead_recorded so a connector can push it.
     *
     * @param array $args finder_id, email, consent (bool), consent_text,
     *                     source, answers (readable array), findings (array of
     *                     [ id, label ]).
     * @return array|false [ 'lead_id' => int, 'submission_id' => int ] or false.
     */
    public static function capture( $args ) {
        global $wpdb;

        $finder_id    = absint( $args['finder_id'] ?? 0 );
        $email        = sanitize_email( $args['email'] ?? '' );
        $consent      = ! empty( $args['consent'] );
        $consent_text = sanitize_textarea_field( $args['consent_text'] ?? '' );
        $source       = esc_url_raw( $args['source'] ?? '' );
        $answers      = isset( $args['answers'] ) ? (array) $args['answers'] : array();
        $findings     = isset( $args['findings'] ) ? (array) $args['findings'] : array();
        $now          = current_time( 'mysql' );

        if ( ! is_email( $email ) ) {
            return false;
        }

        // Find or create the person.
        $lead_id = self::find_lead_id_by_email( $email );
        if ( $lead_id ) {
            $wpdb->update(
                self::leads_table(),
                array(
                    'consent'      => $consent ? 1 : 0,
                    'consent_text' => $consent_text,
                    'source'       => $source,
                    'updated_at'   => $now,
                ),
                array( 'id' => $lead_id ),
                array( '%d', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                self::leads_table(),
                array(
                    'email'        => $email,
                    'consent'      => $consent ? 1 : 0,
                    'consent_text' => $consent_text,
                    'source'       => $source,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ),
                array( '%s', '%d', '%s', '%s', '%s', '%s' )
            );
            $lead_id = (int) $wpdb->insert_id;
        }

        if ( ! $lead_id ) {
            return false;
        }

        // Store the submission, joined to the lead.
        $wpdb->insert(
            self::submissions_table(),
            array(
                'lead_id'      => $lead_id,
                'finder_id'    => $finder_id,
                'email'        => $email,
                'consent'      => $consent ? 1 : 0,
                'consent_text' => $consent_text,
                'source'       => $source,
                'answers'      => wp_json_encode( $answers ),
                'findings'     => wp_json_encode( $findings ),
                'created_at'   => $now,
            ),
            array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
        $submission_id = (int) $wpdb->insert_id;

        /**
         * Fires after a submission is stored. The connector bridge hooks this
         * to push the lead. Findings are provided so each can become a field.
         *
         * @param int   $lead_id
         * @param int   $submission_id
         * @param array $data  email, consent (bool), consent_text, source,
         *                     finder_id, answers, findings.
         */
        do_action( 'asq_lead_recorded', $lead_id, $submission_id, array(
            'email'        => $email,
            'consent'      => $consent,
            'consent_text' => $consent_text,
            'source'       => $source,
            'finder_id'    => $finder_id,
            'answers'      => $answers,
            'findings'     => $findings,
        ) );

        return array( 'lead_id' => $lead_id, 'submission_id' => $submission_id );
    }

    public static function find_lead_id_by_email( $email ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! $email ) {
            return 0;
        }
        return (int) $wpdb->get_var( $wpdb->prepare(
            'SELECT id FROM ' . self::leads_table() . ' WHERE email = %s ORDER BY id DESC LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL
            $email
        ) );
    }

    public static function get_lead( $lead_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . self::leads_table() . ' WHERE id = %d', // phpcs:ignore WordPress.DB.PreparedSQL
            absint( $lead_id )
        ), ARRAY_A );
    }

    public static function get_submission( $submission_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT * FROM ' . self::submissions_table() . ' WHERE id = %d', // phpcs:ignore WordPress.DB.PreparedSQL
            absint( $submission_id )
        ), ARRAY_A );
        if ( ! $row ) {
            return null;
        }
        $row['answers']  = json_decode( (string) $row['answers'], true ) ?: array();
        $row['findings'] = json_decode( (string) $row['findings'], true ) ?: array();
        $row['consent']  = (bool) $row['consent'];
        return $row;
    }

    /* ────────── Unsubscribe ────────── */

    /**
     * A signed unsubscribe token for a lead, so the link cannot be guessed.
     */
    public static function unsubscribe_token( $lead_id ) {
        return substr( wp_hash( 'asq_unsub_' . (int) $lead_id ), 0, 20 );
    }

    /**
     * The working unsubscribe URL for a lead.
     */
    public static function unsubscribe_url( $lead_id ) {
        $lead_id = absint( $lead_id );
        if ( ! $lead_id ) {
            return '';
        }
        return add_query_arg( array(
            'action' => 'asq_unsubscribe',
            'lead'   => $lead_id,
            't'      => self::unsubscribe_token( $lead_id ),
        ), admin_url( 'admin-post.php' ) );
    }

    /**
     * Handle an unsubscribe click: withdraw consent so nothing more is sent or
     * pushed, then show a plain confirmation.
     */
    public function handle_unsubscribe() {
        global $wpdb;

        $lead_id = absint( $_GET['lead'] ?? 0 );
        $token   = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';

        if ( $lead_id && hash_equals( self::unsubscribe_token( $lead_id ), $token ) ) {
            $wpdb->update(
                self::leads_table(),
                array( 'consent' => 0, 'unsubscribed' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => $lead_id ),
                array( '%d', '%s', '%s' ),
                array( '%d' )
            );

            /**
             * Fires when a lead unsubscribes. Connectors may hook this to
             * withdraw the contact at their end too.
             *
             * @param int $lead_id
             */
            do_action( 'asq_lead_unsubscribed', $lead_id );

            wp_die(
                esc_html__( "You're unsubscribed. We won't email you about the skin quiz again.", 'apotheca-skin-quiz' ),
                esc_html__( 'Unsubscribed', 'apotheca-skin-quiz' ),
                array( 'response' => 200 )
            );
        }

        wp_die(
            esc_html__( 'That unsubscribe link is not valid. Please use the link in a recent email.', 'apotheca-skin-quiz' ),
            esc_html__( 'Unsubscribe', 'apotheca-skin-quiz' ),
            array( 'response' => 200 )
        );
    }

    /* ────────── Medical gate (count only, no person data) ────────── */

    public static function record_gate( $finder_id ) {
        $finder_id = absint( $finder_id );
        $events    = get_option( 'asq_gate_events', array() );
        if ( ! is_array( $events ) ) {
            $events = array();
        }
        if ( ! isset( $events[ $finder_id ] ) || ! is_array( $events[ $finder_id ] ) ) {
            $events[ $finder_id ] = array( 'count' => 0, 'last' => '' );
        }
        $events[ $finder_id ]['count'] = (int) $events[ $finder_id ]['count'] + 1;
        $events[ $finder_id ]['last']  = current_time( 'mysql' );
        update_option( 'asq_gate_events', $events, false );

        /**
         * Fires when the gate trips. Only the quiz id, never the person's data.
         *
         * @param int $finder_id
         */
        do_action( 'asq_gate_fired', $finder_id );
    }

    /* ────────── Text helpers ────────── */

    public static function answers_to_text( $answers_json ) {
        $rows = is_array( $answers_json ) ? $answers_json : json_decode( (string) $answers_json, true );
        if ( ! is_array( $rows ) ) {
            return '';
        }
        $out = array();
        foreach ( $rows as $row ) {
            $q = $row['question'] ?? '';
            $a = implode( ', ', (array) ( $row['answers'] ?? array() ) );
            $out[] = $q . ': ' . $a;
        }
        return implode( ' | ', $out );
    }

    public static function findings_to_text( $findings_json ) {
        $rows = is_array( $findings_json ) ? $findings_json : json_decode( (string) $findings_json, true );
        if ( ! is_array( $rows ) ) {
            return '';
        }
        $out = array();
        foreach ( $rows as $row ) {
            $id    = isset( $row['id'] ) ? $row['id'] : '';
            $label = isset( $row['label'] ) ? $row['label'] : '';
            $out[] = trim( $id . ' ' . $label );
        }
        return implode( ', ', $out );
    }

    /* ────────── Admin screen ────────── */

    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=apotheca_skin_quiz',
            __( 'Submissions', 'apotheca-skin-quiz' ),
            __( 'Submissions', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-submissions',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to view submissions.', 'apotheca-skin-quiz' ) );
        }

        global $wpdb;
        $subs = self::submissions_table();

        // Single-row delete.
        if ( isset( $_GET['asq_action'], $_GET['sub'] ) && 'delete' === $_GET['asq_action'] ) {
            $sub_id = absint( $_GET['sub'] );
            check_admin_referer( 'asq_delete_sub_' . $sub_id );
            $wpdb->delete( $subs, array( 'id' => $sub_id ), array( '%d' ) );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Submission deleted.', 'apotheca-skin-quiz' ) . '</p></div>';
        }

        $finder_filter = absint( $_GET['finder'] ?? 0 );
        $paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $offset        = ( $paged - 1 ) * self::PER_PAGE;

        $where = $finder_filter ? $wpdb->prepare( 'WHERE finder_id = %d', $finder_filter ) : '';
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$subs} {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL
            self::PER_PAGE,
            $offset
        ) );

        $finders = get_posts( array(
            'post_type'      => 'apotheca_skin_quiz',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $export_url = wp_nonce_url( add_query_arg( array(
            'action' => 'asq_export_leads',
            'finder' => $finder_filter,
        ), admin_url( 'admin-post.php' ) ), 'asq_export_leads' );

        $base_url = add_query_arg( array(
            'post_type' => 'apotheca_skin_quiz',
            'page'      => 'asq-submissions',
            'finder'    => $finder_filter ?: false,
        ), admin_url( 'edit.php' ) );

        $total_pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Apotheca Skin Quiz Submissions', 'apotheca-skin-quiz' ); ?></h1>
            <?php if ( $total ) : ?>
                <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'apotheca-skin-quiz' ); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">

            <form method="get" style="margin:12px 0;">
                <input type="hidden" name="post_type" value="apotheca_skin_quiz">
                <input type="hidden" name="page" value="asq-submissions">
                <select name="finder">
                    <option value="0"><?php esc_html_e( 'All quizzes', 'apotheca-skin-quiz' ); ?></option>
                    <?php foreach ( $finders as $finder ) : ?>
                        <option value="<?php echo esc_attr( $finder->ID ); ?>" <?php selected( $finder_filter, $finder->ID ); ?>>
                            <?php echo esc_html( $finder->post_title ?: __( '(no title)', 'apotheca-skin-quiz' ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'apotheca-skin-quiz' ); ?></button>
                <span style="margin-left:8px;color:#646970;">
                    <?php printf( esc_html( _n( '%s submission', '%s submissions', $total, 'apotheca-skin-quiz' ) ), esc_html( number_format_i18n( $total ) ) ); ?>
                </span>
            </form>

            <?php if ( ! $rows ) : ?>
                <p><?php esc_html_e( 'No submissions yet. When a visitor completes the quiz and enters their email, it will appear here.', 'apotheca-skin-quiz' ); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Consent', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Findings', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Answers', 'apotheca-skin-quiz' ); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $rows as $row ) : ?>
                            <?php
                            $answers_rows = json_decode( (string) $row->answers, true );
                            $findings     = self::findings_to_text( $row->findings );
                            $delete_url   = wp_nonce_url( add_query_arg( array(
                                'asq_action' => 'delete',
                                'sub'        => $row->id,
                            ), $base_url ), 'asq_delete_sub_' . $row->id );
                            ?>
                            <tr>
                                <td style="white-space:nowrap;"><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->created_at ) ); ?></td>
                                <td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
                                <td><?php echo $row->consent ? '<span style="color:#00a32a;">&#10003; ' . esc_html__( 'Yes', 'apotheca-skin-quiz' ) . '</span>' : esc_html__( 'No', 'apotheca-skin-quiz' ); ?></td>
                                <td><?php echo $findings ? esc_html( $findings ) : '&mdash;'; ?></td>
                                <td>
                                    <?php if ( is_array( $answers_rows ) && $answers_rows ) : ?>
                                        <details>
                                            <summary style="cursor:pointer;"><?php printf( esc_html( _n( '%s answer', '%s answers', count( $answers_rows ), 'apotheca-skin-quiz' ) ), esc_html( number_format_i18n( count( $answers_rows ) ) ) ); ?></summary>
                                            <ul style="margin:8px 0 0;">
                                                <?php foreach ( $answers_rows as $arow ) : ?>
                                                    <li><strong><?php echo esc_html( $arow['question'] ?? '' ); ?></strong><br><?php echo esc_html( implode( ', ', (array) ( $arow['answers'] ?? array() ) ) ); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else : ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td><a href="<?php echo esc_url( $delete_url ); ?>" style="color:#b32d2e;" onclick="return confirm('<?php echo esc_js( __( 'Delete this submission?', 'apotheca-skin-quiz' ) ); ?>');"><?php esc_html_e( 'Delete', 'apotheca-skin-quiz' ); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom"><div class="tablenav-pages">
                        <?php echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput
                            'base'    => add_query_arg( 'paged', '%#%', $base_url ),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $total_pages,
                        ) ); ?>
                    </div></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ────────── CSV export ────────── */

    public function export_csv() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to export submissions.', 'apotheca-skin-quiz' ) );
        }
        check_admin_referer( 'asq_export_leads' );

        global $wpdb;
        $subs          = self::submissions_table();
        $finder_filter = absint( $_GET['finder'] ?? 0 );
        $where         = $finder_filter ? $wpdb->prepare( 'WHERE finder_id = %d', $finder_filter ) : '';
        $rows          = $wpdb->get_results( "SELECT * FROM {$subs} {$where} ORDER BY created_at DESC, id DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL

        $filename = 'apotheca-skin-quiz-submissions-' . gmdate( 'Y-m-d' ) . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'ID', 'Lead ID', 'Date', 'Quiz', 'Email', 'Consent', 'Consent wording', 'Source', 'Findings', 'Answers' ) );

        foreach ( $rows as $row ) {
            fputcsv( $out, array(
                $row->id,
                $row->lead_id,
                $row->created_at,
                get_the_title( $row->finder_id ) ?: '#' . $row->finder_id,
                $row->email,
                $row->consent ? 'yes' : 'no',
                $row->consent_text,
                $row->source,
                self::findings_to_text( $row->findings ),
                self::answers_to_text( $row->answers ),
            ) );
        }

        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
        exit;
    }
}

new ASQ_Leads();

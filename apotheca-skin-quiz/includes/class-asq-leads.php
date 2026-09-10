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

    /* ────────── Funnel (anonymous drop-off counts, no PII) ────────── */

    /**
     * Record that an anonymous visitor reached a question, or completed the
     * quiz. This is the data behind the Question performance screen: it never
     * stores a person, only per-day counts of how many sessions reached each
     * question index and how many finished.
     *
     * Each session increments a given question index at most once (the front
     * end guards it), so reached[idx] reads directly as "sessions that got at
     * least this far", and the gap to the next index is the drop-off.
     *
     * The counts live in one non-autoloaded option, pruned to the last ~400
     * days so it can never grow without bound. Like the gate counter above,
     * this is a lightweight aggregate: under heavy concurrency a rare
     * increment may be lost, which is fine for a funnel read.
     *
     * @param int    $finder_id
     * @param string $event 'reach' or 'complete'.
     * @param int    $q     Question index (ignored for 'complete').
     */
    public static function record_progress( $finder_id, $event, $q = 0 ) {
        $finder_id = absint( $finder_id );
        if ( ! $finder_id ) {
            return;
        }

        $funnel = get_option( 'asq_funnel', array() );
        if ( ! is_array( $funnel ) ) {
            $funnel = array();
        }

        $date = current_time( 'Y-m-d' );
        if ( empty( $funnel[ $finder_id ] ) || ! is_array( $funnel[ $finder_id ] ) ) {
            $funnel[ $finder_id ] = array();
        }
        if ( empty( $funnel[ $finder_id ][ $date ] ) || ! is_array( $funnel[ $finder_id ][ $date ] ) ) {
            $funnel[ $finder_id ][ $date ] = array( 'r' => array(), 'c' => 0 );
        }

        if ( 'complete' === $event ) {
            $funnel[ $finder_id ][ $date ]['c'] = (int) ( $funnel[ $finder_id ][ $date ]['c'] ?? 0 ) + 1;
        } else {
            $q = max( 0, (int) $q );
            $funnel[ $finder_id ][ $date ]['r'][ $q ] = (int) ( $funnel[ $finder_id ][ $date ]['r'][ $q ] ?? 0 ) + 1;
        }

        update_option( 'asq_funnel', self::prune_funnel( $funnel ), false );
    }

    /** Drop day-buckets older than ~400 days across every quiz. */
    protected static function prune_funnel( $funnel ) {
        $cutoff = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -400 days' ) );
        foreach ( $funnel as $fid => $dates ) {
            if ( ! is_array( $dates ) ) {
                unset( $funnel[ $fid ] );
                continue;
            }
            foreach ( $dates as $d => $v ) {
                if ( (string) $d < $cutoff ) {
                    unset( $funnel[ $fid ][ $d ] );
                }
            }
            if ( empty( $funnel[ $fid ] ) ) {
                unset( $funnel[ $fid ] );
            }
        }
        return $funnel;
    }

    /**
     * Sum the funnel for a quiz (0 = all quizzes) since a date (empty = all
     * time).
     *
     * @return array [ 'reached' => [ idx => count ], 'completed' => int ]
     */
    public static function funnel_totals( $finder_id = 0, $since_date = '' ) {
        $funnel = get_option( 'asq_funnel', array() );
        if ( ! is_array( $funnel ) ) {
            $funnel = array();
        }

        $finder_id = absint( $finder_id );
        $reached   = array();
        $completed = 0;

        foreach ( $funnel as $fid => $dates ) {
            if ( $finder_id && (int) $fid !== $finder_id ) {
                continue;
            }
            if ( ! is_array( $dates ) ) {
                continue;
            }
            foreach ( $dates as $d => $v ) {
                if ( $since_date && (string) $d < $since_date ) {
                    continue;
                }
                if ( ! empty( $v['r'] ) && is_array( $v['r'] ) ) {
                    foreach ( $v['r'] as $idx => $n ) {
                        $idx             = (int) $idx;
                        $reached[ $idx ] = ( $reached[ $idx ] ?? 0 ) + (int) $n;
                    }
                }
                $completed += (int) ( $v['c'] ?? 0 );
            }
        }

        ksort( $reached );
        return array( 'reached' => $reached, 'completed' => $completed );
    }

    /* ────────── Deletion (cascades, no orphan rows) ────────── */

    /**
     * All lead ids for an email (normally one, but defensive against dupes).
     */
    public static function get_lead_ids_by_email( $email ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! $email ) {
            return array();
        }
        return array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare(
            'SELECT id FROM ' . self::leads_table() . ' WHERE email = %s', // phpcs:ignore WordPress.DB.PreparedSQL
            $email
        ) ) );
    }

    /**
     * Every submission joined to a lead, oldest first.
     */
    public static function get_submissions_for_lead( $lead_id ) {
        global $wpdb;
        $lead_id = absint( $lead_id );
        if ( ! $lead_id ) {
            return array();
        }
        return $wpdb->get_results( $wpdb->prepare(
            'SELECT * FROM ' . self::submissions_table() . ' WHERE lead_id = %d ORDER BY created_at ASC, id ASC', // phpcs:ignore WordPress.DB.PreparedSQL
            $lead_id
        ), ARRAY_A );
    }

    /**
     * Delete a lead and every quiz response joined to it, in one operation.
     * The responses go first, then the person, so nothing is left orphaned.
     *
     * @return int Total rows removed (responses + the lead).
     */
    public static function delete_lead( $lead_id ) {
        global $wpdb;
        $lead_id = absint( $lead_id );
        if ( ! $lead_id ) {
            return 0;
        }
        $removed  = (int) $wpdb->delete( self::submissions_table(), array( 'lead_id' => $lead_id ), array( '%d' ) );
        $removed += (int) $wpdb->delete( self::leads_table(), array( 'id' => $lead_id ), array( '%d' ) );

        /**
         * Fires after a lead and all their responses are deleted.
         *
         * @param int $lead_id
         */
        do_action( 'asq_lead_deleted', $lead_id );

        return $removed;
    }

    /**
     * Delete every lead for an email plus all their responses. Also sweeps any
     * response rows recorded under that email with no surviving lead.
     *
     * @return int Total rows removed.
     */
    public static function delete_by_email( $email ) {
        global $wpdb;
        $email = sanitize_email( $email );
        if ( ! $email ) {
            return 0;
        }

        $removed = 0;
        foreach ( self::get_lead_ids_by_email( $email ) as $lead_id ) {
            $removed += self::delete_lead( $lead_id );
        }

        // Belt and braces: clear any response stored under this email whose
        // lead has already gone, so no orphan rows survive.
        $removed += (int) $wpdb->delete( self::submissions_table(), array( 'email' => $email ), array( '%s' ) );

        return $removed;
    }

    /**
     * Delete a single response. If it was the lead's last one, the now-orphaned
     * person row is removed too, so a per-response delete never leaves an
     * empty lead behind.
     */
    public static function delete_submission( $submission_id ) {
        global $wpdb;
        $submission_id = absint( $submission_id );
        if ( ! $submission_id ) {
            return false;
        }

        $lead_id = (int) $wpdb->get_var( $wpdb->prepare(
            'SELECT lead_id FROM ' . self::submissions_table() . ' WHERE id = %d', // phpcs:ignore WordPress.DB.PreparedSQL
            $submission_id
        ) );

        $wpdb->delete( self::submissions_table(), array( 'id' => $submission_id ), array( '%d' ) );

        if ( $lead_id ) {
            $remaining = (int) $wpdb->get_var( $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::submissions_table() . ' WHERE lead_id = %d', // phpcs:ignore WordPress.DB.PreparedSQL
                $lead_id
            ) );
            if ( ! $remaining ) {
                $wpdb->delete( self::leads_table(), array( 'id' => $lead_id ), array( '%d' ) );
            }
        }

        return true;
    }

    /* ────────── Text helpers ────────── */

    public static function answers_to_text( $answers_json ) {
        $rows = is_array( $answers_json ) ? $answers_json : json_decode( (string) $answers_json, true );
        if ( ! is_array( $rows ) ) {
            return '';
        }
        $out = array();
        foreach ( $rows as $row ) {
            // Wording may carry styling HTML; keep the export plain text.
            $q = wp_strip_all_tags( (string) ( $row['question'] ?? '' ) );
            $a = wp_strip_all_tags( implode( ', ', (array) ( $row['answers'] ?? array() ) ) );
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
            __( 'Responses', 'apotheca-skin-quiz' ),
            __( 'Responses', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-submissions',
            array( $this, 'render_page' )
        );
    }

    /**
     * The active filters, read from the query string and sanitised. Shared by
     * the Responses screen and the CSV export so both see the same rows.
     */
    protected static function responses_filters() {
        return array(
            'finder'  => absint( $_GET['finder'] ?? 0 ),
            'finding' => isset( $_GET['finding'] ) ? sanitize_text_field( wp_unslash( $_GET['finding'] ) ) : '',
            'from'    => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
            'to'      => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
            's'       => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
        );
    }

    /**
     * Build the WHERE clause for the responses list from the filters:
     * quiz, finding fired, date range and email search.
     */
    protected static function responses_where( $args ) {
        global $wpdb;
        $clauses = array();

        if ( ! empty( $args['finder'] ) ) {
            $clauses[] = $wpdb->prepare( 'finder_id = %d', absint( $args['finder'] ) );
        }
        if ( ! empty( $args['finding'] ) && preg_match( '/^F\d{1,2}$/', $args['finding'] ) ) {
            // Findings are stored as JSON like {"id":"F3","label":"…"}; the
            // closing quote in the pattern keeps F1 from matching F10.
            $clauses[] = $wpdb->prepare(
                'findings LIKE %s',
                '%' . $wpdb->esc_like( '"id":"' . $args['finding'] . '"' ) . '%'
            );
        }
        if ( ! empty( $args['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $args['from'] ) ) {
            $clauses[] = $wpdb->prepare( 'created_at >= %s', $args['from'] . ' 00:00:00' );
        }
        if ( ! empty( $args['to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $args['to'] ) ) {
            $clauses[] = $wpdb->prepare( 'created_at <= %s', $args['to'] . ' 23:59:59' );
        }
        if ( '' !== $args['s'] ) {
            $clauses[] = $wpdb->prepare( 'email LIKE %s', '%' . $wpdb->esc_like( $args['s'] ) . '%' );
        }

        return $clauses ? ( 'WHERE ' . implode( ' AND ', $clauses ) ) : '';
    }

    public function render_page() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to view responses.', 'apotheca-skin-quiz' ) );
        }

        global $wpdb;
        $subs = self::submissions_table();

        // Single-row delete (cascades to remove an orphaned lead).
        if ( isset( $_GET['asq_action'], $_GET['sub'] ) && 'delete' === $_GET['asq_action'] ) {
            $sub_id = absint( $_GET['sub'] );
            check_admin_referer( 'asq_delete_sub_' . $sub_id );
            self::delete_submission( $sub_id );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Response deleted.', 'apotheca-skin-quiz' ) . '</p></div>';
        }

        $filters = self::responses_filters();
        $paged   = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $offset  = ( $paged - 1 ) * self::PER_PAGE;

        $where = self::responses_where( $filters );
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

        $findings_map = ASQ_Config::findings();

        // Filters carried through export, pagination and delete links.
        $carry = array(
            'finder'  => $filters['finder'] ?: false,
            'finding' => $filters['finding'] ?: false,
            'from'    => $filters['from'] ?: false,
            'to'      => $filters['to'] ?: false,
            's'       => '' !== $filters['s'] ? $filters['s'] : false,
        );

        $export_url = wp_nonce_url( add_query_arg( array_merge(
            array( 'action' => 'asq_export_leads' ),
            $carry
        ), admin_url( 'admin-post.php' ) ), 'asq_export_leads' );

        $base_url = add_query_arg( array_merge( array(
            'post_type' => 'apotheca_skin_quiz',
            'page'      => 'asq-submissions',
        ), $carry ), admin_url( 'edit.php' ) );

        $total_pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Apotheca Skin Quiz Responses', 'apotheca-skin-quiz' ); ?></h1>
            <?php if ( $total ) : ?>
                <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'apotheca-skin-quiz' ); ?></a>
            <?php endif; ?>
            <hr class="wp-header-end">

            <form method="get" style="margin:12px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:end;">
                <input type="hidden" name="post_type" value="apotheca_skin_quiz">
                <input type="hidden" name="page" value="asq-submissions">
                <label>
                    <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'Quiz', 'apotheca-skin-quiz' ); ?></span>
                    <select name="finder">
                        <option value="0"><?php esc_html_e( 'All quizzes', 'apotheca-skin-quiz' ); ?></option>
                        <?php foreach ( $finders as $finder ) : ?>
                            <option value="<?php echo esc_attr( $finder->ID ); ?>" <?php selected( $filters['finder'], $finder->ID ); ?>>
                                <?php echo esc_html( $finder->post_title ?: __( '(no title)', 'apotheca-skin-quiz' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'Finding', 'apotheca-skin-quiz' ); ?></span>
                    <select name="finding">
                        <option value=""><?php esc_html_e( 'Any finding', 'apotheca-skin-quiz' ); ?></option>
                        <?php foreach ( $findings_map as $fid => $flabel ) : ?>
                            <?php if ( 'F11' === $fid ) { continue; } // gate is never a stored response ?>
                            <option value="<?php echo esc_attr( $fid ); ?>" <?php selected( $filters['finding'], $fid ); ?>>
                                <?php echo esc_html( $fid . ' — ' . $flabel ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'From', 'apotheca-skin-quiz' ); ?></span>
                    <input type="date" name="from" value="<?php echo esc_attr( $filters['from'] ); ?>">
                </label>
                <label>
                    <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'To', 'apotheca-skin-quiz' ); ?></span>
                    <input type="date" name="to" value="<?php echo esc_attr( $filters['to'] ); ?>">
                </label>
                <label>
                    <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'Email', 'apotheca-skin-quiz' ); ?></span>
                    <input type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" placeholder="<?php esc_attr_e( 'name@example.com', 'apotheca-skin-quiz' ); ?>">
                </label>
                <span>
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'apotheca-skin-quiz' ); ?></button>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=apotheca_skin_quiz&page=asq-submissions' ) ); ?>" class="button-link" style="margin-left:6px;"><?php esc_html_e( 'Reset', 'apotheca-skin-quiz' ); ?></a>
                </span>
            </form>

            <p style="color:#646970;margin:0 0 8px;">
                <?php printf( esc_html( _n( '%s response', '%s responses', $total, 'apotheca-skin-quiz' ) ), esc_html( number_format_i18n( $total ) ) ); ?>
            </p>

            <?php if ( ! $rows ) : ?>
                <p><?php esc_html_e( 'No responses match. When a visitor completes the quiz and enters their email, it will appear here.', 'apotheca-skin-quiz' ); ?></p>
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
                                                    <li><strong><?php echo esc_html( wp_strip_all_tags( (string) ( $arow['question'] ?? '' ) ) ); ?></strong><br><?php echo esc_html( wp_strip_all_tags( implode( ', ', (array) ( $arow['answers'] ?? array() ) ) ) ); ?></li>
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
        $subs    = self::submissions_table();
        $filters = self::responses_filters();
        $where   = self::responses_where( $filters );
        $rows    = $wpdb->get_results( "SELECT * FROM {$subs} {$where} ORDER BY created_at DESC, id DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL

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

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Analytics screens for Apotheca Skin Quiz.
 *
 * Two read-only reports, both registered as submenu pages under the existing
 * quiz menu (no new settings page):
 *
 *   • Finding frequency — which findings fire most often over a chosen period,
 *     read from the stored responses.
 *   • Question performance — how many people reach each question and where they
 *     drop off, read from the anonymous funnel counts (see ASQ_Leads).
 */
class ASQ_Reports {

    /** period key => [ label, days ] (0 days = all time). */
    const PERIODS = array(
        '7'   => 7,
        '30'  => 30,
        '90'  => 90,
        '365' => 365,
        'all' => 0,
    );

    public function __construct() {
        // Priority 15: after Responses (10), before Integrations (20).
        add_action( 'admin_menu', array( $this, 'register_menu' ), 15 );
    }

    public static function capability() {
        return ASQ_Leads::capability();
    }

    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=apotheca_skin_quiz',
            __( 'Finding frequency', 'apotheca-skin-quiz' ),
            __( 'Finding frequency', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-finding-frequency',
            array( $this, 'render_finding_frequency' )
        );

        add_submenu_page(
            'edit.php?post_type=apotheca_skin_quiz',
            __( 'Question performance', 'apotheca-skin-quiz' ),
            __( 'Question performance', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-question-performance',
            array( $this, 'render_question_performance' )
        );
    }

    /* ────────── shared bits ────────── */

    /** The chosen period key, days, and the SQL/date floor it implies. */
    protected function period_context() {
        $key = sanitize_key( $_GET['period'] ?? '30' );
        if ( ! isset( self::PERIODS[ $key ] ) ) {
            $key = '30';
        }
        $days = self::PERIODS[ $key ];

        return array(
            'key'        => $key,
            'days'       => $days,
            // Datetime floor for the responses table (created_at).
            'since_dt'   => $days ? gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) . ' -' . $days . ' days' ) ) : '',
            // Date floor for the funnel option (keyed by Y-m-d).
            'since_date' => $days ? gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -' . $days . ' days' ) ) : '',
        );
    }

    /** All quizzes, for the quiz picker. */
    protected function finders() {
        return get_posts( array(
            'post_type'      => 'apotheca_skin_quiz',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
    }

    /** The period + quiz filter form, shared by both reports. */
    protected function filter_form( $page, $finder_id, $period_key ) {
        $finders = $this->finders();
        ?>
        <form method="get" style="margin:12px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:end;">
            <input type="hidden" name="post_type" value="apotheca_skin_quiz">
            <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
            <label>
                <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'Quiz', 'apotheca-skin-quiz' ); ?></span>
                <select name="finder">
                    <option value="0"><?php esc_html_e( 'All quizzes', 'apotheca-skin-quiz' ); ?></option>
                    <?php foreach ( $finders as $finder ) : ?>
                        <option value="<?php echo esc_attr( $finder->ID ); ?>" <?php selected( $finder_id, $finder->ID ); ?>>
                            <?php echo esc_html( $finder->post_title ?: __( '(no title)', 'apotheca-skin-quiz' ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span style="display:block;font-size:12px;color:#646970;"><?php esc_html_e( 'Period', 'apotheca-skin-quiz' ); ?></span>
                <select name="period">
                    <option value="7"   <?php selected( $period_key, '7' ); ?>><?php esc_html_e( 'Last 7 days', 'apotheca-skin-quiz' ); ?></option>
                    <option value="30"  <?php selected( $period_key, '30' ); ?>><?php esc_html_e( 'Last 30 days', 'apotheca-skin-quiz' ); ?></option>
                    <option value="90"  <?php selected( $period_key, '90' ); ?>><?php esc_html_e( 'Last 90 days', 'apotheca-skin-quiz' ); ?></option>
                    <option value="365" <?php selected( $period_key, '365' ); ?>><?php esc_html_e( 'Last 12 months', 'apotheca-skin-quiz' ); ?></option>
                    <option value="all" <?php selected( $period_key, 'all' ); ?>><?php esc_html_e( 'All time', 'apotheca-skin-quiz' ); ?></option>
                </select>
            </label>
            <span><button type="submit" class="button"><?php esc_html_e( 'Apply', 'apotheca-skin-quiz' ); ?></button></span>
        </form>
        <?php
    }

    /** A slim inline bar. */
    protected function bar( $pct, $color = '#2c3338' ) {
        $pct = max( 0, min( 100, (float) $pct ) );
        return '<span style="display:inline-block;width:160px;height:10px;background:#e5e7eb;border-radius:5px;vertical-align:middle;overflow:hidden;">'
            . '<span style="display:block;height:100%;width:' . esc_attr( $pct ) . '%;background:' . esc_attr( $color ) . ';"></span></span>';
    }

    /* ────────── Finding frequency ────────── */

    public function render_finding_frequency() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to view this report.', 'apotheca-skin-quiz' ) );
        }

        global $wpdb;
        $subs      = ASQ_Leads::submissions_table();
        $finder_id = absint( $_GET['finder'] ?? 0 );
        $period    = $this->period_context();

        $clauses = array();
        if ( $finder_id ) {
            $clauses[] = $wpdb->prepare( 'finder_id = %d', $finder_id );
        }
        if ( $period['since_dt'] ) {
            $clauses[] = $wpdb->prepare( 'created_at >= %s', $period['since_dt'] );
        }
        $where = $clauses ? ( 'WHERE ' . implode( ' AND ', $clauses ) ) : '';

        $rows  = $wpdb->get_col( "SELECT findings FROM {$subs} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL
        $total = count( $rows );

        // Tally each finding id across every response in the period.
        $counts = array();
        foreach ( $rows as $json ) {
            $decoded = json_decode( (string) $json, true );
            if ( ! is_array( $decoded ) ) {
                continue;
            }
            $seen = array();
            foreach ( $decoded as $f ) {
                $id = isset( $f['id'] ) ? $f['id'] : '';
                if ( '' === $id || isset( $seen[ $id ] ) ) {
                    continue; // count each finding once per response
                }
                $seen[ $id ]   = true;
                $counts[ $id ] = ( $counts[ $id ] ?? 0 ) + 1;
            }
        }
        arsort( $counts );
        $top = $total ? max( $counts ?: array( 0 ) ) : 0;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Finding frequency', 'apotheca-skin-quiz' ); ?></h1>
            <p class="description"><?php esc_html_e( 'How often each finding fired across responses in the chosen period. Each finding is counted once per response.', 'apotheca-skin-quiz' ); ?></p>

            <?php $this->filter_form( 'asq-finding-frequency', $finder_id, $period['key'] ); ?>

            <p style="color:#646970;">
                <?php printf( esc_html( _n( '%s response in this period.', '%s responses in this period.', $total, 'apotheca-skin-quiz' ) ), esc_html( number_format_i18n( $total ) ) ); ?>
            </p>

            <?php if ( ! $total ) : ?>
                <p><?php esc_html_e( 'No responses yet for this period.', 'apotheca-skin-quiz' ); ?></p>
            <?php else : ?>
                <table class="widefat striped" style="max-width:760px;">
                    <thead>
                        <tr>
                            <th style="width:70px;"><?php esc_html_e( 'ID', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Finding', 'apotheca-skin-quiz' ); ?></th>
                            <th style="width:200px;"><?php esc_html_e( 'Times fired', 'apotheca-skin-quiz' ); ?></th>
                            <th style="width:110px;"><?php esc_html_e( 'Of responses', 'apotheca-skin-quiz' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $counts as $id => $n ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $id ); ?></code></td>
                                <td><?php echo esc_html( ASQ_Config::finding_label( $id ) ); ?></td>
                                <td>
                                    <?php echo $this->bar( $top ? ( $n / $top ) * 100 : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    <span style="margin-left:8px;"><?php echo esc_html( number_format_i18n( $n ) ); ?></span>
                                </td>
                                <td><?php echo esc_html( $total ? round( ( $n / $total ) * 100 ) . '%' : '0%' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /* ────────── Question performance (drop-off) ────────── */

    public function render_question_performance() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to view this report.', 'apotheca-skin-quiz' ) );
        }

        $finder_id = absint( $_GET['finder'] ?? 0 );
        $period    = $this->period_context();

        $questions = ASQ_Config::questions();
        $funnel    = ASQ_Leads::funnel_totals( $finder_id, $period['since_date'] );
        $reached   = $funnel['reached'];
        $completed = (int) $funnel['completed'];
        $started   = (int) ( $reached[0] ?? 0 );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Question performance', 'apotheca-skin-quiz' ); ?></h1>
            <p class="description"><?php esc_html_e( 'How many people reach each question and where they leave. A big drop on one row means that screen is losing people. Counts are anonymous; no personal data is recorded to build this.', 'apotheca-skin-quiz' ); ?></p>

            <?php $this->filter_form( 'asq-question-performance', $finder_id, $period['key'] ); ?>

            <?php if ( ! $started ) : ?>
                <p><?php esc_html_e( 'No quiz starts recorded yet for this period.', 'apotheca-skin-quiz' ); ?></p>
            <?php else : ?>
                <p style="color:#646970;">
                    <?php
                    printf(
                        /* translators: 1: starts, 2: completions, 3: completion rate */
                        esc_html__( '%1$s started, %2$s reached the result (%3$s completion).', 'apotheca-skin-quiz' ),
                        esc_html( number_format_i18n( $started ) ),
                        esc_html( number_format_i18n( $completed ) ),
                        esc_html( $started ? round( ( $completed / $started ) * 100 ) . '%' : '0%' )
                    );
                    ?>
                </p>

                <table class="widefat striped" style="max-width:900px;">
                    <thead>
                        <tr>
                            <th style="width:60px;"><?php esc_html_e( 'Step', 'apotheca-skin-quiz' ); ?></th>
                            <th><?php esc_html_e( 'Question', 'apotheca-skin-quiz' ); ?></th>
                            <th style="width:200px;"><?php esc_html_e( 'Reached', 'apotheca-skin-quiz' ); ?></th>
                            <th style="width:90px;"><?php esc_html_e( 'Retained', 'apotheca-skin-quiz' ); ?></th>
                            <th style="width:130px;"><?php esc_html_e( 'Drop-off', 'apotheca-skin-quiz' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = count( $questions );
                        for ( $i = 0; $i < $count; $i++ ) :
                            $here    = (int) ( $reached[ $i ] ?? 0 );
                            $next    = ( $i + 1 < $count ) ? (int) ( $reached[ $i + 1 ] ?? 0 ) : $completed;
                            $drop    = max( 0, $here - $next );
                            $droppct = $here ? round( ( $drop / $here ) * 100 ) : 0;
                            $retain  = $started ? ( $here / $started ) * 100 : 0;
                            // Flag the worst leaks.
                            $hot     = $droppct >= 25;
                            $qtext   = isset( $questions[ $i ]['text'] ) ? $questions[ $i ]['text'] : sprintf( __( 'Question %d', 'apotheca-skin-quiz' ), $i + 1 );
                            ?>
                            <tr>
                                <td>Q<?php echo esc_html( $i + 1 ); ?></td>
                                <td><?php echo esc_html( $qtext ); ?></td>
                                <td>
                                    <?php echo $this->bar( $retain ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    <span style="margin-left:8px;"><?php echo esc_html( number_format_i18n( $here ) ); ?></span>
                                </td>
                                <td><?php echo esc_html( round( $retain ) . '%' ); ?></td>
                                <td<?php echo $hot ? ' style="color:#b32d2e;font-weight:600;"' : ''; ?>>
                                    <?php echo esc_html( $drop ? number_format_i18n( $drop ) . ' (' . $droppct . '%)' : '—' ); ?>
                                </td>
                            </tr>
                        <?php endfor; ?>
                        <tr style="background:#f0f6fc;">
                            <td><span class="dashicons dashicons-yes" style="color:#00a32a;"></span></td>
                            <td><strong><?php esc_html_e( 'Result shown', 'apotheca-skin-quiz' ); ?></strong></td>
                            <td>
                                <?php echo $this->bar( $started ? ( $completed / $started ) * 100 : 0, '#00a32a' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                <span style="margin-left:8px;"><?php echo esc_html( number_format_i18n( $completed ) ); ?></span>
                            </td>
                            <td><?php echo esc_html( $started ? round( ( $completed / $started ) * 100 ) . '%' : '0%' ); ?></td>
                            <td>—</td>
                        </tr>
                    </tbody>
                </table>

                <p class="description" style="margin-top:10px;">
                    <?php esc_html_e( 'Rows highlighted in red lose a quarter or more of the people who reached them.', 'apotheca-skin-quiz' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}

new ASQ_Reports();

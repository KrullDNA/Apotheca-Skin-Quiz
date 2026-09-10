<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * "Result wording" admin screen.
 *
 * Lets the whole reading be reworded without touching code: the four section
 * headings, the read-next lines, the medical-gate copy, and each finding's
 * paragraphs. Edits are stored as overrides (see ASQ_Presenter) on top of the
 * code defaults in asq-phrasing.php; a blank field falls back to the default.
 *
 * The reading uses small tokens that the plugin fills in, and they must be left
 * in place when rewording around them:
 *   {a:Q2} / {al:Q2}      the person's own answer, woven into the sentence
 *   {em}…{/em}            an emphasised phrase, shown in the accent colour
 *   {decoder}…{/decoder}  the Ingredient List Decoder link (finding F12 only)
 */
class ASQ_Result_Wording {

    public function __construct() {
        // Priority 16: after the reports (15), before Integrations (20).
        add_action( 'admin_menu', array( $this, 'register_menu' ), 16 );
    }

    public static function capability() {
        return ASQ_Leads::capability();
    }

    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=apotheca_skin_quiz',
            __( 'Result wording', 'apotheca-skin-quiz' ),
            __( 'Result wording', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-result-wording',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to edit the result wording.', 'apotheca-skin-quiz' ) );
        }

        // Save.
        if ( ! empty( $_POST['asq_result_save'] ) ) {
            check_admin_referer( 'asq_result_wording' );
            $raw = isset( $_POST['asq_result'] ) ? wp_unslash( $_POST['asq_result'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised field-by-field in save_phrasing_overrides()
            ASQ_Presenter::save_phrasing_overrides( (array) $raw );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Result wording saved.', 'apotheca-skin-quiz' ) . '</p></div>';
        }

        $eff = ASQ_Presenter::phrasing();          // defaults with overrides applied
        $def = ASQ_Presenter::phrasing_defaults();  // raw code defaults, for placeholders
        $labels = ASQ_Config::findings();

        $token_hint = __( 'Tokens the plugin fills in: {a:Q2} their answer, {em}…{/em} emphasis. Leave them as they are when you reword.', 'apotheca-skin-quiz' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Result wording', 'apotheca-skin-quiz' ); ?></h1>
            <p class="description" style="max-width:820px;">
                <?php esc_html_e( 'Edit any part of the results. This is shared across every quiz. Leave a field blank to use the built-in wording shown as its placeholder.', 'apotheca-skin-quiz' ); ?>
            </p>
            <p class="description" style="max-width:820px;margin-bottom:16px;">
                <strong><?php esc_html_e( 'Keep the tokens.', 'apotheca-skin-quiz' ); ?></strong>
                <?php esc_html_e( 'Some sentences contain markers in curly brackets that the plugin swaps for real content: {a:Q2} (and {al:Q2}) becomes the person\'s own answer, {em}…{/em} marks an emphasised phrase, and {decoder}…{/decoder} is the Ingredient List Decoder link. Reword around them, but leave the markers themselves in place.', 'apotheca-skin-quiz' ); ?>
            </p>

            <form method="post">
                <?php wp_nonce_field( 'asq_result_wording' ); ?>
                <input type="hidden" name="asq_result_save" value="1">

                <h2><?php esc_html_e( 'Section headings', 'apotheca-skin-quiz' ); ?></h2>
                <table class="form-table" role="presentation"><tbody>
                    <?php
                    $section_labels = array(
                        'describing'   => __( 'What you\'re describing', 'apotheca-skin-quiz' ),
                        'probably_not' => __( 'What it probably isn\'t', 'apotheca-skin-quiz' ),
                        'worth_trying' => __( 'One or two things worth trying', 'apotheca-skin-quiz' ),
                        'read_next'    => __( 'Read next', 'apotheca-skin-quiz' ),
                    );
                    foreach ( $section_labels as $key => $desc ) :
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $desc ); ?></th>
                            <td>
                                <input type="text" class="large-text" name="asq_result[sections][<?php echo esc_attr( $key ); ?>]"
                                    value="<?php echo esc_attr( $eff['sections'][ $key ] ?? '' ); ?>"
                                    placeholder="<?php echo esc_attr( $def['sections'][ $key ] ?? '' ); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Read-next intro line', 'apotheca-skin-quiz' ); ?></th>
                        <td><input type="text" class="large-text" name="asq_result[read_next_intro]" value="<?php echo esc_attr( $eff['read_next_intro'] ?? '' ); ?>" placeholder="<?php echo esc_attr( $def['read_next_intro'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( '"Read more" link label', 'apotheca-skin-quiz' ); ?></th>
                        <td><input type="text" class="regular-text" name="asq_result[read_more]" value="<?php echo esc_attr( $eff['read_more'] ?? '' ); ?>" placeholder="<?php echo esc_attr( $def['read_more'] ?? '' ); ?>"></td>
                    </tr>
                </tbody></table>

                <h2><?php esc_html_e( 'The results copy', 'apotheca-skin-quiz' ); ?></h2>
                <p class="description"><?php echo esc_html( $token_hint ); ?></p>

                <?php
                $fp_eff = isset( $eff['findings'] ) ? $eff['findings'] : array();
                $fp_def = isset( $def['findings'] ) ? $def['findings'] : array();
                // Show findings in their configured order, skipping the gate (F11).
                foreach ( $labels as $fid => $label ) :
                    if ( 'F11' === $fid || ! isset( $fp_def[ $fid ] ) ) {
                        continue;
                    }
                    $fe = $fp_eff[ $fid ] ?? array();
                    $fd = $fp_def[ $fid ] ?? array();
                    ?>
                    <fieldset class="asq-dn-fieldset" style="margin:0 0 16px;">
                        <legend><strong><?php echo esc_html( $fid ); ?></strong> &middot; <?php echo esc_html( $label ); ?></legend>

                        <p style="margin:6px 0 2px;font-weight:600;"><?php esc_html_e( 'What you\'re describing', 'apotheca-skin-quiz' ); ?></p>
                        <textarea class="large-text" rows="3" name="asq_result[findings][<?php echo esc_attr( $fid ); ?>][describing]" placeholder="<?php echo esc_attr( $fd['describing'] ?? '' ); ?>"><?php echo esc_textarea( $fe['describing'] ?? '' ); ?></textarea>

                        <p style="margin:10px 0 2px;font-weight:600;"><?php esc_html_e( 'What it probably isn\'t', 'apotheca-skin-quiz' ); ?> <span class="description" style="font-weight:400;">(<?php esc_html_e( 'optional; leave blank to omit this line for this result', 'apotheca-skin-quiz' ); ?>)</span></p>
                        <textarea class="large-text" rows="3" name="asq_result[findings][<?php echo esc_attr( $fid ); ?>][probably_not]" placeholder="<?php echo esc_attr( $fd['probably_not'] ?? '' ); ?>"><?php echo esc_textarea( $fe['probably_not'] ?? '' ); ?></textarea>

                        <p style="margin:10px 0 2px;font-weight:600;"><?php esc_html_e( 'One or two things worth trying', 'apotheca-skin-quiz' ); ?><?php if ( 'F12' === $fid ) : ?> <span class="description" style="font-weight:400;">(<?php esc_html_e( 'keep {decoder}…{/decoder} for the Decoder link', 'apotheca-skin-quiz' ); ?>)</span><?php endif; ?></p>
                        <textarea class="large-text" rows="3" name="asq_result[findings][<?php echo esc_attr( $fid ); ?>][worth_trying]" placeholder="<?php echo esc_attr( $fd['worth_trying']['text'] ?? '' ); ?>"><?php echo esc_textarea( $fe['worth_trying']['text'] ?? '' ); ?></textarea>
                    </fieldset>
                <?php endforeach; ?>

                <h2><?php esc_html_e( 'Medical gate response', 'apotheca-skin-quiz' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Shown instead of the results when an answer trips the safety gate. In the on-page body you can use {ticked}, which becomes exactly what she flagged, e.g. "a mole or mark that has changed".', 'apotheca-skin-quiz' ); ?></p>
                <table class="form-table" role="presentation"><tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Heading', 'apotheca-skin-quiz' ); ?></th>
                        <td><input type="text" class="large-text" name="asq_result[gate][heading]" value="<?php echo esc_attr( $eff['gate']['heading'] ?? '' ); ?>" placeholder="<?php echo esc_attr( $def['gate']['heading'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'On-page body', 'apotheca-skin-quiz' ); ?></th>
                        <td><textarea class="large-text" rows="4" name="asq_result[gate][body]" placeholder="<?php echo esc_attr( $def['gate']['body'] ?? '' ); ?>"><?php echo esc_textarea( $eff['gate']['body'] ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Email acknowledgement', 'apotheca-skin-quiz' ); ?></th>
                        <td><textarea class="large-text" rows="4" name="asq_result[gate][email_ack]" placeholder="<?php echo esc_attr( $def['gate']['email_ack'] ?? '' ); ?>"><?php echo esc_textarea( $eff['gate']['email_ack'] ?? '' ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Read-next intro (gate)', 'apotheca-skin-quiz' ); ?></th>
                        <td><input type="text" class="large-text" name="asq_result[gate][read_next_intro]" value="<?php echo esc_attr( $eff['gate']['read_next_intro'] ?? '' ); ?>" placeholder="<?php echo esc_attr( $def['gate']['read_next_intro'] ?? '' ); ?>"></td>
                    </tr>
                </tbody></table>

                <?php submit_button( __( 'Save result wording', 'apotheca-skin-quiz' ) ); ?>
            </form>
        </div>
        <?php
    }
}

new ASQ_Result_Wording();

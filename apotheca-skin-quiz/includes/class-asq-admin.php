<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin meta boxes and save logic for Apotheca Skin Quiz.
 */
class ASQ_Admin {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_apotheca_skin_quiz', array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'post_row_actions', array( $this, 'add_duplicate_link' ), 10, 2 );
        add_action( 'admin_action_asq_duplicate_finder', array( $this, 'duplicate_finder' ) );
    }

    /* ───────────────────────── Duplicate finder ─────────────── */

    /**
     * Add a "Duplicate" link to each row in the finders list.
     */
    public function add_duplicate_link( $actions, $post ) {
        if ( 'apotheca_skin_quiz' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'asq_duplicate_finder',
                    'post'   => $post->ID,
                ),
                admin_url( 'admin.php' )
            ),
            'asq_duplicate_finder_' . $post->ID
        );

        $actions['asq_duplicate'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'apotheca-skin-quiz' ) . '</a>';
        return $actions;
    }

    /**
     * Clone a finder – title, status draft, and every meta row (questions,
     * options, Day/Night styles, email styles) – then open the copy.
     */
    public function duplicate_finder() {
        $post_id = absint( $_GET['post'] ?? 0 );
        check_admin_referer( 'asq_duplicate_finder_' . $post_id );

        $post = get_post( $post_id );
        if ( ! $post || 'apotheca_skin_quiz' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( esc_html__( 'You are not allowed to duplicate this finder.', 'apotheca-skin-quiz' ) );
        }

        $new_id = wp_insert_post( array(
            'post_type'   => 'apotheca_skin_quiz',
            'post_status' => 'draft',
            /* translators: %s: original finder title */
            'post_title'  => sprintf( __( '%s (Copy)', 'apotheca-skin-quiz' ), $post->post_title ),
        ), true );

        if ( is_wp_error( $new_id ) ) {
            wp_die( esc_html( $new_id->get_error_message() ) );
        }

        foreach ( get_post_meta( $post_id ) as $key => $values ) {
            if ( in_array( $key, array( '_edit_lock', '_edit_last' ), true ) ) {
                continue;
            }
            foreach ( $values as $value ) {
                add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
            }
        }

        wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
        exit;
    }

    /* ───────────────────────── Assets ───────────────────────── */

    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || 'apotheca_skin_quiz' !== $screen->post_type ) {
            return;
        }

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'asq-admin',
            ASQ_PLUGIN_URL . 'admin/css/asq-admin.css',
            array(),
            ASQ_VERSION
        );

        wp_enqueue_script(
            'asq-admin',
            ASQ_PLUGIN_URL . 'admin/js/asq-admin.js',
            array( 'jquery', 'jquery-ui-sortable', 'wp-util' ),
            ASQ_VERSION,
            true
        );

        wp_localize_script( 'asq-admin', 'asqAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'asq_admin_nonce' ),
            'i18n'     => array(
                'select_image' => __( 'Select Image', 'apotheca-skin-quiz' ),
                'remove_image' => __( 'Remove', 'apotheca-skin-quiz' ),
                'use_image'    => __( 'Use this image', 'apotheca-skin-quiz' ),
                'confirm_del'  => __( 'Delete this item?', 'apotheca-skin-quiz' ),
            ),
        ) );
    }

    /* ───────────────────────── Meta Boxes ───────────────────── */

    public function add_meta_boxes() {
        add_meta_box(
            'asq_questions',
            __( 'Quiz Questions', 'apotheca-skin-quiz' ),
            array( $this, 'render_questions_box' ),
            'apotheca_skin_quiz',
            'normal',
            'high'
        );

        add_meta_box(
            'asq_options',
            __( 'Finder Options', 'apotheca-skin-quiz' ),
            array( $this, 'render_options_box' ),
            'apotheca_skin_quiz',
            'side',
            'default'
        );

        add_meta_box(
            'asq_shortcode',
            __( 'Shortcode', 'apotheca-skin-quiz' ),
            array( $this, 'render_shortcode_box' ),
            'apotheca_skin_quiz',
            'side',
            'default'
        );

        add_meta_box(
            'asq_email_styles',
            __( 'Email Styling', 'apotheca-skin-quiz' ),
            array( $this, 'render_email_styles_box' ),
            'apotheca_skin_quiz',
            'normal',
            'default'
        );
    }

    /* ─── Shortcode box ─── */

    public function render_shortcode_box( $post ) {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p>' . esc_html__( 'Publish or save a draft first to get the shortcode.', 'apotheca-skin-quiz' ) . '</p>';
            return;
        }
        echo '<input type="text" readonly class="widefat" value=\'[apotheca_skin_quiz id="' . esc_attr( $post->ID ) . '"]\' onclick="this.select();" />';
    }

    /* ─── Options box ─── */

    public function render_options_box( $post ) {
        $options = get_post_meta( $post->ID, '_asq_options', true );
        $options = wp_parse_args( (array) $options, array(
            'enable_consent' => 1,
            'consent_text'   => '',
            'exchange_text'  => '',
            'notify_email'   => '',
        ) );

        wp_nonce_field( 'asq_save_meta', 'asq_meta_nonce' );
        ?>
        <p>
            <label>
                <input type="checkbox" name="asq_options[enable_consent]" value="1" <?php checked( $options['enable_consent'], 1 ); ?>>
                <strong><?php esc_html_e( 'Marketing Consent Checkbox', 'apotheca-skin-quiz' ); ?></strong>
            </label>
            <span class="description"><?php esc_html_e( 'Show the required opt-in checkbox on the email gate. The choice, and the exact wording shown, is saved with each submission.', 'apotheca-skin-quiz' ); ?></span>
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Consent Label', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="text" name="asq_options[consent_text]" value="<?php echo esc_attr( $options['consent_text'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Yes, email me my result and send me skincare thinking and news from Apotheca®.', 'apotheca-skin-quiz' ); ?>">
            <span class="description"><?php esc_html_e( 'Leave blank to match the Ingredient List Decoder wording.', 'apotheca-skin-quiz' ); ?></span>
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Exchange Text', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <textarea name="asq_options[exchange_text]" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'Shown near the first question, stating the email exchange. Leave blank for the default.', 'apotheca-skin-quiz' ); ?>"><?php echo esc_textarea( $options['exchange_text'] ); ?></textarea>
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Notify on Completion', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="email" name="asq_options[notify_email]" value="<?php echo esc_attr( $options['notify_email'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. you@yourshop.com', 'apotheca-skin-quiz' ); ?>">
            <span class="description"><?php esc_html_e( 'Optional. Sends an instant lead alert to this address every time someone completes the quiz and submits their email. Leave blank to disable.', 'apotheca-skin-quiz' ); ?></span>
        </p>
        <?php
    }

    /* ─── Email Styling box ─── */

    public function render_email_styles_box( $post ) {
        $es = get_post_meta( $post->ID, '_asq_email_styles', true );
        $es = wp_parse_args( (array) $es, array(
            'logo_id'          => 0,
            'header_image_id'  => 0,
            'accent_color'     => '#000000',
            'heading'          => '',
            'sub_heading'      => '',
            'email_subject'    => '',
            'footer_text'      => '',
        ) );
        $logo_url = $es['logo_id'] ? wp_get_attachment_image_url( $es['logo_id'], 'medium' ) : '';
        $header_image_url = $es['header_image_id'] ? wp_get_attachment_image_url( $es['header_image_id'], 'medium' ) : '';
        ?>
        <div class="asq-email-styles-wrap">
            <p class="description"><?php esc_html_e( 'Customise the results email that gets sent to users. Leave fields blank to use defaults.', 'apotheca-skin-quiz' ); ?></p>

            <div class="asq-dn-grid">
                <!-- Logo -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Logo', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <input type="hidden" name="asq_email[logo_id]" value="<?php echo esc_attr( $es['logo_id'] ); ?>" class="asq-email-logo-id">
                        <div class="asq-email-logo-preview" <?php echo $logo_url ? '' : 'style="display:none;"'; ?>>
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:200px;max-height:80px;height:auto;display:block;margin-bottom:6px;border:1px solid #dcdcde;border-radius:4px;">
                            <button type="button" class="button asq-email-remove-logo"><?php esc_html_e( 'Remove Logo', 'apotheca-skin-quiz' ); ?></button>
                        </div>
                        <button type="button" class="button asq-email-select-logo"><?php esc_html_e( 'Select Logo', 'apotheca-skin-quiz' ); ?></button>
                        <span class="description"><?php esc_html_e( 'Displayed at the top of the email.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Header Image (replaces heading + sub-heading text) -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Header Image', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <input type="hidden" name="asq_email[header_image_id]" value="<?php echo esc_attr( $es['header_image_id'] ); ?>" class="asq-email-header-image-id">
                        <div class="asq-email-header-image-preview" <?php echo $header_image_url ? '' : 'style="display:none;"'; ?>>
                            <img src="<?php echo esc_url( $header_image_url ); ?>" alt="" style="max-width:400px;max-height:200px;height:auto;display:block;margin-bottom:6px;border:1px solid #dcdcde;border-radius:4px;">
                            <button type="button" class="button asq-email-remove-header-image"><?php esc_html_e( 'Remove Image', 'apotheca-skin-quiz' ); ?></button>
                        </div>
                        <button type="button" class="button asq-email-select-header-image"><?php esc_html_e( 'Select Header Image', 'apotheca-skin-quiz' ); ?></button>
                        <span class="description"><?php esc_html_e( 'Optional. When set, replaces the heading and sub-heading text with this image. Upload a transparent PNG for best results. The heading and sub-heading text will be used as alt text for accessibility.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Accent Colour -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Accent Colour', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Top Strip & Button Background', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_email[accent_color]" value="<?php echo esc_attr( $es['accent_color'] ); ?>" class="asq-color-field" data-default-color="#000000">
                        <span class="description"><?php esc_html_e( 'Used for the top colour strip and "Shop the Look" button.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Heading -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Heading', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Main Heading', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_email[heading]" value="<?php echo esc_attr( $es['heading'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Your Personalised Routine', 'apotheca-skin-quiz' ); ?>">
                        <span class="description"><?php esc_html_e( 'The large heading displayed under the logo. Defaults to the finder title.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Sub Heading -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Sub Heading', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Text Under Heading', 'apotheca-skin-quiz' ); ?></label>
                        <span class="description"><?php esc_html_e( 'Smaller text shown below the main heading.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                    <?php
                    wp_editor( $es['sub_heading'], 'asq_email_sub_heading', array(
                        'textarea_name' => 'asq_email[sub_heading]',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny'         => true,
                        'quicktags'     => true,
                    ) );
                    ?>
                </fieldset>

                <!-- Email Subject Line -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Email Subject Line', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Subject', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_email[email_subject]" value="<?php echo esc_attr( $es['email_subject'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Your Personalised Results', 'apotheca-skin-quiz' ); ?>">
                        <span class="description"><?php esc_html_e( 'The subject line of the results email. Defaults to "Your [Finder Title] Results".', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Footer Text -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Footer Text', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Footer Message', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_email[footer_text]" value="<?php echo esc_attr( $es['footer_text'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'This email was generated by Apotheca Skin Quiz.', 'apotheca-skin-quiz' ); ?>">
                        <span class="description"><?php esc_html_e( 'Text displayed at the bottom of the email. Leave blank for default.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>

                <!-- Test Email -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Test Email', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <button type="button" class="button asq-send-test-email" data-finder-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Send Test Email', 'apotheca-skin-quiz' ); ?></button>
                        <span class="asq-test-email-result" style="margin-left:8px;"></span><br>
                        <span class="description"><?php esc_html_e( 'Sends a sample results email (using a few placeholder products) to your account email address. Uses the last saved settings – save the finder first to preview unsaved changes.', 'apotheca-skin-quiz' ); ?></span>
                    </p>
                </fieldset>
            </div>
        </div>
        <script>
        jQuery(function($){
            // Re-init colour picker for any new fields.
            $('.asq-email-styles-wrap .asq-color-field').not('.wp-color-picker').wpColorPicker();

            // Logo upload.
            $(document).on('click', '.asq-email-select-logo', function(){
                var frame = wp.media({ title: 'Select Logo', button: { text: 'Use this image' }, multiple: false, library: { type: 'image' } });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                    $('.asq-email-logo-id').val(att.id);
                    $('.asq-email-logo-preview img').attr('src', url);
                    $('.asq-email-logo-preview').show();
                });
                frame.open();
            });

            $(document).on('click', '.asq-email-remove-logo', function(){
                $('.asq-email-logo-id').val('');
                $('.asq-email-logo-preview').hide();
                $('.asq-email-logo-preview img').attr('src', '');
            });

            // Header image upload.
            $(document).on('click', '.asq-email-select-header-image', function(){
                var frame = wp.media({ title: 'Select Header Image', button: { text: 'Use this image' }, multiple: false, library: { type: 'image' } });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                    $('.asq-email-header-image-id').val(att.id);
                    $('.asq-email-header-image-preview img').attr('src', url);
                    $('.asq-email-header-image-preview').show();
                });
                frame.open();
            });

            $(document).on('click', '.asq-email-remove-header-image', function(){
                $('.asq-email-header-image-id').val('');
                $('.asq-email-header-image-preview').hide();
                $('.asq-email-header-image-preview img').attr('src', '');
            });

            // Send test email.
            $(document).on('click', '.asq-send-test-email', function(){
                var $btn    = $(this);
                var $result = $btn.siblings('.asq-test-email-result');
                $btn.prop('disabled', true);
                $result.css('color', '#646970').text('<?php echo esc_js( __( 'Sending…', 'apotheca-skin-quiz' ) ); ?>');
                $.post(asqAdmin.ajax_url, {
                    action: 'asq_send_test_email',
                    nonce: asqAdmin.nonce,
                    finder_id: $btn.data('finder-id')
                }, function(res){
                    $btn.prop('disabled', false);
                    if (res && res.success) {
                        $result.css('color', '#00a32a').text(res.data.message);
                    } else {
                        $result.css('color', '#b32d2e').text(res && res.data && res.data.message ? res.data.message : '<?php echo esc_js( __( 'Failed to send.', 'apotheca-skin-quiz' ) ); ?>');
                    }
                }).fail(function(){
                    $btn.prop('disabled', false);
                    $result.css('color', '#b32d2e').text('<?php echo esc_js( __( 'Failed to send.', 'apotheca-skin-quiz' ) ); ?>');
                });
            });
        });
        </script>
        <?php
    }

    /* ─── Questions box ─── */

    /**
     * The ten questions are defined in code (includes/asq-quiz-config.php) and
     * shared across every quiz, so this box is a read-only reference showing
     * the questions, their options and the findings each option feeds.
     */
    public function render_questions_box( $post ) {
        $questions = ASQ_Config::questions();
        $findings  = ASQ_Config::findings();
        ?>
        <p class="description">
            <?php esc_html_e( 'These ten questions are defined in code and shared across every quiz. To change the wording, the options or the finding mappings, edit includes/asq-quiz-config.php.', 'apotheca-skin-quiz' ); ?>
        </p>
        <ol class="asq-config-questions" style="margin-left:18px;">
            <?php foreach ( $questions as $q ) : ?>
                <li style="margin:0 0 14px;">
                    <strong><?php echo esc_html( $q['text'] ); ?></strong>
                    <?php if ( ! empty( $q['multiple'] ) ) : ?>
                        <em>(<?php esc_html_e( 'select all that apply', 'apotheca-skin-quiz' ); ?>)</em>
                    <?php endif; ?>
                    <?php if ( ! empty( $q['optional'] ) ) : ?>
                        <em>(<?php esc_html_e( 'skippable', 'apotheca-skin-quiz' ); ?>)</em>
                    <?php endif; ?>
                    <ul style="margin:6px 0 0 18px;list-style:disc;">
                        <?php foreach ( $q['answers'] as $a ) : ?>
                            <li>
                                <?php echo esc_html( $a['text'] ); ?>
                                <?php if ( ! empty( $a['findings'] ) ) : ?>
                                    <span style="color:#646970;">&rarr; <?php echo esc_html( implode( ', ', $a['findings'] ) ); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="description">
            <strong><?php esc_html_e( 'Findings', 'apotheca-skin-quiz' ); ?>:</strong>
            <?php
            $bits = array();
            foreach ( $findings as $id => $label ) {
                $bits[] = $id . ' ' . $label;
            }
            echo esc_html( implode( '  ·  ', $bits ) );
            ?>
        </p>
        <?php
    }

    /* ─── Save ─── */

    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['asq_meta_nonce'] ) || ! wp_verify_nonce( $_POST['asq_meta_nonce'], 'asq_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save options (consent + owner notification only).
        $raw_options = $_POST['asq_options'] ?? array();
        $options     = array(
            'enable_consent' => ! empty( $raw_options['enable_consent'] ) ? 1 : 0,
            'consent_text'   => sanitize_text_field( $raw_options['consent_text'] ?? '' ),
            'exchange_text'  => sanitize_textarea_field( $raw_options['exchange_text'] ?? '' ),
            'notify_email'   => sanitize_email( $raw_options['notify_email'] ?? '' ),
        );
        update_post_meta( $post_id, '_asq_options', $options );

        // Save Email styles
        $raw_email    = $_POST['asq_email'] ?? array();
        $email_styles = array(
            'logo_id'          => absint( $raw_email['logo_id'] ?? 0 ),
            'header_image_id'  => absint( $raw_email['header_image_id'] ?? 0 ),
            'accent_color'     => sanitize_hex_color( $raw_email['accent_color'] ?? '#000000' ) ?: '#000000',
            'heading'          => sanitize_text_field( $raw_email['heading'] ?? '' ),
            'sub_heading'      => wp_kses_post( $raw_email['sub_heading'] ?? '' ),
            'email_subject'    => sanitize_text_field( $raw_email['email_subject'] ?? '' ),
            'footer_text'      => sanitize_text_field( $raw_email['footer_text'] ?? '' ),
        );
        update_post_meta( $post_id, '_asq_email_styles', $email_styles );
    }

}

new ASQ_Admin();

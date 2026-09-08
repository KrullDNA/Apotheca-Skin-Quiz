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
            __( 'Questions & Answers', 'apotheca-skin-quiz' ),
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
            'notify_email'   => '',
        ) );

        wp_nonce_field( 'asq_save_meta', 'asq_meta_nonce' );
        ?>
        <p>
            <label>
                <input type="checkbox" name="asq_options[enable_consent]" value="1" <?php checked( $options['enable_consent'], 1 ); ?>>
                <strong><?php esc_html_e( 'Marketing Consent Checkbox', 'apotheca-skin-quiz' ); ?></strong>
            </label>
            <span class="description"><?php esc_html_e( 'Show an opt-in checkbox on the email screen. The choice is saved with each submission.', 'apotheca-skin-quiz' ); ?></span>
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Consent Label', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="text" name="asq_options[consent_text]" value="<?php echo esc_attr( $options['consent_text'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( "I'd like to receive news and offers", 'apotheca-skin-quiz' ); ?>">
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

    public function render_questions_box( $post ) {
        $questions = get_post_meta( $post->ID, '_asq_questions', true );
        if ( ! is_array( $questions ) ) {
            $questions = array();
        }
        ?>
        <div id="asq-questions-wrap">
            <div id="asq-questions-list" class="asq-sortable">
                <?php
                foreach ( $questions as $qi => $question ) {
                    $this->render_question_template( $qi, $question );
                }
                ?>
            </div>
            <p><button type="button" class="button button-primary" id="asq-add-question"><?php esc_html_e( '+ Add Question', 'apotheca-skin-quiz' ); ?></button></p>
        </div>

        <!-- Hidden template for new question -->
        <script type="text/html" id="tmpl-asq-question">
            <?php $this->render_question_template( '{{data.qi}}', array() ); ?>
        </script>

        <!-- Hidden template for new answer -->
        <script type="text/html" id="tmpl-asq-answer">
            <?php $this->render_answer_template( '{{data.qi}}', '{{data.ai}}', array() ); ?>
        </script>

        <!-- Hidden template for follow-up answer -->
        <script type="text/html" id="tmpl-asq-followup-answer">
            <?php $this->render_followup_answer_template( '{{data.qi}}', '{{data.ai}}', '{{data.fai}}', array() ); ?>
        </script>

        <?php
    }

    /* ─── Render helpers ─── */

    private function render_question_template( $qi, $question ) {
        $question = wp_parse_args( $question, array(
            'text'        => '',
            'instruction' => '',
            'multiple'    => 0,
            'answers'     => array(),
        ) );
        $name_prefix = "asq_questions[{$qi}]";
        ?>
        <div class="asq-question" data-qi="<?php echo esc_attr( $qi ); ?>">
            <div class="asq-question-header asq-drag-handle">
                <span class="asq-drag-icon dashicons dashicons-menu"></span>
                <span class="asq-question-title"><?php echo $question['text'] ? esc_html( $question['text'] ) : esc_html__( 'New Question', 'apotheca-skin-quiz' ); ?></span>
                <span class="asq-question-toggle dashicons dashicons-arrow-down-alt2"></span>
                <button type="button" class="asq-remove-question button-link" title="<?php esc_attr_e( 'Delete Question', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="asq-question-body">
                <p>
                    <label><strong><?php esc_html_e( 'Question Text', 'apotheca-skin-quiz' ); ?></strong></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[text]" value="<?php echo esc_attr( $question['text'] ); ?>" class="widefat asq-question-text-input">
                </p>
                <p>
                    <label><strong><?php esc_html_e( 'Instruction Text', 'apotheca-skin-quiz' ); ?></strong></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[instruction]" value="<?php echo esc_attr( $question['instruction'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Select all that apply, Choose your favourite…', 'apotheca-skin-quiz' ); ?>">
                    <span class="description"><?php esc_html_e( 'Shown below the question on the frontend. Leave blank to use the automatic hint.', 'apotheca-skin-quiz' ); ?></span>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( $name_prefix ); ?>[multiple]" value="1" <?php checked( $question['multiple'], 1 ); ?>>
                        <?php esc_html_e( 'Allow multiple answers (checkboxes)', 'apotheca-skin-quiz' ); ?>
                    </label>
                </p>
                <div class="asq-answers-wrap">
                    <h4><?php esc_html_e( 'Answers', 'apotheca-skin-quiz' ); ?></h4>
                    <div class="asq-answers-list asq-sortable-answers">
                        <?php
                        if ( ! empty( $question['answers'] ) ) {
                            foreach ( $question['answers'] as $ai => $answer ) {
                                $this->render_answer_template( $qi, $ai, $answer );
                            }
                        }
                        ?>
                    </div>
                    <p><button type="button" class="button asq-add-answer"><?php esc_html_e( '+ Add Answer', 'apotheca-skin-quiz' ); ?></button></p>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_answer_template( $qi, $ai, $answer ) {
        $answer = wp_parse_args( $answer, array(
            'text'        => '',
            'description' => '',
            'image_id'    => '',
            'products'    => array(),
            'follow_up'   => array(),
        ) );
        $name_prefix = "asq_questions[{$qi}][answers][{$ai}]";
        $thumb_url   = $answer['image_id'] ? wp_get_attachment_image_url( $answer['image_id'], 'thumbnail' ) : '';
        $has_followup = ! empty( $answer['follow_up']['text'] ) || ! empty( $answer['follow_up']['answers'] );
        $fu_prefix    = $name_prefix . '[follow_up]';
        $fu           = wp_parse_args( (array) ( $answer['follow_up'] ?? array() ), array(
            'text'        => '',
            'instruction' => '',
            'multiple'    => 0,
            'answers'     => array(),
        ) );
        ?>
        <div class="asq-answer" data-ai="<?php echo esc_attr( $ai ); ?>">
            <div class="asq-answer-header asq-drag-handle-answer">
                <span class="asq-drag-icon dashicons dashicons-menu"></span>
                <span class="asq-answer-label"><?php echo $answer['text'] ? esc_html( $answer['text'] ) : esc_html__( 'New Answer', 'apotheca-skin-quiz' ); ?></span>
                <button type="button" class="asq-remove-answer button-link" title="<?php esc_attr_e( 'Delete Answer', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="asq-answer-body">
                <!-- Answer text -->
                <p>
                    <label><?php esc_html_e( 'Answer Text', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[text]" value="<?php echo esc_attr( $answer['text'] ); ?>" class="widefat asq-answer-text-input">
                </p>
                <!-- Description (shown under answer text on image layout) -->
                <p>
                    <label><?php esc_html_e( 'Description', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[description]" value="<?php echo esc_attr( $answer['description'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Optional – displayed below the answer text on image layout', 'apotheca-skin-quiz' ); ?>">
                </p>
                <!-- Image -->
                <div class="asq-answer-image-wrap">
                    <label><?php esc_html_e( 'Image', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[image_id]" value="<?php echo esc_attr( $answer['image_id'] ); ?>" class="asq-image-id">
                    <div class="asq-image-preview" <?php echo $thumb_url ? '' : 'style="display:none;"'; ?>>
                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
                        <button type="button" class="asq-remove-image button-link" title="<?php esc_attr_e( 'Remove Image', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Remove', 'apotheca-skin-quiz' ); ?></button>
                    </div>
                    <button type="button" class="button asq-select-image"><?php esc_html_e( 'Select Image', 'apotheca-skin-quiz' ); ?></button>
                </div>
                <!-- Follow-up question -->
                <div class="asq-followup-wrap" <?php echo $has_followup ? '' : 'style="display:none;"'; ?>>
                    <div class="asq-followup-header">
                        <span class="dashicons dashicons-admin-comments"></span>
                        <strong><?php esc_html_e( 'Follow-up Question', 'apotheca-skin-quiz' ); ?></strong>
                        <button type="button" class="asq-remove-followup button-link" title="<?php esc_attr_e( 'Remove Follow-up', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                    <div class="asq-followup-body">
                        <p>
                            <label><?php esc_html_e( 'Follow-up Question Text', 'apotheca-skin-quiz' ); ?></label><br>
                            <input type="text" name="<?php echo esc_attr( $fu_prefix ); ?>[text]" value="<?php echo esc_attr( $fu['text'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. What shade of brunette?', 'apotheca-skin-quiz' ); ?>">
                        </p>
                        <p>
                            <label><?php esc_html_e( 'Instruction', 'apotheca-skin-quiz' ); ?></label><br>
                            <input type="text" name="<?php echo esc_attr( $fu_prefix ); ?>[instruction]" value="<?php echo esc_attr( $fu['instruction'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Optional', 'apotheca-skin-quiz' ); ?>">
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr( $fu_prefix ); ?>[multiple]" value="1" <?php checked( $fu['multiple'], 1 ); ?>>
                                <?php esc_html_e( 'Allow multiple answers', 'apotheca-skin-quiz' ); ?>
                            </label>
                        </p>
                        <div class="asq-followup-answers">
                            <h4><?php esc_html_e( 'Follow-up Answers', 'apotheca-skin-quiz' ); ?></h4>
                            <div class="asq-followup-answers-list">
                                <?php
                                if ( ! empty( $fu['answers'] ) ) {
                                    foreach ( $fu['answers'] as $fai => $fa ) {
                                        $this->render_followup_answer_template( $qi, $ai, $fai, $fa );
                                    }
                                }
                                ?>
                            </div>
                            <p><button type="button" class="button asq-add-followup-answer"><?php esc_html_e( '+ Add Follow-up Answer', 'apotheca-skin-quiz' ); ?></button></p>
                        </div>
                    </div>
                </div>
                <div class="asq-followup-add" <?php echo $has_followup ? 'style="display:none;"' : ''; ?>>
                    <button type="button" class="button asq-add-followup"><span class="dashicons dashicons-admin-comments"></span> <?php esc_html_e( 'Add Follow-up Question', 'apotheca-skin-quiz' ); ?></button>
                    <span class="description"><?php esc_html_e( 'Show an extra question when this answer is selected', 'apotheca-skin-quiz' ); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_followup_answer_template( $qi, $ai, $fai, $fa ) {
        $fa = wp_parse_args( $fa, array(
            'text'        => '',
            'description' => '',
            'image_id'    => '',
            'products'    => array(),
        ) );
        $name_prefix = "asq_questions[{$qi}][answers][{$ai}][follow_up][answers][{$fai}]";
        $thumb_url   = $fa['image_id'] ? wp_get_attachment_image_url( $fa['image_id'], 'thumbnail' ) : '';
        ?>
        <div class="asq-followup-answer" data-fai="<?php echo esc_attr( $fai ); ?>">
            <div class="asq-followup-answer-header">
                <span class="asq-followup-answer-label"><?php echo $fa['text'] ? esc_html( $fa['text'] ) : esc_html__( 'New Answer', 'apotheca-skin-quiz' ); ?></span>
                <button type="button" class="asq-remove-followup-answer button-link" title="<?php esc_attr_e( 'Delete', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-trash"></span></button>
            </div>
            <div class="asq-followup-answer-body">
                <p>
                    <label><?php esc_html_e( 'Answer Text', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[text]" value="<?php echo esc_attr( $fa['text'] ); ?>" class="widefat asq-followup-answer-text-input">
                </p>
                <p>
                    <label><?php esc_html_e( 'Description', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" name="<?php echo esc_attr( $name_prefix ); ?>[description]" value="<?php echo esc_attr( $fa['description'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Optional', 'apotheca-skin-quiz' ); ?>">
                </p>
                <div class="asq-answer-image-wrap">
                    <label><?php esc_html_e( 'Image', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[image_id]" value="<?php echo esc_attr( $fa['image_id'] ); ?>" class="asq-image-id">
                    <div class="asq-image-preview" <?php echo $thumb_url ? '' : 'style="display:none;"'; ?>>
                        <img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
                        <button type="button" class="asq-remove-image button-link" title="<?php esc_attr_e( 'Remove Image', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-no-alt"></span> <?php esc_html_e( 'Remove', 'apotheca-skin-quiz' ); ?></button>
                    </div>
                    <button type="button" class="button asq-select-image"><?php esc_html_e( 'Select Image', 'apotheca-skin-quiz' ); ?></button>
                </div>
            </div>
        </div>
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

        // Save questions
        $raw_questions = $_POST['asq_questions'] ?? array();
        $questions     = $this->sanitize_questions( $raw_questions );
        update_post_meta( $post_id, '_asq_questions', $questions );

        // Save options (consent + owner notification only).
        $raw_options = $_POST['asq_options'] ?? array();
        $options     = array(
            'enable_consent' => ! empty( $raw_options['enable_consent'] ) ? 1 : 0,
            'consent_text'   => sanitize_text_field( $raw_options['consent_text'] ?? '' ),
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

    private function sanitize_questions( $raw ) {
        $clean = array();
        if ( ! is_array( $raw ) ) {
            return $clean;
        }
        foreach ( $raw as $q ) {
            $question = array(
                'text'        => sanitize_text_field( $q['text'] ?? '' ),
                'instruction' => sanitize_text_field( $q['instruction'] ?? '' ),
                'multiple'    => ! empty( $q['multiple'] ) ? 1 : 0,
                'answers'     => array(),
            );
            if ( ! empty( $q['answers'] ) && is_array( $q['answers'] ) ) {
                foreach ( $q['answers'] as $a ) {
                    $answer = array(
                        'text'        => sanitize_text_field( $a['text'] ?? '' ),
                        'description' => sanitize_text_field( $a['description'] ?? '' ),
                        'image_id'    => absint( $a['image_id'] ?? 0 ),
                    );
                    // Follow-up question (optional).
                    $answer['follow_up'] = array();
                    if ( ! empty( $a['follow_up'] ) && is_array( $a['follow_up'] ) && ! empty( $a['follow_up']['text'] ) ) {
                        $fu = array(
                            'text'        => sanitize_text_field( $a['follow_up']['text'] ?? '' ),
                            'instruction' => sanitize_text_field( $a['follow_up']['instruction'] ?? '' ),
                            'multiple'    => ! empty( $a['follow_up']['multiple'] ) ? 1 : 0,
                            'answers'     => array(),
                        );
                        if ( ! empty( $a['follow_up']['answers'] ) && is_array( $a['follow_up']['answers'] ) ) {
                            foreach ( $a['follow_up']['answers'] as $fa ) {
                                $fu['answers'][] = array(
                                    'text'        => sanitize_text_field( $fa['text'] ?? '' ),
                                    'description' => sanitize_text_field( $fa['description'] ?? '' ),
                                    'image_id'    => absint( $fa['image_id'] ?? 0 ),
                                );
                            }
                        }
                        $answer['follow_up'] = $fu;
                    }
                    $question['answers'][] = $answer;
                }
            }
            $clean[] = $question;
        }
        return $clean;
    }

}

new ASQ_Admin();

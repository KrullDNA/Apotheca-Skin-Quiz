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

        // Get current finder_type for this post.
        $asq_options = array();
        if ( isset( $_GET['post'] ) ) {
            $asq_options = get_post_meta( absint( $_GET['post'] ), '_asq_options', true );
        }
        $asq_options = wp_parse_args( (array) $asq_options, array( 'finder_type' => 'cosmeceuticals' ) );

        wp_localize_script( 'asq-admin', 'asqAdmin', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'asq_admin_nonce' ),
            'finder_type'  => $asq_options['finder_type'],
            'i18n'         => array(
                'select_image'      => __( 'Select Image', 'apotheca-skin-quiz' ),
                'remove_image'      => __( 'Remove', 'apotheca-skin-quiz' ),
                'use_image'         => __( 'Use this image', 'apotheca-skin-quiz' ),
                'search_product'    => __( 'Search for a product…', 'apotheca-skin-quiz' ),
                'confirm_del'       => __( 'Delete this item?', 'apotheca-skin-quiz' ),
                'select_variation'  => __( '— Select variation —', 'apotheca-skin-quiz' ),
                'loading_variations'=> __( 'Loading variations…', 'apotheca-skin-quiz' ),
                'no_variations'     => __( 'No variations found (simple product)', 'apotheca-skin-quiz' ),
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
            'asq_dn_styles',
            __( 'Day / Night Tab Styling', 'apotheca-skin-quiz' ),
            array( $this, 'render_dn_styles_box' ),
            'apotheca_skin_quiz',
            'normal',
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
            'listing_template'  => '',
            'cols_desktop'      => 3,
            'cols_tablet'       => 2,
            'cols_mobile'       => 1,
            'finder_type'       => 'cosmeceuticals',
            'enable_day_night'  => 0,
            'enable_consent'    => 1,
            'consent_text'      => '',
            'notify_email'      => '',
        ) );

        wp_nonce_field( 'asq_save_meta', 'asq_meta_nonce' );

        $templates = $this->get_crocoblock_templates();
        ?>
        <div class="asq-finder-type-wrap">
            <label><strong><?php esc_html_e( 'Finder Type', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <label class="asq-finder-type-option">
                <input type="radio" name="asq_options[finder_type]" value="cosmeceuticals" <?php checked( $options['finder_type'], 'cosmeceuticals' ); ?>>
                <?php esc_html_e( 'Cosmeceuticals', 'apotheca-skin-quiz' ); ?>
                <span class="description"><?php esc_html_e( 'Simple product recommendations', 'apotheca-skin-quiz' ); ?></span>
            </label>
            <label class="asq-finder-type-option">
                <input type="radio" name="asq_options[finder_type]" value="beauty" <?php checked( $options['finder_type'], 'beauty' ); ?>>
                <?php esc_html_e( 'Beauty', 'apotheca-skin-quiz' ); ?>
                <span class="description"><?php esc_html_e( 'Product variation recommendations (shades, colours)', 'apotheca-skin-quiz' ); ?></span>
            </label>
        </div>
        <div class="asq-day-night-wrap">
            <label>
                <input type="checkbox" name="asq_options[enable_day_night]" value="1" <?php checked( $options['enable_day_night'], 1 ); ?>>
                <strong><?php esc_html_e( 'Enable Day / Night Results', 'apotheca-skin-quiz' ); ?></strong>
            </label>
            <p class="description"><?php esc_html_e( 'Split results into Day and Night tabs. A "Set" dropdown will appear on each product row.', 'apotheca-skin-quiz' ); ?></p>
        </div>
        <hr>
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
            <span class="description"><?php esc_html_e( 'Optional. Sends an instant lead alert to this address every time someone completes the finder and submits their email. Leave blank to disable.', 'apotheca-skin-quiz' ); ?></span>
        </p>
        <hr>
        <p>
            <label><strong><?php esc_html_e( 'CrocoBlock Listing Template', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <select name="asq_options[listing_template]" class="widefat">
                <option value=""><?php esc_html_e( '— Select template —', 'apotheca-skin-quiz' ); ?></option>
                <?php foreach ( $templates as $id => $title ) : ?>
                    <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $options['listing_template'], $id ); ?>><?php echo esc_html( $title ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Columns – Desktop', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="number" name="asq_options[cols_desktop]" value="<?php echo esc_attr( $options['cols_desktop'] ); ?>" min="1" max="6" class="widefat">
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Columns – Tablet', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="number" name="asq_options[cols_tablet]" value="<?php echo esc_attr( $options['cols_tablet'] ); ?>" min="1" max="6" class="widefat">
        </p>
        <p>
            <label><strong><?php esc_html_e( 'Columns – Mobile', 'apotheca-skin-quiz' ); ?></strong></label><br>
            <input type="number" name="asq_options[cols_mobile]" value="<?php echo esc_attr( $options['cols_mobile'] ); ?>" min="1" max="6" class="widefat">
        </p>
        <?php
    }

    /* ─── Day / Night Tab Styling box ─── */

    public function render_dn_styles_box( $post ) {
        $dn = get_post_meta( $post->ID, '_asq_dn_styles', true );
        $dn = wp_parse_args( (array) $dn, array(
            'day_label'        => 'Day',
            'night_label'      => 'Night',
            'tab_color'        => '',
            'tab_bg'           => '',
            'tab_hover_color'  => '',
            'tab_hover_bg'     => '',
            'tab_active_color' => '',
            'tab_active_bg'    => '',
            'line_color'       => '',
            'line_width'       => '',
            'active_line_color'=> '',
            'tab_padding'      => '',
            'tab_gap'          => '',
            'tab_radius'       => '',
            'tabs_margin'      => '',
            'tabs_align'       => '',
            'font_family'      => '',
            'font_size'        => '',
            'font_weight'      => '',
        ) );
        ?>
        <div class="asq-dn-styles-wrap">
            <p class="description"><?php esc_html_e( 'These styles are applied to the Day / Night result tabs on the frontend. Leave fields blank to use theme defaults.', 'apotheca-skin-quiz' ); ?></p>

            <div class="asq-dn-grid">
                <!-- Labels -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Labels', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Day Label', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[day_label]" value="<?php echo esc_attr( $dn['day_label'] ); ?>" class="regular-text">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Night Label', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[night_label]" value="<?php echo esc_attr( $dn['night_label'] ); ?>" class="regular-text">
                    </p>
                </fieldset>

                <!-- Normal State -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Normal State', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Text Colour', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_color]" value="<?php echo esc_attr( $dn['tab_color'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Background', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_bg]" value="<?php echo esc_attr( $dn['tab_bg'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                </fieldset>

                <!-- Hover State -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Hover State', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Text Colour', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_hover_color]" value="<?php echo esc_attr( $dn['tab_hover_color'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Background', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_hover_bg]" value="<?php echo esc_attr( $dn['tab_hover_bg'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                </fieldset>

                <!-- Active State -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Active State', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Text Colour', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_active_color]" value="<?php echo esc_attr( $dn['tab_active_color'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Background', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_active_bg]" value="<?php echo esc_attr( $dn['tab_active_bg'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                </fieldset>

                <!-- Bottom Line -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Bottom Line', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Line Colour', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[line_color]" value="<?php echo esc_attr( $dn['line_color'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Active Line Colour', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[active_line_color]" value="<?php echo esc_attr( $dn['active_line_color'] ); ?>" class="asq-color-field" data-default-color="">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Line Thickness (px)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="number" name="asq_dn[line_width]" value="<?php echo esc_attr( $dn['line_width'] ); ?>" min="0" max="10" step="1" class="small-text">
                    </p>
                </fieldset>

                <!-- Typography -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Typography', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Font Family', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[font_family]" value="<?php echo esc_attr( $dn['font_family'] ); ?>" class="regular-text" placeholder="e.g. Apotheca, sans-serif">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Font Size (px)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="number" name="asq_dn[font_size]" value="<?php echo esc_attr( $dn['font_size'] ); ?>" min="0" max="100" step="1" class="small-text">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Font Weight', 'apotheca-skin-quiz' ); ?></label><br>
                        <select name="asq_dn[font_weight]" class="widefat">
                            <option value=""><?php esc_html_e( '— Default —', 'apotheca-skin-quiz' ); ?></option>
                            <option value="300" <?php selected( $dn['font_weight'], '300' ); ?>>300 (Light)</option>
                            <option value="400" <?php selected( $dn['font_weight'], '400' ); ?>>400 (Normal)</option>
                            <option value="500" <?php selected( $dn['font_weight'], '500' ); ?>>500 (Medium)</option>
                            <option value="600" <?php selected( $dn['font_weight'], '600' ); ?>>600 (Semi-Bold)</option>
                            <option value="700" <?php selected( $dn['font_weight'], '700' ); ?>>700 (Bold)</option>
                        </select>
                    </p>
                </fieldset>

                <!-- Spacing -->
                <fieldset class="asq-dn-fieldset">
                    <legend><?php esc_html_e( 'Spacing & Layout', 'apotheca-skin-quiz' ); ?></legend>
                    <p>
                        <label><?php esc_html_e( 'Tab Padding (CSS shorthand)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_padding]" value="<?php echo esc_attr( $dn['tab_padding'] ); ?>" class="regular-text" placeholder="e.g. 8px 16px">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Gap Between Tabs (px)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="number" name="asq_dn[tab_gap]" value="<?php echo esc_attr( $dn['tab_gap'] ); ?>" min="0" max="60" step="1" class="small-text">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Border Radius (CSS shorthand)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tab_radius]" value="<?php echo esc_attr( $dn['tab_radius'] ); ?>" class="regular-text" placeholder="e.g. 4px">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Tabs Margin (CSS shorthand)', 'apotheca-skin-quiz' ); ?></label><br>
                        <input type="text" name="asq_dn[tabs_margin]" value="<?php echo esc_attr( $dn['tabs_margin'] ); ?>" class="regular-text" placeholder="e.g. 0 0 24px 0">
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Alignment', 'apotheca-skin-quiz' ); ?></label><br>
                        <select name="asq_dn[tabs_align]" class="widefat">
                            <option value=""><?php esc_html_e( '— Default —', 'apotheca-skin-quiz' ); ?></option>
                            <option value="flex-start" <?php selected( $dn['tabs_align'], 'flex-start' ); ?>><?php esc_html_e( 'Left', 'apotheca-skin-quiz' ); ?></option>
                            <option value="center" <?php selected( $dn['tabs_align'], 'center' ); ?>><?php esc_html_e( 'Centre', 'apotheca-skin-quiz' ); ?></option>
                            <option value="flex-end" <?php selected( $dn['tabs_align'], 'flex-end' ); ?>><?php esc_html_e( 'Right', 'apotheca-skin-quiz' ); ?></option>
                        </select>
                    </p>
                </fieldset>
            </div>
        </div>
        <script>
        jQuery(function($){ $('.asq-color-field').wpColorPicker(); });
        </script>
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

        <!-- Hidden template for product row -->
        <script type="text/html" id="tmpl-asq-product-row">
            <?php $this->render_product_row_template( '{{data.qi}}', '{{data.ai}}', '{{data.pi}}', array() ); ?>
        </script>

        <!-- Hidden template for follow-up answer -->
        <script type="text/html" id="tmpl-asq-followup-answer">
            <?php $this->render_followup_answer_template( '{{data.qi}}', '{{data.ai}}', '{{data.fai}}', array() ); ?>
        </script>

        <!-- Hidden template for follow-up product row -->
        <script type="text/html" id="tmpl-asq-followup-product-row">
            <?php $this->render_followup_product_row_template( '{{data.qi}}', '{{data.ai}}', '{{data.fai}}', '{{data.pi}}', array() ); ?>
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
                <!-- Product search -->
                <div class="asq-answer-products-wrap">
                    <label><?php esc_html_e( 'Products', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" class="widefat asq-product-search" placeholder="<?php esc_attr_e( 'Search for a product…', 'apotheca-skin-quiz' ); ?>" autocomplete="off">
                    <div class="asq-product-search-results"></div>
                    <div class="asq-products-list">
                        <?php
                        if ( ! empty( $answer['products'] ) ) {
                            foreach ( $answer['products'] as $pi => $prod ) {
                                $this->render_product_row_template( $qi, $ai, $pi, $prod );
                            }
                        }
                        ?>
                    </div>
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
                <div class="asq-answer-products-wrap">
                    <label><?php esc_html_e( 'Products', 'apotheca-skin-quiz' ); ?></label><br>
                    <input type="text" class="widefat asq-product-search" placeholder="<?php esc_attr_e( 'Search for a product…', 'apotheca-skin-quiz' ); ?>" autocomplete="off">
                    <div class="asq-product-search-results"></div>
                    <div class="asq-products-list">
                        <?php
                        if ( ! empty( $fa['products'] ) ) {
                            foreach ( $fa['products'] as $pi => $prod ) {
                                $this->render_followup_product_row_template( $qi, $ai, $fai, $pi, $prod );
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_followup_product_row_template( $qi, $ai, $fai, $pi, $prod ) {
        $prod = wp_parse_args( $prod, array(
            'id'              => '',
            'variation_id'    => '',
            'rank'            => 1,
            'result_category' => '',
            'result_set'      => 'both',
        ) );
        $name_prefix  = "asq_questions[{$qi}][answers][{$ai}][follow_up][answers][{$fai}][products][{$pi}]";
        $product_name = $prod['id'] ? get_the_title( $prod['id'] ) : '{{data.name}}';

        $variation_name = '';
        if ( $prod['variation_id'] && function_exists( 'wc_get_product' ) ) {
            $variation = wc_get_product( $prod['variation_id'] );
            if ( $variation && $variation->is_type( 'variation' ) ) {
                $attrs = $variation->get_attributes();
                $variation_name = implode( ', ', array_values( $attrs ) );
            }
        }
        ?>
        <div class="asq-product-row" data-pi="<?php echo esc_attr( $pi ); ?>" data-product-id="<?php echo esc_attr( $prod['id'] ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[id]" value="<?php echo esc_attr( $prod['id'] ); ?>" class="asq-product-id">
            <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[variation_id]" value="<?php echo esc_attr( $prod['variation_id'] ); ?>" class="asq-variation-id">
            <span class="asq-product-name"><?php echo esc_html( $product_name ); ?></span>
            <div class="asq-variation-picker" style="display:none;">
                <select class="asq-variation-select" data-current="<?php echo esc_attr( $prod['variation_id'] ); ?>">
                    <option value=""><?php esc_html_e( '— Select variation —', 'apotheca-skin-quiz' ); ?></option>
                    <?php if ( $prod['variation_id'] && $variation_name ) : ?>
                        <option value="<?php echo esc_attr( $prod['variation_id'] ); ?>" selected><?php echo esc_html( $variation_name ); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <label class="asq-product-category-label asq-product-category-picker" style="display:none;"><?php esc_html_e( 'Category:', 'apotheca-skin-quiz' ); ?>
                <select name="<?php echo esc_attr( $name_prefix ); ?>[result_category]" class="asq-product-category">
                    <option value=""><?php esc_html_e( '— None —', 'apotheca-skin-quiz' ); ?></option>
                    <option value="base" data-type="beauty" <?php selected( $prod['result_category'], 'base' ); ?>><?php esc_html_e( 'Base / Foundation', 'apotheca-skin-quiz' ); ?></option>
                    <option value="concealer" data-type="beauty" <?php selected( $prod['result_category'], 'concealer' ); ?>><?php esc_html_e( 'Concealer', 'apotheca-skin-quiz' ); ?></option>
                    <option value="lip" data-type="beauty" <?php selected( $prod['result_category'], 'lip' ); ?>><?php esc_html_e( 'Lip', 'apotheca-skin-quiz' ); ?></option>
                    <option value="cheek" data-type="beauty" <?php selected( $prod['result_category'], 'cheek' ); ?>><?php esc_html_e( 'Cheek', 'apotheca-skin-quiz' ); ?></option>
                    <option value="lip_cheek" data-type="beauty" <?php selected( $prod['result_category'], 'lip_cheek' ); ?>><?php esc_html_e( 'Lip & Cheek', 'apotheca-skin-quiz' ); ?></option>
                    <option value="eye" data-type="beauty" <?php selected( $prod['result_category'], 'eye' ); ?>><?php esc_html_e( 'Eye', 'apotheca-skin-quiz' ); ?></option>
                    <option value="cleanser" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'cleanser' ); ?>><?php esc_html_e( 'Cleanser', 'apotheca-skin-quiz' ); ?></option>
                    <option value="exfoliator" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'exfoliator' ); ?>><?php esc_html_e( 'Exfoliator', 'apotheca-skin-quiz' ); ?></option>
                    <option value="moisturiser" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'moisturiser' ); ?>><?php esc_html_e( 'Moisturiser', 'apotheca-skin-quiz' ); ?></option>
                    <option value="essential" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'essential' ); ?>><?php esc_html_e( 'Essential', 'apotheca-skin-quiz' ); ?></option>
                    <option value="specialty" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'specialty' ); ?>><?php esc_html_e( 'Specialty', 'apotheca-skin-quiz' ); ?></option>
                </select>
            </label>
            <label class="asq-product-set-label asq-product-set-picker" style="display:none;"><?php esc_html_e( 'Set:', 'apotheca-skin-quiz' ); ?>
                <select name="<?php echo esc_attr( $name_prefix ); ?>[result_set]" class="asq-product-set">
                    <option value="both" <?php selected( $prod['result_set'], 'both' ); ?>><?php esc_html_e( 'Both', 'apotheca-skin-quiz' ); ?></option>
                    <option value="day" <?php selected( $prod['result_set'], 'day' ); ?>><?php esc_html_e( 'Day', 'apotheca-skin-quiz' ); ?></option>
                    <option value="night" <?php selected( $prod['result_set'], 'night' ); ?>><?php esc_html_e( 'Night', 'apotheca-skin-quiz' ); ?></option>
                </select>
            </label>
            <label class="asq-product-rank-label"><?php esc_html_e( 'Rank:', 'apotheca-skin-quiz' ); ?>
                <input type="number" name="<?php echo esc_attr( $name_prefix ); ?>[rank]" value="<?php echo esc_attr( $prod['rank'] ); ?>" min="1" class="asq-product-rank small-text">
            </label>
            <button type="button" class="asq-remove-product button-link" title="<?php esc_attr_e( 'Remove Product', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div>
        <?php
    }

    private function render_product_row_template( $qi, $ai, $pi, $prod ) {
        $prod = wp_parse_args( $prod, array(
            'id'              => '',
            'variation_id'    => '',
            'rank'            => 1,
            'result_category' => '',
            'result_set'      => 'both',
        ) );
        $name_prefix = "asq_questions[{$qi}][answers][{$ai}][products][{$pi}]";
        $product_name = $prod['id'] ? get_the_title( $prod['id'] ) : '{{data.name}}';

        // If a variation is saved, get its name for display.
        $variation_name = '';
        if ( $prod['variation_id'] && function_exists( 'wc_get_product' ) ) {
            $variation = wc_get_product( $prod['variation_id'] );
            if ( $variation && $variation->is_type( 'variation' ) ) {
                $attrs = $variation->get_attributes();
                $variation_name = implode( ', ', array_values( $attrs ) );
            }
        }
        ?>
        <div class="asq-product-row" data-pi="<?php echo esc_attr( $pi ); ?>" data-product-id="<?php echo esc_attr( $prod['id'] ); ?>">
            <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[id]" value="<?php echo esc_attr( $prod['id'] ); ?>" class="asq-product-id">
            <input type="hidden" name="<?php echo esc_attr( $name_prefix ); ?>[variation_id]" value="<?php echo esc_attr( $prod['variation_id'] ); ?>" class="asq-variation-id">
            <span class="asq-product-name"><?php echo esc_html( $product_name ); ?></span>
            <div class="asq-variation-picker" style="display:none;">
                <select class="asq-variation-select" data-current="<?php echo esc_attr( $prod['variation_id'] ); ?>">
                    <option value=""><?php esc_html_e( '— Select variation —', 'apotheca-skin-quiz' ); ?></option>
                    <?php if ( $prod['variation_id'] && $variation_name ) : ?>
                        <option value="<?php echo esc_attr( $prod['variation_id'] ); ?>" selected><?php echo esc_html( $variation_name ); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <label class="asq-product-category-label asq-product-category-picker" style="display:none;"><?php esc_html_e( 'Category:', 'apotheca-skin-quiz' ); ?>
                <select name="<?php echo esc_attr( $name_prefix ); ?>[result_category]" class="asq-product-category">
                    <option value=""><?php esc_html_e( '— None —', 'apotheca-skin-quiz' ); ?></option>
                    <option value="base" data-type="beauty" <?php selected( $prod['result_category'], 'base' ); ?>><?php esc_html_e( 'Base / Foundation', 'apotheca-skin-quiz' ); ?></option>
                    <option value="concealer" data-type="beauty" <?php selected( $prod['result_category'], 'concealer' ); ?>><?php esc_html_e( 'Concealer', 'apotheca-skin-quiz' ); ?></option>
                    <option value="lip" data-type="beauty" <?php selected( $prod['result_category'], 'lip' ); ?>><?php esc_html_e( 'Lip', 'apotheca-skin-quiz' ); ?></option>
                    <option value="cheek" data-type="beauty" <?php selected( $prod['result_category'], 'cheek' ); ?>><?php esc_html_e( 'Cheek', 'apotheca-skin-quiz' ); ?></option>
                    <option value="lip_cheek" data-type="beauty" <?php selected( $prod['result_category'], 'lip_cheek' ); ?>><?php esc_html_e( 'Lip & Cheek', 'apotheca-skin-quiz' ); ?></option>
                    <option value="eye" data-type="beauty" <?php selected( $prod['result_category'], 'eye' ); ?>><?php esc_html_e( 'Eye', 'apotheca-skin-quiz' ); ?></option>
                    <option value="cleanser" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'cleanser' ); ?>><?php esc_html_e( 'Cleanser', 'apotheca-skin-quiz' ); ?></option>
                    <option value="exfoliator" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'exfoliator' ); ?>><?php esc_html_e( 'Exfoliator', 'apotheca-skin-quiz' ); ?></option>
                    <option value="moisturiser" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'moisturiser' ); ?>><?php esc_html_e( 'Moisturiser', 'apotheca-skin-quiz' ); ?></option>
                    <option value="essential" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'essential' ); ?>><?php esc_html_e( 'Essential', 'apotheca-skin-quiz' ); ?></option>
                    <option value="specialty" data-type="cosmeceuticals" <?php selected( $prod['result_category'], 'specialty' ); ?>><?php esc_html_e( 'Specialty', 'apotheca-skin-quiz' ); ?></option>
                </select>
            </label>
            <label class="asq-product-set-label asq-product-set-picker" style="display:none;"><?php esc_html_e( 'Set:', 'apotheca-skin-quiz' ); ?>
                <select name="<?php echo esc_attr( $name_prefix ); ?>[result_set]" class="asq-product-set">
                    <option value="both" <?php selected( $prod['result_set'], 'both' ); ?>><?php esc_html_e( 'Both', 'apotheca-skin-quiz' ); ?></option>
                    <option value="day" <?php selected( $prod['result_set'], 'day' ); ?>><?php esc_html_e( 'Day', 'apotheca-skin-quiz' ); ?></option>
                    <option value="night" <?php selected( $prod['result_set'], 'night' ); ?>><?php esc_html_e( 'Night', 'apotheca-skin-quiz' ); ?></option>
                </select>
            </label>
            <label class="asq-product-rank-label"><?php esc_html_e( 'Rank:', 'apotheca-skin-quiz' ); ?>
                <input type="number" name="<?php echo esc_attr( $name_prefix ); ?>[rank]" value="<?php echo esc_attr( $prod['rank'] ); ?>" min="1" class="asq-product-rank small-text">
            </label>
            <button type="button" class="asq-remove-product button-link" title="<?php esc_attr_e( 'Remove Product', 'apotheca-skin-quiz' ); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div>
        <?php
    }

    /* ───────────────────────── Save ─────────────────────────── */

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

        // Save options
        $raw_options = $_POST['asq_options'] ?? array();
        $finder_type = sanitize_text_field( $raw_options['finder_type'] ?? 'cosmeceuticals' );
        if ( ! in_array( $finder_type, array( 'cosmeceuticals', 'beauty' ), true ) ) {
            $finder_type = 'cosmeceuticals';
        }
        $options     = array(
            'listing_template' => sanitize_text_field( $raw_options['listing_template'] ?? '' ),
            'cols_desktop'     => absint( $raw_options['cols_desktop'] ?? 3 ),
            'cols_tablet'      => absint( $raw_options['cols_tablet'] ?? 2 ),
            'cols_mobile'      => absint( $raw_options['cols_mobile'] ?? 1 ),
            'finder_type'      => $finder_type,
            'enable_day_night' => ! empty( $raw_options['enable_day_night'] ) ? 1 : 0,
            'enable_consent'   => ! empty( $raw_options['enable_consent'] ) ? 1 : 0,
            'consent_text'     => sanitize_text_field( $raw_options['consent_text'] ?? '' ),
            'notify_email'     => sanitize_email( $raw_options['notify_email'] ?? '' ),
        );
        update_post_meta( $post_id, '_asq_options', $options );

        // Save Day / Night tab styles
        $raw_dn     = $_POST['asq_dn'] ?? array();
        $color_keys = array(
            'tab_color', 'tab_bg', 'tab_hover_color', 'tab_hover_bg',
            'tab_active_color', 'tab_active_bg', 'line_color', 'active_line_color',
        );
        $dn_styles  = array();
        foreach ( $color_keys as $ck ) {
            $dn_styles[ $ck ] = sanitize_hex_color( $raw_dn[ $ck ] ?? '' );
        }
        $dn_styles['day_label']    = sanitize_text_field( $raw_dn['day_label'] ?? 'Day' );
        $dn_styles['night_label']  = sanitize_text_field( $raw_dn['night_label'] ?? 'Night' );
        $dn_styles['line_width']   = absint( $raw_dn['line_width'] ?? 0 );
        $dn_styles['font_family']  = sanitize_text_field( $raw_dn['font_family'] ?? '' );
        $dn_styles['font_size']    = absint( $raw_dn['font_size'] ?? 0 );
        $dn_styles['font_weight']  = sanitize_text_field( $raw_dn['font_weight'] ?? '' );
        $dn_styles['tab_padding']  = sanitize_text_field( $raw_dn['tab_padding'] ?? '' );
        $dn_styles['tab_gap']      = absint( $raw_dn['tab_gap'] ?? 0 );
        $dn_styles['tab_radius']   = sanitize_text_field( $raw_dn['tab_radius'] ?? '' );
        $dn_styles['tabs_margin']  = sanitize_text_field( $raw_dn['tabs_margin'] ?? '' );
        $dn_styles['tabs_align']   = sanitize_text_field( $raw_dn['tabs_align'] ?? '' );
        update_post_meta( $post_id, '_asq_dn_styles', $dn_styles );

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
        $valid_categories = array(
            '',
            // Beauty
            'base', 'concealer', 'lip', 'cheek', 'lip_cheek', 'eye',
            // Cosmeceuticals
            'cleanser', 'exfoliator', 'moisturiser', 'essential', 'specialty',
        );
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
                        'products'    => array(),
                    );
                    if ( ! empty( $a['products'] ) && is_array( $a['products'] ) ) {
                        foreach ( $a['products'] as $p ) {
                            $cat = sanitize_key( $p['result_category'] ?? '' );
                            if ( ! in_array( $cat, $valid_categories, true ) ) {
                                $cat = '';
                            }
                            $result_set = sanitize_key( $p['result_set'] ?? 'both' );
                            if ( ! in_array( $result_set, array( 'both', 'day', 'night' ), true ) ) {
                                $result_set = 'both';
                            }
                            $answer['products'][] = array(
                                'id'              => absint( $p['id'] ?? 0 ),
                                'variation_id'    => absint( $p['variation_id'] ?? 0 ),
                                'rank'            => absint( $p['rank'] ?? 1 ),
                                'result_category' => $cat,
                                'result_set'      => $result_set,
                            );
                        }
                    }
                    // Follow-up question (optional)
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
                                $fu_answer = array(
                                    'text'        => sanitize_text_field( $fa['text'] ?? '' ),
                                    'description' => sanitize_text_field( $fa['description'] ?? '' ),
                                    'image_id'    => absint( $fa['image_id'] ?? 0 ),
                                    'products'    => array(),
                                );
                                if ( ! empty( $fa['products'] ) && is_array( $fa['products'] ) ) {
                                    foreach ( $fa['products'] as $fp ) {
                                        $fu_cat = sanitize_key( $fp['result_category'] ?? '' );
                                        if ( ! in_array( $fu_cat, $valid_categories, true ) ) {
                                            $fu_cat = '';
                                        }
                                        $fu_set = sanitize_key( $fp['result_set'] ?? 'both' );
                                        if ( ! in_array( $fu_set, array( 'both', 'day', 'night' ), true ) ) {
                                            $fu_set = 'both';
                                        }
                                        $fu_answer['products'][] = array(
                                            'id'              => absint( $fp['id'] ?? 0 ),
                                            'variation_id'    => absint( $fp['variation_id'] ?? 0 ),
                                            'rank'            => absint( $fp['rank'] ?? 1 ),
                                            'result_category' => $fu_cat,
                                            'result_set'      => $fu_set,
                                        );
                                    }
                                }
                                $fu['answers'][] = $fu_answer;
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

    /* ─── CrocoBlock templates ─── */

    private function get_crocoblock_templates() {
        $templates = array();

        // JetEngine listings
        $posts = get_posts( array(
            'post_type'      => 'jet-engine',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        foreach ( $posts as $p ) {
            $templates[ $p->ID ] = $p->post_title;
        }

        // Also check jet-listing type if available
        $jet_listings = get_posts( array(
            'post_type'      => 'jet-engine-booking',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        foreach ( $jet_listings as $p ) {
            $templates[ $p->ID ] = $p->post_title;
        }

        return $templates;
    }
}

new ASQ_Admin();

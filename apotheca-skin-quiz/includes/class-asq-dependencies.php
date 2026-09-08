<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Companion-plugin dependency notice.
 *
 * Apotheca Skin Quiz runs on its own, but its "read next" article block needs
 * the shared Skin Topic taxonomy (ild_topic) that the Apotheca Ingredient
 * List Decoder registers. Without the decoder active there is no Skin Topic
 * taxonomy, so no topics and no read-next.
 *
 * WordPress's own "Requires Plugins" header only resolves plugins from the
 * wordpress.org directory, so it cannot point at a custom companion like the
 * decoder without breaking activation. Instead this surfaces the dependency
 * two ways that do not block activation: a line under the plugin on the
 * Plugins screen, and a dashboard warning when the decoder is missing.
 */
class ASQ_Dependencies {

    /** Display name of the required companion plugin. */
    const DECODER_NAME = 'Apotheca Ingredient List Decoder';

    public function __construct() {
        add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
    }

    /**
     * Is the Ingredient List Decoder present? We test for the taxonomy it
     * registers (what read-next actually needs) and for its post-types class
     * as an early fallback before init has run.
     */
    public static function decoder_active() {
        return taxonomy_exists( 'ild_topic' ) || class_exists( 'ILD_Post_Types' );
    }

    /**
     * Add a status line beneath the plugin on the Plugins screen.
     */
    public function row_meta( $links, $file ) {
        if ( ASQ_PLUGIN_BASENAME !== $file ) {
            return $links;
        }

        if ( self::decoder_active() ) {
            $links[] = '<span style="color:#207520;">'
                . esc_html__( 'Companion active: Apotheca Ingredient List Decoder', 'apotheca-skin-quiz' )
                . '</span>';
        } else {
            $links[] = '<span style="color:#b32d2e;">'
                /* translators: %s: companion plugin name */
                . sprintf( esc_html__( 'Requires the %s for Skin Topic read-next', 'apotheca-skin-quiz' ), self::DECODER_NAME )
                . '</span>';
        }

        return $links;
    }

    /**
     * Show a warning on the Plugins screen and the quiz admin screens when the
     * decoder is not active. Not shown elsewhere, so it never nags.
     */
    public function maybe_notice() {
        if ( self::decoder_active() || ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $on_plugins = $screen && 'plugins' === $screen->id;
        $on_quiz    = $screen && isset( $screen->post_type ) && 'apotheca_skin_quiz' === $screen->post_type;
        if ( ! $on_plugins && ! $on_quiz ) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>'
            . esc_html__( 'Apotheca Skin Quiz', 'apotheca-skin-quiz' ) . '</strong> '
            /* translators: %s: companion plugin name */
            . sprintf(
                esc_html__( 'works on its own, but its "read next" articles need the %s active, it provides the shared Skin Topic taxonomy. Without it, no topics will show.', 'apotheca-skin-quiz' ),
                '<strong>' . esc_html( self::DECODER_NAME ) . '</strong>'
            )
            . '</p></div>';
    }
}

new ASQ_Dependencies();

<?php
/**
 * Plugin Name: Apotheca Skin Quiz
 * Plugin URI: https://github.com/KrullDNA/Apotheca-Skin-Quiz
 * Description: Apotheca Skin Quiz, a multi-step quiz with full Elementor styling controls and a branded, responsive results email. Forked from Product Finder at v1.0.0; runs independently alongside it.
 * Version: 2.0.7
 * Author: KrullDNA
 * Author URI: https://github.com/KrullDNA
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apotheca-skin-quiz
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ASQ_VERSION', '2.0.7' );
define( 'ASQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASQ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Apotheca Skin Quiz class.
 */
final class Apotheca_Skin_Quiz {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-config.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-engine.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-presenter.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-read-next.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-post-type.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-admin.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-frontend.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-ajax.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-email.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-leads.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-reports.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-result-wording.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-articles-admin.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-privacy.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-connectors.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-integrations.php';
        require_once ASQ_PLUGIN_DIR . 'includes/class-asq-dependencies.php';

        if ( did_action( 'elementor/loaded' ) ) {
            require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-elementor.php';
        } else {
            add_action( 'elementor/loaded', function () {
                require_once ASQ_PLUGIN_DIR . 'elementor/class-asq-elementor.php';
            } );
        }
    }

    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    public function activate() {
        ASQ_Post_Type::register();
        ASQ_Leads::install();
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'apotheca-skin-quiz', false, dirname( ASQ_PLUGIN_BASENAME ) . '/languages/' );
    }
}

/**
 * Boot the plugin.
 */
function apotheca_skin_quiz() {
    return Apotheca_Skin_Quiz::instance();
}

add_action( 'plugins_loaded', 'apotheca_skin_quiz' );

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * "Read-next articles" admin screen.
 *
 * One repeater per reading (and one for the medical gate). Each row is an
 * article: title, URL, and optional short description and image URL. When a
 * fired reading has articles here, they are shown as the read-next grid in the
 * result instead of auto-pulled posts (see ASQ_Read_Next). A reading left empty
 * falls back to the automatic behaviour, so nothing ever comes up blank.
 */
class ASQ_Articles_Admin {

    public function __construct() {
        // Priority 17: after Result wording (16), before Integrations (20).
        add_action( 'admin_menu', array( $this, 'register_menu' ), 17 );
    }

    public static function capability() {
        return ASQ_Leads::capability();
    }

    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=apotheca_skin_quiz',
            __( 'Read-next articles', 'apotheca-skin-quiz' ),
            __( 'Read-next articles', 'apotheca-skin-quiz' ),
            self::capability(),
            'asq-read-next-articles',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        if ( ! current_user_can( self::capability() ) ) {
            wp_die( esc_html__( 'You do not have permission to edit the read-next articles.', 'apotheca-skin-quiz' ) );
        }

        if ( ! empty( $_POST['asq_articles_save'] ) ) {
            check_admin_referer( 'asq_read_next_articles' );
            $raw = isset( $_POST['asq_articles'] ) ? wp_unslash( $_POST['asq_articles'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised field-by-field in save_articles()
            ASQ_Read_Next::save_articles( (array) $raw );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Read-next articles saved.', 'apotheca-skin-quiz' ) . '</p></div>';
        }

        $stored = ASQ_Read_Next::articles();
        $labels = ASQ_Config::findings();

        // The readings, then a "gate" pseudo-reading at the end.
        $groups = array();
        foreach ( $labels as $fid => $label ) {
            if ( 'F11' === $fid ) {
                continue;
            }
            $groups[ $fid ] = $label;
        }
        $groups['gate'] = __( 'Medical gate response', 'apotheca-skin-quiz' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Read-next articles', 'apotheca-skin-quiz' ); ?></h1>
            <p class="description" style="max-width:820px;">
                <?php esc_html_e( 'Add the articles you want shown under each result, as a grid. A URL is all you really need: if it points to a post on this site, the card pulls that post\'s featured image, title and excerpt automatically. Fill in the title, description or image only to override what the post provides, or when the URL is external. Leave a reading empty to let the quiz pull tagged blog posts automatically instead. Shared across every quiz.', 'apotheca-skin-quiz' ); ?>
            </p>

            <form method="post">
                <?php wp_nonce_field( 'asq_read_next_articles' ); ?>
                <input type="hidden" name="asq_articles_save" value="1">

                <?php foreach ( $groups as $fid => $label ) :
                    $rows = isset( $stored[ $fid ] ) ? (array) $stored[ $fid ] : array();
                    ?>
                    <fieldset class="asq-dn-fieldset" style="margin:0 0 18px;">
                        <legend><strong><?php echo esc_html( 'gate' === $fid ? '' : $fid ); ?></strong> <?php echo esc_html( 'gate' === $fid ? $label : ' · ' . $label ); ?></legend>

                        <div class="asq-articles-rows" data-fid="<?php echo esc_attr( $fid ); ?>" data-next="<?php echo esc_attr( count( $rows ) ); ?>">
                            <?php foreach ( $rows as $i => $row ) : ?>
                                <?php $this->render_row( $fid, (int) $i, (array) $row ); ?>
                            <?php endforeach; ?>
                        </div>

                        <p><button type="button" class="button asq-article-add" data-fid="<?php echo esc_attr( $fid ); ?>"><?php esc_html_e( '+ Add article', 'apotheca-skin-quiz' ); ?></button></p>
                    </fieldset>
                <?php endforeach; ?>

                <?php submit_button( __( 'Save read-next articles', 'apotheca-skin-quiz' ) ); ?>
            </form>
        </div>

        <script>
        jQuery(function($){
            function rowMarkup(fid, i){
                var n = '<?php echo esc_js( '__NAME__' ); ?>';
                return <?php echo wp_json_encode( $this->row_template() ); ?>
                    .replace(/__FID__/g, fid)
                    .replace(/__I__/g, i);
            }
            $('.asq-article-add').on('click', function(){
                var fid  = $(this).data('fid');
                var $box = $('.asq-articles-rows[data-fid="' + fid + '"]');
                var i    = parseInt($box.attr('data-next'), 10) || 0;
                $box.append(rowMarkup(fid, i));
                $box.attr('data-next', i + 1);
            });
            $(document).on('click', '.asq-article-remove', function(){
                $(this).closest('.asq-article-row').remove();
            });
        });
        </script>
        <?php
    }

    /** One editable article row. */
    private function render_row( $fid, $i, $row ) {
        $title = isset( $row['title'] ) ? $row['title'] : '';
        $url   = isset( $row['url'] ) ? $row['url'] : '';
        $desc  = isset( $row['desc'] ) ? $row['desc'] : '';
        $image = isset( $row['image'] ) ? $row['image'] : '';
        $base  = 'asq_articles[' . $fid . '][' . $i . ']';
        ?>
        <div class="asq-article-row" style="border:1px solid #dcdcde;border-radius:6px;padding:10px 12px;margin:0 0 8px;background:#fff;">
            <p style="margin:0 0 6px;display:flex;gap:8px;flex-wrap:wrap;">
                <input type="text" style="flex:2;min-width:220px;" name="<?php echo esc_attr( $base ); ?>[title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Article title', 'apotheca-skin-quiz' ); ?>">
                <input type="url" style="flex:2;min-width:220px;" name="<?php echo esc_attr( $base ); ?>[url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php esc_attr_e( 'https://…', 'apotheca-skin-quiz' ); ?>">
            </p>
            <p style="margin:0;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="text" style="flex:2;min-width:220px;" name="<?php echo esc_attr( $base ); ?>[desc]" value="<?php echo esc_attr( $desc ); ?>" placeholder="<?php esc_attr_e( 'Short description (optional)', 'apotheca-skin-quiz' ); ?>">
                <input type="url" style="flex:2;min-width:220px;" name="<?php echo esc_attr( $base ); ?>[image]" value="<?php echo esc_attr( $image ); ?>" placeholder="<?php esc_attr_e( 'Image URL (optional)', 'apotheca-skin-quiz' ); ?>">
                <a href="#" class="asq-article-remove" style="color:#b32d2e;white-space:nowrap;"><?php esc_html_e( 'Remove', 'apotheca-skin-quiz' ); ?></a>
            </p>
        </div>
        <?php
    }

    /** The blank-row template used by the "add" button (tokens __FID__/__I__). */
    private function row_template() {
        ob_start();
        $this->render_row( '__FID__', '__I__', array() );
        return ob_get_clean();
    }
}

new ASQ_Articles_Admin();

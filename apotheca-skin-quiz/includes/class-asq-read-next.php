<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Read-next, against the shared Skin Topic taxonomy.
 *
 * Reuses the taxonomy the Ingredient List Decoder registers (ild_topic). It
 * does not register a taxonomy of its own. Each fired finding maps to one or
 * two Skin Topic terms (see asq-quiz-config.php); this queries published posts
 * carrying those terms, ranks them by how many of the terms they share and
 * then by recency, caps the list, and excludes the page the quiz is on.
 *
 * If no post shares at least one term, it returns nothing. It never falls back
 * to recent or popular posts. If the decoder is not active, the taxonomy does
 * not exist, so it also returns nothing.
 */
class ASQ_Read_Next {

    /** How many candidate posts to pull before ranking. */
    const POOL = 20;

    /** Cap for a normal reading. */
    const CAP = 3;

    public function __construct() {
        // Clear the cache whenever a post is saved.
        add_action( 'save_post', array( __CLASS__, 'bump_cache' ) );
        add_action( 'deleted_post', array( __CLASS__, 'bump_cache' ) );
    }

    /**
     * The shared taxonomy slug, filterable.
     */
    public static function taxonomy() {
        return apply_filters( 'asq_topic_taxonomy', 'ild_topic' );
    }

    /**
     * Read-next for a normal reading: the union of the fired findings' terms.
     *
     * @param array $findings   Engine output (each with an 'id').
     * @param int   $exclude_id Page id to exclude (the page the quiz is on).
     * @return array Up to three cards: { title, url, excerpt, thumb }.
     */
    public static function for_findings( $findings, $exclude_id = 0 ) {
        $slugs = array();
        foreach ( (array) $findings as $f ) {
            $id = isset( $f['id'] ) ? $f['id'] : '';
            foreach ( ASQ_Config::finding_topics( $id ) as $slug ) {
                $slugs[ $slug ] = true;
            }
        }
        return self::query( array_keys( $slugs ), $exclude_id, self::CAP );
    }

    /**
     * Read-next for the medical gate: at most one general article.
     */
    public static function for_gate( $exclude_id = 0 ) {
        return self::query( ASQ_Config::gate_topics(), $exclude_id, 1 );
    }

    /**
     * Query, rank and cap. Cached per finding-set until a post is saved.
     */
    public static function query( $slugs, $exclude_id, $cap ) {
        $slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', (array) $slugs ) ) ) );
        if ( empty( $slugs ) ) {
            return array();
        }

        $taxonomy = self::taxonomy();
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }

        // Only query terms that actually exist, so a mistyped slug just yields
        // nothing rather than a broken query.
        $existing = array();
        foreach ( $slugs as $slug ) {
            if ( get_term_by( 'slug', $slug, $taxonomy ) ) {
                $existing[] = $slug;
            }
        }
        if ( empty( $existing ) ) {
            return array();
        }
        sort( $existing );

        $cache_key = 'asq_rn_' . self::cache_ver() . '_' . md5( $taxonomy . '|' . implode( ',', $existing ) . '|' . (int) $cap . '|' . (int) $exclude_id );
        $cached    = get_transient( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $args = array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => self::POOL,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'tax_query'              => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $existing,
                ),
            ),
        );
        if ( $exclude_id ) {
            $args['post__not_in'] = array( (int) $exclude_id );
        }

        $ids = get_posts( $args );
        if ( empty( $ids ) ) {
            set_transient( $cache_key, array(), 12 * HOUR_IN_SECONDS );
            return array();
        }

        // Rank by number of matching terms, then recency. The query already
        // returns newest first, so a stable sort on the match count keeps the
        // recency order within each tier.
        $ranked = array();
        foreach ( $ids as $pos => $id ) {
            $post_slugs = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'slugs' ) );
            if ( is_wp_error( $post_slugs ) ) {
                $post_slugs = array();
            }
            $matches  = count( array_intersect( $existing, $post_slugs ) );
            $ranked[] = array(
                'id'      => $id,
                'matches' => $matches,
                'order'   => $pos, // preserves recency for the tiebreak
            );
        }
        usort( $ranked, function ( $a, $b ) {
            if ( $a['matches'] !== $b['matches'] ) {
                return $b['matches'] <=> $a['matches'];
            }
            return $a['order'] <=> $b['order'];
        } );

        $cards = array();
        foreach ( array_slice( $ranked, 0, $cap ) as $row ) {
            $id    = $row['id'];
            $cards[] = array(
                'title'   => get_the_title( $id ),
                'url'     => get_permalink( $id ),
                'excerpt' => self::excerpt( $id ),
                'thumb'   => get_the_post_thumbnail_url( $id, 'medium' ) ?: '',
            );
        }

        set_transient( $cache_key, $cards, 12 * HOUR_IN_SECONDS );
        return $cards;
    }

    /**
     * A short plain-text excerpt for a post.
     */
    protected static function excerpt( $id ) {
        $text = get_the_excerpt( $id );
        $text = wp_strip_all_tags( (string) $text );
        return wp_trim_words( $text, 22, '…' );
    }

    /* ────────── cache versioning ────────── */

    public static function cache_ver() {
        return (int) get_option( 'asq_rn_cache_ver', 1 );
    }

    public static function bump_cache() {
        update_option( 'asq_rn_cache_ver', self::cache_ver() + 1, false );
    }
}

new ASQ_Read_Next();

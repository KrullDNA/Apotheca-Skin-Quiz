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

    /**
     * Option holding manually-entered articles, keyed by finding id (F1..F12)
     * and 'gate'. Each value is a list of rows: { title, url, desc, image }.
     * When a fired reading has manual articles they are shown instead of the
     * auto-pulled posts, so the owner can curate exactly what appears.
     */
    const OPTION_ARTICLES = 'asq_readnext_articles';

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
        // Manually-curated articles for the fired readings take priority.
        $manual = self::manual_for_findings( $findings, self::CAP );
        if ( ! empty( $manual ) ) {
            return $manual;
        }

        // Otherwise, posts tagged with the readings' Skin Topics.
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
        $manual = self::manual_for_gate( 1 );
        if ( ! empty( $manual ) ) {
            return $manual;
        }
        return self::query( ASQ_Config::gate_topics(), $exclude_id, 1 );
    }

    /* ────────── Manually-curated articles ────────── */

    /** All stored manual articles, keyed by finding id and 'gate'. */
    public static function articles() {
        $a = get_option( self::OPTION_ARTICLES, array() );
        return is_array( $a ) ? $a : array();
    }

    /** Turn a stored row into a read-next card. */
    protected static function card_from_row( $row ) {
        if ( ! is_array( $row ) ) {
            return array( 'title' => '', 'url' => '', 'excerpt' => '', 'thumb' => '' );
        }
        return array(
            'title'   => isset( $row['title'] ) ? $row['title'] : '',
            'url'     => isset( $row['url'] ) ? $row['url'] : '',
            'excerpt' => isset( $row['desc'] ) ? $row['desc'] : '',
            'thumb'   => isset( $row['image'] ) ? $row['image'] : '',
        );
    }

    /** Manual cards for the fired readings, in order, deduped by URL, capped. */
    protected static function manual_for_findings( $findings, $cap ) {
        $all   = self::articles();
        $cards = array();
        $seen  = array();
        foreach ( (array) $findings as $f ) {
            $id = isset( $f['id'] ) ? $f['id'] : '';
            if ( '' === $id || empty( $all[ $id ] ) ) {
                continue;
            }
            foreach ( (array) $all[ $id ] as $row ) {
                $card = self::card_from_row( $row );
                if ( '' === $card['url'] || isset( $seen[ $card['url'] ] ) ) {
                    continue;
                }
                $seen[ $card['url'] ] = true;
                $cards[]              = $card;
                if ( count( $cards ) >= $cap ) {
                    return $cards;
                }
            }
        }
        return $cards;
    }

    /** Manual cards for the medical gate. */
    protected static function manual_for_gate( $cap ) {
        $all = self::articles();
        if ( empty( $all['gate'] ) ) {
            return array();
        }
        $cards = array();
        foreach ( (array) $all['gate'] as $row ) {
            $card = self::card_from_row( $row );
            if ( '' === $card['url'] ) {
                continue;
            }
            $cards[] = $card;
            if ( count( $cards ) >= $cap ) {
                break;
            }
        }
        return $cards;
    }

    /**
     * Sanitise and store the manual articles from the admin screen. Empty rows
     * (no title and no URL) are dropped.
     *
     * @param array $raw The asq_articles POST array (already unslashed).
     */
    public static function save_articles( $raw ) {
        $clean = array();
        foreach ( (array) $raw as $fid => $rows ) {
            $fid = sanitize_text_field( $fid );
            if ( ! is_array( $rows ) ) {
                continue;
            }
            $list = array();
            foreach ( $rows as $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $title = isset( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '';
                $url   = isset( $row['url'] ) ? esc_url_raw( trim( (string) $row['url'] ) ) : '';
                $desc  = isset( $row['desc'] ) ? sanitize_text_field( $row['desc'] ) : '';
                $image = isset( $row['image'] ) ? esc_url_raw( trim( (string) $row['image'] ) ) : '';
                if ( '' === $title && '' === $url ) {
                    continue; // an empty row
                }
                $list[] = array( 'title' => $title, 'url' => $url, 'desc' => $desc, 'image' => $image );
            }
            if ( $list ) {
                $clean[ $fid ] = $list;
            }
        }
        update_option( self::OPTION_ARTICLES, $clean, false );
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

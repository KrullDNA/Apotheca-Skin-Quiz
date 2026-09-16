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
    public static function for_findings( $findings, $exclude_id = 0, $cap = self::CAP ) {
        $cap = max( 1, (int) $cap );

        // Manually-curated articles for the fired readings take priority.
        $manual = self::manual_for_findings( $findings, $cap );
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
        return self::query( array_keys( $slugs ), $exclude_id, $cap );
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

    /**
     * Turn a stored row into a read-next card. Anything left blank is filled
     * from the linked article when the URL points to a post on this site: the
     * featured image becomes the thumbnail, and the post title and excerpt fill
     * in too. Values entered by hand always win, and an external URL (not a
     * local post) simply leaves the blanks empty.
     */
    protected static function card_from_row( $row ) {
        if ( ! is_array( $row ) ) {
            return array( 'title' => '', 'url' => '', 'excerpt' => '', 'thumb' => '' );
        }

        $title = isset( $row['title'] ) ? $row['title'] : '';
        $url   = isset( $row['url'] ) ? $row['url'] : '';
        $desc  = isset( $row['desc'] ) ? $row['desc'] : '';
        $thumb = isset( $row['image'] ) ? $row['image'] : '';
        $date  = 0;

        if ( '' !== $url && function_exists( 'url_to_postid' ) ) {
            $pid = url_to_postid( $url );
            if ( $pid ) {
                // A local post: fill any blanks and take its date for sorting.
                $date = (int) get_post_time( 'U', true, $pid );
                if ( '' === $thumb ) {
                    $featured = get_the_post_thumbnail_url( $pid, 'medium' );
                    if ( $featured ) {
                        $thumb = $featured;
                    }
                }
                if ( '' === $title ) {
                    $title = get_the_title( $pid );
                }
                if ( '' === $desc ) {
                    $desc = self::excerpt( $pid );
                }
            }
        }

        return array(
            'title'   => $title,
            'url'     => $url,
            'excerpt' => $desc,
            'thumb'   => $thumb,
            'date'    => $date,
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
     * Query, rank and cap, returning up to $cap read-next cards.
     */
    public static function query( $slugs, $exclude_id, $cap ) {
        $cards = array();
        foreach ( self::ranked_ids( $slugs, $exclude_id, $cap ) as $id ) {
            $cards[] = array(
                'title'   => get_the_title( $id ),
                'url'     => get_permalink( $id ),
                'excerpt' => self::excerpt( $id ),
                'thumb'   => get_the_post_thumbnail_url( $id, 'medium' ) ?: '',
                'date'    => (int) get_post_time( 'U', true, $id ),
            );
        }
        return $cards;
    }

    /**
     * Query, rank and cap, returning the ordered post ids. Cached per
     * finding-set until a post is saved. This is the shared core used both by
     * the built-in cards (query) and the JetEngine listing (post_ids_*).
     */
    public static function ranked_ids( $slugs, $exclude_id, $cap ) {
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

        $cache_key = 'asq_rnid_' . self::cache_ver() . '_' . md5( $taxonomy . '|' . implode( ',', $existing ) . '|' . (int) $cap . '|' . (int) $exclude_id );
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

        $out = array();
        foreach ( array_slice( $ranked, 0, $cap ) as $row ) {
            $out[] = (int) $row['id'];
        }

        set_transient( $cache_key, $out, 12 * HOUR_IN_SECONDS );
        return $out;
    }

    /* ────────── JetEngine listing mode ────────── */

    /**
     * The ordered post ids the read-next would show for a set of findings, so a
     * JetEngine Listing can render exactly those posts. Manual articles that are
     * local posts are honoured first (external links can't feed a post listing);
     * otherwise the topic-ranked posts are used.
     *
     * @param array $findings   Engine output (each with an 'id').
     * @param int   $exclude_id Page id to exclude (the page the quiz is on).
     * @param int   $cap        Max ids.
     * @return int[] Ordered, unique post ids.
     */
    public static function post_ids_for_findings( $findings, $exclude_id = 0, $cap = self::CAP ) {
        $cap  = max( 1, (int) $cap );
        $ids  = array();
        $seen = array();
        $all  = self::articles();

        foreach ( (array) $findings as $f ) {
            $fid = isset( $f['id'] ) ? $f['id'] : '';
            if ( '' === $fid || empty( $all[ $fid ] ) ) {
                continue;
            }
            foreach ( (array) $all[ $fid ] as $row ) {
                $url = isset( $row['url'] ) ? $row['url'] : '';
                $pid = ( '' !== $url && function_exists( 'url_to_postid' ) ) ? (int) url_to_postid( $url ) : 0;
                if ( $pid && ! isset( $seen[ $pid ] ) ) {
                    $seen[ $pid ] = true;
                    $ids[]        = $pid;
                    if ( count( $ids ) >= $cap ) {
                        return $ids;
                    }
                }
            }
        }
        if ( ! empty( $ids ) ) {
            return $ids;
        }

        // No curated local posts, so fall back to the topic-ranked posts.
        $slugs = array();
        foreach ( (array) $findings as $f ) {
            $fid = isset( $f['id'] ) ? $f['id'] : '';
            foreach ( ASQ_Config::finding_topics( $fid ) as $slug ) {
                $slugs[ $slug ] = true;
            }
        }
        return self::ranked_ids( array_keys( $slugs ), $exclude_id, $cap );
    }

    /**
     * Render a JetEngine Listing restricted to exactly the given post ids, in
     * that order. Returns '' when JetEngine is not active, no ids, or the
     * listing produces nothing, so the caller can fall back to the built-in
     * cards and the results screen can never break.
     *
     * @param int   $listing_id     JetEngine listing (a jet-engine-listing post id).
     * @param int[] $post_ids       The posts to show.
     * @param int   $columns        Grid columns on desktop.
     * @param int   $columns_tablet Grid columns on tablet (0 to leave to the listing).
     * @param int   $columns_mobile Grid columns on mobile (0 to leave to the listing).
     * @return string HTML, or '' to fall back.
     */
    public static function render_jet_listing( $listing_id, $post_ids, $columns = 3, $columns_tablet = 0, $columns_mobile = 0 ) {
        $listing_id     = absint( $listing_id );
        $post_ids       = array_values( array_filter( array_map( 'absint', (array) $post_ids ) ) );
        $columns        = max( 1, (int) $columns );
        $columns_tablet = max( 0, (int) $columns_tablet );
        $columns_mobile = max( 0, (int) $columns_mobile );

        if ( ! $listing_id || empty( $post_ids ) || ! function_exists( 'jet_engine' ) ) {
            return '';
        }
        $engine = jet_engine();
        if ( ! $engine || empty( $engine->listings ) ) {
            return '';
        }

        // Restrict the listing's query to exactly our posts, in our order, for
        // the duration of this one render only.
        $inject = function ( $args ) use ( $post_ids ) {
            $args['post__in']            = $post_ids;
            $args['orderby']             = 'post__in';
            $args['posts_per_page']      = count( $post_ids );
            $args['ignore_sticky_posts'] = true;
            $args['post__not_in']        = array();
            unset( $args['tax_query'], $args['meta_query'], $args['s'], $args['paged'] );
            return $args;
        };

        add_filter( 'jet-engine/listing/grid/posts-query-args', $inject, 999 );

        $html = '';
        try {
            // Preferred: JetEngine's programmatic grid render. The settings keys
            // include both spellings of the listing id, since JetEngine has used
            // the misspelled "lisitng_id" in the grid render historically.
            if ( method_exists( $engine->listings, 'get_render_instance' ) ) {
                $settings = array(
                    'lisitng_id'          => $listing_id,
                    'listing_id'          => $listing_id,
                    'columns'             => $columns,
                    'columns_tablet'      => $columns_tablet ? $columns_tablet : $columns,
                    'columns_mobile'      => $columns_mobile ? $columns_mobile : 1,
                    'posts_num'           => count( $post_ids ),
                    'is_archive_template' => false,
                );
                $render = $engine->listings->get_render_instance( 'listing-grid', $settings );
                if ( $render && method_exists( $render, 'render' ) ) {
                    ob_start();
                    $render->render();
                    $html = (string) ob_get_clean();
                }
            }

            // Fallback: the listing shortcode, but only if it is actually
            // registered (otherwise do_shortcode returns the raw text).
            if ( '' === trim( $html ) && function_exists( 'shortcode_exists' ) && shortcode_exists( 'jet_engine_listing' ) ) {
                $atts  = 'listing_id="' . $listing_id . '" columns="' . $columns . '"';
                $atts .= $columns_tablet ? ' columns_tablet="' . $columns_tablet . '"' : '';
                $atts .= $columns_mobile ? ' columns_mobile="' . $columns_mobile . '"' : '';
                $atts .= ' posts_num="' . count( $post_ids ) . '"';
                $html  = do_shortcode( '[jet_engine_listing ' . $atts . ']' );
            }
        } catch ( \Throwable $e ) {
            $html = '';
        }

        remove_filter( 'jet-engine/listing/grid/posts-query-args', $inject, 999 );

        $html = is_string( $html ) ? trim( $html ) : '';

        // Never show a raw, unprocessed shortcode. If that is all we got, treat
        // it as a failure so the caller falls back to the built-in cards.
        if ( '' === $html || false !== strpos( $html, '[jet_engine_listing' ) ) {
            return '';
        }
        return $html;
    }

    /* ────────── sorting (front-end sort dropdown) ────────── */

    /** The sort keys the read-next dropdown offers. */
    public static function sort_keys() {
        return array( 'relevance', 'name_asc', 'name_desc', 'date_asc', 'date_desc' );
    }

    /** Normalise a requested sort to a known key, defaulting to relevance. */
    public static function clean_sort( $sort ) {
        $sort = is_string( $sort ) ? $sort : '';
        return in_array( $sort, self::sort_keys(), true ) ? $sort : 'relevance';
    }

    /**
     * Sort a list of post ids for the JetEngine listing. Relevance keeps the
     * ranked order the finding query produced.
     */
    public static function sort_ids( $ids, $sort ) {
        $ids  = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
        $sort = self::clean_sort( $sort );
        if ( 'relevance' === $sort || count( $ids ) < 2 ) {
            return $ids;
        }
        $rows = array();
        foreach ( $ids as $id ) {
            $rows[] = array(
                'id'    => $id,
                'title' => get_the_title( $id ),
                'date'  => (int) get_post_time( 'U', true, $id ),
            );
        }
        usort( $rows, function ( $a, $b ) use ( $sort ) {
            return self::cmp_items( $a, $b, $sort );
        } );
        return array_map( function ( $r ) { return (int) $r['id']; }, $rows );
    }

    /**
     * Sort the built-in cards. Relevance keeps their order as returned.
     */
    public static function sort_cards( $cards, $sort ) {
        $cards = array_values( (array) $cards );
        $sort  = self::clean_sort( $sort );
        if ( 'relevance' === $sort || count( $cards ) < 2 ) {
            return $cards;
        }
        usort( $cards, function ( $a, $b ) use ( $sort ) {
            return self::cmp_items(
                array( 'title' => isset( $a['title'] ) ? $a['title'] : '', 'date' => isset( $a['date'] ) ? (int) $a['date'] : 0 ),
                array( 'title' => isset( $b['title'] ) ? $b['title'] : '', 'date' => isset( $b['date'] ) ? (int) $b['date'] : 0 ),
                $sort
            );
        } );
        return $cards;
    }

    /** Compare two { title, date } items for the given sort key. */
    protected static function cmp_items( $a, $b, $sort ) {
        switch ( $sort ) {
            case 'name_asc':
                return strcasecmp( (string) $a['title'], (string) $b['title'] );
            case 'name_desc':
                return strcasecmp( (string) $b['title'], (string) $a['title'] );
            case 'date_asc':
                return ( (int) $a['date'] ) <=> ( (int) $b['date'] );
            case 'date_desc':
                return ( (int) $b['date'] ) <=> ( (int) $a['date'] );
        }
        return 0;
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

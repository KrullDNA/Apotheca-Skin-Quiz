<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Turns the engine's fired findings into the reading.
 *
 * Four sections in fixed order: what you're describing, what it probably
 * isn't, one or two things worth trying, and read next. Each fired finding
 * contributes copy from the phrasing file to one or more sections. Every
 * sentence lives in asq-phrasing.php; this class only assembles and renders.
 *
 * All copy here is authored by us or drawn from the fixed question config, so
 * there is no visitor free text in the reading. The emphasis spans are ours.
 */
class ASQ_Presenter {

    /** @var array|null Cached phrasing. */
    protected static $phrasing = null;

    /**
     * @var string The Ingredient List Decoder page URL for the F12 link, set
     * per render from the Elementor control. Empty means the {decoder} words
     * render as plain text rather than a broken link.
     */
    protected static $decoder_url = '';

    /**
     * @var string The concern(s) she ticked on the medical-gate question, for
     * the {ticked} token in the gate copy. Empty outside a gate reading.
     */
    protected static $gate_ticked = '';

    /** @var bool Whether read-next links open in a new tab (Elementor control). */
    protected static $rn_new_tab = false;

    /**
     * The internal handshake between the quiz and the decoder. Hard-coded on
     * both sides on purpose: a name read from settings in two places is a quiet
     * way to break the link.
     */
    const DECODER_FROM_KEY   = 'from';
    const DECODER_FROM_VALUE = 'skin-quiz';

    /** Max entries in the "one or two things worth trying" section. */
    const MAX_TRIES = 2;

    /**
     * The option holding editable result wording, overlaid on the code copy in
     * asq-phrasing.php. Only wording is stored; the tokens ({a:Qn}, {em}…{/em},
     * {decoder}…{/decoder}) live inside the wording and are preserved verbatim.
     */
    const OPTION_PHRASING = 'asq_result_copy';

    public static function phrasing() {
        if ( null === self::$phrasing ) {
            $base           = require ASQ_PLUGIN_DIR . 'includes/asq-phrasing.php';
            self::$phrasing = self::apply_phrasing_overrides( $base );
        }
        return self::$phrasing;
    }

    /** The raw code defaults, without overrides, for the editor and diffing. */
    public static function phrasing_defaults() {
        return require ASQ_PLUGIN_DIR . 'includes/asq-phrasing.php';
    }

    /** The stored result-wording overrides, or an empty array. */
    public static function phrasing_overrides() {
        $saved = get_option( self::OPTION_PHRASING, array() );
        return is_array( $saved ) ? $saved : array();
    }

    /**
     * Overlay saved result wording on the code defaults. Section headings, the
     * read-next lines, the gate copy and each finding's paragraphs can be
     * replaced; a finding's "worth trying" keeps its grouping key from code so
     * de-duplication still works, only the text changes.
     */
    protected static function apply_phrasing_overrides( $p ) {
        $ov = get_option( self::OPTION_PHRASING, array() );
        if ( ! is_array( $ov ) || empty( $ov ) ) {
            return $p;
        }

        if ( ! empty( $ov['sections'] ) && is_array( $ov['sections'] ) ) {
            foreach ( $ov['sections'] as $k => $v ) {
                if ( '' !== $v && isset( $p['sections'][ $k ] ) ) {
                    $p['sections'][ $k ] = $v;
                }
            }
        }
        foreach ( array( 'read_next_intro', 'read_more' ) as $k ) {
            if ( ! empty( $ov[ $k ] ) ) {
                $p[ $k ] = $ov[ $k ];
            }
        }
        if ( ! empty( $ov['gate'] ) && is_array( $ov['gate'] ) ) {
            foreach ( $ov['gate'] as $k => $v ) {
                if ( '' !== $v ) {
                    $p['gate'][ $k ] = $v;
                }
            }
        }
        if ( ! empty( $ov['findings'] ) && is_array( $ov['findings'] ) ) {
            foreach ( $ov['findings'] as $fid => $fields ) {
                if ( ! is_array( $fields ) ) {
                    continue;
                }
                foreach ( array( 'describing', 'probably_not' ) as $k ) {
                    if ( isset( $fields[ $k ] ) && '' !== $fields[ $k ] ) {
                        $p['findings'][ $fid ][ $k ] = $fields[ $k ];
                    }
                }
                if ( isset( $fields['worth_trying'] ) && '' !== $fields['worth_trying'] ) {
                    $key = isset( $p['findings'][ $fid ]['worth_trying']['key'] )
                        ? $p['findings'][ $fid ]['worth_trying']['key']
                        : $fid;
                    $p['findings'][ $fid ]['worth_trying'] = array( 'key' => $key, 'text' => $fields['worth_trying'] );
                }
            }
        }

        return $p;
    }

    /**
     * Sanitise and store result-wording overrides from the admin screen. Tokens
     * are plain-text markers, so textarea sanitising keeps them intact. Only
     * values that differ from the code default are stored, so a blank field, or
     * one left at the default, reverts to the built-in copy.
     *
     * @param array $raw The asq_result POST array (already unslashed).
     */
    public static function save_phrasing_overrides( $raw ) {
        $def   = self::phrasing_defaults();
        $clean = array();

        // Section headings.
        if ( ! empty( $raw['sections'] ) && is_array( $raw['sections'] ) ) {
            foreach ( $raw['sections'] as $k => $v ) {
                $v = sanitize_text_field( $v );
                if ( '' !== $v && isset( $def['sections'][ $k ] ) && $v !== $def['sections'][ $k ] ) {
                    $clean['sections'][ $k ] = $v;
                }
            }
        }

        // Read-next lines.
        foreach ( array( 'read_next_intro', 'read_more' ) as $k ) {
            if ( isset( $raw[ $k ] ) ) {
                $v = sanitize_text_field( $raw[ $k ] );
                if ( '' !== $v && $v !== ( $def[ $k ] ?? '' ) ) {
                    $clean[ $k ] = $v;
                }
            }
        }

        // Medical gate copy.
        if ( ! empty( $raw['gate'] ) && is_array( $raw['gate'] ) ) {
            foreach ( $raw['gate'] as $k => $v ) {
                $v = sanitize_textarea_field( $v );
                if ( '' !== $v && isset( $def['gate'][ $k ] ) && $v !== $def['gate'][ $k ] ) {
                    $clean['gate'][ $k ] = $v;
                }
            }
        }

        // Each finding's paragraphs.
        if ( ! empty( $raw['findings'] ) && is_array( $raw['findings'] ) ) {
            foreach ( $raw['findings'] as $fid => $fields ) {
                if ( ! is_array( $fields ) || ! isset( $def['findings'][ $fid ] ) ) {
                    continue;
                }
                $entry = array();
                foreach ( array( 'describing', 'probably_not' ) as $k ) {
                    if ( ! isset( $fields[ $k ] ) ) {
                        continue;
                    }
                    $v   = sanitize_textarea_field( $fields[ $k ] );
                    $cur = $def['findings'][ $fid ][ $k ] ?? '';
                    if ( '' !== $v && $v !== $cur ) {
                        $entry[ $k ] = $v;
                    }
                }
                if ( isset( $fields['worth_trying'] ) ) {
                    $v   = sanitize_textarea_field( $fields['worth_trying'] );
                    $cur = $def['findings'][ $fid ]['worth_trying']['text'] ?? '';
                    if ( '' !== $v && $v !== $cur ) {
                        $entry['worth_trying'] = $v;
                    }
                }
                if ( $entry ) {
                    $clean['findings'][ $fid ] = $entry;
                }
            }
        }

        update_option( self::OPTION_PHRASING, $clean, false );
        self::$phrasing = null; // drop the per-request cache
    }

    /**
     * Build the reading from the engine output.
     *
     * @param array $findings Engine output (ordered, each with id/label/triggers).
     * @param array $answers  { questionIndex => [answerIndex, …] }
     * @return array Either a gate reading ( is_gate => true, heading, body )
     *               or a normal reading ( is_gate => false, sections => [...] ).
     */
    public static function build_reading( $findings, $answers, $articles = array(), $decoder_url = '', $rn_new_tab = false, $followups = array() ) {
        // Set for this render; resolve() reads it when it meets a {decoder} token.
        self::$decoder_url = is_string( $decoder_url ) ? $decoder_url : '';
        self::$rn_new_tab  = (bool) $rn_new_tab;
        self::$gate_ticked = ''; // populated only for a gate reading, below

        $p    = self::phrasing();
        // Follow-up (branch) answers are merged in too, so a reflect-back token
        // like {al:Q8} can quote a branch answer, not just a main one.
        $amap = self::answer_map( $answers, $followups );

        // The medical gate replaces the whole reading. Name back exactly what
        // she flagged, via the {ticked} token, so she isn't left guessing.
        foreach ( (array) $findings as $f ) {
            if ( isset( $f['id'] ) && 'F11' === $f['id'] ) {
                self::$gate_ticked = self::gate_ticked_text( $answers );
                return array(
                    'is_gate'  => true,
                    'heading'  => $p['gate']['heading'],
                    'body'     => self::resolve( $p['gate']['body'], $amap ),
                    'articles' => (array) $articles,
                );
            }
        }

        $fp = isset( $p['findings'] ) ? $p['findings'] : array();

        $describing = array();
        $probably   = array();
        $tries      = array();
        $seen_try   = array();

        foreach ( (array) $findings as $f ) {
            $id = isset( $f['id'] ) ? $f['id'] : '';
            if ( ! isset( $fp[ $id ] ) ) {
                continue;
            }
            $copy = $fp[ $id ];

            if ( ! empty( $copy['describing'] ) ) {
                $describing[] = self::resolve( $copy['describing'], $amap );
            }
            if ( ! empty( $copy['probably_not'] ) ) {
                $probably[] = self::resolve( $copy['probably_not'], $amap );
            }
            if ( ! empty( $copy['worth_trying']['text'] ) ) {
                $key = isset( $copy['worth_trying']['key'] ) ? $copy['worth_trying']['key'] : $id;
                if ( ! isset( $seen_try[ $key ] ) && count( $tries ) < self::MAX_TRIES ) {
                    $seen_try[ $key ] = true;
                    $tries[]          = self::resolve( $copy['worth_trying']['text'], $amap );
                }
            }
        }

        // Assemble the sections in fixed order, dropping any that are empty so
        // a short reading looks deliberate rather than half-filled. The
        // read-next section is added in a later stage from the taxonomy.
        $sections = array();
        if ( $describing ) {
            $sections[] = array( 'key' => 'describing', 'heading' => $p['sections']['describing'], 'paragraphs' => $describing );
        }
        if ( $probably ) {
            $sections[] = array( 'key' => 'probably_not', 'heading' => $p['sections']['probably_not'], 'paragraphs' => $probably );
        }
        if ( $tries ) {
            $sections[] = array( 'key' => 'worth_trying', 'heading' => $p['sections']['worth_trying'], 'paragraphs' => $tries );
        }
        if ( ! empty( $articles ) ) {
            $sections[] = array(
                'key'      => 'read_next',
                'heading'  => $p['sections']['read_next'],
                'intro'    => isset( $p['read_next_intro'] ) ? $p['read_next_intro'] : '',
                'articles' => (array) $articles,
            );
        }

        return array( 'is_gate' => false, 'sections' => $sections );
    }

    /**
     * Build and render the reading to HTML in one step.
     */
    public static function render( $findings, $answers, $articles = array(), $decoder_url = '', $rn_new_tab = false, $followups = array() ) {
        return self::render_html( self::build_reading( $findings, $answers, $articles, $decoder_url, $rn_new_tab, $followups ) );
    }

    /**
     * Render a reading array to HTML.
     */
    public static function render_html( $reading ) {
        $p = self::phrasing();

        if ( ! empty( $reading['is_gate'] ) ) {
            $html  = '<div class="asq-reading asq-reading--gate">';
            $html .= '<h3 class="asq-reading-heading asq-reading-heading--gate">' . esc_html( self::texturize( $reading['heading'] ) ) . '</h3>';
            $html .= '<div class="asq-reading-section asq-reading-section--gate"><p class="asq-reading-p">' . $reading['body'] . '</p></div>';
            if ( ! empty( $reading['articles'] ) ) {
                $intro = isset( $p['gate']['read_next_intro'] ) ? $p['gate']['read_next_intro'] : '';
                $html .= '<section class="asq-reading-section asq-reading-section--read_next">';
                if ( $intro ) {
                    $html .= '<p class="asq-reading-p asq-readnext-intro">' . esc_html( self::texturize( $intro ) ) . '</p>';
                }
                $html .= self::render_articles( $reading['articles'] );
                $html .= '</section>';
            }
            $html .= '</div>';
            return $html;
        }

        $html = '<div class="asq-reading">';
        foreach ( $reading['sections'] as $section ) {
            $html .= self::render_section( $section );
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Render the reading in parts for the results screen: the reading body and
     * the read-next block come back separately, so the front end can lay the
     * read-next out full-width below the Start-over button.
     *
     * @return array is_gate => bool; for a gate: html + readnext; otherwise
     *               intro, rest and readnext.
     */
    public static function render_split( $findings, $answers, $articles = array(), $decoder_url = '', $rn_new_tab = false, $followups = array() ) {
        $reading = self::build_reading( $findings, $answers, $articles, $decoder_url, $rn_new_tab, $followups );
        $p       = self::phrasing();

        // The medical gate: the calm box, and its read-next separately.
        if ( ! empty( $reading['is_gate'] ) ) {
            $html  = '<div class="asq-reading asq-reading--gate">';
            $html .= '<h3 class="asq-reading-heading asq-reading-heading--gate">' . esc_html( self::texturize( $reading['heading'] ) ) . '</h3>';
            $html .= '<div class="asq-reading-section asq-reading-section--gate"><p class="asq-reading-p">' . $reading['body'] . '</p></div>';
            $html .= '</div>';

            $readnext = '';
            if ( ! empty( $reading['articles'] ) ) {
                $intro    = isset( $p['gate']['read_next_intro'] ) ? $p['gate']['read_next_intro'] : '';
                $readnext = self::render_section( array(
                    'key'      => 'read_next',
                    'heading'  => $p['sections']['read_next'],
                    'intro'    => $intro,
                    'articles' => (array) $reading['articles'],
                ) );
            }

            return array( 'is_gate' => true, 'html' => $html, 'readnext' => $readnext );
        }

        // A normal reading: body sections, with read-next pulled out separately.
        $intro    = '';
        $rest     = '';
        $readnext = '';
        $first    = true;
        foreach ( $reading['sections'] as $section ) {
            if ( 'read_next' === $section['key'] ) {
                $readnext = self::render_section( $section );
                continue;
            }
            if ( $first ) {
                $intro .= self::render_section( $section );
                $first  = false;
            } else {
                $rest .= self::render_section( $section );
            }
        }

        return array( 'is_gate' => false, 'intro' => $intro, 'rest' => $rest, 'readnext' => $readnext );
    }

    /**
     * Render one section (heading plus paragraphs, or the read-next cards).
     */
    protected static function render_section( $section ) {
        $html  = '<section class="asq-reading-section asq-reading-section--' . esc_attr( $section['key'] ) . '">';
        $html .= '<h3 class="asq-reading-heading">' . esc_html( self::texturize( $section['heading'] ) ) . '</h3>';

        if ( 'read_next' === $section['key'] ) {
            if ( ! empty( $section['intro'] ) ) {
                $html .= '<p class="asq-reading-p asq-readnext-intro">' . esc_html( self::texturize( $section['intro'] ) ) . '</p>';
            }
            $html .= self::render_articles( $section['articles'] );
        } else {
            foreach ( $section['paragraphs'] as $para ) {
                $html .= '<p class="asq-reading-p">' . $para . '</p>';
            }
        }
        $html .= '</section>';
        return $html;
    }

    /**
     * Render the read-next article cards (thumbnail, title, excerpt, link),
     * in the same shape as the decoder's so the two tools feel related.
     */
    protected static function render_articles( $articles ) {
        $p     = self::phrasing();
        $label = isset( $p['read_more'] ) ? $p['read_more'] : __( 'Read more', 'apotheca-skin-quiz' );

        $target = self::$rn_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

        $html = '<div class="asq-readnext-cards">';
        foreach ( (array) $articles as $card ) {
            $url = isset( $card['url'] ) ? $card['url'] : '';
            if ( '' === $url ) {
                continue;
            }
            $html .= '<a class="asq-readnext-card" href="' . esc_url( $url ) . '"' . $target . '>';
            if ( ! empty( $card['thumb'] ) ) {
                $html .= '<span class="asq-readnext-thumb"><img src="' . esc_url( $card['thumb'] ) . '" alt="" loading="lazy"></span>';
            }
            $html .= '<span class="asq-readnext-body">';
            $html .= '<span class="asq-readnext-title">' . esc_html( self::texturize( isset( $card['title'] ) ? $card['title'] : '' ) ) . '</span>';
            if ( ! empty( $card['excerpt'] ) ) {
                $html .= '<span class="asq-readnext-excerpt">' . esc_html( self::texturize( $card['excerpt'] ) ) . '</span>';
            }
            $html .= '<span class="asq-readnext-more">' . esc_html( $label ) . '</span>';
            $html .= '</span>';
            $html .= '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    /* ────────── internals ────────── */

    /**
     * Map each answered question id to her answer text (comma-joined if she
     * picked more than one), for weaving into the copy.
     */
    protected static function answer_map( $answers, $followups = array() ) {
        $map = array();
        foreach ( ASQ_Config::selected_keys( (array) $answers, (array) $followups ) as $qid => $keys ) {
            $texts = array();
            foreach ( (array) $keys as $key ) {
                // Woven into prose, so strip any styling HTML the option text
                // may carry and keep the sentence clean.
                $t = wp_strip_all_tags( ASQ_Config::answer_text( $qid, $key ) );
                if ( '' !== $t ) {
                    $texts[] = $t;
                }
            }
            $map[ $qid ] = implode( ', ', $texts );
        }
        return $map;
    }

    /**
     * The concern(s) she ticked on the medical-gate question, excluding the
     * safe "none of these" option, each lower-cased for mid-sentence use and
     * joined naturally ("a, b and c"). Empty if nothing qualifying was ticked.
     */
    protected static function gate_ticked_text( $answers ) {
        $meta = ASQ_Config::gate_meta();
        if ( ! $meta ) {
            return '';
        }
        $qi        = (int) $meta['qi'];
        $safe      = (int) $meta['safe'];
        $questions = ASQ_Config::questions();
        if ( empty( $questions[ $qi ]['answers'] ) ) {
            return '';
        }

        $selected = isset( $answers[ $qi ] ) ? (array) $answers[ $qi ] : array();
        $texts    = array();
        foreach ( $selected as $ai ) {
            $ai = (int) $ai;
            if ( $ai === $safe || ! isset( $questions[ $qi ]['answers'][ $ai ]['text'] ) ) {
                continue;
            }
            $t = wp_strip_all_tags( $questions[ $qi ]['answers'][ $ai ]['text'] );
            if ( '' !== $t ) {
                $texts[] = self::lcfirst_mb( $t );
            }
        }
        return self::natural_join( $texts );
    }

    /** Join a list as "a", "a and b", or "a, b and c". */
    protected static function natural_join( $items ) {
        $items = array_values( array_filter( $items, function ( $x ) { return '' !== $x; } ) );
        $n     = count( $items );
        if ( 0 === $n ) {
            return '';
        }
        if ( 1 === $n ) {
            return $items[0];
        }
        $last = array_pop( $items );
        $sep  = ( 1 === count( $items ) ) ? ' ' : ', ';
        return implode( ', ', $items ) . $sep . __( 'and', 'apotheca-skin-quiz' ) . ' ' . $last;
    }

    /**
     * Resolve the tokens in one sentence and texturise it.
     *
     *   {al:Q2}  her answer, first letter lower-cased for mid-sentence use
     *   {a:Q2}   her answer, as written
     *   {ticked} what she ticked on the medical-gate question (gate copy only)
     *   {em}…{/em} an emphasised phrase
     */
    protected static function resolve( $raw, $amap ) {
        $text = (string) $raw;

        $text = preg_replace_callback( '/\{al:(Q\d+)\}/', function ( $m ) use ( $amap ) {
            $v = isset( $amap[ $m[1] ] ) ? $amap[ $m[1] ] : '';
            return '' === $v ? '' : self::lcfirst_mb( $v );
        }, $text );

        $text = preg_replace_callback( '/\{a:(Q\d+)\}/', function ( $m ) use ( $amap ) {
            return isset( $amap[ $m[1] ] ) ? $amap[ $m[1] ] : '';
        }, $text );

        // What she ticked on the gate question. Falls back to a general phrase
        // if, somehow, nothing qualifying is set, so the sentence never breaks.
        if ( false !== strpos( $text, '{ticked}' ) ) {
            $ticked = '' !== self::$gate_ticked ? self::$gate_ticked : __( 'one of the things you ticked', 'apotheca-skin-quiz' );
            $text   = str_replace( '{ticked}', $ticked, $text );
        }

        // Curl quotes and tidy punctuation to house style.
        if ( function_exists( 'wptexturize' ) ) {
            $text = wptexturize( $text );
        }

        $text = str_replace( '{em}', '<span class="asq-reading-em">', $text );
        $text = str_replace( '{/em}', '</span>', $text );

        // The decoder link. Built after texturize so the URL is never mangled.
        // With no page set, the wrapped words become plain text, never a link
        // with a broken or empty href.
        if ( '' !== self::$decoder_url ) {
            $href = esc_url( add_query_arg( self::DECODER_FROM_KEY, self::DECODER_FROM_VALUE, self::$decoder_url ) );
            $open = '<a class="asq-reading-link" href="' . $href . '" target="_blank" rel="noopener noreferrer">';
            $text = str_replace( '{decoder}', $open, $text );
            $text = str_replace( '{/decoder}', '</a>', $text );
        } else {
            $text = str_replace( array( '{decoder}', '{/decoder}' ), '', $text );
        }

        return $text;
    }

    /**
     * Curl quotes and tidy punctuation, if WordPress is loaded.
     */
    protected static function texturize( $str ) {
        return function_exists( 'wptexturize' ) ? wptexturize( $str ) : $str;
    }

    /**
     * Lower-case the first character, multibyte-safe.
     */
    protected static function lcfirst_mb( $str ) {
        if ( '' === $str ) {
            return $str;
        }
        $first = function_exists( 'mb_substr' ) ? mb_substr( $str, 0, 1 ) : substr( $str, 0, 1 );
        $rest  = function_exists( 'mb_substr' ) ? mb_substr( $str, 1 ) : substr( $str, 1 );
        $lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $first ) : strtolower( $first );
        return $lower . $rest;
    }
}

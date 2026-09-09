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
     * The internal handshake between the quiz and the decoder. Hard-coded on
     * both sides on purpose: a name read from settings in two places is a quiet
     * way to break the link.
     */
    const DECODER_FROM_KEY   = 'from';
    const DECODER_FROM_VALUE = 'skin-quiz';

    /** Max entries in the "one or two things worth trying" section. */
    const MAX_TRIES = 2;

    public static function phrasing() {
        if ( null === self::$phrasing ) {
            self::$phrasing = require ASQ_PLUGIN_DIR . 'includes/asq-phrasing.php';
        }
        return self::$phrasing;
    }

    /**
     * Build the reading from the engine output.
     *
     * @param array $findings Engine output (ordered, each with id/label/triggers).
     * @param array $answers  { questionIndex => [answerIndex, …] }
     * @return array Either a gate reading ( is_gate => true, heading, body )
     *               or a normal reading ( is_gate => false, sections => [...] ).
     */
    public static function build_reading( $findings, $answers, $articles = array(), $decoder_url = '' ) {
        // Set for this render; resolve() reads it when it meets a {decoder} token.
        self::$decoder_url = is_string( $decoder_url ) ? $decoder_url : '';

        $p    = self::phrasing();
        $amap = self::answer_map( $answers );

        // The medical gate replaces the whole reading.
        foreach ( (array) $findings as $f ) {
            if ( isset( $f['id'] ) && 'F11' === $f['id'] ) {
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
    public static function render( $findings, $answers, $articles = array(), $decoder_url = '' ) {
        return self::render_html( self::build_reading( $findings, $answers, $articles, $decoder_url ) );
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
     * Render the reading split for the email gate: the first section on its
     * own, and the rest (with read-next) as a separate fragment the front end
     * puts behind the form. A gate reading is not split.
     *
     * @return array is_gate => bool; for a gate: html; otherwise intro, rest.
     */
    public static function render_split( $findings, $answers, $articles = array(), $decoder_url = '' ) {
        $reading = self::build_reading( $findings, $answers, $articles, $decoder_url );

        if ( ! empty( $reading['is_gate'] ) ) {
            return array( 'is_gate' => true, 'html' => self::render_html( $reading ) );
        }

        $sections = $reading['sections'];
        $intro    = '';
        $rest     = '';
        foreach ( $sections as $i => $section ) {
            if ( 0 === $i ) {
                $intro .= self::render_section( $section );
            } else {
                $rest .= self::render_section( $section );
            }
        }

        return array( 'is_gate' => false, 'intro' => $intro, 'rest' => $rest );
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

        $html = '<div class="asq-readnext-cards">';
        foreach ( (array) $articles as $card ) {
            $url = isset( $card['url'] ) ? $card['url'] : '';
            if ( '' === $url ) {
                continue;
            }
            $html .= '<a class="asq-readnext-card" href="' . esc_url( $url ) . '">';
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
    protected static function answer_map( $answers ) {
        $map = array();
        foreach ( ASQ_Config::selected_keys( (array) $answers ) as $qid => $keys ) {
            $texts = array();
            foreach ( (array) $keys as $key ) {
                $t = ASQ_Config::answer_text( $qid, $key );
                if ( '' !== $t ) {
                    $texts[] = $t;
                }
            }
            $map[ $qid ] = implode( ', ', $texts );
        }
        return $map;
    }

    /**
     * Resolve the tokens in one sentence and texturise it.
     *
     *   {al:Q2}  her answer, first letter lower-cased for mid-sentence use
     *   {a:Q2}   her answer, as written
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

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads the single quiz configuration array and reads from it.
 *
 * Everything about the questions lives in asq-quiz-config.php. This class
 * is the only thing that reads that file, so the flow, the placeholder result
 * and the email all go through the same source of truth.
 */
class ASQ_Config {

    /** @var array|null Cached configuration. */
    protected static $data = null;

    /** @var array|null Cached questions with wording overrides applied. */
    protected static $questions_cache = null;

    /**
     * The option holding editable question wording. Keyed by question id, then
     * 'text' / 'instruction' and 'answers' => [ key => [ 'text', 'note' ] ].
     * Only display text lives here; findings, keys and flags stay in code.
     */
    const OPTION_OVERRIDES = 'asq_question_copy';

    /**
     * The small, safe set of inline HTML allowed in question and option wording,
     * so a phrase can be styled (e.g. <span class="descriptive-text">…</span>)
     * without opening the door to scripts, styles or event handlers.
     */
    public static function allowed_copy_html() {
        return array(
            'span'   => array( 'class' => true ),
            'small'  => array( 'class' => true ),
            'strong' => array(),
            'em'     => array(),
            'b'      => array(),
            'i'      => array(),
            'br'     => array(),
        );
    }

    /**
     * Sanitise a piece of question/option wording, keeping only the allowed
     * inline tags above. Used on save and when rendering server-side previews.
     */
    public static function kses_copy( $str ) {
        if ( ! is_string( $str ) ) {
            return '';
        }
        $str = function_exists( 'wp_kses' ) ? wp_kses( $str, self::allowed_copy_html() ) : strip_tags( $str, '<span><small><strong><em><b><i><br>' );
        return trim( $str );
    }

    /**
     * Load (once) and return the whole configuration array.
     */
    public static function all() {
        if ( null === self::$data ) {
            self::$data = require ASQ_PLUGIN_DIR . 'includes/asq-quiz-config.php';
        }
        return self::$data;
    }

    /**
     * The ordered list of questions, with any admin wording overrides applied
     * on top of the code defaults, so edits made in the quiz screen flow to the
     * front end, the reading and the email alike.
     */
    public static function questions() {
        if ( null === self::$questions_cache ) {
            $all = self::all();
            $qs  = isset( $all['questions'] ) ? $all['questions'] : array();
            self::$questions_cache = self::apply_overrides( $qs );
        }
        return self::$questions_cache;
    }

    /**
     * The stored wording overrides, or an empty array. Never fatal on a site
     * where the option was never saved.
     */
    public static function overrides() {
        $saved = get_option( self::OPTION_OVERRIDES, array() );
        return is_array( $saved ) ? $saved : array();
    }

    /**
     * Overlay the saved wording on the code-defined questions. Only text,
     * instruction and answer text/note are touched; keys, findings, safe/skip
     * and multiple/optional always come from code, so the engine can never be
     * broken from the wording screen.
     */
    protected static function apply_overrides( $questions ) {
        $ov = self::overrides();
        if ( empty( $ov ) ) {
            return $questions;
        }

        foreach ( $questions as $i => $q ) {
            $qid = isset( $q['id'] ) ? $q['id'] : '';
            if ( '' === $qid || empty( $ov[ $qid ] ) ) {
                continue;
            }
            $o = $ov[ $qid ];

            if ( ! empty( $o['text'] ) ) {
                $questions[ $i ]['text'] = $o['text'];
            }
            if ( isset( $o['instruction'] ) && '' !== $o['instruction'] ) {
                $questions[ $i ]['instruction'] = $o['instruction'];
            }
            if ( ! empty( $o['answers'] ) && is_array( $o['answers'] ) && ! empty( $q['answers'] ) ) {
                foreach ( $questions[ $i ]['answers'] as $ai => $a ) {
                    $key = isset( $a['key'] ) ? $a['key'] : '';
                    if ( '' === $key || empty( $o['answers'][ $key ] ) ) {
                        continue;
                    }
                    if ( ! empty( $o['answers'][ $key ]['text'] ) ) {
                        $questions[ $i ]['answers'][ $ai ]['text'] = $o['answers'][ $key ]['text'];
                    }
                    if ( isset( $o['answers'][ $key ]['note'] ) && '' !== $o['answers'][ $key ]['note'] ) {
                        $questions[ $i ]['answers'][ $ai ]['note'] = $o['answers'][ $key ]['note'];
                    }

                    // Follow-up (branch) wording, if this option opens one.
                    if ( ! empty( $o['answers'][ $key ]['follow_up'] ) && ! empty( $questions[ $i ]['answers'][ $ai ]['follow_up'] ) ) {
                        $ofu = $o['answers'][ $key ]['follow_up'];
                        if ( ! empty( $ofu['text'] ) ) {
                            $questions[ $i ]['answers'][ $ai ]['follow_up']['text'] = $ofu['text'];
                        }
                        if ( isset( $ofu['instruction'] ) && '' !== $ofu['instruction'] ) {
                            $questions[ $i ]['answers'][ $ai ]['follow_up']['instruction'] = $ofu['instruction'];
                        }
                        if ( ! empty( $ofu['answers'] ) && is_array( $ofu['answers'] ) && ! empty( $questions[ $i ]['answers'][ $ai ]['follow_up']['answers'] ) ) {
                            foreach ( $questions[ $i ]['answers'][ $ai ]['follow_up']['answers'] as $fai => $fa ) {
                                $fkey = isset( $fa['key'] ) ? $fa['key'] : '';
                                if ( '' === $fkey || empty( $ofu['answers'][ $fkey ] ) ) {
                                    continue;
                                }
                                if ( ! empty( $ofu['answers'][ $fkey ]['text'] ) ) {
                                    $questions[ $i ]['answers'][ $ai ]['follow_up']['answers'][ $fai ]['text'] = $ofu['answers'][ $fkey ]['text'];
                                }
                                if ( isset( $ofu['answers'][ $fkey ]['note'] ) && '' !== $ofu['answers'][ $fkey ]['note'] ) {
                                    $questions[ $i ]['answers'][ $ai ]['follow_up']['answers'][ $fai ]['note'] = $ofu['answers'][ $fkey ]['note'];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $questions;
    }

    /**
     * Sanitise and store wording overrides from the admin screen. Only non-empty
     * values that differ from the code default are kept, so clearing a field
     * reverts it to the default rather than blanking the question.
     *
     * @param array $raw The asq_questions POST array (already unslashed).
     */
    public static function save_overrides( $raw ) {
        // Defaults straight from code, to compare against.
        $all      = self::all();
        $defaults = isset( $all['questions'] ) ? $all['questions'] : array();
        $by_id    = array();
        foreach ( $defaults as $q ) {
            if ( ! empty( $q['id'] ) ) {
                $by_id[ $q['id'] ] = $q;
            }
        }

        $clean = array();
        foreach ( (array) $raw as $qid => $fields ) {
            $qid = sanitize_text_field( $qid );
            if ( ! isset( $by_id[ $qid ] ) || ! is_array( $fields ) ) {
                continue;
            }
            $def   = $by_id[ $qid ];
            $entry = array();

            $text = isset( $fields['text'] ) ? self::kses_copy( $fields['text'] ) : '';
            if ( '' !== $text && $text !== ( $def['text'] ?? '' ) ) {
                $entry['text'] = $text;
            }
            $instr = isset( $fields['instruction'] ) ? self::kses_copy( $fields['instruction'] ) : '';
            if ( '' !== $instr && $instr !== ( $def['instruction'] ?? '' ) ) {
                $entry['instruction'] = $instr;
            }

            if ( ! empty( $fields['answers'] ) && is_array( $fields['answers'] ) ) {
                $def_ans = array();
                foreach ( ( $def['answers'] ?? array() ) as $a ) {
                    if ( isset( $a['key'] ) ) {
                        $def_ans[ $a['key'] ] = $a;
                    }
                }
                $answers = array();
                foreach ( $fields['answers'] as $key => $af ) {
                    $key = sanitize_text_field( $key );
                    if ( ! isset( $def_ans[ $key ] ) || ! is_array( $af ) ) {
                        continue;
                    }
                    $a_entry = array();
                    $atext   = isset( $af['text'] ) ? self::kses_copy( $af['text'] ) : '';
                    if ( '' !== $atext && $atext !== ( $def_ans[ $key ]['text'] ?? '' ) ) {
                        $a_entry['text'] = $atext;
                    }
                    $anote = isset( $af['note'] ) ? self::kses_copy( $af['note'] ) : '';
                    if ( '' !== $anote && $anote !== ( $def_ans[ $key ]['note'] ?? '' ) ) {
                        $a_entry['note'] = $anote;
                    }

                    // Follow-up (branch) wording diffs, if this option opens one.
                    if ( ! empty( $af['follow_up'] ) && is_array( $af['follow_up'] ) && ! empty( $def_ans[ $key ]['follow_up'] ) ) {
                        $def_fu   = $def_ans[ $key ]['follow_up'];
                        $fu_entry = array();

                        $ftext = isset( $af['follow_up']['text'] ) ? self::kses_copy( $af['follow_up']['text'] ) : '';
                        if ( '' !== $ftext && $ftext !== ( $def_fu['text'] ?? '' ) ) {
                            $fu_entry['text'] = $ftext;
                        }
                        $finstr = isset( $af['follow_up']['instruction'] ) ? self::kses_copy( $af['follow_up']['instruction'] ) : '';
                        if ( '' !== $finstr && $finstr !== ( $def_fu['instruction'] ?? '' ) ) {
                            $fu_entry['instruction'] = $finstr;
                        }

                        if ( ! empty( $af['follow_up']['answers'] ) && is_array( $af['follow_up']['answers'] ) ) {
                            $def_fa = array();
                            foreach ( ( $def_fu['answers'] ?? array() ) as $fa ) {
                                if ( isset( $fa['key'] ) ) {
                                    $def_fa[ $fa['key'] ] = $fa;
                                }
                            }
                            $fu_answers = array();
                            foreach ( $af['follow_up']['answers'] as $fkey => $faf ) {
                                $fkey = sanitize_text_field( $fkey );
                                if ( ! isset( $def_fa[ $fkey ] ) || ! is_array( $faf ) ) {
                                    continue;
                                }
                                $fa_entry = array();
                                $fatext   = isset( $faf['text'] ) ? self::kses_copy( $faf['text'] ) : '';
                                if ( '' !== $fatext && $fatext !== ( $def_fa[ $fkey ]['text'] ?? '' ) ) {
                                    $fa_entry['text'] = $fatext;
                                }
                                $fanote = isset( $faf['note'] ) ? self::kses_copy( $faf['note'] ) : '';
                                if ( '' !== $fanote && $fanote !== ( $def_fa[ $fkey ]['note'] ?? '' ) ) {
                                    $fa_entry['note'] = $fanote;
                                }
                                if ( $fa_entry ) {
                                    $fu_answers[ $fkey ] = $fa_entry;
                                }
                            }
                            if ( $fu_answers ) {
                                $fu_entry['answers'] = $fu_answers;
                            }
                        }

                        if ( $fu_entry ) {
                            $a_entry['follow_up'] = $fu_entry;
                        }
                    }

                    if ( $a_entry ) {
                        $answers[ $key ] = $a_entry;
                    }
                }
                if ( $answers ) {
                    $entry['answers'] = $answers;
                }
            }

            if ( $entry ) {
                $clean[ $qid ] = $entry;
            }
        }

        update_option( self::OPTION_OVERRIDES, $clean, false );

        // Drop the per-request caches so the new wording is seen immediately.
        self::$questions_cache = null;
        self::$index_by_id     = null;
        self::$followups_by_id = null;
    }

    /**
     * Clear every saved wording override, returning all questions and options
     * to their built-in defaults. Used by the admin reset control, chiefly to
     * recover after a question-set redesign leaves old edits on the wrong slot.
     */
    public static function reset_overrides() {
        delete_option( self::OPTION_OVERRIDES );
        self::$questions_cache = null;
        self::$index_by_id     = null;
        self::$followups_by_id = null;
    }

    /**
     * The findings map: id => human label.
     */
    public static function findings() {
        $all = self::all();
        return isset( $all['findings'] ) ? $all['findings'] : array();
    }

    /**
     * Human label for a finding id (falls back to the id itself).
     */
    public static function finding_label( $id ) {
        $findings = self::findings();
        return isset( $findings[ $id ] ) ? $findings[ $id ] : $id;
    }

    /**
     * The Skin Topic term slugs a finding maps to for read-next.
     */
    public static function finding_topics( $id ) {
        $all = self::all();
        return isset( $all['finding_topics'][ $id ] ) ? (array) $all['finding_topics'][ $id ] : array();
    }

    /**
     * The single general Skin Topic term the medical gate may offer.
     */
    public static function gate_topics() {
        $all = self::all();
        return isset( $all['gate_topics'] ) ? (array) $all['gate_topics'] : array();
    }

    /**
     * The data the front end needs to render the quiz: text, instruction,
     * multiple and the answer labels. Deliberately excludes the finding
     * mappings, which stay server-side.
     */
    public static function frontend_questions() {
        $out = array();
        foreach ( self::questions() as $q ) {
            $answers = array();
            foreach ( $q['answers'] as $a ) {
                $entry = array(
                    'text'        => $a['text'],
                    'description' => '',
                    'image'       => '',
                    // Optional supporting line shown under the option (e.g. Q5 E).
                    'note'        => isset( $a['note'] ) ? $a['note'] : '',
                );

                // A conditional follow-up question, shown only when this option
                // is chosen. Only the wording the browser needs is exposed; the
                // finding mappings stay server-side. The front-end branching JS
                // reads a.follow_up.{text,instruction,multiple,answers}.
                if ( ! empty( $a['follow_up']['answers'] ) ) {
                    $fu       = $a['follow_up'];
                    $fu_ans   = array();
                    foreach ( $fu['answers'] as $fa ) {
                        $fu_ans[] = array(
                            'text'        => isset( $fa['text'] ) ? $fa['text'] : '',
                            'description' => '',
                            'image'       => '',
                            'note'        => isset( $fa['note'] ) ? $fa['note'] : '',
                        );
                    }
                    $entry['follow_up'] = array(
                        'text'        => isset( $fu['text'] ) ? $fu['text'] : '',
                        'instruction' => isset( $fu['instruction'] ) ? $fu['instruction'] : '',
                        'multiple'    => ! empty( $fu['multiple'] ),
                        'answers'     => $fu_ans,
                    );
                }

                $answers[] = $entry;
            }
            $out[] = array(
                'text'        => $q['text'],
                'instruction' => isset( $q['instruction'] ) ? $q['instruction'] : '',
                'multiple'    => ! empty( $q['multiple'] ),
                'answers'     => $answers,
            );
        }
        return $out;
    }

    /**
     * Turn raw answer indices into readable question/answer text.
     *
     * @param array $answers { questionIndex => [answerIndex, …] }
     * @return array [ [ 'question' => str, 'answers' => [str, …] ], … ]
     */
    public static function resolve_answers( $answers, $followups = array() ) {
        $questions = self::questions();
        $readable  = array();

        foreach ( (array) $answers as $qi => $selected ) {
            $qi = (int) $qi;
            if ( ! isset( $questions[ $qi ] ) ) {
                continue;
            }
            $q       = $questions[ $qi ];
            $sel_ints = array_map( 'intval', (array) $selected );
            $texts   = array();
            foreach ( $sel_ints as $ai ) {
                if ( isset( $q['answers'][ $ai ]['text'] ) ) {
                    $texts[] = $q['answers'][ $ai ]['text'];
                }
            }
            $readable[] = array(
                'question' => $q['text'],
                'answers'  => $texts,
            );

            // Append any follow-up (branch) question that was answered because
            // one of the chosen options opened it, so the stored record and the
            // admin views show the whole conversation, branches included.
            foreach ( $sel_ints as $ai ) {
                if ( empty( $q['answers'][ $ai ]['follow_up']['answers'] ) ) {
                    continue;
                }
                $compound = $qi . '_' . $ai;
                if ( empty( $followups[ $compound ] ) ) {
                    continue;
                }
                $fu      = $q['answers'][ $ai ]['follow_up'];
                $fu_text = array();
                foreach ( (array) $followups[ $compound ] as $fai ) {
                    $fai = (int) $fai;
                    if ( isset( $fu['answers'][ $fai ]['text'] ) ) {
                        $fu_text[] = $fu['answers'][ $fai ]['text'];
                    }
                }
                if ( $fu_text ) {
                    $readable[] = array(
                        'question' => isset( $fu['text'] ) ? $fu['text'] : '',
                        'answers'  => $fu_text,
                    );
                }
            }
        }

        return $readable;
    }

    /* ────────── lookups used by the findings engine ────────── */

    /** @var array|null Cached map of question id => config index. */
    protected static $index_by_id = null;

    /**
     * Map each question's short id (Q1, Q2, …) to its position in the config.
     */
    public static function question_index_by_id() {
        if ( null === self::$index_by_id ) {
            self::$index_by_id = array();
            foreach ( self::questions() as $i => $q ) {
                if ( ! empty( $q['id'] ) ) {
                    self::$index_by_id[ $q['id'] ] = $i;
                }
            }
        }
        return self::$index_by_id;
    }

    /**
     * Convert raw answer indices into the option keys the rules refer to,
     * merging any follow-up (branch) answers under their own follow-up ids so a
     * rule can fire on a branch answer just as it does on a main answer.
     *
     * A follow-up answer only counts when its parent option is actually
     * selected. That guard means a change of mind (picking a parent option that
     * has no follow-up after having answered one earlier) can never leave a
     * stale branch answer influencing the result.
     *
     * @param array $answers   { questionIndex => [answerIndex, …] }
     * @param array $followups { "qi_ai" => [followupAnswerIndex, …] }
     * @return array { 'Q1' => ['D'], 'Q2a' => ['C'], 'Q11' => ['A','C'], … }
     */
    public static function selected_keys( $answers, $followups = array() ) {
        $questions = self::questions();
        $out       = array();

        foreach ( (array) $answers as $qi => $selected ) {
            $qi = (int) $qi;
            if ( ! isset( $questions[ $qi ] ) ) {
                continue;
            }
            $qid  = ! empty( $questions[ $qi ]['id'] ) ? $questions[ $qi ]['id'] : 'Q' . ( $qi + 1 );
            $keys = array();
            foreach ( (array) $selected as $ai ) {
                $ai = (int) $ai;
                if ( isset( $questions[ $qi ]['answers'][ $ai ]['key'] ) ) {
                    $keys[] = $questions[ $qi ]['answers'][ $ai ]['key'];
                }
            }
            $out[ $qid ] = $keys;
        }

        // Follow-up (branch) answers. Keyed "qi_ai" by the browser: qi is the
        // parent question index, ai the parent answer index.
        foreach ( (array) $followups as $compound => $selected ) {
            $parts = explode( '_', (string) $compound );
            if ( 2 !== count( $parts ) ) {
                continue;
            }
            $qi = (int) $parts[0];
            $ai = (int) $parts[1];

            // Only honour the branch if its parent option is actually chosen.
            $parent_selected = isset( $answers[ $qi ] ) ? array_map( 'intval', (array) $answers[ $qi ] ) : array();
            if ( ! in_array( $ai, $parent_selected, true ) ) {
                continue;
            }
            if ( empty( $questions[ $qi ]['answers'][ $ai ]['follow_up']['answers'] ) ) {
                continue;
            }
            $fu = $questions[ $qi ]['answers'][ $ai ]['follow_up'];
            if ( empty( $fu['id'] ) ) {
                continue;
            }
            $keys = array();
            foreach ( (array) $selected as $fai ) {
                $fai = (int) $fai;
                if ( isset( $fu['answers'][ $fai ]['key'] ) ) {
                    $keys[] = $fu['answers'][ $fai ]['key'];
                }
            }
            if ( $keys ) {
                $out[ $fu['id'] ] = $keys;
            }
        }

        return $out;
    }

    /* ────────── follow-up (branch) question lookups ────────── */

    /** @var array|null Cached map of follow-up id => follow-up definition. */
    protected static $followups_by_id = null;

    /**
     * Map each follow-up question's id (Q2a, Q7a, Q8a …) to its definition, so
     * the engine and presenter can resolve a branch question's text and its
     * answer labels the same way they resolve a main question's.
     */
    public static function followups_by_id() {
        if ( null === self::$followups_by_id ) {
            self::$followups_by_id = array();
            foreach ( self::questions() as $q ) {
                if ( empty( $q['answers'] ) ) {
                    continue;
                }
                foreach ( $q['answers'] as $a ) {
                    if ( ! empty( $a['follow_up']['id'] ) ) {
                        self::$followups_by_id[ $a['follow_up']['id'] ] = $a['follow_up'];
                    }
                }
            }
        }
        return self::$followups_by_id;
    }

    /**
     * Locate the medical gate for the front end: the question that carries a
     * "safe" answer (the "none of these" option), and that answer's index.
     * Any other selection on that question trips the gate.
     *
     * @return array|null [ 'qi' => int question index, 'safe' => int answer index ]
     */
    public static function gate_meta() {
        foreach ( self::questions() as $i => $q ) {
            if ( empty( $q['answers'] ) ) {
                continue;
            }
            foreach ( $q['answers'] as $ai => $a ) {
                if ( ! empty( $a['safe'] ) ) {
                    return array( 'qi' => (int) $i, 'safe' => (int) $ai );
                }
            }
        }
        return null;
    }

    /**
     * The text of a question, by its short id. Resolves follow-up ids too.
     */
    public static function question_text_by_id( $qid ) {
        $map = self::question_index_by_id();
        if ( isset( $map[ $qid ] ) ) {
            $q = self::questions()[ $map[ $qid ] ];
            return isset( $q['text'] ) ? $q['text'] : '';
        }
        $fus = self::followups_by_id();
        if ( isset( $fus[ $qid ] ) ) {
            return isset( $fus[ $qid ]['text'] ) ? $fus[ $qid ]['text'] : '';
        }
        return '';
    }

    /**
     * The text of one answer, by its question id and option key. Resolves
     * follow-up ids too, so a branch answer can be woven back into the reading.
     */
    public static function answer_text( $qid, $key ) {
        $map = self::question_index_by_id();
        if ( isset( $map[ $qid ] ) ) {
            $q = self::questions()[ $map[ $qid ] ];
            foreach ( $q['answers'] as $a ) {
                if ( isset( $a['key'] ) && $a['key'] === $key ) {
                    return $a['text'];
                }
            }
            return '';
        }
        $fus = self::followups_by_id();
        if ( isset( $fus[ $qid ]['answers'] ) ) {
            foreach ( $fus[ $qid ]['answers'] as $a ) {
                if ( isset( $a['key'] ) && $a['key'] === $key ) {
                    return isset( $a['text'] ) ? $a['text'] : '';
                }
            }
        }
        return '';
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads the single quiz configuration array and reads from it.
 *
 * Everything about the ten questions lives in asq-quiz-config.php. This class
 * is the only thing that reads that file, so the flow, the placeholder result
 * and the email all go through the same source of truth.
 */
class ASQ_Config {

    /** @var array|null Cached configuration. */
    protected static $data = null;

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
     * The ordered list of questions.
     */
    public static function questions() {
        $all = self::all();
        return isset( $all['questions'] ) ? $all['questions'] : array();
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
     * The data the front end needs to render the quiz: text, instruction,
     * multiple and the answer labels. Deliberately excludes the finding
     * mappings, which stay server-side.
     */
    public static function frontend_questions() {
        $out = array();
        foreach ( self::questions() as $q ) {
            $answers = array();
            foreach ( $q['answers'] as $a ) {
                $answers[] = array(
                    'text'        => $a['text'],
                    'description' => '',
                    'image'       => '',
                );
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
    public static function resolve_answers( $answers ) {
        $questions = self::questions();
        $readable  = array();

        foreach ( (array) $answers as $qi => $selected ) {
            $qi = (int) $qi;
            if ( ! isset( $questions[ $qi ] ) ) {
                continue;
            }
            $q     = $questions[ $qi ];
            $texts = array();
            foreach ( (array) $selected as $ai ) {
                $ai = (int) $ai;
                if ( isset( $q['answers'][ $ai ]['text'] ) ) {
                    $texts[] = $q['answers'][ $ai ]['text'];
                }
            }
            $readable[] = array(
                'question' => $q['text'],
                'answers'  => $texts,
            );
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
     * Convert raw answer indices into the option keys the rules refer to.
     *
     * @param array $answers { questionIndex => [answerIndex, …] }
     * @return array { 'Q1' => ['D'], 'Q10' => ['A','C'], … }
     */
    public static function selected_keys( $answers ) {
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
        return $out;
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
     * The text of a question, by its short id.
     */
    public static function question_text_by_id( $qid ) {
        $map = self::question_index_by_id();
        if ( ! isset( $map[ $qid ] ) ) {
            return '';
        }
        $q = self::questions()[ $map[ $qid ] ];
        return isset( $q['text'] ) ? $q['text'] : '';
    }

    /**
     * The text of one answer, by its question id and option key.
     */
    public static function answer_text( $qid, $key ) {
        $map = self::question_index_by_id();
        if ( ! isset( $map[ $qid ] ) ) {
            return '';
        }
        $q = self::questions()[ $map[ $qid ] ];
        foreach ( $q['answers'] as $a ) {
            if ( isset( $a['key'] ) && $a['key'] === $key ) {
                return $a['text'];
            }
        }
        return '';
    }
}

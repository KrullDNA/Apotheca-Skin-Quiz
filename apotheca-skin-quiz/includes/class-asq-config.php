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

    /**
     * Map a set of answers to the findings those answers contribute to.
     *
     * This is NOT the findings engine. It is the union of the per-answer
     * finding arrows from the brief, used only by the Stage 4 placeholder so
     * the result screen shows which findings are in play. Priority,
     * suppression and the real firing rules arrive with the engine.
     *
     * @param array $answers { questionIndex => [answerIndex, …] }
     * @return array Ordered list of [ 'id' => 'F5', 'label' => '…' ].
     */
    public static function map_findings( $answers ) {
        $questions = self::questions();
        $hit       = array();

        foreach ( (array) $answers as $qi => $selected ) {
            $qi = (int) $qi;
            if ( ! isset( $questions[ $qi ] ) ) {
                continue;
            }
            foreach ( (array) $selected as $ai ) {
                $ai  = (int) $ai;
                $ans = isset( $questions[ $qi ]['answers'][ $ai ] ) ? $questions[ $qi ]['answers'][ $ai ] : null;
                if ( $ans && ! empty( $ans['findings'] ) ) {
                    foreach ( $ans['findings'] as $fid ) {
                        $hit[ $fid ] = true;
                    }
                }
            }
        }

        // Return in the canonical finding order from the config.
        $out = array();
        foreach ( self::findings() as $id => $label ) {
            if ( isset( $hit[ $id ] ) ) {
                $out[] = array( 'id' => $id, 'label' => $label );
            }
        }
        return $out;
    }
}

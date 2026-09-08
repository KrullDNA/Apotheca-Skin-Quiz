<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The findings engine.
 *
 * Input is the answer set. Output is an ordered array of fired findings, each
 * with the answers that triggered it, so the result can explain itself back
 * to her. No phrasing and no HTML at this stage.
 *
 * The rules live in asq-findings-rules.php; this class only applies them:
 * evaluate each finding's clauses, apply priority ordering and suppression,
 * honour the gate, and add the two fallbacks (F10, F8).
 */
class ASQ_Engine {

    /** @var array|null Cached rules. */
    protected static $rules = null;

    /**
     * Load (once) the rules configuration.
     */
    public static function rules() {
        if ( null === self::$rules ) {
            self::$rules = require ASQ_PLUGIN_DIR . 'includes/asq-findings-rules.php';
        }
        return self::$rules;
    }

    /**
     * Evaluate the answer set and return the ordered fired findings.
     *
     * @param array $answers { questionIndex => [answerIndex, …] }
     * @return array Ordered list of:
     *               [ 'id' => 'F5', 'label' => '…', 'triggers' => [
     *                   [ 'qid' => 'Q6', 'question' => '…', 'answer' => '…' ], … ] ]
     */
    public static function evaluate( $answers ) {
        $rules    = self::rules();
        $findings = isset( $rules['findings'] ) ? $rules['findings'] : array();
        $selected = ASQ_Config::selected_keys( (array) $answers );

        // 1. The gate overrides everything and stops evaluation.
        $gate_id = isset( $rules['gate'] ) ? $rules['gate'] : '';
        if ( $gate_id && isset( $findings[ $gate_id ]['fires_when'] ) ) {
            $trig = self::first_matching_clause( $findings[ $gate_id ]['fires_when'], $selected );
            if ( null !== $trig ) {
                return array( self::build( $gate_id, $trig ) );
            }
        }

        // 2. The ordinary findings, in priority order.
        $ordinary = array();
        foreach ( $findings as $id => $def ) {
            if ( $id === $gate_id || ! empty( $def['fallback'] ) || empty( $def['fires_when'] ) ) {
                continue;
            }
            $ordinary[ $id ] = $def;
        }
        uasort( $ordinary, array( __CLASS__, 'by_priority' ) );

        $fired = array(); // id => triggers
        foreach ( $ordinary as $id => $def ) {
            $trig = self::first_matching_clause( $def['fires_when'], $selected );
            if ( null !== $trig ) {
                $fired[ $id ] = $trig;
            }
        }

        // 3. Suppression: a fired finding removes those it suppresses.
        foreach ( $ordinary as $id => $def ) {
            if ( isset( $fired[ $id ] ) && ! empty( $def['suppresses'] ) ) {
                foreach ( $def['suppresses'] as $suppressed ) {
                    unset( $fired[ $suppressed ] );
                }
            }
        }

        // 4. Build the ordered output of surviving ordinary findings.
        $out = array();
        foreach ( $ordinary as $id => $def ) {
            if ( isset( $fired[ $id ] ) ) {
                $out[] = self::build( $id, $fired[ $id ] );
            }
        }

        // 5. Fallbacks, in priority order, judged against what fired so far.
        $fallbacks = array();
        foreach ( $findings as $id => $def ) {
            if ( ! empty( $def['fallback'] ) ) {
                $fallbacks[ $id ] = $def;
            }
        }
        uasort( $fallbacks, array( __CLASS__, 'by_priority' ) );

        foreach ( $fallbacks as $id => $def ) {
            if ( 'fewer_than_two' === $def['fallback'] ) {
                if ( count( $out ) < 2 && ! empty( $def['condition'] ) ) {
                    $trig = self::first_matching_clause( array( $def['condition'] ), $selected );
                    if ( null !== $trig ) {
                        $out[] = self::build( $id, $trig );
                    }
                }
            } elseif ( 'none_fired' === $def['fallback'] ) {
                if ( empty( $out ) ) {
                    $out[] = self::build( $id, array() );
                }
            }
        }

        return $out;
    }

    /**
     * True if the answer set trips the medical gate. Used server-side to
     * enforce the data rule regardless of what the browser does.
     */
    public static function is_gate( $answers ) {
        $rules   = self::rules();
        $gate_id = isset( $rules['gate'] ) ? $rules['gate'] : '';
        if ( ! $gate_id || empty( $rules['findings'][ $gate_id ]['fires_when'] ) ) {
            return false;
        }
        $selected = ASQ_Config::selected_keys( (array) $answers );
        return null !== self::first_matching_clause( $rules['findings'][ $gate_id ]['fires_when'], $selected );
    }

    /* ────────── internals ────────── */

    public static function by_priority( $a, $b ) {
        $pa = isset( $a['priority'] ) ? (int) $a['priority'] : 50;
        $pb = isset( $b['priority'] ) ? (int) $b['priority'] : 50;
        return $pa <=> $pb;
    }

    /**
     * Return the triggering answers for the first clause that matches, or
     * null if no clause matches. A clause matches when every condition in it
     * is satisfied by the selected answers.
     */
    protected static function first_matching_clause( $clauses, $selected ) {
        foreach ( (array) $clauses as $clause ) {
            $triggers = array();
            $clause_ok = true;

            foreach ( (array) $clause as $cond ) {
                $qid          = isset( $cond['q'] ) ? $cond['q'] : '';
                $keys         = isset( $cond['keys'] ) ? (array) $cond['keys'] : array();
                $chosen       = isset( $selected[ $qid ] ) ? (array) $selected[ $qid ] : array();
                $matched_keys = array_values( array_intersect( $chosen, $keys ) );

                if ( empty( $matched_keys ) ) {
                    $clause_ok = false;
                    break;
                }

                foreach ( $matched_keys as $key ) {
                    $triggers[] = array(
                        'qid'      => $qid,
                        'question' => ASQ_Config::question_text_by_id( $qid ),
                        'answer'   => ASQ_Config::answer_text( $qid, $key ),
                    );
                }
            }

            if ( $clause_ok ) {
                return $triggers;
            }
        }
        return null;
    }

    /**
     * Shape one fired finding for output.
     */
    protected static function build( $id, $triggers ) {
        return array(
            'id'       => $id,
            'label'    => ASQ_Config::finding_label( $id ),
            'triggers' => $triggers,
        );
    }
}

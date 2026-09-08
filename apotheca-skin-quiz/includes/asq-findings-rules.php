<?php
/**
 * The findings engine rules, section 5 of the brief, as data.
 *
 * Each finding lists the situations that fire it. The structure reads like
 * the brief's table so a rule can be changed here without touching the
 * engine:
 *
 *   fires_when  A list of clauses. The finding fires if ANY clause is true.
 *               A clause is a list of conditions, ALL of which must be true.
 *               A condition is a question id plus the answer keys that satisfy
 *               it, e.g. array( 'q' => 'Q6', 'keys' => array( 'A' ) ) means
 *               "Q6 = A". Keys are the A/B/C... option letters from the
 *               question config.
 *   suppresses  Findings removed when this one fires (same problem described
 *               twice). F5 suppresses F2, F3 suppresses F4.
 *   priority    Lower fires and is listed first.
 *   fallback    Special findings that depend on what else fired:
 *                 none_fired      fires only when nothing else did (F10)
 *                 fewer_than_two  fires when fewer than two findings fired and
 *                                 its condition matches (F8, with Q1 = B)
 *
 * F11 is the gate: it overrides everything and stops evaluation.
 *
 * @package ApothecaSkinQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(

    // The finding that, when it fires, overrides all others and stops the engine.
    'gate' => 'F11',

    'findings' => array(

        // F11 ── Medical referral. Any tick on Q10 other than E.
        'F11' => array(
            'priority'   => 0,
            'fires_when' => array(
                array( array( 'q' => 'Q10', 'keys' => array( 'A', 'B', 'C', 'D' ) ) ),
            ),
        ),

        // F5 ── Barrier disruption. Q6=A, or Q6=B with Q2=A, or Q1=D.
        'F5' => array(
            'priority'   => 1,
            'fires_when' => array(
                array( array( 'q' => 'Q6', 'keys' => array( 'A' ) ) ),
                array( array( 'q' => 'Q6', 'keys' => array( 'B' ) ), array( 'q' => 'Q2', 'keys' => array( 'A' ) ) ),
                array( array( 'q' => 'Q1', 'keys' => array( 'D' ) ) ),
            ),
            'suppresses' => array( 'F2' ),
        ),

        // F3 ── Over-exfoliation. Q4=A, or Q4=D with (Q6=A or B).
        'F3' => array(
            'priority'   => 2,
            'fires_when' => array(
                array( array( 'q' => 'Q4', 'keys' => array( 'A' ) ) ),
                array( array( 'q' => 'Q4', 'keys' => array( 'D' ) ), array( 'q' => 'Q6', 'keys' => array( 'A', 'B' ) ) ),
            ),
            'suppresses' => array( 'F4' ),
        ),

        // F2 ── The cleanser is the problem. Q2=A or D (suppressed by F5).
        'F2' => array(
            'priority'   => 3,
            'fires_when' => array(
                array( array( 'q' => 'Q2', 'keys' => array( 'A', 'D' ) ) ),
            ),
        ),

        // F1 ── Dehydration, not dryness. Q3=A or C, or Q2=D with (Q7=A or B).
        'F1' => array(
            'priority'   => 4,
            'fires_when' => array(
                array( array( 'q' => 'Q3', 'keys' => array( 'A', 'C' ) ) ),
                array( array( 'q' => 'Q2', 'keys' => array( 'D' ) ), array( 'q' => 'Q7', 'keys' => array( 'A', 'B' ) ) ),
            ),
        ),

        // F4 ── Too many actives at once. Q5=C or D, or Q1=C, or Q4=D (suppressed by F3).
        'F4' => array(
            'priority'   => 5,
            'fires_when' => array(
                array( array( 'q' => 'Q5', 'keys' => array( 'C', 'D' ) ) ),
                array( array( 'q' => 'Q1', 'keys' => array( 'C' ) ) ),
                array( array( 'q' => 'Q4', 'keys' => array( 'D' ) ) ),
            ),
        ),

        // F6 ── Hormonal shift pattern. Q7=D, or (Q8=A or B) with (Q9=B, C or D).
        'F6' => array(
            'priority'   => 6,
            'fires_when' => array(
                array( array( 'q' => 'Q7', 'keys' => array( 'D' ) ) ),
                array( array( 'q' => 'Q8', 'keys' => array( 'A', 'B' ) ), array( 'q' => 'Q9', 'keys' => array( 'B', 'C', 'D' ) ) ),
            ),
        ),

        // F7 ── Congestion read as dryness. Q7=A with Q2=C, or Q3=C with Q7=A.
        'F7' => array(
            'priority'   => 7,
            'fires_when' => array(
                array( array( 'q' => 'Q7', 'keys' => array( 'A' ) ), array( 'q' => 'Q2', 'keys' => array( 'C' ) ) ),
                array( array( 'q' => 'Q3', 'keys' => array( 'C' ) ), array( 'q' => 'Q7', 'keys' => array( 'A' ) ) ),
            ),
        ),

        // F9 ── Environmental or seasonal. Q8=D, or Q8=A with Q9=A.
        'F9' => array(
            'priority'   => 8,
            'fires_when' => array(
                array( array( 'q' => 'Q8', 'keys' => array( 'D' ) ) ),
                array( array( 'q' => 'Q8', 'keys' => array( 'A' ) ), array( 'q' => 'Q9', 'keys' => array( 'A' ) ) ),
            ),
        ),

        // F8 ── Reading list only. Fewer than two findings fire and Q1=B.
        'F8' => array(
            'priority'  => 90,
            'fallback'  => 'fewer_than_two',
            'condition' => array( array( 'q' => 'Q1', 'keys' => array( 'B' ) ) ),
        ),

        // F10 ── Nothing obviously wrong. Fires only when nothing else does.
        'F10' => array(
            'priority' => 99,
            'fallback' => 'none_fired',
        ),

    ),
);

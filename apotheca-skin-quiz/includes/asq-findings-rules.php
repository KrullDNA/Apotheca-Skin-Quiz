<?php
/**
 * The findings engine rules, as data.
 *
 * Each finding lists the situations that fire it. The structure reads like a
 * table so a rule can be changed here without touching the engine:
 *
 *   fires_when  A list of clauses. The finding fires if ANY clause is true.
 *               A clause is a list of conditions, ALL of which must be true.
 *               A condition is a question id plus the answer keys that satisfy
 *               it, e.g. array( 'q' => 'Q6', 'keys' => array( 'A' ) ) means
 *               "Q6 = A". A condition may reference a follow-up question by its
 *               own id (Q2a, Q7a, Q8a) exactly as it references a main question;
 *               a follow-up answer only counts when its parent option is chosen,
 *               so branch answers are real, not cosmetic. A condition may add
 *               'min' => N to require at least N of its keys to be ticked
 *               (multi-select), e.g.
 *               array( 'q' => 'Q5', 'keys' => array( 'A','B','C' ), 'min' => 2 ).
 *   suppresses  Findings removed when this one fires (same problem described
 *               twice). An entry is either a plain finding id (unconditional,
 *               e.g. F5 suppresses F2), or an array
 *               array( 'id' => 'F4', 'when' => <clause> ) that suppresses only
 *               when the clause matches (F3 suppresses F4 only when Q5=A).
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

        // F11 ── Medical referral. Any tick on Q11 other than E.
        'F11' => array(
            'priority'   => 0,
            'fires_when' => array(
                array( array( 'q' => 'Q11', 'keys' => array( 'A', 'B', 'C', 'D' ) ) ),
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

        // F13 ── Barrier under-supported. She cleanses to a tight feeling (Q2=A)
        // and then doesn't reliably reseal with a moisturiser (Q2a=B or C). The
        // follow-up answer is what fires this, so it is a genuine adaptive result.
        // Distinct from F5 (a worn barrier) and F2 (a stripping cleanser): this
        // is the missing resealing step. It can sit alongside either; the
        // "worth trying" for each carries a different action, so it never repeats.
        'F13' => array(
            'priority'   => 2,
            'fires_when' => array(
                array( array( 'q' => 'Q2a', 'keys' => array( 'B', 'C' ) ) ),
            ),
        ),

        // F3 ── Over-exfoliation. Q4=A, or Q5=A (an acid or exfoliant is in use)
        // with (Q6=A or B).
        'F3' => array(
            'priority'   => 3,
            'fires_when' => array(
                array( array( 'q' => 'Q4', 'keys' => array( 'A' ) ) ),
                array( array( 'q' => 'Q5', 'keys' => array( 'A' ) ), array( 'q' => 'Q6', 'keys' => array( 'A', 'B' ) ) ),
            ),
            // Suppress F4 only when an acid is actually in use (Q5=A). Then
            // over-exfoliation and "too many actives" are the same behaviour and
            // F3 is the sharper reading. Where several actives are in use but no
            // acid, both findings are true and separate, and both must render.
            'suppresses' => array(
                array( 'id' => 'F4', 'when' => array( array( 'q' => 'Q5', 'keys' => array( 'A' ) ) ) ),
            ),
        ),

        // F2 ── The cleanser is doing too much. Q2=A or D (suppressed by F5).
        'F2' => array(
            'priority'   => 4,
            'fires_when' => array(
                array( array( 'q' => 'Q2', 'keys' => array( 'A', 'D' ) ) ),
            ),
        ),

        // F1 ── Dehydration, not dryness. Q3=A or C, or Q2=D with (Q7=A or B).
        'F1' => array(
            'priority'   => 5,
            'fires_when' => array(
                array( array( 'q' => 'Q3', 'keys' => array( 'A', 'C' ) ) ),
                array( array( 'q' => 'Q2', 'keys' => array( 'D' ) ), array( 'q' => 'Q7', 'keys' => array( 'A', 'B' ) ) ),
            ),
        ),

        // F4 ── Too many actives at once. Two or more of Q5 A/B/C ticked (acid,
        // retinoid, vitamin C), or Q1=C. Suppressed by F3 only when Q5=A is also
        // ticked.
        'F4' => array(
            'priority'   => 6,
            'fires_when' => array(
                array( array( 'q' => 'Q5', 'keys' => array( 'A', 'B', 'C' ), 'min' => 2 ) ),
                array( array( 'q' => 'Q1', 'keys' => array( 'C' ) ) ),
            ),
        ),

        // F14 ── Photoprotection gap. A daily-use gap (Q8=C or D) together with a
        // visible signal (Q8a=A or B: lingering redness or marks). Both are
        // required, so it never fires on the gap alone and stays advisory.
        'F14' => array(
            'priority'   => 7,
            'fires_when' => array(
                array( array( 'q' => 'Q8', 'keys' => array( 'C', 'D' ) ), array( 'q' => 'Q8a', 'keys' => array( 'A', 'B' ) ) ),
            ),
        ),

        // F6 ── Hormonal shift pattern. Skin that stopped being oily (Q7=D) with
        // corroborating change (Q7a=A or B), or a change over time (Q9=A or B)
        // in the 40+ ranges (Q10=B, C or D). A drop in oil on its own is not
        // read as hormonal without one of these.
        'F6' => array(
            'priority'   => 8,
            'fires_when' => array(
                array( array( 'q' => 'Q7', 'keys' => array( 'D' ) ), array( 'q' => 'Q7a', 'keys' => array( 'A', 'B' ) ) ),
                array( array( 'q' => 'Q9', 'keys' => array( 'A', 'B' ) ), array( 'q' => 'Q10', 'keys' => array( 'B', 'C', 'D' ) ) ),
            ),
        ),

        // F7 ── Congestion read as dryness. Q7=A with Q2=C, or Q3=C with Q7=A.
        'F7' => array(
            'priority'   => 9,
            'fires_when' => array(
                array( array( 'q' => 'Q7', 'keys' => array( 'A' ) ), array( 'q' => 'Q2', 'keys' => array( 'C' ) ) ),
                array( array( 'q' => 'Q3', 'keys' => array( 'C' ) ), array( 'q' => 'Q7', 'keys' => array( 'A' ) ) ),
            ),
        ),

        // F9 ── Environmental or seasonal. Q9=D, or Q9=A with Q10=A.
        'F9' => array(
            'priority'   => 10,
            'fires_when' => array(
                array( array( 'q' => 'Q9', 'keys' => array( 'D' ) ) ),
                array( array( 'q' => 'Q9', 'keys' => array( 'A' ) ), array( 'q' => 'Q10', 'keys' => array( 'A' ) ) ),
            ),
        ),

        // F12 ── Not sure what's in her products. Q5=E, or Q4=D. Carries the
        // Ingredient List Decoder link.
        'F12' => array(
            'priority'   => 11,
            'fires_when' => array(
                array( array( 'q' => 'Q5', 'keys' => array( 'E' ) ) ),
                array( array( 'q' => 'Q4', 'keys' => array( 'D' ) ) ),
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

<?php
/**
 * Apotheca Skin Quiz, single source of truth (v2.0.0, adaptive).
 *
 * The questions, their options, the follow-up questions some options open, and
 * the findings each answer contributes to all live here, in one place, so the
 * whole set can be edited without touching any flow logic.
 *
 * Structure:
 *   findings   id => human label
 *   questions  ordered list; each question has:
 *                id          short code (Q1..Q12), for the engine and admin
 *                text        the question shown to her
 *                instruction optional helper line under the question
 *                multiple    true for a multi-select question
 *                optional    true if she may decline to answer
 *                answers     ordered list; each answer has:
 *                              key        A, B, C ... (for the engine)
 *                              text       the option shown to her
 *                              findings   which findings this option feeds
 *                              note       optional supporting line under the option
 *                              safe       true for a "none of these" gate option
 *                              skip       true for a "rather not say" option
 *                              follow_up  optional. A conditional question shown
 *                                         only when this option is chosen. It has
 *                                         its own id (e.g. Q2a), text, optional
 *                                         instruction, multiple flag and answers.
 *                                         Its answers carry keys and findings just
 *                                         like a main question, so a branch answer
 *                                         genuinely fires findings, it is not
 *                                         cosmetic. Follow-up ids never collide
 *                                         with the main Qn ids.
 *
 * The order of questions and answers here IS the order shown on screen, and the
 * engine maps answers back by that same order. A follow-up answer only counts
 * when its parent option is actually selected, so a changed mind can never leave
 * a stale branch answer influencing the result.
 *
 * Finding firing rules, priority and suppression are NOT here; they are the job
 * of the findings engine in asq-findings-rules.php. This file only records which
 * findings each answer contributes to.
 *
 * Brand voice: plain words, contractions, UK English, no em dashes, no condition
 * ever named to her, no product ever recommended.
 *
 * @package ApothecaSkinQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(

    'findings' => array(
        'F1'  => __( 'Dehydration, not dryness', 'apotheca-skin-quiz' ),
        'F2'  => __( 'The cleanser is doing too much', 'apotheca-skin-quiz' ),
        'F3'  => __( 'Over-exfoliation', 'apotheca-skin-quiz' ),
        'F4'  => __( 'Too many actives at once', 'apotheca-skin-quiz' ),
        'F5'  => __( 'Barrier disruption', 'apotheca-skin-quiz' ),
        'F6'  => __( 'Hormonal shift pattern', 'apotheca-skin-quiz' ),
        'F7'  => __( 'Congestion read as dryness', 'apotheca-skin-quiz' ),
        'F8'  => __( 'Reading list only', 'apotheca-skin-quiz' ),
        'F9'  => __( 'Environmental or seasonal', 'apotheca-skin-quiz' ),
        'F10' => __( 'Nothing obviously wrong', 'apotheca-skin-quiz' ),
        'F11' => __( 'Medical referral', 'apotheca-skin-quiz' ),
        'F12' => __( "Not sure what's in her products", 'apotheca-skin-quiz' ),
        'F13' => __( 'Barrier under-supported', 'apotheca-skin-quiz' ),
        'F14' => __( 'Photoprotection gap', 'apotheca-skin-quiz' ),
    ),

    // Read-next mapping: each finding points at one or two Skin Topic terms, by
    // slug, on the shared taxonomy the Ingredient List Decoder registers. The
    // read-next block queries published posts carrying these terms.
    //
    // The decoder seeds these ten by default, so their slugs are:
    //   pigmentation, firmness-and-collagen, barrier-and-sensitivity,
    //   hydration, texture-and-pores, congestion, perimenopause-and-skin,
    //   ageing-and-cell-turnover, formulation-and-use-levels,
    //   clean-and-natural-origin
    // Edit the mapping here if you rename a term or add your own.
    'finding_topics' => array(
        'F1'  => array( 'hydration' ),
        'F2'  => array( 'barrier-and-sensitivity' ),
        'F3'  => array( 'barrier-and-sensitivity', 'texture-and-pores' ),
        'F4'  => array( 'formulation-and-use-levels', 'barrier-and-sensitivity' ),
        'F5'  => array( 'barrier-and-sensitivity' ),
        'F6'  => array( 'perimenopause-and-skin', 'ageing-and-cell-turnover' ),
        'F7'  => array( 'congestion', 'hydration' ),
        'F8'  => array( 'formulation-and-use-levels', 'clean-and-natural-origin' ),
        'F9'  => array( 'hydration', 'barrier-and-sensitivity' ),
        'F10' => array( 'ageing-and-cell-turnover' ),
        'F12' => array( 'formulation-and-use-levels' ),
        'F13' => array( 'barrier-and-sensitivity', 'hydration' ),
        'F14' => array( 'pigmentation', 'ageing-and-cell-turnover' ),
    ),

    // The gate offers at most one general, non-specific article. One term.
    'gate_topics' => array( 'ageing-and-cell-turnover' ),

    'questions' => array(

        // Q1 ── The framing question.
        array(
            'id'       => 'Q1',
            'text'     => __( 'Why are you taking the quiz today?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( "Something's changed and I'm not sure why", 'apotheca-skin-quiz' ), 'findings' => array( 'F6', 'F9' ) ),
                array( 'key' => 'B', 'text' => __( "My skin's never really had a calm baseline", 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( "I'm using a lot of things and I'm not seeing many results", 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'D', 'text' => __( 'Everything stings lately', 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
            ),
        ),

        // Q2 ── Tightness after cleansing. Option A opens a follow-up about
        // whether she reseals with a moisturiser, which is what actually fires
        // the barrier-under-supported finding (F13).
        array(
            'id'       => 'Q2',
            'text'     => __( 'How does your skin feel straight after you cleanse, before you put anything on?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array(
                    'key'       => 'A',
                    'text'      => __( 'Tight, like it needs something urgently', 'apotheca-skin-quiz' ),
                    'findings'  => array( 'F2', 'F5' ),
                    'follow_up' => array(
                        'id'       => 'Q2a',
                        'text'     => __( 'When it feels like that, do you put a moisturiser on fairly soon after?', 'apotheca-skin-quiz' ),
                        'multiple' => false,
                        'answers'  => array(
                            array( 'key' => 'A', 'text' => __( 'Yes, pretty much always', 'apotheca-skin-quiz' ), 'findings' => array() ),
                            array( 'key' => 'B', 'text' => __( 'Only sometimes', 'apotheca-skin-quiz' ), 'findings' => array( 'F13' ) ),
                            array( 'key' => 'C', 'text' => __( 'Not really, I tend to leave it a while', 'apotheca-skin-quiz' ), 'findings' => array( 'F13' ) ),
                        ),
                    ),
                ),
                array( 'key' => 'B', 'text' => __( 'Comfortable', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Already oily again', 'apotheca-skin-quiz' ), 'findings' => array( 'F7' ) ),
                array( 'key' => 'D', 'text' => __( 'Tight in some places, oily in others', 'apotheca-skin-quiz' ), 'findings' => array( 'F1', 'F2' ) ),
            ),
        ),

        // Q3 ── Dehydration vs dryness.
        array(
            'id'       => 'Q3',
            'text'     => __( 'When your skin looks dull or feels rough, does moisturiser fix it?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( "It helps for an hour or two, then it's back", 'apotheca-skin-quiz' ), 'findings' => array( 'F1' ) ),
                array( 'key' => 'B', 'text' => __( 'Yes, that sorts it', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Moisturiser makes it feel greasy but still rough underneath', 'apotheca-skin-quiz' ), 'findings' => array( 'F1' ) ),
                array( 'key' => 'D', 'text' => __( "I don't really notice this", 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q4 ── Exfoliation, in any form.
        array(
            'id'       => 'Q4',
            'text'     => __( 'How often do you exfoliate, in any form? Acids, scrubs, cleansing devices, peels.', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Most days', 'apotheca-skin-quiz' ), 'findings' => array( 'F3' ) ),
                array( 'key' => 'B', 'text' => __( 'Two or three times a week', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Once a week or less', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'D', 'text' => __( "I'm not sure whether some of my products count", 'apotheca-skin-quiz' ), 'findings' => array( 'F12' ) ),
            ),
        ),

        // Q5 ── What she's using now. Multi-select. Option A is deliberately
        // loose; option E (not sure) is a real answer and feeds F12.
        array(
            'id'          => 'Q5',
            'text'        => __( 'Which of these are you using at the moment?', 'apotheca-skin-quiz' ),
            'instruction' => __( 'Select all that apply.', 'apotheca-skin-quiz' ),
            'multiple'    => true,
            'answers'     => array(
                array( 'key' => 'A', 'text' => __( 'Something with an acid in it, or anything that exfoliates', 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'B', 'text' => __( 'A retinoid, retinol or retinal', 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'C', 'text' => __( 'Vitamin C', 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'D', 'text' => __( 'None of these', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'E', 'text' => __( "I'm not sure what's in my products", 'apotheca-skin-quiz' ), 'findings' => array( 'F12' ), 'note' => __( "Not sure? That's the most common answer. We'll show you how to find out at the end.", 'apotheca-skin-quiz' ) ),
            ),
        ),

        // Q6 ── Reacting to things that used to be fine.
        array(
            'id'       => 'Q6',
            'text'     => __( 'Have any products started stinging, flushing or reacting that used to be fine?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( "Yes, and it's most things now", 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
                array( 'key' => 'B', 'text' => __( 'Yes, one or two products', 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
                array( 'key' => 'C', 'text' => __( 'No', 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q7 ── Oiliness, including the quiet hormonal answer. Option D opens a
        // gentle follow-up that, together, fires the hormonal-shift finding (F6),
        // so a drop in oil on its own isn't read as hormonal without corroboration.
        array(
            'id'       => 'Q7',
            'text'     => __( 'How oily is your skin?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Oily across most of my face', 'apotheca-skin-quiz' ), 'findings' => array( 'F7' ) ),
                array( 'key' => 'B', 'text' => __( 'Oily through the middle, drier at the edges', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Rarely oily anywhere', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array(
                    'key'       => 'D',
                    'text'      => __( "It used to be oily and it isn't any more", 'apotheca-skin-quiz' ),
                    'findings'  => array( 'F6' ),
                    'follow_up' => array(
                        'id'       => 'Q7a',
                        'text'     => __( 'Has anything else shifted around the same time?', 'apotheca-skin-quiz' ),
                        'instruction' => __( 'Things like more dryness, less bounce or firmness, or the odd breakout along your jaw.', 'apotheca-skin-quiz' ),
                        'multiple' => false,
                        'answers'  => array(
                            array( 'key' => 'A', 'text' => __( 'Yes, other things have changed too', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                            array( 'key' => 'B', 'text' => __( 'Maybe one or two', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                            array( 'key' => 'C', 'text' => __( 'No, just the oil', 'apotheca-skin-quiz' ), 'findings' => array() ),
                        ),
                    ),
                ),
            ),
        ),

        // Q8 ── Everyday sunscreen. Option C or D (a daily-use gap) opens a
        // follow-up about a visible signal. The photoprotection finding (F14)
        // fires only when there is both a gap and a signal, so it stays advisory
        // and never reads as alarm.
        array(
            'id'       => 'Q8',
            'text'     => __( 'On a normal day, do you wear sunscreen on your face?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Yes, every day', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'B', 'text' => __( 'Most days', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array(
                    'key'       => 'C',
                    'text'      => __( "Only when it's sunny or hot", 'apotheca-skin-quiz' ),
                    'findings'  => array(),
                    'follow_up' => array(
                        'id'       => 'Q8a',
                        'text'     => __( 'Do you get redness, or marks and patches that linger?', 'apotheca-skin-quiz' ),
                        'instruction' => __( 'Especially after time outside, or later in the day.', 'apotheca-skin-quiz' ),
                        'multiple' => false,
                        'answers'  => array(
                            array( 'key' => 'A', 'text' => __( 'Yes, fairly often', 'apotheca-skin-quiz' ), 'findings' => array( 'F14' ) ),
                            array( 'key' => 'B', 'text' => __( 'Sometimes', 'apotheca-skin-quiz' ), 'findings' => array( 'F14' ) ),
                            array( 'key' => 'C', 'text' => __( 'Not really', 'apotheca-skin-quiz' ), 'findings' => array() ),
                        ),
                    ),
                ),
                array(
                    'key'       => 'D',
                    'text'      => __( 'Rarely or never', 'apotheca-skin-quiz' ),
                    'findings'  => array(),
                    'follow_up' => array(
                        'id'       => 'Q8a',
                        'text'     => __( 'Do you get redness, or marks and patches that linger?', 'apotheca-skin-quiz' ),
                        'instruction' => __( 'Especially after time outside, or later in the day.', 'apotheca-skin-quiz' ),
                        'multiple' => false,
                        'answers'  => array(
                            array( 'key' => 'A', 'text' => __( 'Yes, fairly often', 'apotheca-skin-quiz' ), 'findings' => array( 'F14' ) ),
                            array( 'key' => 'B', 'text' => __( 'Sometimes', 'apotheca-skin-quiz' ), 'findings' => array( 'F14' ) ),
                            array( 'key' => 'C', 'text' => __( 'Not really', 'apotheca-skin-quiz' ), 'findings' => array() ),
                        ),
                    ),
                ),
            ),
        ),

        // Q9 ── What has changed. Self-contained (names "your skin") and the
        // options are the changes themselves, so the question and answers sit on
        // the same footing. Multi-select: change is rarely one thing. The first
        // three are the perimenopause cluster (feed F6), the fourth is seasonal
        // (feeds F9), and "no, it's been steady" clears it.
        array(
            'id'          => 'Q9',
            'text'        => __( 'Over the last couple of years, what changes do you see in your skin?', 'apotheca-skin-quiz' ),
            'instruction' => __( 'Select all that apply.', 'apotheca-skin-quiz' ),
            'multiple'    => true,
            'answers'     => array(
                array( 'key' => 'A', 'text' => __( "It's drier or tighter than it used to be", 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'B', 'text' => __( "It's less firm, or a bit less bouncy", 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'C', 'text' => __( 'More breakouts, especially along my jaw or chin', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'D', 'text' => __( 'It flares up with the weather or the seasons', 'apotheca-skin-quiz' ), 'findings' => array( 'F9' ) ),
                array( 'key' => 'E', 'text' => __( "No, it's been steady", 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q10 ── Age range. Skippable, with the reason given in the question.
        array(
            'id'       => 'Q10',
            'text'     => __( 'What is your age group?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'optional' => true,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( '30-39', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'B', 'text' => __( '40-49', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'C', 'text' => __( '50-59', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'D', 'text' => __( '60+', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'E', 'text' => __( "I'd rather not say", 'apotheca-skin-quiz' ), 'findings' => array(), 'skip' => true ),
            ),
        ),

        // Q11 ── Gender. Used only to switch the hormonal reading (F6) off for
        // men; it feeds no finding of its own. "Prefer not to say" keeps the
        // reading available, as it is suppressed only for "Man".
        array(
            'id'       => 'Q11',
            'text'     => __( "What's your gender?", 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Woman', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'B', 'text' => __( 'Man', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Non-binary', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'D', 'text' => __( 'Prefer not to say', 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q12 ── The medical safety gate. Multi-select.
        array(
            'id'          => 'Q12',
            'text'        => __( 'Are any of these happening with your skin?', 'apotheca-skin-quiz' ),
            'instruction' => __( 'Select all that apply.', 'apotheca-skin-quiz' ),
            'multiple'    => true,
            'answers'     => array(
                array( 'key' => 'A', 'text' => __( 'Redness that keeps hanging around', 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'B', 'text' => __( "Sore bumps that won't clear up", 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'C', 'text' => __( "A spot or patch that's slow to heal", 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'D', 'text' => __( 'A mole or freckle that looks different lately', 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'E', 'text' => __( 'None of these', 'apotheca-skin-quiz' ), 'findings' => array(), 'safe' => true ),
            ),
        ),

    ),
);

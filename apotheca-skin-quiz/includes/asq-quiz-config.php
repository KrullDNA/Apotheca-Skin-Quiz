<?php
/**
 * Apotheca Skin Quiz, single source of truth.
 *
 * The ten questions, their options and the findings each option contributes
 * to all live here, in one place, so the whole set can be edited without
 * touching any flow logic. This is section 4 of the project brief.
 *
 * Structure:
 *   findings   id => human label (section 5 of the brief)
 *   questions  ordered list; each question has:
 *                id          short code (Q1..Q10), for the engine and admin
 *                text        the question shown to her
 *                instruction optional helper line under the question
 *                multiple    true only for the multi-select question (Q10)
 *                optional    true if she may decline to answer (Q9)
 *                answers     ordered list; each answer has:
 *                              key       A, B, C ... (for the engine)
 *                              text      the option shown to her
 *                              findings  which findings this option feeds
 *                              safe      true for a "none of these" style option
 *
 * The order of questions and answers here IS the order shown on screen, and
 * the engine and the placeholder result map answers back by that same order.
 *
 * Finding firing rules, priority and suppression are NOT here; they are the
 * job of the findings engine in a later stage. This file only records which
 * findings each answer contributes to (the arrows in section 4 of the brief).
 *
 * @package ApothecaSkinQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(

    'findings' => array(
        'F1'  => __( 'Dehydration, not dryness', 'apotheca-skin-quiz' ),
        'F2'  => __( 'The cleanser is the problem', 'apotheca-skin-quiz' ),
        'F3'  => __( 'Over-exfoliation', 'apotheca-skin-quiz' ),
        'F4'  => __( 'Too many actives at once', 'apotheca-skin-quiz' ),
        'F5'  => __( 'Barrier disruption', 'apotheca-skin-quiz' ),
        'F6'  => __( 'Hormonal shift pattern', 'apotheca-skin-quiz' ),
        'F7'  => __( 'Congestion read as dryness', 'apotheca-skin-quiz' ),
        'F8'  => __( 'Reading list only', 'apotheca-skin-quiz' ),
        'F9'  => __( 'Environmental or seasonal', 'apotheca-skin-quiz' ),
        'F10' => __( 'Nothing obviously wrong', 'apotheca-skin-quiz' ),
        'F11' => __( 'Medical referral', 'apotheca-skin-quiz' ),
    ),

    'questions' => array(

        // Q1 ── The framing question.
        array(
            'id'       => 'Q1',
            'text'     => __( 'What brought you here today?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( "Something has changed and I don't know why", 'apotheca-skin-quiz' ), 'findings' => array( 'F6', 'F9' ) ),
                array( 'key' => 'B', 'text' => __( "It's never really settled", 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( "I'm using a lot of things and I'm not sure any of it is working", 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'D', 'text' => __( 'Everything stings lately', 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
            ),
        ),

        // Q2 ── Tightness after cleansing.
        array(
            'id'       => 'Q2',
            'text'     => __( 'How does your skin feel twenty minutes after you cleanse, before you put anything on?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Tight, like it needs something urgently', 'apotheca-skin-quiz' ), 'findings' => array( 'F2', 'F5' ) ),
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
                array( 'key' => 'D', 'text' => __( "I don't really get this", 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q4 ── Exfoliation, in any form.
        array(
            'id'          => 'Q4',
            'text'        => __( 'How often do you exfoliate, in any form? Acids, scrubs, cleansing devices, peels.', 'apotheca-skin-quiz' ),
            'multiple'    => false,
            'answers'     => array(
                array( 'key' => 'A', 'text' => __( 'Most days', 'apotheca-skin-quiz' ), 'findings' => array( 'F3' ) ),
                array( 'key' => 'B', 'text' => __( 'Two or three times a week', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Once a week or less', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'D', 'text' => __( "I'm not sure whether some of my products count", 'apotheca-skin-quiz' ), 'findings' => array( 'F3', 'F4' ) ),
            ),
        ),

        // Q5 ── How many actives.
        array(
            'id'       => 'Q5',
            'text'     => __( 'How many products are you currently using that contain a retinoid, an acid, or vitamin C?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'None that I know of', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'B', 'text' => __( 'One', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Two or three', 'apotheca-skin-quiz' ), 'findings' => array( 'F4' ) ),
                array( 'key' => 'D', 'text' => __( "More than three, or I've lost track", 'apotheca-skin-quiz' ), 'findings' => array( 'F4', 'F5' ) ),
            ),
        ),

        // Q6 ── Reacting to things that used to be fine.
        array(
            'id'       => 'Q6',
            'text'     => __( 'Has anything started stinging, flushing or reacting that used to be fine?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( "Yes, and it's most things now", 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
                array( 'key' => 'B', 'text' => __( 'Yes, one or two products', 'apotheca-skin-quiz' ), 'findings' => array( 'F5' ) ),
                array( 'key' => 'C', 'text' => __( 'No', 'apotheca-skin-quiz' ), 'findings' => array() ),
            ),
        ),

        // Q7 ── Oiliness, including the quiet hormonal answer.
        array(
            'id'       => 'Q7',
            'text'     => __( 'Where does your skin sit on oiliness?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Oily across most of my face', 'apotheca-skin-quiz' ), 'findings' => array( 'F7' ) ),
                array( 'key' => 'B', 'text' => __( 'Oily through the middle, drier at the edges', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'C', 'text' => __( 'Rarely oily anywhere', 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'D', 'text' => __( "It used to be oily and it isn't any more", 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
            ),
        ),

        // Q8 ── Change over the last two years.
        array(
            'id'       => 'Q8',
            'text'     => __( 'Has any of this changed in the last two years?', 'apotheca-skin-quiz' ),
            'multiple' => false,
            'answers'  => array(
                array( 'key' => 'A', 'text' => __( 'Yes, fairly suddenly', 'apotheca-skin-quiz' ), 'findings' => array( 'F6', 'F9' ) ),
                array( 'key' => 'B', 'text' => __( 'Yes, gradually', 'apotheca-skin-quiz' ), 'findings' => array( 'F6' ) ),
                array( 'key' => 'C', 'text' => __( "No, it's been like this a long time", 'apotheca-skin-quiz' ), 'findings' => array() ),
                array( 'key' => 'D', 'text' => __( 'It changes with the seasons', 'apotheca-skin-quiz' ), 'findings' => array( 'F9' ) ),
            ),
        ),

        // Q9 ── Age range. Skippable, with the reason given in the question.
        array(
            'id'       => 'Q9',
            'text'     => __( 'Which age range are you in? This changes what is most likely, and nothing else.', 'apotheca-skin-quiz' ),
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

        // Q10 ── The medical safety gate. Multi-select.
        array(
            'id'          => 'Q10',
            'text'        => __( 'Is any of this happening?', 'apotheca-skin-quiz' ),
            'instruction' => __( 'Tick anything that applies.', 'apotheca-skin-quiz' ),
            'multiple'    => true,
            'answers'     => array(
                array( 'key' => 'A', 'text' => __( "Redness that doesn't settle down", 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'B', 'text' => __( 'Deep or painful lumps under the skin', 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'C', 'text' => __( 'Something spreading, weeping or not healing', 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'D', 'text' => __( 'A mole or mark that has changed', 'apotheca-skin-quiz' ), 'findings' => array( 'F11' ) ),
                array( 'key' => 'E', 'text' => __( 'None of these', 'apotheca-skin-quiz' ), 'findings' => array(), 'safe' => true ),
            ),
        ),

    ),
);

<?php
/**
 * The reading, every sentence in one place.
 *
 * Keyed by finding and by section so any wording can be changed here without
 * touching the renderer. Written in the Apotheca® brand voice: plain words,
 * contractions, UK English, no em dashes, no condition ever named, no product
 * ever recommended.
 *
 * Tokens the renderer understands inside any sentence:
 *   {a:Q2}      her actual answer to that question, woven into the sentence
 *   {em}…{/em}  an emphasised phrase (the reframe), styled with the accent
 *
 * Straight apostrophes here are fine: the renderer runs the text through
 * wptexturize(), so the reader sees proper curly quotes.
 *
 * worth_trying is a keyed action. When several findings point at the same
 * action (for example pausing actives), the renderer shows it once, and the
 * whole section is capped at two, so a long result never turns into a
 * to-do list.
 *
 * @package ApothecaSkinQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(

    // The four section headings, in fixed order.
    'sections' => array(
        'describing'   => __( "What you're describing", 'apotheca-skin-quiz' ),
        'probably_not' => __( "What it probably isn't", 'apotheca-skin-quiz' ),
        'worth_trying' => __( 'One or two things worth trying', 'apotheca-skin-quiz' ),
        'read_next'    => __( 'Read next', 'apotheca-skin-quiz' ),
    ),

    // Lead-in above the read-next articles (used from Stage 8).
    'read_next_intro' => __( "A few things worth reading next.", 'apotheca-skin-quiz' ),

    // The medical gate response (used from Stage 7). Calm, names nothing,
    // offers no reassurance, makes no attempt to be clever.
    'gate' => array(
        'heading' => __( 'Worth showing to someone', 'apotheca-skin-quiz' ),
        'body'    => __( "One of the things you ticked is worth showing to a doctor or a pharmacist, rather than working through with us. It can be looked at properly, and the things that help are mostly not skincare. We've stopped the rest of the reading here on purpose. Anything we said about routines would be beside the point, and we'd rather say less than send you off in the wrong direction.", 'apotheca-skin-quiz' ),
    ),

    'findings' => array(

        // F5 ── Barrier disruption.
        'F5' => array(
            'describing'   => __( "The way your skin's been reacting, and how tight it feels not long after cleansing, point at {em}a barrier that's been worn down{/em} rather than skin that's simply turned sensitive. That difference matters, because a worn barrier can be rebuilt, and it usually settles once you stop asking so much of it.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Skin that suddenly reacts to things that were fine for years is more often a barrier that can't hold anything out at the moment than a brand new allergy. Reaching for sensitive-skin products is a fair response, and it tends to treat the feeling rather than the cause.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Give everything with an active in it a rest for a couple of weeks. Cleanse, moisturise, wear sunscreen, and nothing else. If things calm down you'll know what was behind it, and you can bring one thing back at a time.", 'apotheca-skin-quiz' ) ),
        ),

        // F3 ── Over-exfoliation.
        'F3' => array(
            'describing'   => __( "You're exfoliating more often than your skin is likely to want, and probably more often than you think, because acids hide in toners, cleansers and moisturisers as easily as in anything labelled a scrub or a peel.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Rough, dull skin feels like it needs more exfoliating, which is the trap. Past a certain point, exfoliating is what's keeping it rough, not what's putting it right.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Give everything that exfoliates a rest for a couple of weeks, acids and scrubs and cleansing brushes included, and see whether the roughness you're chasing eases off on its own.", 'apotheca-skin-quiz' ) ),
        ),

        // F2 ── The cleanser is the problem.
        'F2' => array(
            'describing'   => __( "You told us that not long after cleansing, before anything goes back on, your skin feels {al:Q2}. That tightness is about the clearest sign there is that {em}the cleanser is doing too much{/em}, and it's almost the last thing most people suspect.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "That tight feeling reads as dryness, so the usual next move is a richer moisturiser. It rarely helps for long, because the trouble is what's being stripped away first, not what's being put back afterwards.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'gentler_cleanser', 'text' => __( "For a couple of weeks, change nothing but the cleanser, and use the mildest one you have. If the tightness eases, you've found it.", 'apotheca-skin-quiz' ) ),
        ),

        // F1 ── Dehydration, not dryness.
        'F1' => array(
            'describing'   => __( "Moisturiser helps for an hour or two and then the dullness is back. That's the signature of skin that's short of {em}water{/em} rather than short of oil, and the two want different things.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This usually isn't dryness in the sense of needing a heavier cream, though that's the reflex. Dry skin is short of oil. What you're describing sounds more like water leaving faster than it should, which is why a richer cream can feel greasy on top and still rough underneath.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'water_then_seal', 'text' => __( "The useful move here usually isn't heavier, it's getting water into the skin and then keeping it there. Look at whether anything in your routine is doing that first job at all, before you reach for something richer.", 'apotheca-skin-quiz' ) ),
        ),

        // F4 ── Too many actives at once.
        'F4' => array(
            'describing'   => __( "You're using several strong ingredients at once, and it's easy to lose count, since a retinoid, an acid and a vitamin C can each turn up in more than one product. Used all together they often work against each other rather than adding up.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "When a routine like this isn't working, it can look like you need something stronger. More often there's already too much going on for any one thing to do its job.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Pare it right back for a couple of weeks, then bring one active back at a time. It's the only way to see what's actually helping and what's just along for the ride.", 'apotheca-skin-quiz' ) ),
        ),

        // F6 ── Hormonal shift pattern. Brand voice may name the life stage.
        'F6' => array(
            'describing'   => __( "Some of what you've told us fits a pattern of change rather than anything you've done. Skin that was reliably oily and quietly stopped being oily, or that shifted fairly suddenly from your 40s on, is one of the more recognisable signs of perimenopause and menopause, and it tends to arrive without much warning.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This isn't a sign you've started doing something wrong. It's more often that a routine built for the skin you had is quietly no longer the routine for the skin you've got.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'question_routine', 'text' => __( "Look at what your current products are for, rather than what they cost. Anything chosen years ago to control oil or to mattify is worth questioning first.", 'apotheca-skin-quiz' ) ),
        ),

        // F7 ── Congestion read as dryness.
        'F7' => array(
            'describing'   => __( "Your skin sits on the oilier side and yet it can feel tight or rough at the same time. That combination is often {em}congestion being read as dryness{/em}, and it wants the opposite of what dryness would.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "The instinct is to add richness to the rough patches. On skin that's already oily underneath, that usually adds to the congestion rather than easing it.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'stop_stripping', 'text' => __( "Ease off anything that strips or mattifies, and give the skin a chance to settle before you decide it needs more.", 'apotheca-skin-quiz' ) ),
        ),

        // F9 ── Environmental or seasonal.
        'F9' => array(
            'describing'   => __( "A good deal of what you've described tracks with the weather and your surroundings rather than with your skin itself. Skin that changes with the seasons, or that shifted around a move or a change in climate, is usually responding to what's around it.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "It's tempting to overhaul the whole routine when this happens. More often the routine was fine, and it's the conditions around it that changed.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'adjust_seasonally', 'text' => __( "Treat it as something to adjust rather than fix. A lighter touch in humidity, a little more protection in cold or wind, and less pressure on any one product to solve it.", 'apotheca-skin-quiz' ) ),
        ),

        // F10 ── Nothing obviously wrong. Deliberately short.
        'F10' => array(
            'describing'   => __( "Here's the honest version: nothing you've told us points to a clear problem. Your answers describe skin that's behaving, more or less, and a routine that isn't fighting it.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'change_one_thing', 'text' => __( "If it isn't broken, you don't have to fix it. If you do change something, change one thing at a time, so you can actually tell what it did.", 'apotheca-skin-quiz' ) ),
        ),

        // F8 ── Reading list only. Leans on the read-next block.
        'F8' => array(
            'describing'   => __( "You've told us this has never quite settled, and nothing in your answers points to one clear thing to change. That isn't a dead end. Sometimes the most useful next step is to read a little, rather than to buy something or swap everything at once.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'read_first', 'text' => __( "Before you change anything, have a read of what's below. It's a better use of the next ten minutes than another purchase.", 'apotheca-skin-quiz' ) ),
        ),

    ),
);

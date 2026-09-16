<?php
/**
 * The reading, every sentence in one place.
 *
 * Keyed by finding and by section so any wording can be changed here without
 * touching the renderer. Written in the Apotheca® brand voice: plain words,
 * contractions, UK English, no em dashes, no product ever recommended. Brand
 * voice may name the life stage plainly (perimenopause, menopause); it never
 * diagnoses a medical condition or writes as if it has lived the experience.
 *
 * Tokens the renderer understands inside any sentence:
 *   {a:Q2}      her actual answer to that question, woven into the sentence
 *   {ticked}    the medical-gate concern(s) she ticked, named back to her, for
 *               use in the gate body only (empty everywhere else)
 *   {em}…{/em}  an emphasised phrase (the reframe), styled with the accent
 *   {decoder}…{/decoder}  a link to the Ingredient List Decoder page set on the
 *                Elementor widget, opening in a new tab with from=skin-quiz
 *                appended. If no page is set, the wrapped words render as plain
 *                text, never a broken or empty link.
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

    // The section headings, in fixed order. "Good to know" is the optional
    // deeper-detail block, shown collapsed so the results stay calm to scan.
    'sections' => array(
        'describing'   => __( "What you're describing", 'apotheca-skin-quiz' ),
        'probably_not' => __( "What it probably isn't", 'apotheca-skin-quiz' ),
        'worth_trying' => __( 'One or two things worth trying', 'apotheca-skin-quiz' ),
        'good_to_know' => __( 'Good to know', 'apotheca-skin-quiz' ),
        'read_next'    => __( 'Read next', 'apotheca-skin-quiz' ),
    ),

    // Lead-in above the read-next articles, and the card link label.
    'read_next_intro' => __( 'A few things worth reading next.', 'apotheca-skin-quiz' ),
    'read_more'       => __( 'Read more', 'apotheca-skin-quiz' ),

    // The medical gate response (used from Stage 7). Calm, names nothing,
    // offers no reassurance, makes no attempt to be clever.
    'gate' => array(
        'heading'   => __( 'Worth showing to someone', 'apotheca-skin-quiz' ),
        'body'      => __( "What you flagged, {ticked}, is worth showing to a doctor or a pharmacist rather than working through with us. It can be looked at properly, and the things that help are mostly not skincare. We've stopped the rest of your results here on purpose. Anything we might say about routines wouldn't help you here, and we'd rather say less than point you the wrong way.", 'apotheca-skin-quiz' ),
        'email_ack'       => __( "Thanks for taking the quiz. Based on one of your answers, we'd gently suggest showing that to a doctor or a pharmacist rather than working through it with us. We haven't put together your results this time, on purpose, and we'd rather say less than point you in the wrong direction.", 'apotheca-skin-quiz' ),
        'read_next_intro' => __( "If it's useful, here's what's worth reading in the meantime.", 'apotheca-skin-quiz' ),
    ),

    'findings' => array(

        // F5 ── Barrier disruption.
        'F5' => array(
            'describing'   => __( "The way your skin's been reacting, and how tight it feels not long after cleansing, point at {em}a barrier that's been worn down{/em} rather than skin that's simply turned sensitive. That difference matters, because a worn barrier can be rebuilt, and it usually settles once you stop asking so much of it.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Skin that suddenly reacts to things that were fine for years is more often a barrier that can't hold anything out at the moment than a brand new allergy. Reaching for sensitive-skin products is a fair response, and it tends to treat the feeling rather than the cause.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Give everything with an active in it a rest for a couple of weeks. Cleanse, moisturise, wear sunscreen, and nothing else. If things calm down you'll know what was behind it, and you can bring one thing back at a time.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What 'barrier' actually means", 'apotheca-skin-quiz' ),
                'text'  => __( "The barrier is your skin's outermost layer, and it works a bit like a brick wall, skin cells for the bricks and a mix of oils and ceramides for the mortar. Ceramides are the skin's own barrier fats. When that mortar gets stripped, water escapes and things that would normally stay out get in more easily, which is the reacting and stinging you've been feeling. The good news is it rebuilds itself given the chance, usually over a few weeks, as long as you stop wearing it down faster than it can recover.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F3 ── Over-exfoliation.
        'F3' => array(
            'describing'   => __( "You're exfoliating more often than your skin is likely to want, and probably more often than you think, because acids hide in toners, cleansers and moisturisers as easily as in anything labelled a scrub or a peel.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Rough, dull skin feels like it needs more exfoliating, which is the trap. Past a certain point, exfoliating is what's keeping it rough, not what's putting it right.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Give everything that exfoliates a rest for a couple of weeks, acids and scrubs and cleansing brushes included, and see whether the roughness you're chasing eases off on its own.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'How exfoliating too often backfires', 'apotheca-skin-quiz' ),
                'text'  => __( "Exfoliating speeds up how fast your skin sheds its surface cells. Now and then, that leaves it smoother. Too often, and you're clearing the surface away faster than the skin can rebuild it, so it never gets to finish the wall, and the roughness and sensitivity you're trying to fix are partly the exfoliating itself. Acids count here as much as scrubs, and they hide in cleansers, toners and moisturisers, so it adds up quicker than it looks.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F2 ── The cleanser is the problem.
        'F2' => array(
            'describing'   => __( "You told us that not long after cleansing, before anything goes back on, your skin feels {al:Q2}. That tightness is about the clearest sign there is that {em}the cleanser is doing too much{/em}, and it's almost the last thing most people suspect.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "That tight feeling reads as dryness, so the usual next move is a richer moisturiser. It rarely helps for long, because the trouble is what's being stripped away first, not what's being put back afterwards.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'gentler_cleanser', 'text' => __( "For a couple of weeks, change nothing but the cleanser, and use the mildest one you have. If the tightness eases, you've found it.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'What a cleanser can quietly take with it', 'apotheca-skin-quiz' ),
                'text'  => __( "Your skin sits at a slightly acidic pH, around 5, which is what its protective layer likes best. A high-pH or heavily foaming cleanser can nudge that upward and strip away the oils your skin uses to hold water in, and that's usually where the tight feeling comes from. A milder, lower-pH cleanser lifts off dirt and make-up just as well, without taking the good stuff with it.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F1 ── Dehydration, not dryness.
        'F1' => array(
            'describing'   => __( "Moisturiser helps for an hour or two and then the dullness is back. That's the signature of skin that's short of {em}water{/em} rather than short of oil, and the two want different things.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This usually isn't dryness, the sort a heavier moisturiser fixes, even though that's the first thing most of us reach for. Dry skin is short of oil. What you're describing sounds more like water escaping faster than it should, which is why a richer moisturiser can sit greasy on top and still feel rough underneath.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'water_then_seal', 'text' => __( "The useful move here usually isn't heavier, it's getting water into the skin and then holding it there, and that's two jobs, not one. First, get the water in. Look for a humectant, an ingredient that pulls water towards the skin and holds it, like glycerine, hyaluronic acid, panthenol or urea, sitting near the top of the ingredient list rather than the bottom. Put it on while your skin's still slightly damp. Then seal it in. That's where a good moisturiser earns its place, the richer step that sits on top and slows the water escaping again. Ingredients like shea butter, plant oils or squalane are the ones doing that sealing work. One draws the water in, the other stops it drifting off, and skin that's been feeling tight usually needs both.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Getting water in, and keeping it there', 'apotheca-skin-quiz' ),
                'text'  => __( "That sealing step has a proper name, an occlusive, which just means something that sits on the surface and slows water evaporating out of the skin. Squalane, shea butter and plant oils all do that job. Ceramides are worth knowing too, they're the skin's own barrier fats, the mortar between skin cells, and topping them up helps your skin hold on to water by itself rather than leaning on something sitting on top. So a humectant brings the water, and an occlusive or some ceramides keep it from leaving.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F4 ── Too many actives at once.
        'F4' => array(
            'describing'   => __( "You're using several strong ingredients at once, and it's easy to lose count, since a retinoid, an acid and a vitamin C can each turn up in more than one product. Used all together they often work against each other rather than adding up.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "When a routine like this isn't working, it can look like you need something stronger. More often there's already too much going on for any one thing to do its job.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Pare it right back for a couple of weeks, then bring one active back at a time. It's the only way to see what's actually helping and what's just along for the ride.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "Why more actives isn't more results", 'apotheca-skin-quiz' ),
                'text'  => __( "Actives are the ingredients doing the real work, retinoids, which are vitamin A ingredients, vitamin C, exfoliating acids and the like. Each one asks something of your skin, and they each work best in their own conditions, some in acid, some not. Stack too many at once and they can cancel each other out, or simply overwhelm the barrier, so you get the irritation without the benefit. A few, used properly, almost always beats many used together.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F6 ── Hormonal shift pattern. Brand voice may name the life stage.
        'F6' => array(
            'describing'   => __( "Some of what you've told us fits a pattern of change rather than anything you've done. Skin getting drier, a little less firm, or breaking out along the jaw when it never used to, often more than one of those at once from your late 30s or 40s on, is one of the more recognisable signs of perimenopause and menopause. It tends to arrive without much warning, and it isn't something you've brought on.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This isn't a sign you've started doing something wrong. It's more often that a routine built for the skin you had is quietly no longer the routine for the skin you've got.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'question_routine', 'text' => __( "Look at what your current products are for, rather than what they cost. Anything chosen years ago to control oil or to mattify is worth questioning first.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What's changing underneath", 'apotheca-skin-quiz' ),
                'text'  => __( "As oestrogen dips through perimenopause and menopause, your skin makes less of two things it used to lean on, oil and collagen. Less oil means it holds water less easily, so it can feel drier. Less collagen, the protein that keeps skin springy, means it can feel a little less firm. None of that is something you've caused, and it doesn't mean your skin is damaged, it's a shift in what your skin needs, which is why a routine built for oilier, younger skin can suddenly feel wrong.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F7 ── Congestion read as dryness.
        'F7' => array(
            'describing'   => __( "Your skin sits on the oilier side and yet it can feel tight or rough at the same time. That combination is often {em}congestion being read as dryness{/em}, and it wants the opposite of what dryness would.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "The instinct is to add richness to the rough patches. On skin that's already oily underneath, that usually adds to the congestion rather than easing it.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'stop_stripping', 'text' => __( "Ease off anything that strips or mattifies, and give the skin a chance to settle before you decide it needs more.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Oily and rough at the same time', 'apotheca-skin-quiz' ),
                'text'  => __( "Skin can be oily underneath and still feel rough or tight on top, which is confusing, because it looks like two opposite problems at once. Usually it's congestion, oil and dead cells caught in the pores, rather than a shortage of moisture. Piling richer creams on top tends to feed that. Easing off the stripping and the mattifying, and giving your skin a chance to settle, usually does more than adding anything new.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F9 ── Environmental or seasonal.
        'F9' => array(
            'describing'   => __( "A good deal of what you've described tracks with the weather and your surroundings rather than with your skin itself. Skin that changes with the seasons, or that shifted around a move or a change in climate, is usually responding to what's around it.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "It's tempting to overhaul the whole routine when this happens. More often the routine was fine, and it's the conditions around it that changed.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'adjust_seasonally', 'text' => __( "Treat it as something to adjust rather than fix. A lighter touch in humidity, a little more protection in cold or wind, and less pressure on any one product to solve it.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Why skin shifts with the weather', 'apotheca-skin-quiz' ),
                'text'  => __( "Skin loses water faster in dry, cold or windy air, and holds on to it more easily when it's humid, so the same routine can feel just right in one season and not enough in another. Heating and air conditioning do the same thing indoors. It's less that something's gone wrong, and more that the conditions around your skin changed, so the fix is usually to move with them, a bit more protection when it's harsh, a lighter touch when it's humid.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F12 ── Not sure what's in her products. Not a fault. Carries the
        // Ingredient List Decoder link in worth_trying via the {decoder} token,
        // which the renderer turns into a link (or plain text if no URL is set).
        'F12' => array(
            'describing'   => __( "You've told us you're not entirely sure what's in your products, and honestly, that's the most common answer we get. Acids and retinoids turn up in toners, cleansers and moisturisers as readily as in anything labelled a treatment, so it's easy to be {em}using more than you think{/em}, without putting a foot wrong.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'decode_products', 'text' => __( "The simplest next step is to find out what you're actually using. {decoder}Paste your products into our Ingredient List Decoder{/decoder} and it'll tell you what's in there, in plain English.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'How to read an ingredient list', 'apotheca-skin-quiz' ),
                'text'  => __( "Ingredients are listed in order of how much is in the product, most first, down to the least. So whatever sits near the top is most of what you're putting on, and anything past the halfway mark is usually there in tiny amounts. That's why a vitamin C serum with the vitamin C near the bottom of the list may have very little in it. Our decoder does this reading for you, but it's a handy thing to know when you're stood in a shop holding a bottle.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F13 ── Barrier under-supported. She cleanses to a tight feeling and
        // doesn't reliably reseal with a moisturiser. Distinct action from F5.
        'F13' => array(
            'describing'   => __( "You told us your skin feels {al:Q2} after cleansing, and that a moisturiser doesn't always go straight back on. That's worth pausing on, because {em}the step that's missing is the one that seals everything in{/em}. Bare skin loses water faster than most people expect, so cleansing or treating and then leaving it is often what's behind that tight, thin feeling.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This usually isn't skin that needs a stronger treatment or a richer moisturiser. It's more often skin that's being cleansed or worked on and then left to fend for itself, so the good you're doing quietly leaks back out.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'reseal', 'text' => __( "For the next couple of weeks, treat moisturiser as the step you don't skip, not the optional one. Put it on within a minute or two of cleansing, while your skin's still a little damp, and see whether that tightness eases on its own.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Why bare skin loses water so fast', 'apotheca-skin-quiz' ),
                'text'  => __( "Straight after cleansing, your skin has nothing on top to slow water escaping, so it evaporates off quicker than most people expect. Getting a moisturiser on within a minute or two, while the skin's still slightly damp, traps that water before it goes, which is why the timing matters as much as the product you use. A moisturiser with a humectant in it, something that draws water in, like glycerine or hyaluronic acid, gives it something to hold on to as well.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F14 ── Photoprotection gap. A daily-use gap plus a visible signal. Kept
        // advisory: about habit and consistency, never alarm, never medical.
        'F14' => array(
            'describing'   => __( "You told us you wear sunscreen {al:Q8}, and that redness or marks that linger are something you notice. Everyday light adds up more than it feels like it should, through cloud and through glass as much as on a bright day, and {em}protecting from it is one of the few things that reliably shifts lingering marks and redness{/em}. This isn't about a day at the beach, it's the ordinary daylight that builds up without you noticing.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Reaching for something to fade the marks or calm the redness is the usual next move, and it tends to work against a tide that's still coming in. Protecting from the light first is what lets everything else you do actually hold.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'daily_spf', 'text' => __( "The single most useful change here is a broad-spectrum sunscreen worn on a normal day, not just a sunny one. Worn daily, it does more for tone and comfort over time than almost anything you'd layer on top.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What 'broad-spectrum' means", 'apotheca-skin-quiz' ),
                'text'  => __( "Sunlight reaches your skin as two kinds of ultraviolet, UVB, which burns, and UVA, which is the one that ages skin and drives a lot of lingering marks and redness. Broad-spectrum simply means a sunscreen covers both, not just the burning kind. UVA comes through cloud and glass, which is why ordinary daylight, indoors by a window as much as outside, adds up even when it never feels strong.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F10 ── Nothing obviously wrong. Deliberately short.
        'F10' => array(
            'describing'   => __( "Here's the honest version: nothing you've told us points to a clear problem. Your answers describe skin that's behaving, more or less, and a routine that isn't fighting it.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'change_one_thing', 'text' => __( "If it isn't broken, you don't have to fix it. If you do change something, change one thing at a time, so you can actually tell what it did.", 'apotheca-skin-quiz' ) ),
        ),

        // F8 ── Reading list only. Leans on the read-next block.
        'F8' => array(
            'describing'   => __( "You've told us this has never quite settled, and nothing in your answers points to one clear thing to change. That isn't a dead end. Sometimes the most useful next step is to read a little, rather than to buy something or swap everything at once.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'read_first', 'text' => __( "Before you change anything, have a read of what's below. It's usually a better use of your time than another purchase.", 'apotheca-skin-quiz' ) ),
        ),

    ),
);

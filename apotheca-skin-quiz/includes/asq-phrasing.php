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
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "For a couple of weeks, take everything with an active in it out of your routine, retinoids, acids, vitamin C, strong scrubs, and go back to just three things: a gentle cleanser, a plain moisturiser and a daily sunscreen. This is about giving the barrier, your skin's outer layer, a chance to rebuild without being knocked back each day. If you want to help it along, look for a moisturiser with barrier ingredients in it, ceramides, which are the skin's own repairing fats, alongside glycerine, squalane or panthenol. Keep it simple and consistent, and once the stinging and reacting settle, usually within a couple of weeks, bring your actives back one at a time so you can see what your skin can handle.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What 'barrier' actually means", 'apotheca-skin-quiz' ),
                'text'  => __( "Your barrier is your skin's outermost layer, and skincare borrows a builder's image for it, the 'brick-and-mortar' model: your skin cells are the bricks, and a mix of oils, cholesterol and ceramides is the mortar holding them together. Ceramides are your skin's own barrier fats. When that mortar gets stripped, by over-cleansing, over-exfoliating, or simply too much all at once, two things happen together: water escapes more easily from underneath, so skin feels tight and looks dull, and things that would normally stay on the surface get in, so it stings and reacts to products that were fine before. That's why a worn barrier can look like sudden sensitivity or a new allergy when it's neither. The reassuring part is that it's built to repair itself, usually over two to four weeks, as long as you stop wearing it down faster than it can rebuild.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F3 ── Over-exfoliation.
        'F3' => array(
            'describing'   => __( "You're exfoliating more often than your skin is likely to want, and probably more often than you think, because acids hide in toners, cleansers and moisturisers as easily as in anything labelled a scrub or a peel.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Rough, dull skin feels like it needs more exfoliating, which is the trap. Past a certain point, exfoliating is what's keeping it rough, not what's putting it right.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Give everything that exfoliates a rest for a couple of weeks, and be generous about what counts. Scrubs and cleansing brushes are the obvious ones, but the acids do most of the quiet work, so check your toner, cleanser, serums and even your moisturiser for names like glycolic, lactic, mandelic or salicylic acid, or anything that says 'exfoliating', 'resurfacing', 'renewing' or 'smoothing'. Pause the lot, keep only a gentle cleanser, a plain moisturiser and daily sunscreen, and let your skin catch up. If the roughness you've been chasing starts to ease on its own, that's your answer, and you can bring one thing back every couple of weeks to see what your skin actually wants.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'How exfoliating too often backfires', 'apotheca-skin-quiz' ),
                'text'  => __( "Exfoliating speeds up how fast your skin sheds its surface cells. Now and then, that leaves it smoother and brighter. Do it too often, though, and you're clearing the surface away faster than your skin can rebuild it, so the wall never gets finished, and the roughness, tightness and stinging you're trying to exfoliate away are partly the exfoliating itself. The reason it sneaks up on people is that the acids hide everywhere. The two main families are AHAs, water-loving acids like glycolic and lactic that work on the surface, and BHAs, mainly salicylic acid, which is oil-soluble, so it suits oilier, congested skin and can work inside a pore rather than only on top (how well it does that depends on the product it's in). Both turn up in cleansers, toners, serums and moisturisers, not just the thing labelled a peel. Add a scrub or a brush on top and it stacks up fast. Backing right off, rather than pushing through, is almost always what settles it.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F2 ── The cleanser is the problem.
        'F2' => array(
            'describing'   => __( "You told us that not long after cleansing, before anything goes back on, your skin feels {al:Q2}. That tightness is about the clearest sign there is that {em}the cleanser is doing too much{/em}, and it's almost the last thing most people suspect.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "That tight feeling reads as dryness, so the usual next move is a richer moisturiser. It rarely helps for long, because the trouble is what's being stripped away first, not what's being put back afterwards.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'gentler_cleanser', 'text' => __( "For a couple of weeks, change nothing but your cleanser, so if things improve you know exactly what did it. Reach for the mildest one you have, a cream, milk, balm or low-foaming gel, and pay attention to how your skin feels straight after: it should feel comfortable, not squeaky, not tight, not like it needs moisturiser urgently. If you're buying, 'gentle', 'low-pH' or 'for sensitive skin' on the front is a fair start, and the real test is that comfortable feeling afterwards. Skip anything that strips, foams hard or leaves you tight, and you've usually found what was behind it.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'What a cleanser can quietly take with it', 'apotheca-skin-quiz' ),
                'text'  => __( "Your skin sits at a slightly acidic pH, around 5, which is what its protective layer likes best. pH is just the acid-to-alkaline scale, and soap and heavily foaming washes tend to sit high on it, on the alkaline side. Used twice a day, a high-pH or aggressively foaming cleanser can nudge your skin's own pH upward and strip away the oils it relies on to hold water in, and that stripped, over-clean feeling is exactly where the tightness comes from. It's also why the richer moisturiser most people reach for next doesn't really fix it, the trouble is happening at the cleansing step, before anything gets put back. A milder, lower-pH cleanser lifts off dirt, sunscreen and make-up just as well without taking the good stuff with it, and skin that's been feeling tight often settles within a week or two of that one change.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F1 ── Dehydration, not dryness.
        'F1' => array(
            'describing'   => __( "Moisturiser helps for an hour or two and then the dullness is back. That's the signature of skin that's short of {em}water{/em} rather than short of oil, and the two want different things.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This usually isn't dryness, the sort a heavier moisturiser fixes, even though that's the first thing most of us reach for. Dry skin is short of oil. What you're describing sounds more like water escaping faster than it should, which is why a richer moisturiser can sit greasy on top and still feel rough underneath.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'water_then_seal', 'text' => __( "The useful move here usually isn't heavier, it's getting water into the skin and then holding it there, and that's two jobs, not one. First, get the water in. Look for a humectant, an ingredient that pulls water towards the skin and holds it, like glycerine, hyaluronic acid, panthenol or urea, sitting near the top of the ingredient list rather than the bottom. Put it on while your skin's still slightly damp. Then seal it in. That's where a good moisturiser earns its place, the richer step that sits on top and slows the water escaping again. Ingredients like shea butter, plant oils or squalane are the ones doing that sealing work. One draws the water in, the other stops it drifting off, and skin that's been feeling tight usually needs both.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Getting water in, and keeping it there', 'apotheca-skin-quiz' ),
                'text'  => __( "There's a neat three-part way to think about hydration, and each part has a name. A humectant is anything that pulls water in and holds it, glycerine, hyaluronic acid, panthenol and urea are the common ones. An occlusive is anything that sits on the surface and slows that water evaporating back out, so squalane, shea butter and plant oils all do that job. And ceramides are your skin's own barrier fats, the mortar between skin cells, so topping them up helps your skin hold water by itself rather than leaning on something sitting on top. Dehydrated skin usually needs the first two working together, water drawn in, then sealed, and the reason a heavier cream on its own often doesn't fix it is that it's only doing the sealing half, with nothing underneath for it to hold.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F4 ── Too many actives at once.
        'F4' => array(
            'describing'   => __( "You're using several strong ingredients at once, and it's easy to lose count, since a retinoid, an acid and a vitamin C can each turn up in more than one product. Used all together they often work against each other rather than adding up.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "When a routine like this isn't working, it can look like you need something stronger. More often there's already too much going on for any one thing to do its job.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'pause_actives', 'text' => __( "Pare it right back to the essentials for a couple of weeks, a gentle cleanser, a moisturiser and a daily sunscreen, and put the actives away. Then bring them back one at a time, giving each a couple of weeks on its own before you add the next. Actives are the ingredients doing the heavy lifting, retinol and other vitamin A ingredients, vitamin C, exfoliating acids, niacinamide and the like, and reintroducing them slowly is the only way to tell what's genuinely helping from what's just irritating you. Stacking a lot at once mostly adds up to irritation, and while a few things do clash at the point of application, a low-pH active like vitamin C may not sink in as well if you layer something over it straight away, the usual problem is simply too much at once for your skin to take. Most people find they need fewer than they were using, and that the two or three that stay have properly earned their place.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "Why more actives isn't more results", 'apotheca-skin-quiz' ),
                'text'  => __( "Actives each work best in their own conditions, and some, like vitamin C, need a low pH to sink in, so layering things on top of each other at the same moment can stop one getting in properly. Once something's actually absorbed, though, another product's pH won't undo it, your skin quickly buffers back to its own. The bigger issue with piling on actives isn't that they destroy each other, it's that together they can overwhelm the barrier, your skin's outer defence, and once skin is irritated nothing works as well as it should. So the routine feels like it's doing a great deal while it's actually going backwards. A short, simple routine your skin tolerates beats a long, clever one that leaves it red and reactive, every time.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F6 ── Hormonal shift pattern. Brand voice may name the life stage.
        'F6' => array(
            'describing'   => __( "Some of what you've told us fits a pattern of change rather than anything you've done. Skin getting drier, a little less firm, or breaking out along the jaw when it never used to, often more than one of those at once from your late 30s or 40s on, is one of the more recognisable signs of perimenopause and menopause. It tends to arrive without much warning, and it isn't something you've brought on.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This isn't a sign you've started doing something wrong. It's more often that a routine built for the skin you had is quietly no longer the routine for the skin you've got.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'question_routine', 'text' => __( "Go through your routine and ask what each product is actually for, rather than how much it cost or how long you've trusted it. The ones worth questioning first are anything chosen years ago for oilier skin, mattifying moisturisers, foaming or clay cleansers, oil-control primers, because skin that's drier now will read those as stripping. As a rough direction, this stage tends to want the opposite: a gentle, non-foaming cleanser, a richer moisturiser, ingredients that draw in and hold water like glycerine and hyaluronic acid, and daily sunscreen. You don't need to replace everything at once. Swap the most stripping thing first, give it a few weeks, and let your skin tell you what it needs from there.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What's changing underneath", 'apotheca-skin-quiz' ),
                'text'  => __( "As oestrogen dips through perimenopause, the years of change leading up to your periods stopping, and then menopause, your skin makes less of a few things it used to rely on. Two matter most here. It makes less oil, so it holds on to water less easily and can feel drier, tighter or more sensitive than it ever did. And it makes less collagen, the protein that keeps skin firm and springy, with research suggesting a good deal of it goes in the first few years around menopause, which is why skin can feel thinner or less bouncy. None of this is something you've done, and it doesn't mean your skin is damaged. It's a change in what your skin needs, which is the real reason a routine that served you well for twenty years can suddenly feel like it's working against you.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F7 ── Congestion read as dryness.
        'F7' => array(
            'describing'   => __( "Your skin sits on the oilier side and yet it can feel tight or rough at the same time. That combination is often {em}congestion being read as dryness{/em}, and it wants the opposite of what dryness would.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "The instinct is to add richness to the rough patches. On skin that's already oily underneath, that usually adds to the congestion rather than easing it.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'stop_stripping', 'text' => __( "Ease off the things that strip, and give your skin a fortnight to settle before you decide it needs more, not less. That means parking foaming or clay cleansers, mattifying and oil-control products, and any strong scrubs, all of which push oily-but-rough skin to make more oil and get rougher. In their place, a gentle cleanser and a light, non-greasy moisturiser, the sort that says 'gel', 'lightweight' or 'non-comedogenic', meaning it's designed not to block pores. If you want one active, a gentle salicylic acid, which is oil-soluble so it can get into the pores, once or twice a week does more for congestion than piling on richness ever will. The aim is to calm the cycle, not to scrub your way out of it.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Oily and rough at the same time', 'apotheca-skin-quiz' ),
                'text'  => __( "Oily and rough at the same time feels like a contradiction, which is why it's so easily misread as dryness. Usually it isn't a shortage of moisture at all, it's congestion: a mix of oil and dead surface cells sitting in and around the pores, which feels rough on top even while the skin underneath is oily. Reach for a rich cream and you tend to feed it, so it gets worse in a way that's genuinely confusing. Two things drive the cycle: stripping the skin, which makes it pump out more oil to compensate, and heavy products, which sit on top of the congestion. Ease off both, keep it light and consistent, and the roughness usually lifts as the pores clear, without the rich creams that seemed like the obvious fix.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F9 ── Environmental or seasonal.
        'F9' => array(
            'describing'   => __( "A good deal of what you've described tracks with the weather and your surroundings rather than with your skin itself. Skin that changes with the seasons, or that shifted around a move or a change in climate, is usually responding to what's around it.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "It's tempting to overhaul the whole routine when this happens. More often the routine was fine, and it's the conditions around it that changed.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'adjust_seasonally', 'text' => __( "Treat this as something to adjust with the conditions rather than a fault to fix, because the routine probably isn't broken, the weather changed. When it's cold, dry or windy, or you've got heating or air conditioning running, lean richer: a more cushioning moisturiser, maybe a hydrating layer underneath with glycerine or hyaluronic acid to pull in water, and a barrier balm on any spots that chap. When it's hot and humid, do the opposite, go lighter, swap the cream for a gel, and let the air do some of the work. Sunscreen stays daily either way. The trick is to keep two gears rather than hunting for one product that copes with every season, because none really does.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Why skin shifts with the weather', 'apotheca-skin-quiz' ),
                'text'  => __( "Your skin is constantly losing a little water to the air, and how fast depends on what's around it. In cold, dry or windy weather, and in heated or air-conditioned rooms, the air is thirsty and pulls water out of your skin faster, so the exact routine that felt perfect in summer can leave you tight and flaky in winter. When it's humid, the reverse happens, the air holds more moisture, less escapes, and a rich cream that was lovely in January can feel heavy and congesting in July. So a product 'stopping working' is often just the conditions moving underneath it. Adjusting with the seasons, richer and more protective when the air is harsh, lighter when it's humid, tends to work far better than asking one routine to hold the line all year.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F12 ── Not sure what's in her products. Not a fault. Carries the
        // Ingredient List Decoder link in worth_trying via the {decoder} token,
        // which the renderer turns into a link (or plain text if no URL is set).
        'F12' => array(
            'describing'   => __( "You've told us you're not entirely sure what's in your products, and honestly, that's the most common answer we get. Acids and retinoids turn up in toners, cleansers and moisturisers as readily as in anything labelled a treatment, so it's easy to be {em}using more than you think{/em}, without putting a foot wrong.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'decode_products', 'text' => __( "The simplest next step is to find out what you're actually putting on, because you can't tell what's helping or clashing until you can see it. Gather the products you use most, cleanser, any serums, moisturiser, and look at the ingredient lists, or {decoder}paste them into our Ingredient List Decoder{/decoder} and it'll translate them into plain English for you. What you're checking for is easy to miss: the same active turning up in three different products, or exfoliating acids and a retinoid you didn't realise were in there. Once you can see what's actually in your routine, the rest of your results make a lot more sense.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'How to read an ingredient list', 'apotheca-skin-quiz' ),
                'text'  => __( "Ingredient lists follow one simple rule: they're in order of how much is in the bottle, most first, down to the least. So whatever sits in the first few names is the bulk of what you're putting on, usually water, then the oils, humectants and thickeners that make up the base, and the last stretch is often fragrance and preservatives. Two things are worth knowing before you judge a product by this, though. First, position tells you the amount, not whether it's working: plenty of actives do their job at tiny percentages, and some are legally capped there, retinol, for instance, is limited to 0.3% in leave-on products in the EU, so seeing it low down the list is completely normal and no sign it's underdosed. Second, it cuts the other way too: an ingredient that only works in a decent amount, like vitamin C, sitting right near the bottom may well be there in token amounts, however it's sold on the front. One last quirk: below one per cent, ingredients can be listed in any order, so the very tail of the list isn't strictly ranked. Our decoder does all this reading for you, but it's a handy habit when you're stood in a shop holding a bottle, wondering if it's worth it.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F13 ── Barrier under-supported. She cleanses to a tight feeling and
        // doesn't reliably reseal with a moisturiser. Distinct action from F5.
        'F13' => array(
            'describing'   => __( "You told us your skin feels {al:Q2} after cleansing, and that a moisturiser doesn't always go straight back on. That's worth pausing on, because {em}the step that's missing is the one that seals everything in{/em}. Bare skin loses water faster than most people expect, so cleansing or treating and then leaving it is often what's behind that tight, thin feeling.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "This usually isn't skin that needs a stronger treatment or a richer moisturiser. It's more often skin that's being cleansed or worked on and then left to fend for itself, so the good you're doing quietly leaks back out.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'reseal', 'text' => __( "For the next couple of weeks, treat moisturiser as the non-negotiable step, not the one you skip when you're tired. The timing does a lot of the work: get it on within a minute or two of cleansing, while your skin's still slightly damp, so you trap water in rather than letting it evaporate off first. Look for a moisturiser that both draws water in and holds it there, so a humectant like glycerine, hyaluronic acid or panthenol on the label, alongside something richer like squalane, shea butter or plant oils to seal it. If your skin feels tight the moment you've patted it dry, that's the window, don't wait. Do this consistently and the tight, thin feeling usually eases on its own within a couple of weeks.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( 'Why bare skin loses water so fast', 'apotheca-skin-quiz' ),
                'text'  => __( "Freshly cleansed skin is briefly defenceless: you've just rinsed away the surface oils that normally slow water escaping, and with nothing on top, that water evaporates off far quicker than most people expect, which is exactly the tight feeling you get a minute or two after towelling dry. There's a name for that water leaving through the surface, transepidermal water loss, or TEWL, and it climbs when the barrier is bare or worn. Getting a moisturiser on while the skin is still slightly damp does two useful things at once: it traps the water that's already there, and, if the moisturiser contains humectants, ingredients that attract water, it gives your skin something to hold on to as it settles. That's why the habit and the timing matter as much as which product you pick, cleansing or treating and then leaving skin bare is what lets the good you're doing quietly leak back out.", 'apotheca-skin-quiz' ),
            ),
        ),

        // F14 ── Photoprotection gap. A daily-use gap plus a visible signal. Kept
        // advisory: about habit and consistency, never alarm, never medical.
        'F14' => array(
            'describing'   => __( "You told us you wear sunscreen {al:Q8}, and that redness or marks that linger are something you notice. Everyday light adds up more than it feels like it should, through cloud and through glass as much as on a bright day, and {em}protecting from it is one of the few things that reliably shifts lingering marks and redness{/em}. This isn't about a day at the beach, it's the ordinary daylight that builds up without you noticing.", 'apotheca-skin-quiz' ),
            'probably_not' => __( "Reaching for something to fade the marks or calm the redness is the usual next move, and it tends to work against a tide that's still coming in. Protecting from the light first is what lets everything else you do actually hold.", 'apotheca-skin-quiz' ),
            'worth_trying' => array( 'key' => 'daily_spf', 'text' => __( "The single most useful change here is a broad-spectrum sunscreen worn every ordinary day, not just the sunny ones or the beach ones. 'Broad-spectrum' is the bit that matters, it means it covers both types of UV, and aim for SPF 30 or higher. The trick is making it a habit rather than a daily decision: keep it by your toothbrush, put it on as the last step of your morning routine, and use enough, about two fingers' length for your face and neck, since most people use a third of what they should and get a third of the protection. If that feels like too much in one go, or it sits heavy, split it: put half on, give it a minute to sink in, then add the other half. You still get the full amount, just without the greasy hit. Worn like that, daily, it does more for even tone, lingering marks and overall comfort over time than almost anything you'd layer on top, and it lets everything else you do actually hold.", 'apotheca-skin-quiz' ) ),
            'learn_more'   => array(
                'title' => __( "What 'broad-spectrum' means", 'apotheca-skin-quiz' ),
                'text'  => __( "Sunlight reaches your skin as two kinds of ultraviolet, and they do different things. UVB is the one that burns, strongest in the middle of the day and in summer. UVA is the quieter one: it's fairly constant all year, it passes through cloud and window glass, and it's the main driver of the lingering marks, uneven tone and loss of firmness people put down to ageing. 'Broad-spectrum' simply means a sunscreen is built to cover both, not just the burning kind, which is why the word is worth looking for on the front. Because UVA comes through glass and cloud, the light you get on an overcast day, on the commute, or sitting by a window indoors, adds up more than it ever feels like it should. That's the case for wearing it daily rather than saving it for obviously sunny days, it's the everyday, low-level exposure that quietly does the most over the years.", 'apotheca-skin-quiz' ),
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

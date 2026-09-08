(function ($) {
    'use strict';

    // Verbose console logging is only active with ?asq_debug=1 in the URL,
    // so customer consoles stay clean but troubleshooting stays easy.
    var ASQ_DEBUG = /[?&]asq_debug=1/.test(window.location.search);
    function asqLog() {
        if (ASQ_DEBUG && window.console) {
            console.log.apply(console, arguments);
        }
    }

    /**
     * Apotheca Skin Quiz – Frontend Controller
     *
     * Manages the multi-step quiz flow:
     *   Question slides → Email capture → Loading → Results
     */
    function SkinQuiz($el) {
        this.$el        = $el;
        this.finderId   = $el.data('finder-id');
        this.questions  = $el.data('questions') || [];
        this.options    = $el.data('options') || {};
        this.current    = 0;
        this.answers    = {};  // { questionIndex: [answerIndices] }
        this.followupAnswers = {};  // { "qi_ai": [followupAnswerIndices] }
        this.history    = [];  // navigation stack: [{ type:'question', qi:N }, { type:'followup', qi:N, ai:M }, ...]
        this.totalQ     = this.questions.length;
        this.view       = 'question';  // which screen is showing: question | followup | email
        this.gate       = asqFrontend.gate || null;  // { qi, safe } medical gate

        this.$progress      = $el.find('.asq-progress-fill');
        this.$progressText  = $el.find('.asq-progress-text');
        this.$container     = $el.find('.asq-questions-container');
        this.$loadingScreen = $el.find('.asq-loading-screen');
        this.$resultsScreen = $el.find('.asq-results-screen');

        this.init();
    }

    SkinQuiz.prototype = {

        init: function () {
            if (!this.totalQ) return;
            this.applyI18n();
            this.bindGlobal();

            // If a results token is present in the URL, skip straight to results.
            if (asqFrontend.results_token) {
                this.loadSessionResults(asqFrontend.results_token);
                return;
            }

            // Resume where she left off if a refresh interrupted the quiz.
            if (this.restoreState()) {
                return;
            }

            this.history.push({ type: 'question', qi: 0 });
            this.renderQuestion(0);
            this.updateProgress();
        },

        /* ───────── i18n labels ───────── */

        applyI18n: function () {
            var i = asqFrontend.i18n;

            // Custom heading overrides from Elementor widget data attributes
            var customLoading = this.$el.data('loading-heading');
            var customResults = this.$el.data('results-heading');

            this.$loadingScreen.find('.asq-loading-text').text(customLoading || i.loading);
            this.$resultsScreen.find('.asq-results-title').text(customResults || i.your_results);
            this.$resultsScreen.find('.asq-start-over').text(i.start_over);
        },

        /* ───────── Global events ───────── */

        bindGlobal: function () {
            var self = this;

            this.$el.on('click', '.asq-answer-option', function () {
                self.handleAnswerClick($(this));
            });

            // Text answer hover-out animation: slide out to the right.
            // Only attach on devices that truly support hover (no touch ghost events).
            if (window.matchMedia('(hover: hover)').matches) {
                this.$el.on('mouseenter', '.asq-answer-option--text', function () {
                    $(this).removeClass('asq-hover-out asq-no-transition');
                });
                this.$el.on('mouseleave', '.asq-answer-option--text', function () {
                    if (!$(this).hasClass('asq-selected')) {
                        $(this).addClass('asq-hover-out');
                        // After slide-out animation, snap ::before back to start without transition
                        // (prevents flash through translateX(0) on the way from 100% to -100%)
                        var $opt = $(this);
                        setTimeout(function () {
                            $opt.addClass('asq-no-transition').removeClass('asq-hover-out');
                            // Force reflow so the snap happens before re-enabling transitions
                            void $opt[0].offsetHeight;
                            requestAnimationFrame(function () {
                                $opt.removeClass('asq-no-transition');
                            });
                        }, 350);
                    }
                });
            }

            this.$el.on('click', '.asq-btn-continue', function () {
                self.transitionOut(function () { self.goNext(); });
            });

            this.$el.on('click', '.asq-btn-back', function () {
                self.transitionOut(function () { self.goBack(); });
            });

            this.$el.on('click', '.asq-send-email', function () {
                self.sendEmail();
            });

            // Enable the submit only once consent is ticked, and say why.
            this.$el.on('change', '.asq-consent-checkbox', function () {
                self.syncConsent();
            });

            this.$el.on('click', '.asq-start-over', function () {
                self.startOver();
            });
        },

        // Reflect the consent checkbox state on the submit button and hint.
        syncConsent: function () {
            var $gate = this.$el.find('.asq-gate');
            if (!$gate.length) return;
            var ticked = $gate.find('.asq-consent-checkbox').is(':checked');
            var required = asqFrontend.consent_enabled;
            var ok = ticked || !required;
            $gate.find('.asq-send-email').prop('disabled', !ok).toggleClass('asq-btn-disabled', !ok);
            $gate.find('.asq-consent-hint').css('visibility', ok ? 'hidden' : 'visible');
        },

        /* ───────── Render question ───────── */

        renderQuestion: function (idx) {
            var q = this.questions[idx];
            if (!q) return;
            this.view = 'question';

            // Anonymous funnel: note this question was reached (once per session).
            this.trackReach(idx);

            // When auto-advancing from a single-select tap, suppress pointer
            // events on the incoming answers so the browser cannot apply a
            // ghost hover / active state to whatever element lands under the
            // finger.  The class is baked into the HTML *before* DOM insertion
            // so there is zero window for the browser to match :hover.
            var suppress = this._suppressTouch;
            this._suppressTouch = false;
            var noPtr = suppress ? ' asq-no-pointer' : '';

            var hasImages = q.answers.some(function (a) { return !!a.image; });
            var html = '<div class="asq-question-slide" data-qi="' + idx + '">';

            // State the exchange up front, on the first question.
            if (idx === 0 && asqFrontend.exchange_text) {
                html += '<p class="asq-exchange-note">' + this.escHtml(asqFrontend.exchange_text) + '</p>';
            }

            var instructionText = q.instruction || (q.multiple ? 'Select all that apply' : 'Select one option');

            if (hasImages) {
                // Image grid layout
                html += '<h2 class="asq-question-text asq-question-text--center">' + this.escHtml(q.text) + '</h2>';
                html += '<p class="asq-question-instruction asq-question-instruction--center">' + this.escHtml(instructionText) + '</p>';
                html += '<div class="asq-answers-grid asq-answers-grid--images">';
                for (var i = 0; i < q.answers.length; i++) {
                    var a = q.answers[i];
                    var selected = this.isSelected(idx, i) ? ' asq-selected' : '';
                    html += '<div class="asq-answer-option asq-answer-option--image' + selected + noPtr + '" data-ai="' + i + '">';
                    if (a.image) {
                        html += '<div class="asq-answer-img-wrap"><img src="' + this.escHtml(a.image) + '" alt="' + this.escHtml(a.text) + '"></div>';
                    }
                    html += '<span class="asq-answer-text">' + this.escHtml(a.text) + '</span>';
                    if (a.description) {
                        html += '<span class="asq-answer-desc">' + this.escHtml(a.description) + '</span>';
                    }
                    if (q.multiple) {
                        html += '<span class="asq-checkbox"><span class="asq-check-icon"></span></span>';
                    }
                    html += '</div>';
                }
                html += '</div>';
            } else {
                // Two-column text layout
                html += '<div class="asq-text-layout">';
                html += '<div class="asq-text-left">';
                html += '<h2 class="asq-question-text">' + this.escHtml(q.text) + '</h2>';
                html += '<p class="asq-question-instruction">' + this.escHtml(instructionText) + '</p>';
                html += '</div>';
                html += '<div class="asq-text-right">';
                html += '<div class="asq-answers-grid asq-answers-grid--text">';
                for (var j = 0; j < q.answers.length; j++) {
                    var b = q.answers[j];
                    var sel = this.isSelected(idx, j) ? ' asq-selected' : '';
                    html += '<div class="asq-answer-option asq-answer-option--text' + sel + noPtr + '" data-ai="' + j + '">';
                    html += '<span class="asq-answer-text">' + this.escHtml(b.text) + '</span>';
                    if (q.multiple) {
                        html += '<span class="asq-checkbox"><span class="asq-check-icon"></span></span>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }

            // Navigation buttons
            html += '<div class="asq-nav-buttons">';
            if (idx > 0) {
                html += '<button type="button" class="asq-btn asq-btn-secondary asq-btn-back">' + asqFrontend.i18n.back + '</button>';
            } else {
                html += '<span></span>';
            }
            if (q.multiple) {
                var hasSelection = this.answers[idx] && this.answers[idx].length > 0;
                html += '<button type="button" class="asq-btn asq-btn-primary asq-btn-continue' + (hasSelection ? '' : ' asq-btn-disabled') + '"' + (hasSelection ? '' : ' disabled') + '>' + asqFrontend.i18n.next + '</button>';
            }
            html += '</div>';

            html += '</div>';

            this.$container.html(html);

            // Lift the pointer suppression after a short cooldown.
            if (suppress) {
                var $answers = this.$container.find('.asq-answer-option');
                setTimeout(function () { $answers.removeClass('asq-no-pointer'); }, 400);
            }

            // Animate in
            this.$container.find('.asq-question-slide').addClass('asq-slide-in');
        },

        /* ───────── Render follow-up question ───────── */

        renderFollowupQuestion: function (qi, ai) {
            var fu = this.questions[qi].answers[ai].follow_up;
            if (!fu) return;
            this.view = 'followup';

            var suppress = this._suppressTouch;
            this._suppressTouch = false;
            var noPtr = suppress ? ' asq-no-pointer' : '';

            var hasImages = fu.answers.some(function (a) { return !!a.image; });
            var html = '<div class="asq-question-slide asq-followup-slide" data-qi="' + qi + '" data-ai="' + ai + '">';

            var instructionText = fu.instruction || (fu.multiple ? 'Select all that apply' : 'Select one option');

            if (hasImages) {
                html += '<h2 class="asq-question-text asq-question-text--center">' + this.escHtml(fu.text) + '</h2>';
                html += '<p class="asq-question-instruction asq-question-instruction--center">' + this.escHtml(instructionText) + '</p>';
                html += '<div class="asq-answers-grid asq-answers-grid--images">';
                for (var i = 0; i < fu.answers.length; i++) {
                    var a = fu.answers[i];
                    var selected = this.isFollowupSelected(qi, ai, i) ? ' asq-selected' : '';
                    html += '<div class="asq-answer-option asq-answer-option--image' + selected + noPtr + '" data-ai="' + i + '">';
                    if (a.image) {
                        html += '<div class="asq-answer-img-wrap"><img src="' + this.escHtml(a.image) + '" alt="' + this.escHtml(a.text) + '"></div>';
                    }
                    html += '<span class="asq-answer-text">' + this.escHtml(a.text) + '</span>';
                    if (a.description) {
                        html += '<span class="asq-answer-desc">' + this.escHtml(a.description) + '</span>';
                    }
                    if (fu.multiple) {
                        html += '<span class="asq-checkbox"><span class="asq-check-icon"></span></span>';
                    }
                    html += '</div>';
                }
                html += '</div>';
            } else {
                html += '<div class="asq-text-layout">';
                html += '<div class="asq-text-left">';
                html += '<h2 class="asq-question-text">' + this.escHtml(fu.text) + '</h2>';
                html += '<p class="asq-question-instruction">' + this.escHtml(instructionText) + '</p>';
                html += '</div>';
                html += '<div class="asq-text-right">';
                html += '<div class="asq-answers-grid asq-answers-grid--text">';
                for (var j = 0; j < fu.answers.length; j++) {
                    var b = fu.answers[j];
                    var sel = this.isFollowupSelected(qi, ai, j) ? ' asq-selected' : '';
                    html += '<div class="asq-answer-option asq-answer-option--text' + sel + noPtr + '" data-ai="' + j + '">';
                    html += '<span class="asq-answer-text">' + this.escHtml(b.text) + '</span>';
                    if (fu.multiple) {
                        html += '<span class="asq-checkbox"><span class="asq-check-icon"></span></span>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }

            // Navigation buttons – always show back for follow-ups
            html += '<div class="asq-nav-buttons">';
            html += '<button type="button" class="asq-btn asq-btn-secondary asq-btn-back">' + asqFrontend.i18n.back + '</button>';
            if (fu.multiple) {
                var key = qi + '_' + ai;
                var hasSelection = this.followupAnswers[key] && this.followupAnswers[key].length > 0;
                html += '<button type="button" class="asq-btn asq-btn-primary asq-btn-continue' + (hasSelection ? '' : ' asq-btn-disabled') + '"' + (hasSelection ? '' : ' disabled') + '>' + asqFrontend.i18n.next + '</button>';
            }
            html += '</div>';

            html += '</div>';

            this.$container.html(html);

            if (suppress) {
                var $answers = this.$container.find('.asq-answer-option');
                setTimeout(function () { $answers.removeClass('asq-no-pointer'); }, 400);
            }

            this.$container.find('.asq-question-slide').addClass('asq-slide-in');
        },

        /* ───────── Answer click ───────── */

        handleAnswerClick: function ($opt) {
            var $slide = this.$container.find('.asq-question-slide');
            var isFollowup = $slide.hasClass('asq-followup-slide');
            var ai = $opt.data('ai');

            if (isFollowup) {
                // Follow-up answer click
                var fuQi = $slide.data('qi');
                var fuAi = $slide.data('ai');
                var key = fuQi + '_' + fuAi;
                var fu = this.questions[fuQi].answers[fuAi].follow_up;

                if (fu.multiple) {
                    $opt.toggleClass('asq-selected');
                    if (!this.followupAnswers[key]) this.followupAnswers[key] = [];
                    var pos = this.followupAnswers[key].indexOf(ai);
                    if (pos === -1) {
                        this.followupAnswers[key].push(ai);
                    } else {
                        this.followupAnswers[key].splice(pos, 1);
                    }
                    var $btn = this.$container.find('.asq-btn-continue');
                    if (this.followupAnswers[key].length > 0) {
                        $btn.removeClass('asq-btn-disabled').prop('disabled', false);
                    } else {
                        $btn.addClass('asq-btn-disabled').prop('disabled', true);
                    }
                    this.persistState();
                } else {
                    this.$container.find('.asq-answer-option').removeClass('asq-selected');
                    $opt.addClass('asq-selected');
                    this.followupAnswers[key] = [ai];

                    var self = this;
                    setTimeout(function () {
                        $slide.addClass('asq-slide-out');
                        setTimeout(function () {
                            self._suppressTouch = true;
                            self.goNext();
                        }, 280);
                    }, 200);
                }
            } else {
                // Main question answer click
                var qi = this.current;
                var q  = this.questions[qi];

                if (q.multiple) {
                    $opt.toggleClass('asq-selected');
                    if (!this.answers[qi]) this.answers[qi] = [];
                    var pos = this.answers[qi].indexOf(ai);
                    if (pos === -1) {
                        this.answers[qi].push(ai);
                    } else {
                        this.answers[qi].splice(pos, 1);
                    }
                    var $btn = this.$container.find('.asq-btn-continue');
                    if (this.answers[qi].length > 0) {
                        $btn.removeClass('asq-btn-disabled').prop('disabled', false);
                    } else {
                        $btn.addClass('asq-btn-disabled').prop('disabled', true);
                    }
                    this.persistState();
                } else {
                    this.$container.find('.asq-answer-option').removeClass('asq-selected');
                    $opt.addClass('asq-selected');
                    this.answers[qi] = [ai];

                    var self = this;
                    setTimeout(function () {
                        $slide.addClass('asq-slide-out');
                        setTimeout(function () {
                            self._suppressTouch = true;
                            self.goNext();
                        }, 280);
                    }, 200);
                }
            }
        },

        isSelected: function (qi, ai) {
            return this.answers[qi] && this.answers[qi].indexOf(ai) !== -1;
        },

        isFollowupSelected: function (qi, parentAi, fai) {
            var key = qi + '_' + parentAi;
            return this.followupAnswers[key] && this.followupAnswers[key].indexOf(fai) !== -1;
        },

        /* ───────── Navigation ───────── */

        /**
         * Fade out the current slide, then call a callback to render the next view.
         * If no slide is visible (e.g. first render) the callback fires immediately.
         */
        transitionOut: function (cb) {
            var $slide = this.$container.find('.asq-question-slide');
            if (!$slide.length) { cb(); return; }
            $slide.addClass('asq-slide-out');
            setTimeout(cb, 280); // slightly longer than the 250ms animation
        },

        goNext: function () {
            var lastEntry = this.history[this.history.length - 1];

            if (lastEntry.type === 'followup') {
                // Coming from a follow-up, advance to next main question
                var nextQi = lastEntry.qi + 1;
                if (nextQi >= this.totalQ) {
                    this.finish();
                    return;
                }
                this.current = nextQi;
                this.history.push({ type: 'question', qi: nextQi });
                this.renderQuestion(nextQi);
                this.updateProgress();
                return;
            }

            // Coming from a main question – check if selected answer has a follow-up
            var qi = lastEntry.qi;
            var q = this.questions[qi];
            var selected = this.answers[qi] || [];
            var followup = null;

            for (var i = 0; i < selected.length; i++) {
                var ai = selected[i];
                var a = q.answers[ai];
                if (a && a.follow_up && a.follow_up.text && a.follow_up.answers && a.follow_up.answers.length) {
                    followup = { qi: qi, ai: ai };
                    break;
                }
            }

            if (followup) {
                this.history.push({ type: 'followup', qi: followup.qi, ai: followup.ai });
                this.renderFollowupQuestion(followup.qi, followup.ai);
                this.updateProgress();
            } else {
                var nextQi = qi + 1;
                if (nextQi >= this.totalQ) {
                    this.finish();
                    return;
                }
                this.current = nextQi;
                this.history.push({ type: 'question', qi: nextQi });
                this.renderQuestion(nextQi);
                this.updateProgress();
            }
        },

        goBack: function () {
            if (this.history.length <= 1) return;

            this.history.pop();
            var prev = this.history[this.history.length - 1];

            if (prev.type === 'followup') {
                this.renderFollowupQuestion(prev.qi, prev.ai);
            } else {
                this.current = prev.qi;
                this.renderQuestion(prev.qi);
            }
            this.updateProgress();
        },

        /* ───────── Progress bar ───────── */

        updateProgress: function () {
            // Progress reflects the current position in the quiz.
            // current is 0-based, so current/totalQ gives the fraction
            // of the quiz the user has reached.
            var pct = Math.round((this.current / this.totalQ) * 100);
            this.$progress.css('width', pct + '%');
            this.$progressText.text(pct + '%');
            // Persist progress so a refresh resumes at the same screen.
            this.persistState();
        },

        /* ───────── Finish: gate or email ───────── */

        // True if any selection on the gate question is something other than
        // the safe "none of these" option.
        isGated: function () {
            if (!this.gate) return false;
            var sel = this.answers[this.gate.qi] || [];
            for (var i = 0; i < sel.length; i++) {
                if (sel[i] !== this.gate.safe) return true;
            }
            return false;
        },

        // Tell the server the gate fired. No answers, no option, no address.
        recordGate: function () {
            if (this._gateRecorded) return;
            this._gateRecorded = true;
            $.post(asqFrontend.ajax_url, {
                action: 'asq_record_gate',
                nonce: asqFrontend.nonce,
                finder_id: this.finderId
            });
        },

        /* ───────── Anonymous funnel (drop-off) ───────── */

        // A tiny fire-and-forget beacon carrying no personal data: which quiz,
        // which question was reached, or that the quiz finished. Used only to
        // build the Question performance report. sendBeacon keeps it off the
        // critical path; a plain post is the fallback.
        beacon: function (event, q) {
            var payload = {
                action: 'asq_track',
                nonce: asqFrontend.nonce,
                finder_id: this.finderId,
                event: event,
                q: q || 0
            };
            try {
                if (navigator && typeof navigator.sendBeacon === 'function') {
                    var fd = new FormData();
                    for (var k in payload) {
                        if (Object.prototype.hasOwnProperty.call(payload, k)) {
                            fd.append(k, payload[k]);
                        }
                    }
                    navigator.sendBeacon(asqFrontend.ajax_url, fd);
                    return;
                }
            } catch (e) { /* fall through to $.post */ }
            $.post(asqFrontend.ajax_url, payload);
        },

        // Count a question as reached at most once per session, so the funnel
        // reads as "sessions that got at least this far".
        trackReach: function (idx) {
            idx = idx | 0;
            if (!this._reached) { this._reached = {}; }
            var key = 'asq_reach_' + this.finderId;
            var seen = this._reached;
            try {
                var stored = window.sessionStorage.getItem(key);
                if (stored) { seen = this._reached = JSON.parse(stored) || {}; }
            } catch (e) { /* sessionStorage may be unavailable */ }
            if (seen[idx]) { return; }
            seen[idx] = 1;
            try { window.sessionStorage.setItem(key, JSON.stringify(seen)); } catch (e) {}
            this.beacon('reach', idx);
        },

        // Count the completion once per session.
        trackComplete: function () {
            if (this._completed) { return; }
            this._completed = true;
            var key = 'asq_done_' + this.finderId;
            try {
                if (window.sessionStorage.getItem(key)) { return; }
                window.sessionStorage.setItem(key, '1');
            } catch (e) {}
            this.beacon('complete', 0);
        },

        // Reached the end of the questions.
        finish: function () {
            this.trackComplete();
            if (this.isGated()) {
                // The medical gate replaces the normal path: no email gate,
                // no reading. Don't leave the medical selections in the browser.
                this.clearState();
                this.recordGate();
            }
            this.showLoading();
        },

        /* ───────── First-party unlock cookie ───────── */

        cookieName: function () {
            return 'asq_unlocked_' + this.finderId;
        },

        isUnlocked: function () {
            return new RegExp('(?:^|; )' + this.cookieName() + '=1').test(document.cookie);
        },

        setUnlocked: function () {
            var days = parseInt(asqFrontend.cookie_days, 10) || 180;
            var d = new Date();
            d.setTime(d.getTime() + days * 864e5);
            document.cookie = this.cookieName() + '=1; expires=' + d.toUTCString() + '; path=/; SameSite=Lax';
        },

        /* ───────── Email gate ───────── */

        // Build the inline gate form that sits between the first section and
        // the rest of the reading.
        gateHtml: function () {
            var i = asqFrontend.i18n;
            var h = '<div class="asq-gate">';
            h += '<p class="asq-gate-lead">' + this.escHtml(i.email_gate_lead) + '</p>';
            h += '<div class="asq-gate-form">';
            h += '<input type="email" class="asq-email-input" autocomplete="email" aria-label="' + this.escHtml(i.email_placeholder) + '" placeholder="' + this.escHtml(i.email_placeholder) + '">';
            if (asqFrontend.consent_enabled) {
                h += '<label class="asq-consent-label"><input type="checkbox" class="asq-consent-checkbox" value="1"><span>' + this.escHtml(asqFrontend.consent_text) + '</span></label>';
            }
            // Honeypot: hidden from people (and screen readers), left empty by
            // real users. Bots that auto-fill every field give themselves away.
            h += '<div class="asq-hp" aria-hidden="true">';
            h += '<label>Leave this field empty<input type="text" name="asq_hp" class="asq-hp-input" tabindex="-1" autocomplete="off" value=""></label>';
            h += '</div>';
            var disabled = asqFrontend.consent_enabled ? ' disabled' : '';
            var dcls = asqFrontend.consent_enabled ? ' asq-btn-disabled' : '';
            h += '<button type="button" class="asq-btn asq-btn-primary asq-send-email' + dcls + '"' + disabled + '>' + this.escHtml(i.send_reading) + '</button>';
            h += '<p class="asq-consent-hint">' + this.escHtml(i.consent_hint) + '</p>';
            h += '<div class="asq-email-message" style="display:none;"></div>';
            h += '</div></div>';
            return h;
        },

        sendEmail: function () {
            var self  = this;
            var $gate = this.$el.find('.asq-gate');
            var email = $gate.find('.asq-email-input').val().trim();
            var $msg  = $gate.find('.asq-email-message');
            var consentEnabled = asqFrontend.consent_enabled;
            var ticked = $gate.find('.asq-consent-checkbox').is(':checked');

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $msg.text('Please enter a valid email.').css('color', '#b32d2e').show();
                return;
            }
            if (consentEnabled && !ticked) {
                $msg.text(asqFrontend.i18n.consent_hint).css('color', '#b32d2e').show();
                return;
            }

            $msg.text('Sending…').css('color', '#666').show();

            // Save the session first, so the emailed link shows the full reading.
            $.post(asqFrontend.ajax_url, {
                action: 'asq_save_results_session',
                nonce: asqFrontend.nonce,
                finder_id: self.finderId,
                answers: JSON.stringify(self.answers),
                followup_answers: JSON.stringify(self.followupAnswers)
            }, function (sessionRes) {
                var resultsUrl = '';
                if (sessionRes.success) {
                    var token = sessionRes.data.token;
                    var baseUrl = asqFrontend.page_url || window.location.href.split('?')[0];
                    var sep = baseUrl.indexOf('?') !== -1 ? '&' : '?';
                    resultsUrl = baseUrl + sep + 'asq_results=' + encodeURIComponent(token);
                }

                // The exact consent wording shown, so the server stores it.
                var consentText = $gate.find('.asq-consent-label span').text() || asqFrontend.consent_text;

                $.post(asqFrontend.ajax_url, {
                    action: 'asq_send_results_email',
                    nonce: asqFrontend.nonce,
                    finder_id: self.finderId,
                    source_id: asqFrontend.source_id,
                    email: email,
                    results_url: resultsUrl,
                    consent: (consentEnabled ? (ticked ? 1 : 0) : 1),
                    consent_text: consentText,
                    asq_hp: $gate.find('.asq-hp-input').val() || '',
                    answers: JSON.stringify(self.answers),
                    followup_answers: JSON.stringify(self.followupAnswers)
                }, function (res) {
                    if (res.success) {
                        self.unlockReading();
                    } else if (res.data && res.data.rate_limited) {
                        // Reached the per-IP limit: show it calmly, not as an error.
                        $msg.text(res.data.message).css('color', '#666').show();
                    } else {
                        $msg.text(res.data && res.data.message ? res.data.message : asqFrontend.i18n.email_fail).css('color', '#b32d2e').show();
                    }
                }).fail(function () {
                    $msg.text(asqFrontend.i18n.email_fail).css('color', '#b32d2e').show();
                });
            });
        },

        // Reveal the rest of the reading and remember this device.
        unlockReading: function () {
            this.setUnlocked();
            this.clearState();
            var $gate = this.$el.find('.asq-gate');
            $gate.find('.asq-gate-form').hide();
            $gate.find('.asq-gate-lead').text(asqFrontend.i18n.sent_confirm);
            this.$resultsScreen.find('.asq-reading-rest').slideDown(300);
        },

        /**
         * Load results from a stored session token.
         * Skips the quiz and shows results directly.
         */
        loadSessionResults: function (token) {
            var self = this;

            // Hide quiz UI, show loading.
            this.$container.hide();
            this.$el.find('.asq-progress-bar-wrap').hide();
            this.$loadingScreen.fadeIn(300);

            // Re-compute results using the stored answers.
            $.post(asqFrontend.ajax_url, {
                action: 'asq_compute_results',
                nonce: asqFrontend.nonce,
                finder_id: self.finderId,
                source_id: asqFrontend.source_id,
                answers: '{}',
                followup_answers: '{}',
                results_token: token
            }, function (res) {
                if (res.success) {
                    setTimeout(function () { self.showResults(res.data); }, 800);
                } else {
                    // Token invalid or expired – start quiz normally.
                    self.$loadingScreen.hide();
                    self.$el.find('.asq-progress-bar-wrap').show();
                    self.history.push({ type: 'question', qi: 0 });
                    self.renderQuestion(0);
                    self.updateProgress();
                }
            }).fail(function () {
                self.$loadingScreen.hide();
                self.$el.find('.asq-progress-bar-wrap').show();
                self.history.push({ type: 'question', qi: 0 });
                self.renderQuestion(0);
                self.updateProgress();
            });
        },

        /* ───────── Loading screen ───────── */

        showLoading: function () {
            var self = this;
            this.$container.hide();
            this.$loadingScreen.fadeIn(300);

            // Update progress to 100%
            this.$progress.css('width', '100%');
            this.$progressText.text(asqFrontend.i18n.complete);

            if (this._cachedResults) {
                setTimeout(function () { self.showResults(self._cachedResults); }, 1500);
            } else {
                this.computeResults(function (data) {
                    // Show loading for at least 1.5s for UX
                    setTimeout(function () { self.showResults(data); }, 1500);
                });
            }
        },

        /* ───────── Compute results ───────── */

        computeResults: function (callback) {
            var self = this;
            $.post(asqFrontend.ajax_url, {
                action: 'asq_compute_results',
                nonce: asqFrontend.nonce,
                finder_id: this.finderId,
                source_id: asqFrontend.source_id,
                answers: JSON.stringify(this.answers),
                followup_answers: JSON.stringify(this.followupAnswers)
            }, function (res) {
                if (res.success) {
                    callback(res.data);
                } else {
                    console.warn('[Apotheca Skin Quiz] compute_results returned success=false', res);
                    callback({ answers: [], options: self.options });
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.error('[Apotheca Skin Quiz] AJAX FAILED:', textStatus, errorThrown);
                callback({ answers: [], options: self.options });
            });
        },

        /* ───────── Results screen ───────── */

        showResults: function (data) {
            this.$loadingScreen.hide();
            this._cachedResults = data;

            var $title     = this.$resultsScreen.find('.asq-results-title');
            var $container = this.$resultsScreen.find('.asq-results-container');

            if (data.is_gate) {
                // The medical gate response stands alone.
                $title.hide();
                $container.html((data.reading_html || '').trim());
            } else {
                $title.show();
                // A reading in two parts: the first section is always shown; the
                // rest sits behind the email gate unless she is already unlocked
                // (came from her emailed link, or has the returning-visitor cookie).
                var intro = (data.reading_intro_html || '').trim();
                var rest  = (data.reading_rest_html || '').trim();
                var unlocked = !!asqFrontend.results_token || this.isUnlocked();

                var html = '<div class="asq-reading">';
                html += intro;
                if (!unlocked && rest) {
                    html += this.gateHtml();
                    html += '<div class="asq-reading-rest" style="display:none;">' + rest + '</div>';
                } else {
                    html += '<div class="asq-reading-rest">' + rest + '</div>';
                }
                html += '</div>';
                $container.html(html);
                this.syncConsent();
            }

            // Reveal the results screen. The results container is a static
            // aria-live region (see the shell markup), so injecting the reading
            // above announces it to screen readers. We also move keyboard focus
            // to the heading (gate: the reading region) so keyboard users land
            // at the start of the result rather than back at the top of the page.
            this.$resultsScreen.css({ opacity: 0, display: 'block' });
            this.$resultsScreen.animate({ opacity: 1 }, 300);

            var $focusTarget = data.is_gate ? $container : $title;
            if ($focusTarget && $focusTarget.length) {
                if (data.is_gate) {
                    $container.attr('tabindex', '-1');
                }
                try { $focusTarget.trigger('focus'); } catch (e) {}
            }
        },

        /* ───────── Start over ───────── */

        startOver: function () {
            this.clearState();
            this.current = 0;
            this.answers = {};
            this.followupAnswers = {};
            this.history = [{ type: 'question', qi: 0 }];
            this._cachedResults = null;

            this.$resultsScreen.hide().css('opacity', '');
            this.$resultsScreen.find('.asq-results-container').empty();
            this.$loadingScreen.hide();

            // Remove asq_results from URL if present.
            if (window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.delete('asq_results');
                window.history.replaceState({}, '', url.toString());
            }

            this.$el.find('.asq-progress-bar-wrap').show();
            this.$container.show();
            this.renderQuestion(0);
            this.updateProgress();
        },

        /* ───────── Session persistence ───────── */

        stateKey: function () {
            return 'asq_state_' + this.finderId;
        },

        // Save answers, position and current screen so a page refresh does not
        // lose her progress. Stored per quiz, for this browser tab only.
        persistState: function () {
            try {
                sessionStorage.setItem(this.stateKey(), JSON.stringify({
                    answers: this.answers,
                    followupAnswers: this.followupAnswers,
                    history: this.history,
                    current: this.current,
                    view: this.view
                }));
            } catch (e) {}
        },

        // Rebuild the quiz from saved progress. Returns true if it restored.
        restoreState: function () {
            var s;
            try {
                var raw = sessionStorage.getItem(this.stateKey());
                if (!raw) return false;
                s = JSON.parse(raw);
            } catch (e) { return false; }

            if (!s || !s.history || !s.history.length) return false;

            this.answers         = s.answers || {};
            this.followupAnswers = s.followupAnswers || {};
            this.history         = s.history;
            this.current         = typeof s.current === 'number' ? s.current : 0;
            this.view            = s.view || 'question';

            var top = this.history[this.history.length - 1];
            if (top && top.type === 'followup') {
                this.renderFollowupQuestion(top.qi, top.ai);
                this.updateProgress();
            } else {
                this.current = (top && typeof top.qi === 'number') ? top.qi : this.current;
                this.renderQuestion(this.current);
                this.updateProgress();
            }
            return true;
        },

        clearState: function () {
            try { sessionStorage.removeItem(this.stateKey()); } catch (e) {}
        },

        /* ───────── Utilities ───────── */

        escHtml: function (str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    };

    /* ───────── Initialise all finders on page ───────── */

    $(function () {
        $('.asq-finder').each(function () {
            new SkinQuiz($(this));
        });
    });

})(jQuery);

(function ($) {
    'use strict';

    var questionIndex       = 0;
    var answerIndex         = 0;
    var followupAnswerIndex = 0;

    /* ───────────── Init ───────────── */

    $(function () {
        initExistingIndices();
        initSortable();
        bindEvents();
    });

    function initExistingIndices() {
        // Find the highest existing indices so new items don't collide.
        $('.asq-question').each(function () {
            var qi = parseInt($(this).data('qi'), 10);
            if (!isNaN(qi) && qi >= questionIndex) { questionIndex = qi + 1; }
        });
        $('.asq-answer').each(function () {
            var ai = parseInt($(this).data('ai'), 10);
            if (!isNaN(ai) && ai >= answerIndex) { answerIndex = ai + 1; }
        });
        $('.asq-followup-answer').each(function () {
            var fai = parseInt($(this).data('fai'), 10);
            if (!isNaN(fai) && fai >= followupAnswerIndex) { followupAnswerIndex = fai + 1; }
        });
    }

    /* ───────────── Sortable ───────────── */

    function initSortable() {
        $('#asq-questions-list').sortable({
            handle: '.asq-drag-handle',
            placeholder: 'asq-sortable-placeholder',
            opacity: 0.7,
            tolerance: 'pointer'
        });
        initAnswerSortable();
    }

    function initAnswerSortable() {
        $('.asq-sortable-answers').sortable({
            handle: '.asq-drag-handle-answer',
            placeholder: 'asq-sortable-placeholder',
            opacity: 0.7,
            tolerance: 'pointer',
            connectWith: false
        });
    }

    /* ───────────── Events ───────────── */

    function bindEvents() {
        // Add question
        $('#asq-add-question').on('click', addQuestion);

        // Toggle question body
        $(document).on('click', '.asq-question-header', function (e) {
            if ($(e.target).closest('.asq-remove-question').length) return;
            $(this).closest('.asq-question').find('.asq-question-body').slideToggle(200);
            $(this).find('.asq-question-toggle').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        });

        // Remove question
        $(document).on('click', '.asq-remove-question', function (e) {
            e.stopPropagation();
            if (confirm(asqAdmin.i18n.confirm_del)) {
                $(this).closest('.asq-question').slideUp(200, function () { $(this).remove(); });
            }
        });

        // Update question title live
        $(document).on('input', '.asq-question-text-input', function () {
            var val = $(this).val() || 'New Question';
            $(this).closest('.asq-question').find('.asq-question-title').text(val);
        });

        // Toggle answer body
        $(document).on('click', '.asq-answer-header', function (e) {
            if ($(e.target).closest('.asq-remove-answer').length) return;
            $(this).closest('.asq-answer').find('.asq-answer-body').slideToggle(200);
        });

        // Add answer
        $(document).on('click', '.asq-add-answer', addAnswer);

        // Remove answer
        $(document).on('click', '.asq-remove-answer', function (e) {
            e.stopPropagation();
            if (confirm(asqAdmin.i18n.confirm_del)) {
                $(this).closest('.asq-answer').slideUp(200, function () { $(this).remove(); });
            }
        });

        // Update answer label live
        $(document).on('input', '.asq-answer-text-input', function () {
            var val = $(this).val() || 'New Answer';
            $(this).closest('.asq-answer').find('.asq-answer-label').text(val);
        });

        // Image
        $(document).on('click', '.asq-select-image', selectImage);
        $(document).on('click', '.asq-remove-image', removeImage);

        // Follow-up question controls
        $(document).on('click', '.asq-add-followup', addFollowup);
        $(document).on('click', '.asq-remove-followup', removeFollowup);
        $(document).on('click', '.asq-add-followup-answer', addFollowupAnswer);
        $(document).on('click', '.asq-remove-followup-answer', function (e) {
            e.stopPropagation();
            if (confirm(asqAdmin.i18n.confirm_del)) {
                $(this).closest('.asq-followup-answer').slideUp(200, function () { $(this).remove(); });
            }
        });
        $(document).on('input', '.asq-followup-answer-text-input', function () {
            var val = $(this).val() || 'New Answer';
            $(this).closest('.asq-followup-answer').find('.asq-followup-answer-label').first().text(val);
        });
        $(document).on('click', '.asq-followup-answer-header', function (e) {
            if ($(e.target).closest('.asq-remove-followup-answer').length) return;
            $(this).closest('.asq-followup-answer').find('.asq-followup-answer-body').slideToggle(200);
        });
    }

    /* ───────────── Add Question / Answer ───────────── */

    function addQuestion() {
        var tmpl = wp.template('asq-question');
        var html = tmpl({ qi: questionIndex });
        questionIndex++;

        var $html = $(html);
        $('#asq-questions-list').append($html);
        $html.find('.asq-question-body').show();
        initAnswerSortable();
    }

    function addAnswer() {
        var $question = $(this).closest('.asq-question');
        var qi = $question.data('qi');
        var tmpl = wp.template('asq-answer');
        var html = tmpl({ qi: qi, ai: answerIndex });
        answerIndex++;

        var $html = $(html);
        $question.find('.asq-answers-list').append($html);
        $html.find('.asq-answer-body').show();
        initAnswerSortable();
    }

    /* ───────────── Image handling ───────────── */

    function selectImage() {
        var $wrap = $(this).closest('.asq-answer-image-wrap');

        var frame = wp.media({
            title: asqAdmin.i18n.select_image,
            button: { text: asqAdmin.i18n.use_image },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $wrap.find('.asq-image-id').val(attachment.id);
            var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $wrap.find('.asq-image-preview img').attr('src', url);
            $wrap.find('.asq-image-preview').show();
        });

        frame.open();
    }

    function removeImage(e) {
        e.preventDefault();
        e.stopPropagation();
        var $wrap = $(this).closest('.asq-answer-image-wrap');
        $wrap.find('.asq-image-id').val('');
        $wrap.find('.asq-image-preview').hide();
        $wrap.find('.asq-image-preview img').attr('src', '');
    }

    /* ───────────── Follow-up Questions ───────────── */

    function addFollowup() {
        var $answer = $(this).closest('.asq-answer');
        $answer.find('.asq-followup-wrap').slideDown(200);
        $answer.find('.asq-followup-add').hide();
    }

    function removeFollowup() {
        var $answer = $(this).closest('.asq-answer');
        var $wrap   = $answer.find('.asq-followup-wrap');
        // Clear all follow-up data.
        $wrap.find('input[type="text"], input[type="hidden"]').val('');
        $wrap.find('input[type="checkbox"]').prop('checked', false);
        $wrap.find('.asq-followup-answers-list').empty();
        $wrap.slideUp(200);
        $answer.find('.asq-followup-add').show();
    }

    function addFollowupAnswer() {
        var $answer   = $(this).closest('.asq-answer');
        var $question = $answer.closest('.asq-question');
        var qi  = $question.data('qi');
        var ai  = $answer.data('ai');
        var fai = followupAnswerIndex;
        followupAnswerIndex++;

        var tmpl = wp.template('asq-followup-answer');
        var html = tmpl({ qi: qi, ai: ai, fai: fai });
        var $html = $(html);
        $answer.find('.asq-followup-answers-list').append($html);
        $html.find('.asq-followup-answer-body').show();
    }

})(jQuery);

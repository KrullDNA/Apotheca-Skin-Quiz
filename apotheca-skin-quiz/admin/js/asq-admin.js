(function ($) {
    'use strict';

    var questionIndex       = 0;
    var answerIndex         = 0;
    var productIndex        = 0;
    var followupAnswerIndex = 0;

    /* ───────────── Init ───────────── */

    $(function () {
        initExistingIndices();
        initSortable();
        bindEvents();
        applyFinderType( getFinderType() );
        applyDayNight();
    });

    /**
     * Read the currently selected finder type from the radio buttons.
     * Falls back to the value passed from PHP via asqAdmin.finder_type.
     */
    function getFinderType() {
        var checked = $('input[name="asq_options[finder_type]"]:checked').val();
        return checked || asqAdmin.finder_type || 'cosmeceuticals';
    }

    /**
     * Show or hide all variation pickers based on finder type.
     * In "beauty" mode, also load variations for any product rows
     * that don't have their dropdown populated yet.
     */
    function applyFinderType( type ) {
        // Category picker is shown for both beauty and cosmeceuticals.
        if ( type === 'beauty' || type === 'cosmeceuticals' ) {
            $('.asq-product-category-picker').show();
            filterCategoryOptions( type );
        } else {
            $('.asq-product-category-picker').hide();
        }

        // Variation picker is beauty-only.
        if ( type === 'beauty' ) {
            $('.asq-variation-picker').show();
            // Load variations for existing product rows that need them.
            $('.asq-product-row').each(function () {
                var $row = $(this);
                var $select = $row.find('.asq-variation-select');
                // Only fetch if the select has 1 or fewer options (the placeholder).
                if ( $select.find('option').length <= 1 || ( $select.find('option').length === 2 && $select.data('current') ) ) {
                    var productId = $row.find('.asq-product-id').val();
                    if ( productId ) {
                        loadVariationsForRow( $row, productId );
                    }
                }
            });
        } else {
            $('.asq-variation-picker').hide();
        }
    }

    /**
     * Show/hide category <option> elements based on finder type.
     * Each option has data-type="beauty" or data-type="cosmeceuticals".
     * The "— None —" option (no data-type) is always visible.
     * Options are also disabled because Safari ignores display:none
     * on <option> elements.
     */
    function filterCategoryOptions( type ) {
        $('.asq-product-category').each(function () {
            var $select = $(this);
            $select.find('option[data-type]').each(function () {
                if ( $(this).data('type') === type ) {
                    $(this).show().prop('disabled', false);
                } else {
                    // If this hidden option is currently selected, reset to empty.
                    if ( $(this).is(':selected') ) {
                        $select.val('');
                    }
                    $(this).hide().prop('disabled', true);
                }
            });
        });
    }

    /**
     * Fetch variations for a product and populate the select dropdown.
     */
    function loadVariationsForRow( $row, productId ) {
        var $select = $row.find('.asq-variation-select');
        var currentVariation = $row.find('.asq-variation-id').val();

        $select.empty().append('<option value="">' + asqAdmin.i18n.loading_variations + '</option>');

        $.ajax({
            url: asqAdmin.ajax_url,
            data: {
                action: 'asq_get_variations',
                nonce: asqAdmin.nonce,
                product_id: productId
            },
            success: function (data) {
                $select.empty().append('<option value="">' + asqAdmin.i18n.select_variation + '</option>');

                if ( data && data.length ) {
                    $.each(data, function (_, v) {
                        var sel = ( String(v.id) === String(currentVariation) ) ? ' selected' : '';
                        $select.append('<option value="' + v.id + '"' + sel + '>' + escHtml(v.text) + (v.price ? ' – ' + v.price : '') + '</option>');
                    });
                } else {
                    $select.empty().append('<option value="">' + asqAdmin.i18n.no_variations + '</option>');
                }
            }
        });
    }

    /**
     * Show or hide all result-set pickers based on the Day/Night checkbox.
     */
    function applyDayNight() {
        var enabled = $('input[name="asq_options[enable_day_night]"]').is(':checked');
        if ( enabled ) {
            $('.asq-product-set-picker').show();
        } else {
            $('.asq-product-set-picker').hide();
        }
    }

    function initExistingIndices() {
        // Find the highest existing indices so new items don't collide
        $('.asq-question').each(function () {
            var qi = parseInt($(this).data('qi'), 10);
            if (!isNaN(qi) && qi >= questionIndex) {
                questionIndex = qi + 1;
            }
        });
        $('.asq-answer').each(function () {
            var ai = parseInt($(this).data('ai'), 10);
            if (!isNaN(ai) && ai >= answerIndex) {
                answerIndex = ai + 1;
            }
        });
        $('.asq-product-row').each(function () {
            var pi = parseInt($(this).data('pi'), 10);
            if (!isNaN(pi) && pi >= productIndex) {
                productIndex = pi + 1;
            }
        });
        $('.asq-followup-answer').each(function () {
            var fai = parseInt($(this).data('fai'), 10);
            if (!isNaN(fai) && fai >= followupAnswerIndex) {
                followupAnswerIndex = fai + 1;
            }
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
        // Finder type toggle
        $(document).on('change', 'input[name="asq_options[finder_type]"]', function () {
            applyFinderType( $(this).val() );
        });

        // Day/Night toggle
        $(document).on('change', 'input[name="asq_options[enable_day_night]"]', function () {
            applyDayNight();
        });

        // Variation select change – update hidden input
        $(document).on('change', '.asq-variation-select', function () {
            var $row = $(this).closest('.asq-product-row');
            $row.find('.asq-variation-id').val( $(this).val() );
        });

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

        // Select image
        $(document).on('click', '.asq-select-image', selectImage);

        // Remove image
        $(document).on('click', '.asq-remove-image', removeImage);

        // Product search
        $(document).on('input', '.asq-product-search', debounce(searchProducts, 400));
        $(document).on('click', '.asq-product-search-result', selectProduct);

        // Remove product
        $(document).on('click', '.asq-remove-product', removeProduct);

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

        // Close search results on outside click
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.asq-answer-products-wrap').length) {
                $('.asq-product-search-results').hide();
            }
        });
    }

    /* ───────────── Add Question ───────────── */

    function addQuestion() {
        var tmpl = wp.template('asq-question');
        var html = tmpl({ qi: questionIndex });
        questionIndex++;

        // We need to parse the template: replace {{data.qi}} placeholders that wp.template may not replace
        var $html = $(html);
        $('#asq-questions-list').append($html);
        $html.find('.asq-question-body').show();
        initAnswerSortable();
    }

    /* ───────────── Add Answer ───────────── */

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

    /* ───────────── Product search ───────────── */

    function searchProducts() {
        var $input   = $(this);
        var term     = $input.val();
        var $results = $input.siblings('.asq-product-search-results');

        if (term.length < 2) {
            $results.hide().empty();
            return;
        }

        $.ajax({
            url: asqAdmin.ajax_url,
            data: {
                action: 'asq_search_products',
                nonce: asqAdmin.nonce,
                term: term
            },
            success: function (data) {
                $results.empty();
                if (data && data.length) {
                    $.each(data, function (_, item) {
                        var $row = $('<div class="asq-product-search-result" data-id="' + item.id + '" data-name="' + escHtml(item.text) + '"></div>');
                        if (item.thumb) {
                            $row.append('<img src="' + item.thumb + '" width="30" height="30">');
                        }
                        $row.append('<span>' + escHtml(item.text) + '</span>');
                        $results.append($row);
                    });
                    $results.show();
                } else {
                    $results.hide();
                }
            }
        });
    }

    function selectProduct() {
        var $this    = $(this);
        var $wrap    = $this.closest('.asq-answer-products-wrap');
        var $question= $this.closest('.asq-question');

        var qi = $question.data('qi');
        var pi = productIndex;
        productIndex++;

        var prodId   = $this.data('id');
        var prodName = $this.data('name');

        // Detect if we're inside a follow-up answer
        var $followupAnswer = $this.closest('.asq-followup-answer');
        var tmpl, html;

        if ( $followupAnswer.length ) {
            var $answer = $followupAnswer.closest('.asq-answer');
            var ai  = $answer.data('ai');
            var fai = $followupAnswer.data('fai');
            tmpl = wp.template('asq-followup-product-row');
            html = tmpl({ qi: qi, ai: ai, fai: fai, pi: pi, name: prodName });
        } else {
            var $answer = $this.closest('.asq-answer');
            var ai = $answer.data('ai');
            tmpl = wp.template('asq-product-row');
            html = tmpl({ qi: qi, ai: ai, pi: pi, name: prodName });
        }

        // Parse the HTML and set the product ID
        var $row = $(html);
        $row.find('.asq-product-id').val(prodId);
        $row.find('.asq-product-name').text(prodName);
        $row.attr('data-product-id', prodId);

        $wrap.find('.asq-products-list').append($row);
        $wrap.find('.asq-product-search').val('');
        $wrap.find('.asq-product-search-results').hide().empty();

        var finderType = getFinderType();

        // Show category picker for both beauty and cosmeceuticals.
        if ( finderType === 'beauty' || finderType === 'cosmeceuticals' ) {
            $row.find('.asq-product-category-picker').show();
            filterCategoryOptions( finderType );
        }

        // Beauty mode: also show variation picker and load variations.
        if ( finderType === 'beauty' ) {
            $row.find('.asq-variation-picker').show();
            loadVariationsForRow( $row, prodId );
        }

        // If Day/Night is enabled, show the set picker.
        if ( $('input[name="asq_options[enable_day_night]"]').is(':checked') ) {
            $row.find('.asq-product-set-picker').show();
        }
    }

    function removeProduct() {
        $(this).closest('.asq-product-row').remove();
    }

    /* ───────────── Follow-up Questions ───────────── */

    function addFollowup() {
        var $answer = $(this).closest('.asq-answer');
        var $wrap   = $answer.find('.asq-followup-wrap');
        $wrap.slideDown(200);
        $answer.find('.asq-followup-add').hide();
    }

    function removeFollowup() {
        var $answer = $(this).closest('.asq-answer');
        var $wrap   = $answer.find('.asq-followup-wrap');
        // Clear all follow-up data
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

    /* ───────────── Utilities ───────────── */

    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})(jQuery);

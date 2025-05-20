(function($) {
    'use strict';

    $(document).ready(function() {
        // Color pickers
        $('.color-picker').wpColorPicker();

        // Add new condition
        $('#wc-progress-bar-add-condition').on('click', function() {
            var $conditions = $('#wc-progress-bar-conditions');
            var index = $conditions.find('.wc-progress-bar-condition').length;

            var $newCondition = $('<div class="wc-progress-bar-condition" data-index="' + index + '"></div>');
            $newCondition.append(wcProgressBarAdmin.getConditionHtml(index));
            $conditions.append($newCondition);
        });

        // Remove condition
        $(document).on('click', '.wc-progress-bar-remove-condition', function() {
            $(this).closest('.wc-progress-bar-condition').remove();
            // Reindex remaining conditions
            $('#wc-progress-bar-conditions .wc-progress-bar-condition').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name').replace(/\[\d+\]/, '[' + i + ']');
                    $(this).attr('name', name);
                });
            });
        });

        // Add sub-condition
        $(document).on('click', '.wc-progress-bar-add-sub-condition', function() {
            var $condition = $(this).closest('.wc-progress-bar-condition');
            var parentIndex = $condition.data('index');
            var $subConditionsContainer = $condition.find('.wc-progress-bar-sub-conditions');
            var subIndex = $subConditionsContainer.find('.wc-progress-bar-sub-condition').length;

            var $newSubCondition = $('<div class="wc-progress-bar-sub-condition"></div>');
            $newSubCondition.append(wcProgressBarAdmin.getSubConditionHtml(parentIndex, subIndex));
            $subConditionsContainer.append($newSubCondition);
        });

        // Remove sub-condition
        $(document).on('click', '.wc-progress-bar-remove-sub-condition', function() {
            var $subCondition = $(this).closest('.wc-progress-bar-sub-condition');
            var $condition = $subCondition.closest('.wc-progress-bar-condition');
            var parentIndex = $condition.data('index');

            $subCondition.remove();

            // Reindex remaining sub-conditions
            $condition.find('.wc-progress-bar-sub-condition').each(function(i) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name').replace(/\[sub_conditions\]\[\d+\]/, '[sub_conditions][' + i + ']');
                    $(this).attr('name', name);
                });
            });
        });

        // Make conditions sortable
        $('#wc-progress-bar-conditions').sortable({
            handle: '.wc-progress-bar-main-condition',
            update: function() {
                // Update priorities after sorting
                $(this).find('.wc-progress-bar-condition').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('input[name$="[priority]"]').val(i);
                    $(this).find('input, select').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            // Only update the main condition index, not subcondition indexes
                            name = name.replace(/wc_progress_bar_conditions\[\d+\]/, 'wc_progress_bar_conditions[' + i + ']');
                            $(this).attr('name', name);
                        }
                    });
                });
            }
        });
    });

    // Template for new condition
    wcProgressBarAdmin = window.wcProgressBarAdmin || {};
    wcProgressBarAdmin.getConditionHtml = function(index) {
        var html = '<div class="wc-progress-bar-condition-fields">' +
            '<div class="wc-progress-bar-main-condition">' +
                '<select name="wc_progress_bar_conditions[' + index + '][type]" class="wc-progress-bar-condition-type">' +
                    '<option value="cart_total">' + wcProgressBarAdmin.i18n.cartTotal + '</option>' +
                    '<option value="product_count">' + wcProgressBarAdmin.i18n.productCount + '</option>' +
                '</select>' +

                '<select name="wc_progress_bar_conditions[' + index + '][operator]" class="wc-progress-bar-condition-operator">' +
                    '<option value=">">' + wcProgressBarAdmin.i18n.greaterThan + '</option>' +
                    '<option value=">=">' + wcProgressBarAdmin.i18n.greaterThanOrEqual + '</option>' +
                    '<option value="<">' + wcProgressBarAdmin.i18n.lessThan + '</option>' +
                    '<option value="<=">' + wcProgressBarAdmin.i18n.lessThanOrEqual + '</option>' +
                    '<option value="==">' + wcProgressBarAdmin.i18n.equalTo + '</option>' +
                '</select>' +

                '<input type="number" name="wc_progress_bar_conditions[' + index + '][value]" min="0" step="0.01" value="" placeholder="0">' +

                '<span>' + wcProgressBarAdmin.i18n.thenSetProgressTo + '</span>' +
                '<input type="number" name="wc_progress_bar_conditions[' + index + '][progress]" min="0" max="100" value="50">%' +

                '<input type="hidden" name="wc_progress_bar_conditions[' + index + '][priority]" value="' + index + '">' +

                '<button type="button" class="wc-progress-bar-remove-condition button-link">' + wcProgressBarAdmin.i18n.remove + '</button>' +
            '</div>' +

            '<div class="wc-progress-bar-logic-operator">' +
                '<select name="wc_progress_bar_conditions[' + index + '][logic]" class="wc-progress-bar-condition-logic">' +
                    '<option value="and">' + wcProgressBarAdmin.i18n.and + '</option>' +
                    '<option value="or">' + wcProgressBarAdmin.i18n.or + '</option>' +
                '</select>' +
                '<button type="button" class="wc-progress-bar-add-sub-condition button">' + wcProgressBarAdmin.i18n.addSubCondition + '</button>' +
            '</div>' +

            '<div class="wc-progress-bar-sub-conditions"></div>' +

            '<div class="wc-progress-bar-condition-text">' +
                '<label>' + wcProgressBarAdmin.i18n.displayText + '</label>' +
                '<input type="text" name="wc_progress_bar_conditions[' + index + '][text]" value="" class="regular-text">' +
                '<p class="description">' + wcProgressBarAdmin.i18n.conditionPlaceholders + '</p>' +
            '</div>' +
        '</div>';

        return html;
    };

    // Template for new sub-condition
    wcProgressBarAdmin.getSubConditionHtml = function(parentIndex, subIndex) {
        var html = '<div class="wc-progress-bar-sub-condition-fields">' +
            '<select name="wc_progress_bar_conditions[' + parentIndex + '][sub_conditions][' + subIndex + '][type]" class="wc-progress-bar-condition-type">' +
                '<option value="cart_total">' + wcProgressBarAdmin.i18n.cartTotal + '</option>' +
                '<option value="product_count">' + wcProgressBarAdmin.i18n.productCount + '</option>' +
            '</select>' +

            '<select name="wc_progress_bar_conditions[' + parentIndex + '][sub_conditions][' + subIndex + '][operator]" class="wc-progress-bar-condition-operator">' +
                '<option value=">">' + wcProgressBarAdmin.i18n.greaterThan + '</option>' +
                '<option value=">=">' + wcProgressBarAdmin.i18n.greaterThanOrEqual + '</option>' +
                '<option value="<">' + wcProgressBarAdmin.i18n.lessThan + '</option>' +
                '<option value="<=">' + wcProgressBarAdmin.i18n.lessThanOrEqual + '</option>' +
                '<option value="==">' + wcProgressBarAdmin.i18n.equalTo + '</option>' +
            '</select>' +

            '<input type="number" name="wc_progress_bar_conditions[' + parentIndex + '][sub_conditions][' + subIndex + '][value]" min="0" step="0.01" value="" placeholder="0">' +

            '<button type="button" class="wc-progress-bar-remove-sub-condition button-link">' + wcProgressBarAdmin.i18n.remove + '</button>' +
        '</div>';

        return html;
    };
})(jQuery);
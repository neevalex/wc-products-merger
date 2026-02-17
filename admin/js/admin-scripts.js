// This file contains JavaScript for handling user interactions and AJAX requests in the admin area.

jQuery(document).ready(function($) {
    // Handle the bulk action selection
    $('#bulk-action-selector').on('change', function() {
        var selectedAction = $(this).val();
        if (selectedAction) {
            // Redirect to the merge selection page with selected products
            var selectedProducts = [];
            $('input[name="product_ids[]"]:checked').each(function() {
                selectedProducts.push($(this).val());
            });

            if (selectedProducts.length > 0) {
                var redirectUrl = '<?php echo admin_url("admin.php?page=merge-selection"); ?>' + '&products=' + selectedProducts.join(',');
                window.location.href = redirectUrl;
            } else {
                alert('Please select at least one product.');
            }
        }
    });

    (function($) {
        'use strict';

        $(document).ready(function() {
            var $form = $('#merge-products-form');
            var $submitBtn = $('#merge-submit-btn');

            // Validate form on submit
            $form.on('submit', function(e) {
                var $selectedRadio = $('input[name="primary_product"]:checked');

                if ($selectedRadio.length === 0) {
                    e.preventDefault();
                    alert(wcProductsMerger.i18n.selectProduct);
                    return false;
                }

                if (!confirm(wcProductsMerger.i18n.confirmMerge)) {
                    e.preventDefault();
                    return false;
                }

                // Disable button to prevent double submission
                $submitBtn.prop('disabled', true).text('Processing...');
            });

            // Highlight selected row
            $('input[name="primary_product"]').on('change', function() {
                $('table.wp-list-table tbody tr').removeClass('selected');
                $(this).closest('tr').addClass('selected');
            });
        });

    })(jQuery);
});
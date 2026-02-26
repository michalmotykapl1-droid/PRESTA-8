// JavaScript code for your module in the administration panel
// e.g., for dynamic column width adjustment or other interactions.

$(document).ready(function() {
    // Function to check/uncheck all product checkboxes
    $('.check_all_products').on('change', function() {
        var isChecked = $(this).prop('checked');
        $(this).closest('table').find('.product-checkbox').prop('checked', isChecked);
    });

    // AJAX form submission handler
    $('.ajax-assign-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        var $form = $(this);
        var $panel = $form.closest('.panel'); // Find the parent panel
        var $submitButton = $form.find('button[type="submit"]');
        var originalButtonHtml = $submitButton.html();

        // Check if any products are selected
        if ($form.find('.product-checkbox:checked').length === 0) {
            // Note: For production, these messages should be localized via PrestaShop's translation system
            showFeedback('Proszę zaznaczyć przynajmniej jeden produkt.', 'danger'); 
            return;
        }

        // Change button state during processing
        $submitButton.html('<i class="process-icon-loading"></i> Przetwarzanie...').prop('disabled', true); 
        
        $.ajax({
            type: 'POST',
            // Construct the URL using the form's action attribute and append AJAX parameters
            url: $form.attr('action') + '&ajax=1&action=assignProductsToCategory', 
            dataType: 'json',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showFeedback(response.message, 'success');
                    
                    // Remove assigned products from the table
                    $.each(response.assigned_ids, function(index, productId) {
                        $form.find('tr[data-product-id="' + productId + '"]').fadeOut('slow', function() {
                            $(this).remove();
                            // After removal, update the product count
                            updateProductCount($panel);
                        });
                    });
                } else {
                    showFeedback(response.message, 'danger');
                }
            },
            error: function() {
                showFeedback('Wystąpił nieoczekiwany błąd serwera.', 'danger'); 
            },
            complete: function() {
                // Restore button to its original state
                $submitButton.html(originalButtonHtml).prop('disabled', false);
            }
        });
    });

    // Function to update product count and visibility of table/message
    function updateProductCount($panel) {
        var $tbody = $panel.find('tbody');
        var currentCount = $tbody.find('tr').length;
        $panel.find('.product-count').text(currentCount);

        // If no products left, hide the form and show the "no products" message
        if (currentCount === 0) {
            $panel.find('.assign-form-container').hide();
            $panel.find('.no-products-message').show();
        }
    }

    // Function to display feedback messages
    function showFeedback(message, type) {
        var alertClass = (type === 'success') ? 'alert-success' : 'alert-danger';
        var feedbackDiv = $('#product-assign-feedback');
        
        // Clear previous messages before adding a new one
        feedbackDiv.empty(); 
        feedbackDiv.html('<div class="alert ' + alertClass + '">' + message + '</div>');
        
        // Hide the message after 5 seconds
        setTimeout(function() {
            feedbackDiv.fadeOut('slow', function() {
                $(this).html('').show(); // Clear and show for next message
            });
        }, 5000);
    }
});

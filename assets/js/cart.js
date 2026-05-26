/**
 * Urdu Books World - AJAX Shopping Cart Integrations
 */

$(document).ready(function() {

    // 1. Grid/List "Add to Cart" Button Handler
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var bookId = $btn.data('id');
        
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
        
        $.ajax({
            url: window.siteUrl + '/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                book_id: bookId,
                qty: 1,
                csrf_token: window.csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update header badge
                    $('#cart-counter-header').text(response.cart_count);
                    
                    // Show confirmation state
                    $btn.removeClass('btn-maroon').addClass('btn-gold').html('<i class="fa fa-circle-check"></i> Added!');
                    setTimeout(function() {
                        $btn.removeClass('btn-gold').addClass('btn-maroon').prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
                    }, 2000);
                } else {
                    alert(response.message);
                    $btn.prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
                }
            },
            error: function() {
                alert('Could not add item to cart. Please check your network connection.');
                $btn.prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
            }
        });
    });

    // 2. Book Detail "Add to Cart" Button Handler
    $(document).on('click', '.add-to-cart-detail-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var bookId = $btn.data('id');
        var qty = parseInt($('#detail-qty').val()) || 1;
        
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Adding...');
        
        $.ajax({
            url: window.siteUrl + '/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                book_id: bookId,
                qty: qty,
                csrf_token: window.csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-counter-header').text(response.cart_count);
                    
                    $btn.removeClass('btn-maroon').addClass('btn-gold').html('<i class="fa fa-circle-check"></i> Added to Cart!');
                    setTimeout(function() {
                        $btn.removeClass('btn-gold').addClass('btn-maroon').prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
                    }, 2000);
                } else {
                    alert(response.message);
                    $btn.prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
                }
            },
            error: function() {
                alert('Could not add item to cart.');
                $btn.prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Add to Cart');
            }
        });
    });

});

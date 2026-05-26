/**
 * Urdu Books World - Core UI JavaScript
 */

$(document).ready(function() {
    
    // 1. Mobile Menu Collapsible Trigger
    $('.mobile-menu-trigger').on('click', function(e) {
        e.preventDefault();
        $('.nav-bar').toggleClass('active');
        $(this).find('i').toggleClass('fa-bars fa-xmark');
    });

    // 2. Mobile Submenu dropdown toggling
    if ($(window).width() <= 768) {
        $('.nav-item.has-dropdown > .nav-link').on('click', function(e) {
            e.preventDefault();
            $(this).siblings('.nav-dropdown').slideToggle(200);
            $(this).find('.nav-chevron').toggleClass('fa-chevron-down fa-chevron-up');
        });
    }

    // 3. Asynchronous Newsletter Form submission (AJAX)
    $('#newsletter-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var email = $form.find('input[name="newsletter_email"]').val();
        var $msgBox = $('#newsletter-message');
        
        $msgBox.removeClass('alert-success alert-danger').text('').hide();
        
        $.ajax({
            url: window.siteUrl + '/index.php',
            method: 'POST',
            data: {
                newsletter_email: email,
                newsletter_subscribe: 1,
                csrf_token: window.csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $msgBox.addClass('alert alert-success').text(response.message).fadeIn();
                    $form.find('input[name="newsletter_email"]').val('');
                } else {
                    $msgBox.addClass('alert alert-danger').text(response.message).fadeIn();
                }
            },
            error: function() {
                $msgBox.addClass('alert alert-danger').text('An error occurred. Please try again.').fadeIn();
            }
        });
    });

});

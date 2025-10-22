/**
 * Instructions Page - Accordion Functionality
 */
jQuery(document).ready(function($) {
    'use strict';

    // Accordion toggle functionality
    $('.accordion-header').on('click', function() {
        var $header = $(this);
        var $content = $header.next('.accordion-content');
        var $item = $header.parent('.accordion-item');

        // Toggle active class on header
        $header.toggleClass('active');

        // Slide toggle the content
        $content.slideToggle(300);

        // Optional: Close other accordion items (comment out if you want multiple open at once)
        // $('.accordion-header').not($header).removeClass('active');
        // $('.accordion-content').not($content).slideUp(300);
    });

    // Optional: Open first accordion item by default
    $('.accordion-item:first-child .accordion-header').addClass('active');
    $('.accordion-item:first-child .accordion-content').show();
});

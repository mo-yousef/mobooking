<?php get_header(); ?>

<div class="mobooking-container" data-test="mobooking">
    <h1 class="mobooking-title">MoBooking</h1>
    <p class="mobooking-description">MoBooking is a booking system for mobile devices.</p>
    <?php 
    if (have_posts()) :
        while (have_posts()) : the_post();
            the_content();
        endwhile;
    endif;
    ?>
</div>

<?php get_footer(); ?>
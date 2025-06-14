<?php
/**
 * The template for displaying 404 pages (Not Found)
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title"><?php _e( 'Oops! That page can&rsquo;t be found.', 'mobooking' ); ?></h1>
            </header><!-- .page-header -->

            <div class="page-content">
                <p><?php _e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'mobooking' ); ?></p>

                <?php
                // Attempt to include a search form.
                // This function is theme-dependent but is a standard WordPress way.
                get_search_form();
                ?>

                <div style="margin-top: 20px;">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button">
                        <?php _e( 'Go to Homepage', 'mobooking' ); ?>
                    </a>
                </div>

                <?php
                // Optionally, display some popular posts or pages as suggestions.
                // This is a more advanced addition and depends on theme/plugin structure.
                // For now, we'll keep it simple.
                /*
                if ( function_exists( 'wp_list_pages' ) ) {
                    echo '<h2>' . __( 'Maybe try one of these pages?', 'mobooking' ) . '</h2>';
                    wp_list_pages( array(
                        'depth'        => 1,
                        'sort_column'  => 'menu_order, post_title',
                        'number'       => 5,
                        'title_li'     => ''
                    ) );
                }
                */
                ?>
            </div><!-- .page-content -->
        </section><!-- .error-404 -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
?>

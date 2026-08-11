<?php

defined('ABSPATH') || exit;

get_header();

while (have_posts()) : the_post();
    $safariId = get_the_ID();
    $duration = (string) get_post_meta($safariId, 'duration', true);
    $shift = (string) get_post_meta($safariId, 'shift', true);
    $meetingPoint = (string) get_post_meta($safariId, 'meeting_point', true);
    $offerPrice = (float) get_post_meta($safariId, 'offer_price', true);
    $regularPrice = (float) get_post_meta($safariId, 'regular_price', true);
    ?>
    <main class="pwt-single-wrap">
        <article class="pwt-single-safari">
            <header class="pwt-single-hero">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium_large'); ?>
                <?php endif; ?>
            </header>

            <section class="pwt-section">
                <h1><?php the_title(); ?></h1>
                <div class="pwt-meta-grid">
                    <?php if ($duration) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Duration', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($duration); ?></div><?php endif; ?>
                    <?php if ($shift) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Shift', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html(ucfirst(str_replace('_', ' ', $shift))); ?></div><?php endif; ?>
                    <?php if ($meetingPoint) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Meeting Point', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($meetingPoint); ?></div><?php endif; ?>
                    <?php if ($offerPrice > 0) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Offer Price', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html('INR ' . number_format_i18n($offerPrice, 0)); ?></div><?php endif; ?>
                    <?php if ($regularPrice > 0) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Regular Price', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html('INR ' . number_format_i18n($regularPrice, 0)); ?></div><?php endif; ?>
                </div>
                <div>
                    <?php the_content(); ?>
                </div>
            </section>

            <?php echo do_shortcode('[pwt_booking_form]'); ?>
        </article>
    </main>
    <?php
endwhile;

get_footer();

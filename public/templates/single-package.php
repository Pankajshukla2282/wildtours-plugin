<?php

defined('ABSPATH') || exit;

get_header();

while (have_posts()) : the_post();
    $packageId = get_the_ID();
    $regularPrice = (float) get_post_meta($packageId, 'regular_price', true);
    $offerPrice = (float) get_post_meta($packageId, 'offer_price', true);
    $duration = (string) get_post_meta($packageId, 'duration', true);
    $days = (int) get_post_meta($packageId, 'days', true);
    $nights = (int) get_post_meta($packageId, 'nights', true);
    $inclusions = \PWT\Frontend\Content::getField($packageId, 'inclusions');
    $exclusions = \PWT\Frontend\Content::getField($packageId, 'exclusions');
    $itineraryRows = \PWT\Frontend\Content::getRepeaterRows($packageId, 'days_itinerary', ['title', 'description', 'photo']);
    ?>
    <main class="pwt-single-wrap">
        <article class="pwt-single-package">
            <header class="pwt-single-hero">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium_large'); ?>
                <?php endif; ?>
            </header>

            <section class="pwt-section">
                <h1><?php the_title(); ?></h1>
                <div class="pwt-meta-grid">
                    <?php if ($duration) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Duration', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($duration); ?></div><?php endif; ?>
                    <?php if ($days) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Days', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html((string) $days); ?></div><?php endif; ?>
                    <?php if ($nights) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Nights', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html((string) $nights); ?></div><?php endif; ?>
                    <?php if ($offerPrice > 0) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Offer Price', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html('INR ' . number_format_i18n($offerPrice, 0)); ?></div><?php endif; ?>
                    <?php if ($regularPrice > 0) : ?><div class="pwt-meta-chip"><strong><?php esc_html_e('Regular Price', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html('INR ' . number_format_i18n($regularPrice, 0)); ?></div><?php endif; ?>
                </div>
                <div>
                    <?php the_content(); ?>
                </div>
            </section>

            <?php if ($inclusions || $exclusions) : ?>
                <section class="pwt-section pwt-grid-two">
                    <?php if ($inclusions) : ?>
                        <div>
                            <h2><?php esc_html_e('Inclusions', 'wildtours-plugin'); ?></h2>
                            <div><?php echo wp_kses_post(wpautop((string) $inclusions)); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($exclusions) : ?>
                        <div>
                            <h2><?php esc_html_e('Exclusions', 'wildtours-plugin'); ?></h2>
                            <div><?php echo wp_kses_post(wpautop((string) $exclusions)); ?></div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($itineraryRows) : ?>
                <section class="pwt-section">
                    <header class="pwt-section-header">
                        <h2><?php esc_html_e('Day-wise Itinerary', 'wildtours-plugin'); ?></h2>
                    </header>
                    <div class="pwt-itinerary-list">
                        <?php foreach ($itineraryRows as $index => $row) : ?>
                            <article class="pwt-itinerary-item">
                                <div>
                                    <p class="pwt-tag"><?php echo esc_html(sprintf(__('Day %d', 'wildtours-plugin'), $index + 1)); ?></p>
                                    <h3><?php echo esc_html((string) ($row['title'] ?? '')); ?></h3>
                                    <div><?php echo wp_kses_post(wpautop((string) ($row['description'] ?? ''))); ?></div>
                                </div>
                                <?php if (!empty($row['photo']['url'])) : ?>
                                    <div class="pwt-itinerary-image"><img src="<?php echo esc_url($row['photo']['url']); ?>" alt="<?php echo esc_attr((string) ($row['title'] ?? get_the_title())); ?>"></div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php echo do_shortcode('[pwt_booking_form]'); ?>
        </article>
    </main>
    <?php
endwhile;

get_footer();

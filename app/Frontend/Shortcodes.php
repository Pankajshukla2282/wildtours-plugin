<?php

namespace PWT\Frontend;

defined('ABSPATH') || exit;

class Shortcodes
{
    public function register(): void
    {
        add_shortcode('pwt_homepage', [$this, 'homepage']);
        add_shortcode('pwt_packages', [$this, 'packages']);
        add_shortcode('pwt_safaris', [$this, 'safaris']);
        add_shortcode('pwt_destinations', [$this, 'destinations']);
        add_shortcode('pwt_testimonials', [$this, 'testimonials']);
        add_shortcode('pwt_faq', [$this, 'faqs']);
        add_shortcode('pwt_reviews', [$this, 'reviews']);
        add_shortcode('pwt_contact_card', [$this, 'contactCard']);
        add_shortcode('pwt_booking_form', [$this, 'bookingForm']);
        add_shortcode('pwt_payment_page', [$this, 'paymentPage']);
    }

    public function homepage(): string
    {
        ob_start();
        ?>
        <div class="pwt-site">
            <?php echo $this->heroSection(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->servicesSection(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->packages(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->safaris(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->destinations(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->testimonials(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->reviews(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->faqs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo do_shortcode('[pwt_availability_calendar]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <section class="pwt-section pwt-grid-two">
                <div>
                    <?php echo $this->contactCard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <div>
                    <?php echo $this->bookingForm(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </section>
        </div>
        <?php

        return ob_get_clean();
    }

    public function packages(): string
    {
        $settings = get_option('pwt_settings', []);
        $featuredRaw = (string) ($settings['featured_package_ids'] ?? '');
        $featuredIds = array_values(array_filter(array_map('absint', array_map('trim', explode(',', $featuredRaw)))));

        $args = [
            'post_type' => 'pwt_package',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ];

        if (!empty($featuredIds)) {
            $args['post__in'] = $featuredIds;
            $args['orderby'] = 'post__in';
            $args['posts_per_page'] = max(6, count($featuredIds));
        }

        $query = new \WP_Query($args);

        return $this->renderCardsSection(
            __('Featured Packages', 'wildtours-plugin'),
            __('Handpicked itineraries for families, couples, and wildlife photographers.', 'wildtours-plugin'),
            $query,
            'duration'
        );
    }

    public function safaris(): string
    {
        $query = new \WP_Query([
            'post_type' => 'pwt_safari',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ]);

        return $this->renderCardsSection(
            __('Safari Experiences', 'wildtours-plugin'),
            __('Morning and evening safaris with expert drivers and naturalists.', 'wildtours-plugin'),
            $query,
            'shift'
        );
    }

    public function destinations(): string
    {
        $query = new \WP_Query([
            'post_type' => 'pwt_destination',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ]);

        return $this->renderCardsSection(
            __('Explore Destinations', 'wildtours-plugin'),
            __('From tiger reserve landscapes to hidden waterfalls and heritage temples.', 'wildtours-plugin'),
            $query,
            ''
        );
    }

    public function testimonials(): string
    {
        $query = new \WP_Query([
            'post_type' => 'pwt_testimonial',
            'post_status' => 'publish',
            'posts_per_page' => 4,
        ]);

        ob_start();
        ?>
        <section class="pwt-section">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('Guest Stories', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('What travelers say about their Panna adventure with us.', 'wildtours-plugin'); ?></p>
            </header>
            <div class="pwt-testimonials">
                <?php
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        ?>
                        <article class="pwt-testimonial-card">
                            <p class="pwt-quote">"<?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(null, false), 40)); ?>"</p>
                            <h3><?php echo esc_html(get_the_title()); ?></h3>
                        </article>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    ?>
                    <p><?php esc_html_e('Add testimonials to showcase guest experiences.', 'wildtours-plugin'); ?></p>
                    <?php
                }
                ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function faqs(): string
    {
        $query = new \WP_Query([
            'post_type' => 'pwt_faq',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        ]);

        ob_start();
        ?>
        <section class="pwt-section pwt-faq">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('Frequently Asked Questions', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('Everything you need before planning your wild tour.', 'wildtours-plugin'); ?></p>
            </header>
            <div class="pwt-faq-list">
                <?php
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        ?>
                        <details class="pwt-faq-item">
                            <summary><?php echo esc_html(get_the_title()); ?></summary>
                            <div><?php echo wp_kses_post(wpautop(get_the_content(null, false))); ?></div>
                        </details>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    ?>
                    <p><?php esc_html_e('Add FAQs to answer common traveler questions.', 'wildtours-plugin'); ?></p>
                    <?php
                }
                ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function reviews(): string
    {
        $query = new \WP_Query([
            'post_type' => 'pwt_review',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        ]);

        ob_start();
        ?>
        <section class="pwt-section">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('Verified Reviews', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('Recent guest reviews from completed tours.', 'wildtours-plugin'); ?></p>
            </header>
            <div class="pwt-testimonials">
                <?php if ($query->have_posts()) : ?>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <article class="pwt-testimonial-card">
                            <p class="pwt-quote">"<?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(null, false), 35)); ?>"</p>
                            <h3><?php echo esc_html(get_the_title()); ?></h3>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p><?php esc_html_e('Add reviews to strengthen trust and conversion.', 'wildtours-plugin'); ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    public function contactCard(): string
    {
        $settings = get_option('pwt_settings', []);
        $company = $settings['company_name'] ?? get_bloginfo('name');
        $phone = $settings['contact_phone'] ?? '';
        $email = $settings['contact_email'] ?? get_bloginfo('admin_email');
        $address = $settings['company_address'] ?? '';
        $whatsapp = $settings['whatsapp_number'] ?? '';
        $whatsappUrl = $whatsapp ? 'https://wa.me/' . rawurlencode($whatsapp) : '';

        ob_start();
        ?>
        <section class="pwt-section pwt-contact-card">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('Plan with Local Experts', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('Talk to our team for custom itineraries, permits, and stay recommendations.', 'wildtours-plugin'); ?></p>
            </header>
            <ul class="pwt-contact-list">
                <li><strong><?php esc_html_e('Company', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($company); ?></li>
                <?php if ($phone) : ?>
                    <li><strong><?php esc_html_e('Phone', 'wildtours-plugin'); ?>:</strong> <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></li>
                <?php endif; ?>
                <?php if ($email) : ?>
                    <li><strong><?php esc_html_e('Email', 'wildtours-plugin'); ?>:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li>
                <?php endif; ?>
                <?php if ($address) : ?>
                    <li><strong><?php esc_html_e('Address', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($address); ?></li>
                <?php endif; ?>
            </ul>
            <?php if ($whatsappUrl) : ?>
                <p><a class="pwt-btn" href="<?php echo esc_url($whatsappUrl); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Chat on WhatsApp', 'wildtours-plugin'); ?></a></p>
            <?php endif; ?>
        </section>
        <?php

        return ob_get_clean();
    }

    public function bookingForm(): string
    {
        return (new \PWT\Bookings\BookingForm())->render();
    }

    public function paymentPage(): string
    {
        $token = sanitize_text_field($_GET['pwt_payment'] ?? '');

        if (!$token) {
            return '<section class="pwt-section"><p>' . esc_html__('Payment link is invalid or missing.', 'wildtours-plugin') . '</p></section>';
        }

        $context = \PWT\Payments\PaymentManager::portalContext($token);

        if (!$context) {
            return '<section class="pwt-section"><p>' . esc_html__('Payment request not found.', 'wildtours-plugin') . '</p></section>';
        }

        $methodLabels = [
            'upi' => __('UPI', 'wildtours-plugin'),
            'bank_transfer' => __('Bank Transfer', 'wildtours-plugin'),
            'cash' => __('Cash', 'wildtours-plugin'),
            'card' => __('Card', 'wildtours-plugin'),
            'net_banking' => __('Net Banking', 'wildtours-plugin'),
        ];

        ob_start();
        ?>
        <section class="pwt-section pwt-payment-portal">
            <header class="pwt-section-header">
                <div>
                    <h2><?php esc_html_e('Complete Your Booking Payment', 'wildtours-plugin'); ?></h2>
                    <p><?php esc_html_e('Pay the advance amount and submit the transaction reference for confirmation.', 'wildtours-plugin'); ?></p>
                </div>
                <div class="pwt-portal-toolbar">
                    <?php echo $this->currencySwitcher(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </header>
            <?php if (!empty($_GET['payment_success'])) : ?>
                <p class="pwt-form-message is-success"><?php esc_html_e('Payment reference submitted. We will verify and confirm your booking shortly.', 'wildtours-plugin'); ?></p>
            <?php elseif (!empty($_GET['payment_error'])) : ?>
                <p class="pwt-form-message is-error"><?php esc_html_e('Payment reference is required.', 'wildtours-plugin'); ?></p>
            <?php endif; ?>
            <div class="pwt-meta-grid">
                <div class="pwt-meta-chip"><strong><?php esc_html_e('Guest', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($context['name']); ?></div>
                <div class="pwt-meta-chip"><strong><?php esc_html_e('Package', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($context['package_name']); ?></div>
                <div class="pwt-meta-chip"><strong><?php esc_html_e('Travel Date', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($context['travel_date']); ?></div>
                <div class="pwt-meta-chip"><strong><?php esc_html_e('Advance Due', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($this->formatPortalAmount((float) $context['advance_amount'])); ?></div>
                <div class="pwt-meta-chip"><strong><?php esc_html_e('Status', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html(\PWT\Payments\PaymentManager::statusLabel((string) $context['status'])); ?></div>
            </div>
            <?php if ($context['upi_id']) : ?>
                <p><strong><?php esc_html_e('UPI ID', 'wildtours-plugin'); ?>:</strong> <?php echo esc_html($context['upi_id']); ?></p>
            <?php endif; ?>
            <?php if ($context['instructions']) : ?>
                <div><?php echo wp_kses_post(wpautop($context['instructions'])); ?></div>
            <?php endif; ?>

            <?php if (!in_array($context['status'], ['paid', 'partial_paid', 'verification_pending'], true)) : ?>
                <form method="post" class="pwt-booking-form">
                    <?php wp_nonce_field('pwt_payment_portal_' . $context['booking_id']); ?>
                    <input type="hidden" name="action" value="pwt_submit_payment_reference">
                    <input type="hidden" name="payment_token" value="<?php echo esc_attr($token); ?>">
                    <div class="pwt-form-grid">
                        <label>
                            <span><?php esc_html_e('Payment Method', 'wildtours-plugin'); ?></span>
                            <select name="payment_method">
                                <?php foreach (($context['allowed_methods'] ?? ['upi', 'bank_transfer', 'cash']) as $methodSlug) : ?>
                                    <option value="<?php echo esc_attr((string) $methodSlug); ?>"><?php echo esc_html($methodLabels[$methodSlug] ?? ucfirst((string) $methodSlug)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span><?php esc_html_e('Payment Reference / UTR *', 'wildtours-plugin'); ?></span>
                            <input type="text" name="payment_reference" required>
                        </label>
                    </div>
                    <button type="submit" class="pwt-btn"><?php esc_html_e('Submit Payment Reference', 'wildtours-plugin'); ?></button>
                </form>
            <?php endif; ?>
        </section>
        <?php

        return ob_get_clean();
    }

    private function currencySwitcher(): string
    {
        if (!function_exists('wildtours_component')) {
            return '';
        }

        ob_start();
        wildtours_component('currency-switcher');
        return (string) ob_get_clean();
    }

    private function formatPortalAmount(float $amountInr): string
    {
        if (
            function_exists('wildtours_currency')
            && function_exists('wildtours_currency_symbol')
            && function_exists('wildtours_convert_amount')
        ) {
            $currency = \wildtours_currency();
            $converted = \wildtours_convert_amount($amountInr, $currency);

            return \wildtours_currency_symbol($currency)
                . ' '
                . number_format_i18n((float) $converted, 0);
        }

        return 'INR ' . number_format_i18n($amountInr, 0);
    }

    private function heroSection(): string
    {
        $settings = get_option('pwt_settings', []);
        $title = $settings['hero_title'] ?? '';
        $subtitle = $settings['hero_subtitle'] ?? '';

        if (!$title) {
            $title = __('Your Gateway to Panna Wild Tours', 'wildtours-plugin');
        }

        if (!$subtitle) {
            $subtitle = __('Book tiger safaris, curated packages, and local experiences with one trusted team.', 'wildtours-plugin');
        }

        ob_start();
        ?>
        <section class="pwt-hero">
            <div class="pwt-hero-inner">
                <p class="pwt-kicker"><?php esc_html_e('Panna Tiger Reserve', 'wildtours-plugin'); ?></p>
                <h1><?php echo esc_html($title); ?></h1>
                <p><?php echo esc_html($subtitle); ?></p>
                <a class="pwt-btn" href="#pwt-booking"><?php esc_html_e('Start Booking', 'wildtours-plugin'); ?></a>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    private function servicesSection(): string
    {
        ob_start();
        ?>
        <section class="pwt-section">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('The Services We Offer', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('Complete travel support from planning to checkout, designed for smooth wildlife holidays in and around Panna.', 'wildtours-plugin'); ?></p>
            </header>
            <div class="pwt-cards-grid">
                <article class="pwt-card">
                    <div class="pwt-card-body">
                        <h3><?php esc_html_e('Safari Planning Concierge', 'wildtours-plugin'); ?></h3>
                        <p><?php esc_html_e('Permit guidance, gate/slot planning, and personalized itinerary coordination for first-time and repeat travelers.', 'wildtours-plugin'); ?></p>
                    </div>
                </article>
                <article class="pwt-card">
                    <div class="pwt-card-body">
                        <h3><?php esc_html_e('Stay and Transfer Support', 'wildtours-plugin'); ?></h3>
                        <p><?php esc_html_e('Verified resorts, station/airport pickup, and on-ground coordination for stress-free travel days.', 'wildtours-plugin'); ?></p>
                    </div>
                </article>
                <article class="pwt-card">
                    <div class="pwt-card-body">
                        <h3><?php esc_html_e('Custom Local Experiences', 'wildtours-plugin'); ?></h3>
                        <p><?php esc_html_e('Tailored add-ons such as waterfall trails, riverside visits, and family-friendly local explorations.', 'wildtours-plugin'); ?></p>
                    </div>
                </article>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    private function renderCardsSection(string $title, string $subtitle, \WP_Query $query, string $metaKey): string
    {
        ob_start();
        ?>
        <section class="pwt-section">
            <header class="pwt-section-header">
                <h2><?php echo esc_html($title); ?></h2>
                <p><?php echo esc_html($subtitle); ?></p>
            </header>
            <div class="pwt-cards-grid">
                <?php
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $meta = $metaKey ? get_post_meta(get_the_ID(), $metaKey, true) : '';
                        ?>
                        <article class="pwt-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="pwt-card-image"><?php echo get_the_post_thumbnail(get_the_ID(), 'large'); ?></div>
                            <?php endif; ?>
                            <div class="pwt-card-body">
                                <h3><?php echo esc_html(get_the_title()); ?></h3>
                                <?php if ($meta) : ?>
                                    <p class="pwt-tag"><?php echo esc_html((string) $meta); ?></p>
                                <?php endif; ?>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(null, false), 20)); ?></p>
                                <a class="pwt-text-link" href="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('View details', 'wildtours-plugin'); ?></a>
                            </div>
                        </article>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    ?>
                    <p><?php esc_html_e('No published items found yet. Add and publish content from PWT Dashboard or Quick Content Forms to show this section.', 'wildtours-plugin'); ?></p>
                    <?php
                }
                ?>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }
}

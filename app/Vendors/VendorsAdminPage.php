<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

final class VendorsAdminPage
{
    private const CAP = 'manage_options';

    public function __construct(
        private readonly VendorRepository $vendors,
        private readonly VendorRateRepository $rates,
        private readonly SettlementRepository $settlements,
        private readonly CostService $costs
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menus'], 35);
        add_action('admin_post_pwt_vendor_save', [$this, 'handleVendorSave']);
        add_action('admin_post_pwt_vendor_rate_save', [$this, 'handleRateSave']);
        add_action('admin_post_pwt_vendor_rate_delete', [$this, 'handleRateDelete']);
        add_action('admin_post_pwt_vendor_settlement', [$this, 'handleSettlement']);
    }

    public function menus(): void
    {
        add_submenu_page(
            'pwt-dashboard',
            __('Vendors', 'wildtours-plugin'),
            __('Vendors', 'wildtours-plugin'),
            self::CAP,
            'pwt-vendors',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $vendorId = absint($_GET['vendor_id'] ?? 0);
        $vendor = $vendorId ? $this->vendors->find($vendorId) : null;

        if ($vendor) {
            $this->renderVendor($vendor);
            return;
        }

        $this->renderList();
    }

    private function renderList(): void
    {
        $rows = $this->vendors->all();
        $this->notices();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Vendors', 'wildtours-plugin'); ?></h1>
            <p><?php esc_html_e('Track suppliers, their net rate cards and outstanding settlements. Supplier costs are pulled automatically onto booking items for margin reporting.', 'wildtours-plugin'); ?></p>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('Vendor', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Type', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Contact', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Rate Cards', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Outstanding', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Status', 'wildtours-plugin'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=pwt-vendors&vendor_id=' . (int)$r['id'])); ?>"><?php echo esc_html($r['name']); ?></a></td>
                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', (string)$r['vendor_type']))); ?></td>
                        <td><?php echo esc_html(trim((string)($r['contact_person'] ?? '') . ' ' . (string)($r['phone'] ?? ''))); ?></td>
                        <td><?php echo esc_html(count($this->rates->forVendor((int)$r['id']))); ?></td>
                        <td><?php echo esc_html(number_format_i18n($this->costs->vendorOutstanding((int)$r['id']), 2)); ?></td>
                        <td><?php echo esc_html($r['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e('Add Vendor', 'wildtours-plugin'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pwt-form">
                <input type="hidden" name="action" value="pwt_vendor_save">
                <?php wp_nonce_field('pwt_vendor_save', '_wpnonce'); ?>
                <table class="form-table">
                    <tr><th><label for="pwt-name"><?php esc_html_e('Name', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-name" type="text" name="name" required class="regular-text"></td></tr>
                    <tr><th><label for="pwt-type"><?php esc_html_e('Type', 'wildtours-plugin'); ?></label></th>
                        <td><select id="pwt-type" name="vendor_type">
                            <?php foreach (['hotel','restaurant','vehicle','guide','housekeeping','laundry','other'] as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html(ucwords($type)); ?></option>
                            <?php endforeach; ?>
                        </select></td></tr>
                    <tr><th><label for="pwt-contact"><?php esc_html_e('Contact Person', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-contact" type="text" name="contact_person" class="regular-text"></td></tr>
                    <tr><th><label for="pwt-email"><?php esc_html_e('Email', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-email" type="email" name="email" class="regular-text"></td></tr>
                    <tr><th><label for="pwt-phone"><?php esc_html_e('Phone', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-phone" type="text" name="phone" class="regular-text"></td></tr>
                    <tr><th><label for="pwt-pan"><?php esc_html_e('PAN', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-pan" type="text" name="pan" class="regular-text"></td></tr>
                    <tr><th><label for="pwt-gstin"><?php esc_html_e('GSTIN', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-gstin" type="text" name="gstin" class="regular-text"></td></tr>
                    <tr><th><label for="pwt-bank"><?php esc_html_e('Bank Details', 'wildtours-plugin'); ?></label></th>
                        <td><textarea id="pwt-bank" name="bank_details" class="large-text" rows="3"></textarea></td></tr>
                    <tr><th><label for="pwt-notes"><?php esc_html_e('Notes', 'wildtours-plugin'); ?></label></th>
                        <td><textarea id="pwt-notes" name="notes" class="large-text" rows="3"></textarea></td></tr>
                </table>
                <?php submit_button(__('Add Vendor', 'wildtours-plugin')); ?>
            </form>
        </div>
        <?php
    }

    private function renderVendor(array $vendor): void
    {
        $id = (int)$vendor['id'];
        $rateRows = $this->rates->forVendor($id);
        $settled = $this->settlements->forVendor($id);
        $outstanding = $this->costs->vendorOutstanding($id);
        $this->notices();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($vendor['name']); ?></h1>
            <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=pwt-vendors')); ?>">&larr; <?php esc_html_e('Back to vendors', 'wildtours-plugin'); ?></a></p>

            <h2><?php esc_html_e('Settlement Summary', 'wildtours-plugin'); ?></h2>
            <div class="pwt-admin-cards">
                <div class="pwt-admin-card"><strong><?php echo esc_html(number_format_i18n($outstanding, 2)); ?></strong><span><?php esc_html_e('Outstanding', 'wildtours-plugin'); ?></span></div>
                <div class="pwt-admin-card"><strong><?php echo esc_html(number_format_i18n($this->costs->settled($id), 2)); ?></strong><span><?php esc_html_e('Settled', 'wildtours-plugin'); ?></span></div>
            </div>

            <h2><?php esc_html_e('Details', 'wildtours-plugin'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="pwt_vendor_save">
                <input type="hidden" name="vendor_id" value="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('pwt_vendor_save', '_wpnonce'); ?>
                <table class="form-table">
                    <tr><th><label for="pwt-name"><?php esc_html_e('Name', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-name" type="text" name="name" required class="regular-text" value="<?php echo esc_attr($vendor['name']); ?>"></td></tr>
                    <tr><th><label for="pwt-type"><?php esc_html_e('Type', 'wildtours-plugin'); ?></label></th>
                        <td><select id="pwt-type" name="vendor_type">
                            <?php foreach (['hotel','restaurant','vehicle','guide','housekeeping','laundry','other'] as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($vendor['vendor_type'], $type); ?>><?php echo esc_html(ucwords($type)); ?></option>
                            <?php endforeach; ?>
                        </select></td></tr>
                    <tr><th><label for="pwt-contact"><?php esc_html_e('Contact Person', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-contact" type="text" name="contact_person" class="regular-text" value="<?php echo esc_attr((string)($vendor['contact_person'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="pwt-email"><?php esc_html_e('Email', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-email" type="email" name="email" class="regular-text" value="<?php echo esc_attr((string)($vendor['email'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="pwt-phone"><?php esc_html_e('Phone', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-phone" type="text" name="phone" class="regular-text" value="<?php echo esc_attr((string)($vendor['phone'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="pwt-pan"><?php esc_html_e('PAN', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-pan" type="text" name="pan" class="regular-text" value="<?php echo esc_attr((string)($vendor['pan'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="pwt-gstin"><?php esc_html_e('GSTIN', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-gstin" type="text" name="gstin" class="regular-text" value="<?php echo esc_attr((string)($vendor['gstin'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="pwt-bank"><?php esc_html_e('Bank Details', 'wildtours-plugin'); ?></label></th>
                        <td><textarea id="pwt-bank" name="bank_details" class="large-text" rows="3"><?php echo esc_textarea((string)($vendor['bank_details'] ?? '')); ?></textarea></td></tr>
                    <tr><th><label for="pwt-notes"><?php esc_html_e('Notes', 'wildtours-plugin'); ?></label></th>
                        <td><textarea id="pwt-notes" name="notes" class="large-text" rows="3"><?php echo esc_textarea((string)($vendor['notes'] ?? '')); ?></textarea></td></tr>
                    <tr><th><label for="pwt-status"><?php esc_html_e('Status', 'wildtours-plugin'); ?></label></th>
                        <td><select id="pwt-status" name="status">
                            <option value="active" <?php selected($vendor['status'], 'active'); ?>><?php esc_html_e('Active', 'wildtours-plugin'); ?></option>
                            <option value="inactive" <?php selected($vendor['status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'wildtours-plugin'); ?></option>
                        </select></td></tr>
                </table>
                <?php submit_button(__('Save Vendor', 'wildtours-plugin')); ?>
            </form>

            <h2><?php esc_html_e('Rate Cards', 'wildtours-plugin'); ?></h2>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('Resource', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Rate Name', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Unit Price', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Valid', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Priority', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Status', 'wildtours-plugin'); ?></th>
                    <th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rateRows as $rate): ?>
                    <tr>
                        <td><?php echo esc_html($rate['resource_type'] . ' #' . $rate['resource_id']); ?></td>
                        <td><?php echo esc_html($rate['rate_name'] ?: '—'); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float)$rate['unit_price'], 2) . ' ' . $rate['currency']); ?></td>
                        <td><?php echo esc_html(($rate['start_date'] ?: 'Any') . ' → ' . ($rate['end_date'] ?: 'Any')); ?></td>
                        <td><?php echo esc_html($rate['priority']); ?></td>
                        <td><?php echo esc_html($rate['status']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <input type="hidden" name="action" value="pwt_vendor_rate_delete">
                                <input type="hidden" name="rate_id" value="<?php echo esc_attr($rate['id']); ?>">
                                <input type="hidden" name="vendor_id" value="<?php echo esc_attr($id); ?>">
                                <?php wp_nonce_field('pwt_vendor_rate_delete', '_wpnonce'); ?>
                                <?php submit_button(__('Delete', 'wildtours-plugin'), 'button-link', '', false, ['onclick' => "return confirm('" . esc_js(__('Delete this rate card?', 'wildtours-plugin')) . "');"]); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e('Add Rate Card', 'wildtours-plugin'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pwt-form">
                <input type="hidden" name="action" value="pwt_vendor_rate_save">
                <input type="hidden" name="vendor_id" value="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('pwt_vendor_rate_save', '_wpnonce'); ?>
                <table class="form-table">
                    <tr><th><label for="pwt-rt"><?php esc_html_e('Resource Type', 'wildtours-plugin'); ?></label></th>
                        <td><select id="pwt-rt" name="resource_type">
                            <?php foreach (['room_unit','safari_schedule','vehicle','restaurant','service'] as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Use resource ID 0 for a generic rate across all resources of this type.', 'wildtours-plugin'); ?></p></td></tr>
                    <tr><th><label for="pwt-rid"><?php esc_html_e('Resource ID', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-rid" type="number" name="resource_id" min="0" value="0" class="small-text"></td></tr>
                    <tr><th><label for="pwt-rn"><?php esc_html_e('Rate Name', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-rn" type="text" name="rate_name" class="regular-text" placeholder="<?php esc_attr_e('e.g. Peak-season double', 'wildtours-plugin'); ?>"></td></tr>
                    <tr><th><label for="pwt-price"><?php esc_html_e('Net Unit Price', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-price" type="number" name="unit_price" required step="0.01" min="0" class="regular-text">
                            <input type="text" name="currency" value="INR" maxlength="3" class="small-text"></td></tr>
                    <tr><th><label for="pwt-rs"><?php esc_html_e('Valid From', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-rs" type="date" name="start_date"></td></tr>
                    <tr><th><label for="pwt-re"><?php esc_html_e('Valid To', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-re" type="date" name="end_date"></td></tr>
                    <tr><th><label for="pwt-rp"><?php esc_html_e('Priority', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-rp" type="number" name="priority" value="10" class="small-text">
                            <p class="description"><?php esc_html_e('Lower wins when multiple cards match.', 'wildtours-plugin'); ?></p></td></tr>
                </table>
                <?php submit_button(__('Add Rate Card', 'wildtours-plugin')); ?>
            </form>

            <h2><?php esc_html_e('Settlements', 'wildtours-plugin'); ?></h2>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e('Date', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Booking', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Amount', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Reference', 'wildtours-plugin'); ?></th>
                    <th><?php esc_html_e('Notes', 'wildtours-plugin'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($settled as $s): ?>
                    <tr>
                        <td><?php echo esc_html($s['settled_at']); ?></td>
                        <td><?php echo esc_html($s['booking_id'] ?: '—'); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float)$s['amount'], 2) . ' ' . $s['currency']); ?></td>
                        <td><?php echo esc_html($s['reference'] ?: '—'); ?></td>
                        <td><?php echo esc_html($s['notes'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e('Record Settlement', 'wildtours-plugin'); ?></h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pwt-form">
                <input type="hidden" name="action" value="pwt_vendor_settlement">
                <input type="hidden" name="vendor_id" value="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('pwt_vendor_settlement', '_wpnonce'); ?>
                <table class="form-table">
                    <tr><th><label for="pwt-amt"><?php esc_html_e('Amount', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-amt" type="number" name="amount" required step="0.01" min="0.01" class="regular-text">
                            <input type="text" name="currency" value="INR" maxlength="3" class="small-text"></td></tr>
                    <tr><th><label for="pwt-bid"><?php esc_html_e('Booking ID (optional)', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-bid" type="number" name="booking_id" min="1" class="small-text"></td></tr>
                    <tr><th><label for="pwt-sdate"><?php esc_html_e('Settled On', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-sdate" type="datetime-local" name="settled_at"></td></tr>
                    <tr><th><label for="pwt-ref"><?php esc_html_e('Reference', 'wildtours-plugin'); ?></label></th>
                        <td><input id="pwt-ref" type="text" name="reference" class="regular-text" placeholder="<?php esc_attr_e('e.g. UTR / cheque no.', 'wildtours-plugin'); ?>"></td></tr>
                    <tr><th><label for="pwt-snotes"><?php esc_html_e('Notes', 'wildtours-plugin'); ?></label></th>
                        <td><textarea id="pwt-snotes" name="notes" class="large-text" rows="2"></textarea></td></tr>
                </table>
                <?php submit_button(__('Record Settlement', 'wildtours-plugin')); ?>
            </form>
        </div>
        <?php
    }

    public function handleVendorSave(): void
    {
        $this->guard('pwt_vendor_save');
        $id = absint($_POST['vendor_id'] ?? 0);
        $data = $this->vendorData();
        $redirect = admin_url('admin.php?page=pwt-vendors');

        if ($id) {
            $updated = $this->vendors->update($id, $data);
            $redirect = add_query_arg('vendor_id', $id, $redirect);
            $this->redirectResult($redirect, $updated, __('Vendor updated.', 'wildtours-plugin'));
        }

        $created = $this->vendors->create($data);
        if ($created) {
            $redirect = add_query_arg(['vendor_id' => $created, 'pwt_notice' => 'success', 'pwt_msg' => urlencode(__('Vendor added.', 'wildtours-plugin'))], $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $this->redirectError($redirect, __('Unable to add vendor.', 'wildtours-plugin'));
    }

    public function handleRateSave(): void
    {
        $this->guard('pwt_vendor_rate_save');
        $vendorId = absint($_POST['vendor_id'] ?? 0);
        $created = $this->rates->create([
            'vendor_id' => $vendorId,
            'resource_type' => sanitize_key((string)($_POST['resource_type'] ?? 'service')),
            'resource_id' => absint($_POST['resource_id'] ?? 0),
            'rate_name' => (string)($_POST['rate_name'] ?? ''),
            'unit_price' => (float)($_POST['unit_price'] ?? 0),
            'currency' => (string)($_POST['currency'] ?? 'INR'),
            'start_date' => (string)($_POST['start_date'] ?? ''),
            'end_date' => (string)($_POST['end_date'] ?? ''),
            'priority' => (int)($_POST['priority'] ?? 10),
        ]);
        $redirect = $this->vendorUrl($vendorId);
        $this->redirectResult($redirect, $created > 0, __('Rate card added.', 'wildtours-plugin'));
    }

    public function handleRateDelete(): void
    {
        $this->guard('pwt_vendor_rate_delete');
        $rateId = absint($_POST['rate_id'] ?? 0);
        $vendorId = absint($_POST['vendor_id'] ?? 0);
        $deleted = $this->rates->delete($rateId);
        $this->redirectResult($this->vendorUrl($vendorId), $deleted, __('Rate card deleted.', 'wildtours-plugin'));
    }

    public function handleSettlement(): void
    {
        $this->guard('pwt_vendor_settlement');
        $vendorId = absint($_POST['vendor_id'] ?? 0);
        $created = $this->settlements->create([
            'vendor_id' => $vendorId,
            'booking_id' => absint($_POST['booking_id'] ?? 0),
            'amount' => (float)($_POST['amount'] ?? 0),
            'currency' => (string)($_POST['currency'] ?? 'INR'),
            'reference' => (string)($_POST['reference'] ?? ''),
            'settled_at' => sanitize_text_field((string)($_POST['settled_at'] ?? '')),
            'notes' => (string)($_POST['notes'] ?? ''),
        ]);
        $this->redirectResult($this->vendorUrl($vendorId), $created > 0, __('Settlement recorded.', 'wildtours-plugin'));
    }

    private function guard(string $nonceAction): void
    {
        if (!wp_verify_nonce((string)($_REQUEST['_wpnonce'] ?? ''), $nonceAction)) {
            wp_die(__('Security check failed.', 'wildtours-plugin'));
        }
        if (!current_user_can(self::CAP)) {
            wp_die(__('You do not have permission to do this.', 'wildtours-plugin'));
        }
    }

    private function vendorData(): array
    {
        return [
            'name' => (string)($_POST['name'] ?? ''),
            'vendor_type' => (string)($_POST['vendor_type'] ?? 'other'),
            'contact_person' => (string)($_POST['contact_person'] ?? ''),
            'email' => (string)($_POST['email'] ?? ''),
            'phone' => (string)($_POST['phone'] ?? ''),
            'pan' => (string)($_POST['pan'] ?? ''),
            'gstin' => (string)($_POST['gstin'] ?? ''),
            'bank_details' => (string)($_POST['bank_details'] ?? ''),
            'notes' => (string)($_POST['notes'] ?? ''),
            'status' => (string)($_POST['status'] ?? 'active'),
        ];
    }

    private function vendorUrl(int $vendorId): string
    {
        return add_query_arg('vendor_id', $vendorId, admin_url('admin.php?page=pwt-vendors'));
    }

    private function notices(): void
    {
        if (empty($_GET['pwt_notice'])) {
            return;
        }
        echo '<div class="notice notice-' . esc_attr(sanitize_key((string)$_GET['pwt_notice'])) . ' is-dismissible"><p>'
            . esc_html(stripslashes((string)($_GET['pwt_msg'] ?? ''))) . '</p></div>';
    }

    private function redirectResult(string $url, mixed $result, string $successMessage): void
    {
        if (!$result) {
            $this->redirectError($url, __('Action failed.', 'wildtours-plugin'));
        }
        wp_safe_redirect(add_query_arg(['pwt_notice' => 'success', 'pwt_msg' => urlencode($successMessage)], $url));
        exit;
    }

    private function redirectError(string $url, string $message): void
    {
        wp_safe_redirect(add_query_arg(['pwt_notice' => 'error', 'pwt_msg' => urlencode($message)], $url));
        exit;
    }
}
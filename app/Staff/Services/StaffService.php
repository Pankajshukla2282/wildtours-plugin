<?php
declare(strict_types=1);
namespace PWT\Staff\Services;
defined('ABSPATH') || exit;
use PWT\Staff\Roles\RoleRegistrar;

final class StaffService
{
    public function create(array $data): int|\WP_Error
    {
        $email = sanitize_email((string)($data['email'] ?? ''));
        $name = sanitize_text_field((string)($data['name'] ?? ''));
        $role = sanitize_key((string)($data['role'] ?? ''));
        if ($name === '' || !is_email($email) || !RoleRegistrar::isStaffRole($role)) return new \WP_Error('pwt_staff_invalid', __('Please provide a name, valid email and staff role.', 'wildtours-plugin'));
        if (email_exists($email)) return new \WP_Error('pwt_staff_exists', __('A WordPress user with this email already exists.', 'wildtours-plugin'));
        $base = sanitize_user(strtolower(str_replace(' ', '.', $name)), true) ?: 'pwt.staff';
        $username = $base; $i = 2; while (username_exists($username)) $username = $base . $i++;
        $password = wp_generate_password(32, true, true);
        $id = wp_insert_user(['user_login'=>$username,'user_pass'=>$password,'user_email'=>$email,'display_name'=>$name,'role'=>$role]);
        if (is_wp_error($id)) return $id;
        update_user_meta($id, 'pwt_staff_role', $role);
        update_user_meta($id, 'pwt_staff_created_at', current_time('mysql'));
        wp_new_user_notification($id, null, 'user');
        return (int)$id;
    }
}

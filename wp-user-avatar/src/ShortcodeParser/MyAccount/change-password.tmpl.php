<?php

use ProfilePress\Core\ShortcodeParser\MyAccount\MyAccountTag;

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$current_user = get_user_by('id', get_current_user_id());

do_action('ppress_myaccount_change_password_before');

$success_message = apply_filters('ppress_password_change_confirmation_message', esc_html__('Password successfully updated.', 'wp-user-avatar'));
?>
    <div class="profilepress-myaccount-change-password">

        <?php if (isset($_GET['edit']) && $_GET['edit'] == 'true') : ?>
            <?php MyAccountTag::alert_message($success_message) ?>
        <?php endif; ?>

        <?php if ( ! empty($this->myac_change_password_error)) : ?>
            <?php MyAccountTag::alert_message($this->myac_change_password_error, 'error') ?>
        <?php endif; ?>

        <h2><?= esc_html__('Change Password', 'wp-user-avatar') ?></h2>

        <form method="post" id="ppmyac-form-changePassword" enctype="multipart/form-data">

            <div class="profilepress-myaccount-form-wrap">

                <div class="profilepress-myaccount-form-field">
                    <label for="password_current"><?= esc_html__('Current password', 'wp-user-avatar') ?></label>
                    <input type="password" name="password_current" id="password_current" required="required" class="profilepress-myaccount-form-control">
                </div>


                <div class="profilepress-myaccount-form-field">
                    <label for="password_new"><?= esc_html__('New password', 'wp-user-avatar') ?></label>
                    <input type="password" name="password_new" id="password_new" required="required" class="profilepress-myaccount-form-control">
                </div>


                <div class="profilepress-myaccount-form-field">
                    <label for="password_confirm_new"><?= esc_html__('Confirm new password', 'wp-user-avatar') ?></label>
                    <input type="password" name="password_confirm_new" id="password_confirm_new" required="required" class="profilepress-myaccount-form-control">
                </div>


                <div class="profilepress-myaccount-form-field">
                    <div id="pp-pass-strength-result"><?php _e('Strength indicator', 'wp-user-avatar'); ?></div>
                </div>


                <div class="profilepress-myaccount-form-field">
                    <input name="submit-form" id="submit-form" type="submit" value="<?= esc_html__('Change Password', 'wp-user-avatar') ?>">
                </div>
            </div>

            <input type="hidden" name="ppmyac_form_action" value="changePassword">
            <?= ppress_nonce_field(); ?>
        </form>

    </div>
<?php

do_action('ppress_myaccount_change_password');

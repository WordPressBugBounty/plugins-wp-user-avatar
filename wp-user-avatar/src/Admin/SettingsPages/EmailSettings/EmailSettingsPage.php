<?php

namespace ProfilePress\Core\Admin\SettingsPages\EmailSettings;

use ProfilePress\Core\Classes\SendEmail;
use ProfilePress\Core\Membership\Emails\EmailDataTrait;
use ProfilePress\Custom_Settings_Page_Api;

class EmailSettingsPage
{
    use EmailDataTrait;

    const ACCOUNT_EMAIL_TYPE = 'account';
    const ORDER_EMAIL_TYPE = 'order';
    const SUBSCRIPTION_EMAIL_TYPE = 'subscription';

    public $email_notification_list_table;

    public $settingsPageInstance;

    public function __construct()
    {
        add_filter('ppress_settings_page_submenus_tabs', [$this, 'menu_tab']);
        add_action('ppress_admin_settings_submenu_page_email_account', [$this, 'emails_admin_page']);
        add_action('ppress_admin_settings_submenu_page_email_order', [$this, 'emails_admin_page']);
        add_action('ppress_admin_settings_submenu_page_email_subscription', [$this, 'emails_admin_page']);
        add_action('ppress_admin_settings_submenu_page_email_settings', [$this, 'email_settings_admin_page']);

        add_action('ppress_settings_page_screen_option', [$this, 'screen_option']);
        add_action('admin_init', [$this, 'handle_email_preview']);

        add_action('admin_enqueue_scripts', function ($hook_suffix) {
            if ($hook_suffix == 'profilepress_page_ppress-config') {
                wp_enqueue_script('customize-loader');
            }
        });

        add_filter('ppress_general_settings_admin_page_title', function ($title) {
            if (isset($_GET['view']) && $_GET['view'] == 'email') {
                $title = $this->get_admin_title();
            }

            return $title;
        });

        add_action('ppress_register_menu_page', function () {

            add_filter('wp_cspa_sanitize_skip', function ($return, $fieldkey, $value) {

                if (isset($_GET['type']) && $fieldkey == sanitize_text_field($_GET['type']) . '_email_content') {
                    return stripslashes($value);
                }

                return $return;

            }, 10, 3);

            if (ppressGET_var('view') == 'email') {
                $this->settingsPageInstance = Custom_Settings_Page_Api::instance('', PPRESS_SETTINGS_DB_OPTION_NAME);
            }
        });
    }

    public function screen_option()
    {
        if (isset($_GET['view']) && $_GET['view'] == 'email') {
            add_filter('screen_options_show_screen', '__return_false');

            $type = ppressGET_var('section', self::ACCOUNT_EMAIL_TYPE, true);

            $this->email_notification_list_table = new WPListTable($this->email_notifications($type));
        }
    }

    public function menu_tab($tabs)
    {
        $tabs[214] = [
            'parent' => 'email',
            'id'     => 'account',
            'label'  => esc_html__('Account', 'wp-user-avatar')
        ];

        $tabs[215] = [
            'parent' => 'email',
            'id'     => 'order',
            'label'  => esc_html__('Order', 'wp-user-avatar')
        ];

        $tabs[216] = [
            'parent' => 'email',
            'id'     => 'subscription',
            'label'  => esc_html__('Subscription', 'wp-user-avatar')
        ];

        $tabs[217] = [
            'parent' => 'email',
            'id'     => 'settings',
            'label'  => esc_html__('Settings', 'wp-user-avatar')
        ];


        return $tabs;
    }

    public function get_admin_title()
    {
        return esc_html__('Emails', 'wp-user-avatar');
    }

    /**
     * @return mixed|void
     */
    public function email_notifications($type = '')
    {
        $site_title = ppress_site_title();

        $notifications = apply_filters('ppress_email_notifications', [
            [
                'type'         => self::ACCOUNT_EMAIL_TYPE,
                'key'          => 'welcome_message',
                'title'        => esc_html__('Account Welcome Email', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('Welcome To %s', 'wp-user-avatar'), $site_title),
                'message'      => ppress_welcome_msg_content_default(),
                'description'  => esc_html__('Email that is sent to the user upon successful registration.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Users', 'wp-user-avatar'),
                'placeholders' => [
                    '{{username}}'            => esc_html__('Username of the registered user.', 'wp-user-avatar'),
                    '{{userid}}'              => esc_html__('User ID of the registered user.', 'wp-user-avatar'),
                    '{{email}}'               => esc_html__('Email address of the registered user.', 'wp-user-avatar'),
                    '{{password}}'            => esc_html__('Password of the registered user.', 'wp-user-avatar'),
                    '{{site_title}}'          => esc_html__('Website title or name.', 'wp-user-avatar'),
                    '{{first_name}}'          => esc_html__('First Name entered by user on registration.', 'wp-user-avatar'),
                    '{{last_name}}'           => esc_html__('Last Name entered by user on registration.', 'wp-user-avatar'),
                    '{{password_reset_link}}' => esc_html__('URL to reset password.', 'wp-user-avatar'),
                    '{{login_link}}'          => esc_html__('URL to login.', 'wp-user-avatar'),
                    '{{field_key}}'           => sprintf(
                        esc_html__('Replace "field_key" with the %scustom field key%s or usermeta key.', 'wp-user-avatar'),
                        '<a href="' . PPRESS_CUSTOM_FIELDS_SETTINGS_PAGE . '" target="_blank">', '</a>'
                    )
                ],
            ],
            [
                'type'         => self::ACCOUNT_EMAIL_TYPE,
                'key'          => 'password_reset',
                'title'        => esc_html__('Password Reset Email', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('[%s] Password Reset', 'wp-user-avatar'), $site_title),
                'message'      => ppress_password_reset_content_default(),
                'description'  => esc_html__('Email that is sent to the user upon password reset request.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Users', 'wp-user-avatar'),
                'placeholders' => [
                    '{{username}}'            => esc_html__('Username of user.', 'wp-user-avatar'),
                    '{{email}}'               => esc_html__('Email address of the registered user.', 'wp-user-avatar'),
                    '{{site_title}}'          => esc_html__('Website title or name.', 'wp-user-avatar'),
                    '{{password_reset_link}}' => esc_html__('URL to reset password.', 'wp-user-avatar'),
                ]
            ],
            [
                'type'         => self::ACCOUNT_EMAIL_TYPE,
                'key'          => 'new_user_admin_email',
                'title'        => esc_html__('New User Admin Notification', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('[%s] New User Registration', 'wp-user-avatar'), $site_title),
                'message'      => ppress_new_user_admin_notification_message_default(),
                'description'  => esc_html__('Email that is sent to the admin when there is a new user registration.', 'wp-user-avatar'),
                'recipient'    => ppress_get_admin_notification_emails(),
                'placeholders' => [
                    '{{username}}'   => esc_html__('Username of the newly registered user.', 'wp-user-avatar'),
                    '{{email}}'      => esc_html__('Email address of the newly registered user.', 'wp-user-avatar'),
                    '{{first_name}}' => esc_html__('First name of the newly registered user.', 'wp-user-avatar'),
                    '{{last_name}}'  => esc_html__('Last name of the newly registered user.', 'wp-user-avatar'),
                    '{{site_title}}' => esc_html__('Website title or name.', 'wp-user-avatar'),
                    '{{field_key}}'  => sprintf(
                        esc_html__('Replace "field_key" with the %scustom field key%s or usermeta key.', 'wp-user-avatar'),
                        '<a href="' . PPRESS_CUSTOM_FIELDS_SETTINGS_PAGE . '" target="_blank">', '</a>'
                    )
                ]
            ],
            [
                'type'         => self::ORDER_EMAIL_TYPE,
                'key'          => 'new_order_receipt',
                'title'        => esc_html__('New Order Receipt', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('New Order Receipt', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_order_receipt_content(),
                'description'  => esc_html__('Email sent whenever a customer completes an order.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders' => $this->get_order_placeholders()
            ],
            [
                'type'         => self::ORDER_EMAIL_TYPE,
                'key'          => 'renewal_order_receipt',
                'title'        => esc_html__('Renewal Order Receipt', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('Subscription Renewal Receipt', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_order_receipt_content(true),
                'description'  => esc_html__('Email sent to customer whenever a renewal order occurs.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders' => $this->get_order_placeholders()
            ],
            [
                'type'         => self::ORDER_EMAIL_TYPE,
                'key'          => 'new_order_admin_notification',
                'title'        => esc_html__('New Order Admin Notification', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('New Order #{{order_id}}', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_new_order_admin_notification_content(),
                'description'  => esc_html__('Email sent to the admin when there is a new order.', 'wp-user-avatar'),
                'recipient'    => ppress_get_admin_notification_emails(),
                'placeholders' => $this->get_order_placeholders()
            ],
            [
                'type'         => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'          => 'subscription_cancelled_notification',
                'title'        => esc_html__('Subscription Cancelled Notification', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('Your subscription has been cancelled.', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_subscription_cancelled_content(),
                'description'  => esc_html__('Email sent to customer whenever their subscription is cancelled.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders' => $this->get_subscription_placeholders()
            ],
            [
                'type'         => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'          => 'subscription_expired_notification',
                'title'        => esc_html__('Subscription Expired Notification', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('Your subscription has expired.', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_subscription_expired_content(),
                'description'  => esc_html__('Email sent to customer whenever their subscription expires.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders' => $this->get_subscription_placeholders()
            ],
            [
                'type'         => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'          => 'subscription_completed_notification',
                'title'        => esc_html__('Subscription Completed Notification', 'wp-user-avatar'),
                'subject'      => sprintf(esc_html__('Your subscription is now complete.', 'wp-user-avatar'), $site_title),
                'message'      => $this->get_subscription_completed_content(),
                'description'  => esc_html__('Email sent to customer whenever they complete their subscription payments.', 'wp-user-avatar'),
                'recipient'    => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders' => $this->get_subscription_placeholders()
            ],
            [
                'type'          => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'           => 'subscription_renewal_reminder',
                'title'         => esc_html__('Upcoming Renewal Reminder', 'wp-user-avatar'),
                'subject'       => sprintf(esc_html__('Your subscription is renewing soon.', 'wp-user-avatar'), $site_title),
                'message'       => $this->get_subscription_renewal_reminder_content(),
                'description'   => esc_html__('Email sent to customer to remind them that their subscription is approaching its renewal.', 'wp-user-avatar'),
                'recipient'     => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders'  => $this->get_subscription_placeholders(),
                'reminder_days' => '1'
            ],
            [
                'type'          => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'           => 'subscription_expiration_reminder',
                'title'         => esc_html__('Upcoming Expiration Reminder', 'wp-user-avatar'),
                'subject'       => sprintf(esc_html__('Your subscription is expiring soon.', 'wp-user-avatar'), $site_title),
                'message'       => $this->get_subscription_renewal_reminder_content(true),
                'description'   => esc_html__('Email sent to customer to remind them that their subscription is approaching its expiration.', 'wp-user-avatar'),
                'recipient'     => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders'  => $this->get_subscription_placeholders(),
                'reminder_days' => '1'
            ],
            [
                'type'          => self::SUBSCRIPTION_EMAIL_TYPE,
                'key'           => 'subscription_after_expired_reminder',
                'title'         => esc_html__('After Subscription Expired Notification', 'wp-user-avatar'),
                'subject'       => sprintf(esc_html__('Your subscription has expired.', 'wp-user-avatar'), $site_title),
                'message'       => $this->get_subscription_expired_content(),
                'description'   => esc_html__('Email sent to customer few days after their subscription expires.', 'wp-user-avatar'),
                'recipient'     => esc_html__('Customers', 'wp-user-avatar'),
                'placeholders'  => $this->get_subscription_placeholders(),
                'reminder_days' => '2'
            ],
        ]);

        if ( ! empty($type)) {
            return wp_list_filter($notifications, ['type' => $type]);
        }

        return $notifications;
    }

    public function email_edit_screen_setup()
    {
        $key  = sanitize_text_field($_GET['type']);
        $data = wp_list_filter($this->email_notifications(), ['key' => $key]);

        $data = array_shift($data);

        if (empty($data)) {
            ppress_do_admin_redirect(PPRESS_SETTINGS_SETTING_GENERAL_PAGE);
        }

        $page_header = $data['title'];

        $email_content_field_type = 'email_editor';
        $content_type             = ppress_get_setting('email_content_type', 'text/html');

        if ($content_type == 'text/html' && ppress_get_setting('email_template_type', 'default') == 'default') {
            $email_content_field_type = 'wp_editor';
        }

        if ($content_type == 'text/plain') {
            $email_content_field_type = 'textarea';
        }

        add_action('wp_cspa_media_button', function () use ($key) {
            add_action('media_buttons', function () use ($key) {
                $url = add_query_arg([
                    'pp_email_preview' => $key,
                    '_wpnonce'         => ppress_create_nonce()
                ],
                    admin_url()
                );

                printf(
                    '<a target="_blank" href="%s" class="button"><span class="wp-media-buttons-icon dashicons dashicons-visibility"></span> %s</a>',
                    $url,
                    esc_html__('Preview Email', 'wp-user-avatar')
                );
            });
        });

        add_action('wp_cspa_after_wp_editor_field', function () use ($data) {
            if (isset($data['placeholders'])) {
                $this->placeholder_tags_table($data['placeholders']);
            }
        });

        add_action('wp_cspa_after_email_editor_field', function () use ($data) {
            if (isset($data['placeholders'])) {
                $this->placeholder_tags_table($data['placeholders']);
            }
        });

        $email_settings = [
            [
                $key . '_email_enabled' => [
                    'type'           => 'checkbox',
                    'label'          => esc_html__('Enable Notification', 'wp-user-avatar'),
                    'checkbox_label' => esc_html__('Enable', 'wp-user-avatar'),
                    'value'          => 'on',
                    'default_value'  => 'on',
                    'description'    => esc_html__('Check to enable this email notification.', 'wp-user-avatar')
                ],
                $key . '_email_subject' => [
                    'type'        => 'text',
                    'value'       => $data['subject'],
                    'label'       => esc_html__('Subject Line', 'wp-user-avatar'),
                    'description' => esc_html__('Enter the subject or title of the email.', 'wp-user-avatar')
                ],
                $key . '_email_content' => [
                    'type'  => $email_content_field_type,
                    'value' => $data['message'],
                    'label' => esc_html__('Message Body', 'wp-user-avatar'),
                ],
            ]
        ];

        if ($key == 'new_order_receipt') {
            $email_settings[0][$key . '_disable_free_orders'] = [
                'type'           => 'checkbox',
                'checkbox_label' => esc_html__('Check to Disable', 'wp-user-avatar'),
                'label'          => esc_html__('Disable for Free Orders', 'wp-user-avatar'),
                'description'    => esc_html__('Optionally disable sending email receipts for free orders.', 'wp-user-avatar')
            ];
        }

        if ($key == 'subscription_renewal_reminder') {
            $email_settings[0][$key . '_reminder_days'] = [
                'type'        => 'number',
                'value'       => $data['reminder_days'],
                'label'       => esc_html__('Reminder Days', 'wp-user-avatar'),
                'description' => esc_html__('The number of days before the upcoming payment due date to notify the customer.', 'wp-user-avatar')
            ];
        }

        if ($key == 'subscription_expiration_reminder') {
            $email_settings[0][$key . '_reminder_days'] = [
                'type'        => 'number',
                'value'       => $data['reminder_days'],
                'label'       => esc_html__('Reminder Days', 'wp-user-avatar'),
                'description' => esc_html__('The number of days before the subscription expiration due date to notify the customer.', 'wp-user-avatar')
            ];
        }

        if ($key == 'subscription_after_expired_reminder') {
            $email_settings[0][$key . '_reminder_days'] = [
                'type'        => 'number',
                'value'       => $data['reminder_days'],
                'label'       => esc_html__('Reminder Days', 'wp-user-avatar'),
                'description' => esc_html__('The number of days after the subscription expired to notify the customer.', 'wp-user-avatar')
            ];
        }

        return [
            'page_header'    => $page_header,
            'email_settings' => $email_settings
        ];
    }

    public function emails_admin_page()
    {
        if ( ! isset($_GET['type'])) {
            add_filter('wp_cspa_main_content_area', function () {
                ob_start();
                $this->email_notification_list_table->prepare_items();
                $this->email_notification_list_table->display();

                return ob_get_clean();
            });
        }

        $page_header = $this->get_admin_title();

        $email_settings = [];

        if (isset($_GET['type'])) {

            $edit_screen_setup_args = $this->email_edit_screen_setup();

            $page_header = $edit_screen_setup_args['page_header'];

            $email_settings = $edit_screen_setup_args['email_settings'];
        }

        $this->settingsPageInstance->page_header($page_header);
        $this->settingsPageInstance->main_content($email_settings);
        $this->settingsPageInstance->remove_white_design();
        $this->settingsPageInstance->header_without_frills();
        $this->settingsPageInstance->build(true);

        $this->toggle_field_js_script();
    }

    public function email_settings_admin_page()
    {
        $email_settings = [
            [
                'admin_email_addresses'      => [
                    'label'       => esc_html__('Admin Email Address(es)', 'wp-user-avatar'),
                    'description' => esc_html__('The Email address to receive admin notifications. Use comma to separate multiple email addresses.', 'wp-user-avatar'),
                    'type'        => 'text',
                    'value'       => ppress_admin_email()
                ],
                'email_sender_name'          => [
                    'label'       => esc_html__('Sender Name', 'wp-user-avatar'),
                    'description' => esc_html__('The name to use as the sender of all ProfilePress emails. Preferably your website name.', 'wp-user-avatar'),
                    'type'        => 'text',
                    'value'       => ppress_site_title()
                ],
                'email_sender_email'         => [
                    'value'       => ppress_admin_email(),
                    'label'       => esc_html__('Sender Email Address', 'wp-user-avatar'),
                    'description' => esc_html__('The email address to use as the sender of all ProfilePress emails.', 'wp-user-avatar'),
                    'type'        => 'text'
                ],
                'email_content_type'         => [
                    'type'        => 'select',
                    'options'     => [
                        'text/html'  => esc_html__('HTML', 'wp-user-avatar'),
                        'text/plain' => esc_html__('Plain Text', 'wp-user-avatar')
                    ],
                    'value'       => 'text/html',
                    'label'       => esc_html__('Content Type', 'wp-user-avatar'),
                    'description' => esc_html__('Choose whether to send ProfilePress emails in HTML or plain text. HTML is recommended.', 'wp-user-avatar')
                ],
                'email_template_type'        => [
                    'type'        => 'select',
                    'options'     => [
                        'default' => esc_html__('Default Template', 'wp-user-avatar'),
                        'custom'  => esc_html__('Custom Email Template', 'wp-user-avatar')
                    ],
                    'value'       => 'default',
                    'label'       => esc_html__('Email Template', 'wp-user-avatar'),
                    'description' => esc_html__('Choose "Custom Email Template" if you want to code your own email template from scratch.', 'wp-user-avatar')
                ],
                'customize_default_template' => [
                    'type' => 'custom_field_block',
                    'data' => sprintf(
                        '<a href="%s" target="_blank" class="button load-customize"><span style="margin-top: 3px;margin-right: 3px;" class="dashicons dashicons-edit"></span> <span>%s</span></a>',
                        add_query_arg(['ppress-preview-template' => 'true'], admin_url('customize.php')),
                        esc_html__('Customize Default Template', 'wp-user-avatar')
                    ),
                ]
            ],
        ];

        $this->settingsPageInstance->page_header($this->get_admin_title());
        $this->settingsPageInstance->main_content($email_settings);
        $this->settingsPageInstance->remove_white_design();
        $this->settingsPageInstance->header_without_frills();
        $this->settingsPageInstance->build(true);

        $this->toggle_field_js_script();
    }

    public function handle_email_preview()
    {
        if ( ! isset($_GET['pp_email_preview']) || empty($_GET['pp_email_preview'])) return;

        if ( ! current_user_can('manage_options')) return;

        ppress_verify_nonce();

        $key = sanitize_text_field($_GET['pp_email_preview']);

        $data = wp_list_filter($this->email_notifications(), ['key' => $key]);

        $data = array_shift($data);

        $subject = ppress_get_setting($key . '_email_subject', $data['subject'], true);
        $content = ppress_get_setting($key . '_email_content', $data['message'], true);
        echo (new SendEmail('', $subject, $content))->templatified_email();
        exit;
    }

    protected function placeholder_tags_table($placeholders)
    {
        ?>
        <div class="ppress-placeholder-tags">
            <table class="widefat striped">
                <tbody>
                <tr>
                    <th colspan="2"><?= esc_html__('Available placeholders for subject and message body', 'wp-user-avatar'); ?></th>
                </tr>
                <?php foreach ($placeholders as $tag => $description) : ?>
                    <tr>
                        <td><?= $tag ?></td>
                        <td><?= $description ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function toggle_field_js_script()
    {
        ?>
        <script type="text/javascript">
            (function ($) {

                $('#email_template_type').on('change', function () {
                    var cache = $('#customize_default_template_row');
                    cache.hide();
                    if (this.value === 'default') {
                        cache.show();
                    }
                }).trigger('change');

                $('#email_content_type').on('change', function () {
                    var cache = $('#email_template_type_row, #customize_default_template_row');
                    cache.hide();
                    if (this.value === 'text/html') {
                        cache.show();
                        $('#email_template_type').trigger('change');
                    }

                }).trigger('change');
            })(jQuery);
        </script>
        <?php
    }

    public static function get_instance()
    {
        static $instance = null;

        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }
}
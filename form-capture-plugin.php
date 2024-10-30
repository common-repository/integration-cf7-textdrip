<?php
/**
 * Plugin Name: Integration for CF7 to Textdrip
 * Description: Capture Contact Form 7 submissions and send to Textdrip
 * Version: 1.0
 * Requires at least: 5.7
 * Requires PHP: 7.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


if (!defined('ABSPATH')) {
	exit;
}

// Add admin menu
add_action('admin_menu', 'textdrip_add_admin_menu');
function textdrip_add_admin_menu() {
    add_menu_page(
        'Textdrip Contact Form 7',
        'Textdrip CF7',
        'manage_options',
        'textdrip',
        'textdrip_settings_page',
        'https://imagedelivery.net/ykKK3unAFWyIbSlrQrfCRg/eb1ab807-f7e0-42db-38bc-02f845950700/public' // URL of your custom icon image
    );
}

// Display settings page
function textdrip_settings_page() {
    ?>
    <div class="wrap">
        <h2>Textdrip Contact Form 7</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('textdrip_options');
            do_settings_sections('textdrip');
            ?>
            <label for="textdrip_api_key">API Key:</label>
            <input type="text" id="textdrip_api_key" name="textdrip_api_key" value="<?php echo esc_attr(get_option('textdrip_api_key')); ?>" placeholder="Enter API Key" />
            <button type="button" id="fetch-textdrip-campaigns">Fetch Textdrip Campaigns</button>
            <br>
            <div id="debugger"></div>
            <br>
            <?php
            $args = array(
                'post_type' => 'wpcf7_contact_form',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );
            $cf7Forms = get_posts($args);
            foreach ($cf7Forms as $form) {
                $currentCampaign = get_option('cf7form_campaign_' . $form->ID);
                echo '<label for="cf7form_' . esc_attr($form->ID) . '">';
                echo '<input type="checkbox" id="cf7form_' . esc_attr($form->ID) . '" name="cf7form_' . esc_attr($form->ID) . '" value="1" ' . checked(1, get_option('cf7form_' . $form->ID), false) . ' />';
                echo esc_attr($form->post_title);
                echo '</label>';
                echo '<select id="cf7form_campaign_' . esc_attr($form->ID) . '" class="textdrip-campaign-select" name="cf7form_campaign_' . esc_attr($form->ID) . '">';
                echo '<option value="">Select Campaign</option>';
                // Pre-select the current campaign if it exists
                if ($currentCampaign) {
                    echo '<option value="' . esc_attr($currentCampaign) . '" selected="selected">' . esc_html($currentCampaign) . '</option>';
                }
                echo '</select>';
                echo '<br>';
            }
            ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'textdrip_settings_init');
function textdrip_settings_init() {
    register_setting('textdrip_options', 'textdrip_api_key');
}

// Capture CF7 form data and send to API
add_action('wpcf7_before_send_mail', 'textdrip_capture_form_data');
function textdrip_capture_form_data($contact_form) {
    $submission = WPCF7_Submission::get_instance();

    if ($submission) {
        $data = $submission->get_posted_data();

        $email = $data['Email'] ?? $data['your-email'] ?? '';
        $name = $data['FullName'] ?? $data['your-name'] ?? '';
        $phone = $data['Phone'] ?? $data['PhoneNumber'] ?? '';

        $form_id = $contact_form->id();
        $api_key = get_option('textdrip_api_key');
        $campaign = get_option('cf7form_campaign_' . $form_id);

        if (get_option('cf7form_' . $form_id)) {
            $api_url = "https://api.textdrip.com/api/create-contact";

            $payload = array(
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'campaign' => [$campaign]
            );

            $args = array(
                'body' => json_encode($payload),
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
            );

            // Send to Textdrip API
            $response = wp_safe_remote_post($api_url, $args);

            // Handle the API response here (e.g., log errors)
        }
    }
}

// Enqueue JavaScript
function textdrip_enqueue_custom_admin_script() {
    wp_enqueue_script('my-custom-script', plugins_url('/textdrip-admin.js', __FILE__), array('jquery'));
}
add_action('admin_enqueue_scripts', 'textdrip_enqueue_custom_admin_script');
?>

<?php
defined('ABSPATH') or die('This script cannot be accessed directly.');
/**
 * The settings page for the Evercate plugin.
 */

new SNEV_Settings();

class SNEV_Settings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'create_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function admin_init()
    {
        add_settings_section(
            SNILLRIK_EV_NAME . '-section-credentials',
            'Credentials',
            array($this, 'credentials_section'),
            SNILLRIK_EV_NAME
        );

        /**
         * Adding the credential fields.
         */
        $this->add_field("API TOKEN");
        $this->add_field("API URL");

        add_filter('allowed_options', array($this, 'whitelist_options'), 100, 1);
    }

    public function main_page()
    {
        echo '<form action="options.php" method="post">';
        do_settings_sections(SNILLRIK_EV_NAME);
        submit_button();
        echo '</form>';

        echo '<div class="' . SNILLRIK_EV_NAME . '_admin_block"><h2>' . esc_attr__('Test connection', SNILLRIK_EV_NAME) . '</h2>
        <p>' . esc_attr__('Make a test call to evercate. Use console to see response.', SNILLRIK_EV_NAME) . '</p>
        <input type="text" id="' . SNILLRIK_EV_NAME . '_testcall_id" placeholder="ID | Mail" />
        <input type="button" id="' . SNILLRIK_EV_NAME . '_testcall" value="' . esc_attr__('Test call', SNILLRIK_EV_NAME) . '" /></div>';
    }

    public function credentials_section()
    {
        settings_fields(SNILLRIK_EV_NAME . '_options_group');
    }

    public function create_menu()
    {
        add_menu_page(
            'Everacate +',
            'Evercate +',
            'administrator',
            SNILLRIK_EV_NAME,
            array($this, 'main_page'),
            SNILLRIK_EV_PLUGIN_URL . 'images/snillrik_icon.svg'
        );
    }

    public function add_field($name)
    {
        $nice_name = sanitize_key($name);
        add_settings_field(
            SNILLRIK_EV_NAME . "_" . $nice_name,
            __($name, SNILLRIK_EV_NAME),
            array($this, "print_field_" . $nice_name),
            SNILLRIK_EV_NAME,
            SNILLRIK_EV_NAME . '-section-credentials',
            array('label_for' => SNILLRIK_EV_NAME . "_" . $nice_name)
        );

        register_setting(SNILLRIK_EV_NAME . '_options_group', SNILLRIK_EV_NAME . "_" . $nice_name);
    }

    public function print_field_apiurl()
    {
        $setting = get_option(SNILLRIK_EV_NAME . '_apiurl', "https://api-v1.evercate.com/");
        $placeholder = esc_attr__('Long and very weird text string', SNILLRIK_EV_NAME);
        $nameid = SNILLRIK_EV_NAME . '_apiurl';

        echo '<input type="text" id="' . esc_attr($nameid) . '" name="' . esc_attr($nameid) . '" placeholder="' . esc_attr($placeholder) . '" value="' . esc_attr($setting) . '" />';
    }

    public function print_field_apitoken()
    {
        $setting = get_option(SNILLRIK_EV_NAME . '_apitoken');
        $placeholder = esc_attr__('Long and very weird text string', SNILLRIK_EV_NAME);
        $nameid = esc_attr(SNILLRIK_EV_NAME . '_apitoken');
        echo '<input type="text" id="' . $nameid . '" name="' . $nameid . '" placeholder="' . $placeholder . '" value="' . $setting . '" />';
    }

    /**
     * To not save token if input field is empty. Might be used later
     */
    public function whitelist_options($options)
    {
        if (isset($options[SNILLRIK_EV_NAME . '-options-group'])) {
            foreach ($options[SNILLRIK_EV_NAME . '-options-group'] as $index => $key) {

                if (empty($_POST[$key]) && $key == SNILLRIK_EV_NAME . '_apitoken') {
                    unset($options[SNILLRIK_EV_NAME . '-options-group'][$index]);
                }
            }
        }
        return $options;
    }
}

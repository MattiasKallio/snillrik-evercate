<?php
defined('ABSPATH') or die('This script cannot be accessed directly.');
/**
 * Basic connections to Evercate
 */

new SNEvercate_Woocommerce();

class SNEvercate_Woocommerce
{
    private $woocommercemeta = [];

    public function __construct()
    {
        add_action(SNILLRIK_EV_WOO_HOOK, [$this, 'woocommerce_payment_complete'], 10, 3); //mostly if not set automatically with hook below.
        add_action("woocommerce_payment_complete", [$this, 'woocommerce_payment_complete'], 10, 3);
        add_action("woocommerce_admin_order_data_after_shipping_address", [$this, 'woocommerce_admin_order_data'], 10, 3);
        add_action('woocommerce_add_to_cart_validation', [$this, 'add_to_cart'], 40, 2);

        /*For course sku*/
        add_action('save_post', [$this, 'meta_save']);
        add_filter('woocommerce_product_data_tabs', [$this, 'evercate_tab'], 98, 1);
        add_filter('woocommerce_product_data_panels', [$this, 'evercate_course_field_tag']);

        //Adding title/function field to checkout fields
        if(SNILLRIK_EV_ADD_TITLE)
            add_filter('woocommerce_billing_fields', [$this, 'custom_woocommerce_billing_fields']);
        //to hide address fields on virtual product
        if(SNILLRIK_EV_NO_ADDRESS_ON_VIRTUAL)
            add_filter('woocommerce_checkout_fields', [$this, 'simplify_checkout_virtual']);

        //For pushing button in order in admin if adding user failed.
        add_action('wp_ajax_evercate_push_woo_to_register',        [$this, 'push_woo_to_register']);
    }

    /**
     * Removing adressfields if virtual product.
     */
    function simplify_checkout_virtual($fields)
    {
        $only_virtual = true;

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            // Check if there are non-virtual products
            if (!$cart_item['data']->is_virtual()) $only_virtual = false;
        }

        if ($only_virtual) {
            unset($fields['billing']['billing_address_1']);
            unset($fields['billing']['billing_address_2']);
            unset($fields['billing']['billing_postcode']);
            unset($fields['billing']['billing_country']);
            unset($fields['billing']['billing_state']);
            add_filter('woocommerce_enable_order_notes_field', '__return_false');
        }

        return $fields;
    }

    /**
     * Add extra fields
     */
    function custom_woocommerce_billing_fields($fields)
    {
        $fields[SNILLRIK_EV_NAME.'_billing_titlefunction'] = array(
            'label' => __('Titel / Funktion', 'woocommerce'), // Add custom field label
            'placeholder' => esc_attr_x('Skriv in titel eller funktion...', 'placeholder', 'woocommerce'), // Add custom field placeholder
            'required' => false, // if field is required or not
            'clear' => false, // add clear or not
            'type' => 'text', // add field type
            'priority'    => 30,
        );

        return $fields;
    }

    /**
     * For the tab on product pages.
     */
    public function evercate_tab($tabs)
    {
        $tabs['sn_evercate'] = array(
            'label'        => __('Evercate', SNILLRIK_EV_NAME),
            'target'    => 'evercate_course',
            'class'        => array('show_if_simple', 'show_if_variable'),
        );

        return $tabs;
    }

    /**
     * Field for the course tag in admin.
     */
    public function evercate_course_field_tag()
    {
        global $post;
        $usergroupes = SNEV_API::get_usergroups();
        echo "<div id='evercate_course' class='panel woocommerce_options_panel'>";
        $ugstr = "";
        //echo print_r($usergroupes, true);
        $usergroupes = reset($usergroupes);
        if (isset($usergroupes["Tags"])) {
            foreach ($usergroupes["Tags"] as $group) {
                $ugstr .= "<span class='evercate-select-tag' data-tagid='" . esc_attr($group["Id"]) . "'>" . esc_attr($group["Name"]) . "</span>";
            }
        }

        $usergroup_id = esc_attr($usergroupes["Id"]);
        $usergroup_name = esc_attr($usergroupes["Name"]);

        woocommerce_wp_checkbox(array(
            'id'          => 'evercate_course_product',
            'label'       => esc_attr__('Enable Evevercate', SNILLRIK_EV_NAME),
            'description' => esc_attr__('To enable evercate on this product.', SNILLRIK_EV_NAME),
            'desc_tip'    => true,
        ));

        woocommerce_wp_text_input(array(
            'id' => 'evercate_course_group',
            'class' => 'evercate_course_usergroup short',
            'label' => esc_attr__("Evercate user group ($usergroup_name)", SNILLRIK_EV_NAME),
            'description' => "<div class='evercate-select-group_info'>" . sprintf(esc_attr__('User Group %s', SNILLRIK_EV_NAME), $usergroup_id) . "</div>",
            'desc_tip' => true,
            'value' => $usergroup_id,
            'custom_attributes' => array('readonly' => 'readonly')
        ));
        woocommerce_wp_text_input(array(
            'id' => 'evercate_course_tag',
            'class' => 'evercate_course_field_tag short',
            'label' => __('Evercate kurs-tagg', SNILLRIK_EV_NAME),
            'description' => "<div class='evercate-select-tag-info'>" . sprintf(esc_attr__('Click to select the course if this is a course %s', SNILLRIK_EV_NAME), $ugstr)  . "</div>",
            'desc_tip' => false
        ));

        echo "</div>";
    }

    /**
     * Saves meta from admin metabox.
     */
    public function meta_save($post_id)
    {

        if (!in_array(get_post_type($post_id), ["product"])) {
            return;
        }

        $evercate_course_product = isset($_POST['evercate_course_product']) ? esc_attr($_POST['evercate_course_product']) : '';
        update_post_meta($post_id, 'evercate_course_product', $evercate_course_product);

        if (isset($_POST['evercate_course_product'])) {

            //Course group
            if (isset($_POST['evercate_course_group'])) {
                update_post_meta($post_id, 'evercate_course_group', $_POST['evercate_course_group']);
            } else {
                delete_post_meta($post_id, 'evercate_course_group');
            }

            //Course tag
            if (isset($_POST['evercate_course_tag'])) {
                update_post_meta($post_id, 'evercate_course_tag', $_POST['evercate_course_tag']);
            } else {
                delete_post_meta($post_id, 'evercate_course_tag');
            }

            if (!empty($_POST['evercate_course_extra_info'])) {
                $data = htmlspecialchars($_POST['evercate_course_extra_info']);
                update_post_meta($post_id, 'evercate_course_extra_info', $data);
            }
        } else {
            delete_post_meta($post_id, 'evercate_course_group');
            delete_post_meta($post_id, 'evercate_course_tag');
        }
    }

    /**
     * Add to cart. (To check for existing users etc.)
     */
    public function add_to_cart($cart_item_key, $product_id)
    {
        global $woocommerce;

        $product = wc_get_product($product_id);
        $product_data = $product->get_data();

        $api = new SNEV_API();

        $evercate_course = get_post_meta($product_id, 'evercate_course_tag', true);

        if ($evercate_course != "") { //ie if field not set.
            $emails = array_filter($_POST, function ($item) {
                if (is_string($item) && is_email($item))
                    return $item;
            });

            $first_mail = reset($emails);
            //test if alredy in course.
            if (isset($first_mail) && is_email($first_mail)) {
                //check if alredy on course $evercate_course
                $ev_user = $api::get_user($first_mail);

                if ($ev_user) {
                    //has tag and same as product
                    $evercate_user = new SNEV_User($ev_user);
                    $alredy_in_course = $evercate_user->has_tag($evercate_course);

                    if ($alredy_in_course) {
                        //in course
                        wc_add_notice(esc_attr__('User already in current course.', SNILLRIK_EV_NAME), 'error');
                        return false;
                    } else {
                        //go on, will add course to current user in woo.
                        return true;
                    }
                } else {
                    //go on, will add new user in woo
                    return true;
                }
            }
        }

        return true;
    }


    /**
     * Information in the shipping part of a specific WooCommerce order.
     * @param Order $order magically from WooComemrce action.
     */
    public function woocommerce_admin_order_data($order)
    {
        $evercate_user_info = json_decode($order->get_meta(SNILLRIK_EV_NAME . '_user_added_info'));
        if ($evercate_user_info != "") {
            echo "<br/><h3>" . esc_attr__("Orderinformation from Evercate", SNILLRIK_EV_NAME) . "</h3>";

            if (isset($evercate_user_info->UserCreatedDate)) {
                echo "<strong>" . esc_attr__("Date added: ", SNILLRIK_EV_NAME) . "</strong>" . date("Y-m-d H:i:s", strtotime($evercate_user_info->UserCreatedDate));
            } else {
                $status = $order->get_status();
                if ($status == "completed" || $status == "processing") {
                    echo "<p>" . esc_attr__("Should be done automatically but status is completed or processing.", SNILLRIK_EV_NAME) . "</p>
                    <button class='button button-primary everrcate-button' id='snevercate_woo_order_post'>" . esc_attr__("Post to Evercate", SNILLRIK_EV_NAME) . "</button>
                    <div id='snevercate_order_push_message'></div>";
                }
            }
        }
    }

    /**
     * For the button that pushes to Evercate manually.
     */
    function push_woo_to_register()
    {
        $woo_order_id = isset($_POST["woo_order_id"]) ? sanitize_post($_POST["woo_order_id"]) : false;
        $order = new WC_Order($woo_order_id);
        $evercate_user_info = json_decode($order->get_meta(SNILLRIK_EV_NAME . '_user_added_info'));
        if ($evercate_user_info != "") {
            $snevwoo = new SNEvercate_Woocommerce();
            $response = $snevwoo->woocommerce_payment_complete($order->get_id());
            $response_text = $response == "" ? "" : "Svar från server: " . esc_html(print_r($response, true));
            wp_send_json_success($response_text);
        }
    }

    /**
     * Action for when payment is completed.
     * Adding user to Evercate if not exists and adding to course according to the Evercate tag.. So it both tries to post to Evercate on payment complete and status is set to payment complete in case not automatic payments are set or working.
     * @param integer @order_id the order id from WooCommerce action.
     */
    public function woocommerce_payment_complete($order_id)
    {
        $order = new WC_Order($order_id);

        $evercate_user_info = json_decode($order->get_meta(SNILLRIK_EV_NAME . '_user_added_info'));
        if ($evercate_user_info != "")
            return;

        $items = $order->get_items();
        $has_evercate = false;

        $email = $order->get_billing_email();
        $titlefunction = SNILLRIK_EV_ADD_TITLE ? $order->get_meta(SNILLRIK_EV_NAME.'_billing_titlefunction') : "";
        $city = $order->get_billing_city();
        $company = $order->get_billing_company();

        $userinfo = [
            "FirstName" => $order->get_billing_first_name(),
            "LastName" => $order->get_billing_last_name(),
            "Username" => $email,
            "Phone" => $order->get_billing_phone(),
            "Notes" => "$company\n$titlefunction\n$city"
        ];

        //check if user exists otherwise create.
        $evercate_user = new SNEV_User($email);
        if ($evercate_user->Id == null) {
            $evercate_user = new SNEV_User($userinfo);
            $evercate_user->UserTags = [];
        }
        //loop through items to check for evercate.
        foreach ($items as $item_id => $item) {
            $product_name = $item['name'];
            $product_id = $item['product_id'];
            //$product = wc_get_product($product_id);
            $course_tag = get_post_meta($product_id, 'evercate_course_tag', true);
            $course_group = get_post_meta($product_id, 'evercate_course_group', true);

            if ($course_tag != "" && $course_group != "") { //and if posted not set
                $has_evercate = true;
                //  if kurs check tag to match in api
                $evercate_user->GroupId = $course_group;

                if (!in_array($course_tag, $evercate_user->UserTags)) {
                    $evercate_user->UserTags[] = intval($course_tag); //add to existing tags.
                }
            }
        }

        //save to evercate and woo if found.
        if ($has_evercate && count($evercate_user->UserTags) > 0) {

            $response = $evercate_user->save();

            if ($response) {
                $order->update_meta_data(SNILLRIK_EV_NAME . '_user_added_info', $response);
                $order->save();
                error_log("evercate response: " . print_r($response, true));
            } else
                error_log("woo response: " . print_r($response, true));
        }
    }
}

<?php
defined('ABSPATH') or die('This script cannot be accessed directly.');
/**
 * Basic connections to P1
 */

new SNP1_Woocommerce();

class SNP1_Woocommerce
{
    private $woocommercemeta = [];

    public function __construct()
    {
        add_action("woocommerce_order_status_completed", [$this, 'woocommerce_payment_complete'], 10, 3);
        add_action("woocommerce_admin_order_data_after_shipping_address", [$this, 'woocommerce_admin_order_data'], 10, 3);
        add_action('woocommerce_add_to_cart_validation', [$this, 'add_to_cart'], 40, 2);

        /*For course sku*/
        add_action('save_post', [$this, 'meta_save']);
        add_filter('woocommerce_product_data_tabs', [$this, 'evercate_tab'], 98, 1);
        add_filter('woocommerce_product_data_panels', [$this, 'evercate_course_field_tag']);
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

        $usergroupes = SNEV_API::get_usergroups();
        echo "<div id='evercate_course' class='panel woocommerce_options_panel'>";
        $ugstr = "";
        //echo print_r($usergroupes, true);
        $usergroupes = reset($usergroupes);
        if (isset($usergroupes["Tags"])) {
            foreach ($usergroupes["Tags"] as $group) {
                $ugstr .= "<span class='evercate-select-tag' data-tagid='" . $group["Id"] . "'>" . $group["Name"] . "</span>";
            }
        }

        $usergroup_id = $usergroupes["Id"];
        $usergroup_name = $usergroupes["Name"];

        woocommerce_wp_checkbox(array(
            'id'          => 'evercate_course_product',
            'label'       => __('Enable Evevercate', SNILLRIK_EV_NAME),
            'description' => __('To enable evercate on this product.', SNILLRIK_EV_NAME),
            'desc_tip'    => true,
        ));

        woocommerce_wp_text_input(array(
            'id' => 'evercate_course_group',
            'class' => 'evercate_course_usergroup short',
            'label' => __("Evercate user group ($usergroup_name)", SNILLRIK_EV_NAME),
            'description' => "<div class='evercate-select-group_info'>" . sprintf(__('User Group %s', SNILLRIK_EV_NAME), $usergroup_id) . "</div>",
            'desc_tip' => true,
            'value' => $usergroup_id,
            'custom_attributes' => array('readonly' => 'readonly')
        ));
        woocommerce_wp_text_input(array(
            'id' => 'evercate_course_tag',
            'class' => 'evercate_course_field_tag short',
            'label' => __('Evercate kurs-tagg', SNILLRIK_EV_NAME),
            'description' => "<div class='evercate-select-tag-info'>" . sprintf(__('Click to select the course if this is a course %s', SNILLRIK_EV_NAME), $ugstr)  . "</div>",
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
                        wc_add_notice(__('User already in current course.', SNILLRIK_EV_NAME), 'error');
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
        $evercate_user_id = json_decode($order->get_meta(SNILLRIK_EV_NAME . '_user_added_id'));
        if ($evercate_user_id != "") {
            echo "<br/><h3>" . __("Orderinformation from Evercate", SNILLRIK_EV_NAME) . "</h3>";

            if (isset($evercate_user_id->UserCreatedDate))
                echo "<strong>" . __("Date added: ", SNILLRIK_EV_NAME) . "</strong>" . date("Y-m-d H:i:s", strtotime($evercate_user_id->UserCreatedDate));
        }
    }

    /**
     * Action for when payment is completed.
     * Adding user to Evercate if not exists and adding to course according to the Evercate tag..
     * @param integer @order_id the order id from WooCommerce action.
     */
    public function woocommerce_payment_complete($order_id)
    {
        $order = new WC_Order($order_id);
        $items = $order->get_items();
        $articles = [];

        //Loop through them, you can get all the relevant data:
        $new_users = [];
        foreach ($items as $item_id => $item) {
            $product_name = $item['name'];
            $product_id = $item['product_id'];
            $course_tag = get_post_meta($product_id, 'evercate_course_tag', true);
            $course_group = get_post_meta($product_id, 'evercate_course_group', true);


            if ($course_tag != "" && $course_group != "") { //and if posted not set
                //  if kurs check tag to match in api

                $product = wc_get_product($product_id);

                //New user from aticle
                $item_meta = $item->get_meta_data();

                //ToDo do a matchmaker thingie that's not hardcoded.
                $matchmaker = [
                    "Förnamn" => "FirstName",
                    "Efternamn" => "LastName",
                    "E-postadress" => "Username",
                    "Mobilnummer" => "Phone",
                ];
                $userinfo = [];
                $userinfo["Notes"] = "";

                foreach ($item_meta as $new_user_info) {
                    if (in_array($new_user_info->key, array_keys($matchmaker))) {
                        $userinfo[$matchmaker[$new_user_info->key]] = $new_user_info->value;
                    } else if (is_string($new_user_info->value)) {

                        $user_info_str = $new_user_info->key . ": " . $new_user_info->value . "\n";
                        $userinfo["Notes"] .= $user_info_str;
                    }
                }

                $evercate_user = new SNEV_User($userinfo["Username"]);
                $userinfo["GroupId"] = $course_group;


                if ($evercate_user->Id != null) {
                    if (!in_array($course_tag, $evercate_user->UserTags))
                        $evercate_user->UserTags[] = $course_tag; //add to existing tags.
                } else {
                    $evercate_user = new SNEV_User($userinfo);
                    $evercate_user->UserTags[] = $course_tag; //add to new tags.
                }

                $response = $evercate_user->save();

                if ($response) {
                    $order->update_meta_data(SNILLRIK_EV_NAME . '_user_added_id', $response);
                    $order->save();
                    error_log("evercate response: " . print_r($response, true));
                } else
                    error_log("woo response: " . print_r($response, true));
            }
        }
    }
}

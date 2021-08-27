<?php
defined('ABSPATH') or die('This script cannot be accessed directly.');
/**
 * Basic connections to Evercate
 */

new SNEV_API();

class SNEV_API
{
    public function __construct()
    {
        add_action('wp_ajax_snev_testcall', [$this, 'testcall']);
    }


    /**
     * For testing purposes only, will be removed later.
     */
    public function testcall()
    {
        $userid = isset($_POST["userid"]) ? sanitize_text_field($_POST["userid"]) : false;
        error_log(print_r($userid, true));
        $response = SNEV_API::get_user($userid);
        echo wp_send_json($response);
    }

    /**
     * To check if user exists by email. 
     * @param string $email users email adress.
     * @return object user if found and false if not.
     */
    public static function get_user($email)
    {
        //check user by email
        $api = new SNEV_API();
        $response = $api->call("users", [], "GET", $email);
        
        //If response is numeric it's most likley 400, 404, 500, if not, it's the users info
        return is_numeric($response) ? false : json_decode($response,true);
    }

    /**
     * Get user groups.
     */
    public static function get_usergroups(){
        $api = new SNEV_API();
        $response = $api->call("usergroups", [], "GET");
        return is_numeric($response) ? false : json_decode($response,true);
    }


    /**
     * A basic call to the register.
     *
     * @param string $endpoint
     * @param array $args
     * @param string $method POST|PUY|GET etc.
     * @param integer|boolean $userid if specific users should be used.
     */
    public function call($endpoint, $args = array(), $method = "GET", $userid = false)
    {
        //error_log(print_r($userid, true));
        $apiurl = get_option(SNILLRIK_EV_NAME . "_apiurl");
        $apitoken = get_option(SNILLRIK_EV_NAME . "_apitoken");
        if ($apiurl == "" || $apitoken == ""){
            error_log("API Credentials to Evercate not set.");
            return 401;
        }

        $urlen = mb_substr($apiurl, -1) == "/" ? $apiurl . $endpoint : $apiurl . "/" . $endpoint;

        $argstrarray = array();
        switch ($method) {
            case "GET": //can be both email and evercate-userid
                if ($userid) {
                    $urlen .= "/" . $userid;
                }
                $urlen .= is_array($args) && count($args) > 0 ? "/" . http_build_query($args) : "";
                break;
            case "PUT": //must be evercate-userid
                if ($userid) {
                    $urlen .= "/" . $userid;
                } else {
                    return;
                }
                break;

            case "POST":
                break;
        }
/* 
        if($method!=="GET"){
            error_log("Meth: $method " . print_r($urlen, true));
            error_log("args : " . print_r($args, true));
            wp_die("end");
        } */

        $response = wp_remote_request(
            $urlen,
            array(
                'method' => $method,
                'headers' => array(
                    "Content-type" => "application/json",
                    "Authorization" => "Bearer " . $apitoken,
                ),
                'body' => $method != "GET" && is_array($args) && count($args) > 0 
                    ? json_encode($args) 
                    : "",
            )
        );

        $responsecode = wp_remote_retrieve_response_code($response);

        if (in_array($responsecode, [200, 201]))
            return isset($response["body"]) ? $response["body"] : $response;
        else{
            error_log("Response : " . print_r($response, true));
            return $responsecode;
        }
    }
}

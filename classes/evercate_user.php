<?php
defined('ABSPATH') or die('This script cannot be accessed directly.');
/**
 * Basic connections to Evercate
 */

class SNEV_User
{
    public $Id;
    public $Username;
    public $FirstName;
    public $LastName;
    public $ExternalId;
    public $Phone;
    public $Notes;
    public $Locale;
    public $GroupId;
    public $UserTags;

    public function __construct($userinfo)
    {
        if (is_numeric($userinfo) || (is_string($userinfo) && is_email($userinfo))) {
            $userinfo = SNEV_API::get_user($userinfo);

            if (is_string($userinfo) && is_email($userinfo))
                $userinfo["Username"] = $userinfo;
        }

        if ($userinfo)
            foreach ($userinfo as $key => $value) $this->{$key} = $value;
    }

    /**
     * Check if user has a specific tag, ie course tag
     */
    public function has_tag($tag)
    {
        if (in_array($tag, $this->UserTags))
            return true;
        return false;
    }

    /**
     * Save user to Evercate
     */
    public function save()
    {
        $api = new SNEV_API();

        if (!isset($this->Username) || !is_email($this->Username)) {
            error_log("username is not email " . $this->Username);
            return;
        }

        $user_response = false;

        if (isset($this->Id) && is_numeric($this->Id)) {
            //updating user
            $user_response = $api->call("users", $this->to_array(true), "PUT", $this->Id);
        } else {
            //new user
            $user_response = $api->call("users", $this->to_array(), "POST");
        }

        do_action("snev_after_save_user", $user_response, $this);
        return $user_response;
    }

    /**
     * Params to array.
     */
    public function to_array($is_update = false)
    {
        $array_out = [
            "Id" => $this->Id,
            "Username" => $this->Username,
            "FirstName" => $this->FirstName,
            "LastName" => $this->LastName,
            "ExternalId" => $this->ExternalId,
            "Phone" => $this->Phone,
            "Notes" => $this->Notes,
            "Locale" => $this->Locale,
            "GroupId" => $this->GroupId,
            "UserTags" => $this->UserTags
        ];

        if ($is_update) {
            $array_out["ExistingUsername"] = esc_attr($this->Username);
        }

        return array_filter($array_out);
    }
}

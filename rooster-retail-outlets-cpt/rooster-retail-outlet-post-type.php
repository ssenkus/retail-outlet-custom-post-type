<?php

/*
  Plugin Name: Retail Outlet Post Types
  Plugin URI: http://www.roosterapparatus.com
  Description: Create the retail-outlets post type.
  Version: 0.1
  Author: SS
  Author URI: http://www.steven-senkus.com/
 */

class RoosterRetailOutletCPT {

    public $cptName = 'retail_outlets';
    
    public function __construct() {

        /* register the post type */
        add_action('init', array($this, 'registerPostType'));
        add_action('save_post', array($this, 'saveMetaData', ),1, 2); // save the custom fields
        add_filter('manage_edit-'. $this->cptName .'_columns', array($this, 'adminColumns'));
        add_action('manage_' . $this->cptName . '_posts_custom_column', array($this, 'columnContent'), 10, 2);
        
        register_activation_hook(__FILE__, 'activatePlugin');
        register_deactivation_hook(__FILE__, 'deactivatePlugin');

    }

    public function activatePlugin() {
        flush_rewrite_rules();
    }

    public function deactivatePlugin() {
        flush_rewrite_rules();
    }

    public function adminColumns($columns) {

        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Company Name'),
            'address' => __('Address')
        );
        return $columns;
    }

    public function registerPostType() {
        $cpt_name = 'retail_outlets';
        $retail_outlet_args = array(
            'public' => true,
            'query_var' => 'retail-outlet',
            'rewrite' => false,
            'supports' => array(
                'title',
                'revisions'
            ),
            'rewrite' => array('slug' => 'retail-outlets'),
            'menu_icon' => '', // set this to an image per WP spec
            'show_in_nav_menus' => false,
            'has_archive' => true,
            'register_meta_box_cb' => array($this, 'addMetaBoxes'),
            'labels' => array(
                'name' => 'Retail Outlets',
                'singular_name' => 'Retail Outlet',
                'add_new' => 'Add New Retail Outlet',
                'add_new_item' => 'Add New Retail Outlet',
                'edit_item' => 'Edit Retail Outlet',
                'new_item' => 'New Retail Outlet',
                'view_item' => 'View Retail Outlet',
                'search_items' => 'Search Retail Outlets',
                'not_found' => 'No Retail Outlets Found',
                'not_found_in_trash' => 'No Retail Outlets Found In Trash'
            )
        );

        /* Register the retail_outlets post type */
        register_post_type($cpt_name, $retail_outlet_args);
    }

// Save the Metabox Data    

    public function saveMetaData($post_id, $post) {


// verify this came from the our screen and with proper authorization,
// because save_post can be triggered at other times
        if (!wp_verify_nonce($_POST['eventmeta_noncename'], plugin_basename(__FILE__))) {
            return $post->ID;
        }
// Is the user allowed to edit the post or page?
        if (!current_user_can('edit_post', $post->ID))
            return $post->ID;
// OK, we're authenticated: we need to find and save the data
// We'll put it into an array to make it easier to loop though.
//$retail_outlet_meta['company_name'] = $_POST['company_name'];
        $retail_outlet_meta['street_address_1'] = $_POST['street_address_1'];
        $retail_outlet_meta['street_address_2'] = $_POST['street_address_2'];
        $retail_outlet_meta['city'] = $_POST['city'];
        $retail_outlet_meta['state'] = $_POST['state'];
        $retail_outlet_meta['country'] = $_POST['country'];
        $retail_outlet_meta['zip_code'] = $_POST['zip_code'];
        $retail_outlet_meta['phone_number'] = $_POST['phone_number'];
        $retail_outlet_meta['website_url'] = $_POST['website_url'];

// Add values as custom fields
        foreach ($retail_outlet_meta as $key => $value) {
            if ($post->post_type == 'revision')
                return; // Don't store custom data twice
            $value = implode(',', (array) $value); // If $value is an array, make it a CSV (unlikely)
            if (get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
                update_post_meta($post->ID, $key, $value);
            } else { // If the custom field doesn't have a value
                add_post_meta($post->ID, $key, $value);
            }
            if (!$value)
                delete_post_meta($post->ID, $key); // Delete if blank
        }
    }

    public function addMetaBoxes() {
        add_meta_box('rooster_retail_outlets_form', 'Retail Outlet', array($this, 'metaForm'), 'retail_outlets', 'normal', 'high');
    }

    public function metaForm() {



        global $post;
// Noncename needed to verify where the data originated
        echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
        wp_create_nonce(plugin_basename(__FILE__)) . '" />';
// Get the location data if its already been entered
//$meta_form_data['company_name'] = get_post_meta($post->ID, 'company_name', true);
        $meta_form_data['street_address_1'] = get_post_meta($post->ID, 'street_address_1', true);
        $meta_form_data['street_address_2'] = get_post_meta($post->ID, 'street_address_2', true);
        $meta_form_data['city'] = get_post_meta($post->ID, 'city', true);
        $meta_form_data['state'] = get_post_meta($post->ID, 'state', true);
        $meta_form_data['country'] = get_post_meta($post->ID, 'country', true);
        $meta_form_data['zip_code'] = get_post_meta($post->ID, 'zip_code', true);
        $meta_form_data['phone_number'] = get_post_meta($post->ID, 'phone_number', true);
        $meta_form_data['website_url'] = get_post_meta($post->ID, 'website_url', true);

// Echo out the field
        echo <<< FORM_INPUT
    <table class="form-table">
        <tr>
            <td>
                <label>Street Address 1</label>
            </td>
            <td>
                <input type="text" name="street_address_1" value="{$meta_form_data['street_address_1']}" class="widefat" />
            </td>
        </tr>
        <tr>
            <td>
                <label>Street Address 2</label>
            </td>
            <td>
                <input type="text" name="street_address_2" value="{$meta_form_data['street_address_2']}" class="widefat" />
            </td>
        </tr>
        <tr>
            <td>
                <label>City</label>
            </td>
            <td>
                <input type="text" name="city" value="{$meta_form_data['city']}" class="widefat" />
            </td>
        </tr>                
        <tr>
            <td>
                <label>State</label>
            </td>
            <td>
                <select name="state" value="{$meta_form_data['state']}">
                    <option  value="AL">Alabama</option>
                    <option  value="AK">Alaska</option>
                    <option  value="AZ">Arizona</option>
                    <option  value="AR">Arkansas</option>
                    <option  value="CA">California</option>
                    <option  value="CO">Colorado</option>
                    <option  value="CT">Connecticut</option>
                    <option  value="DE">Delaware</option>
                    <option  value="DC">District of Columbia</option>
                    <option  value="FL">Florida</option>
                    <option  value="GA">Georgia</option>
                    <option  value="HI">Hawaii</option>
                    <option  value="ID">Idaho</option>
                    <option  value="IL">Illinois</option>
                    <option  value="IN">Indiana</option>
                    <option  value="IA">Iowa</option>
                    <option  value="KS">Kansas</option>
                    <option  value="KY">Kentucky</option>
                    <option  value="LA">Louisiana</option>
                    <option  value="ME">Maine</option>
                    <option  value="MD">Maryland</option>
                    <option  value="MA">Massachusetts</option>
                    <option  value="MI">Michigan</option>
                    <option  value="MN">Minnesota</option>
                    <option  value="MS">Mississippi</option>
                    <option  value="MO">Missouri</option>
                    <option  value="MT">Montana</option>
                    <option  value="NE">Nebraska</option>
                    <option  value="NV">Nevada</option>
                    <option  value="NH">New Hampshire</option>
                    <option  value="NJ">New Jersey</option>
                    <option  value="NM">New Mexico</option>
                    <option  value="NY">New York</option>
                    <option  value="NC">North Carolina</option>
                    <option  value="ND">North Dakota</option>
                    <option  value="OH">Ohio</option>
                    <option  value="OK">Oklahoma</option>
                    <option  value="OR">Oregon</option>
                    <option  value="PA">Pennsylvania</option>
                    <option  value="RI">Rhode Island</option>
                    <option  value="SC">South Carolina</option>
                    <option  value="SD">South Dakota</option>
                    <option  value="TN">Tennessee</option>
                    <option  value="TX">Texas</option>
                    <option  value="UT">Utah</option>
                    <option  value="VT">Vermont</option>
                    <option  value="VA">Virginia</option>
                    <option  value="WA">Washington</option>
                    <option  value="WV">West Virginia</option>
                    <option  value="WI">Wisconsin</option>
                    <option  value="WY">Wyoming</option>                
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>Country</label>
            </td>
            <td>
                <select name="country" value="{$meta_form_data['country']}">
                    <option>USA</option>
                    <option>Canada</option>
                </select>
            </td>
        </tr>                
        <tr>
            <td>
                <label>Zip Code</label>
            </td>
            <td>
                <input type="text" name="zip_code" size="30" value="{$meta_form_data['zip_code']}" />
            </td>
        </tr>                
        <tr>
            <td>
                <label>Phone #</label>
            </td>
            <td>
                <input type="tel" name="phone_number" class="widefat" value="{$meta_form_data['phone_number']}" />
            </td>
        </tr>
        <tr>
            <td>
                <label>Website URL</label>
            </td>
            <td>
                <input type="text" name="website_url" class="widefat" value="{$meta_form_data['website_url']}" /><br />
            </td>
        </tr>
    </table>                
FORM_INPUT;
    }

    public function columnContent($column, $post_id) {
        global $post;

        switch ($column) {
            case 'state':
                /* Get the post meta. */
                $state = get_post_meta($post_id, 'state', true);

                /* If no duration is found, output a default message. */
                if (empty($state))
                    echo __('Unknown');

                /* If there is a duration, append 'minutes' to the text string. */
                else
                    printf(__('%s'), $state);

                break;

            case 'address':
                /* Get the post meta. */
                $add1 = get_post_meta($post_id, 'street_address_1', true);
                $add2 = get_post_meta($post_id, 'street_address_2', true);
                $city = get_post_meta($post_id, 'city', true);
                $state = get_post_meta($post_id, 'state', true);
                $zip = get_post_meta($post_id, 'zip_code', true);

                if (!empty($add2)) {
                    $add1 .= '<br />';
                }
                printf(__('%s %s <br />%s, %s %s'), $add1, $add2, $city, $state, $zip);
        }
    }

}
$rooster_outlets = new RoosterRetailOutletCPT();
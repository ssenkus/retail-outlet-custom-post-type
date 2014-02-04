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
    public $states = array(
        'AL' => "Alabama",
        'AK' => "Alaska",
        'AZ' => "Arizona",
        'AR' => "Arkansas",
        'CA' => "California",
        'CO' => "Colorado",
        'CT' => "Connecticut",
        'DE' => "Delaware",
        'DC' => "District Of Columbia",
        'FL' => "Florida",
        'GA' => "Georgia",
        'HI' => "Hawaii",
        'ID' => "Idaho",
        'IL' => "Illinois",
        'IN' => "Indiana",
        'IA' => "Iowa",
        'KS' => "Kansas",
        'KY' => "Kentucky",
        'LA' => "Louisiana",
        'ME' => "Maine",
        'MD' => "Maryland",
        'MA' => "Massachusetts",
        'MI' => "Michigan",
        'MN' => "Minnesota",
        'MS' => "Mississippi",
        'MO' => "Missouri",
        'MT' => "Montana",
        'NE' => "Nebraska",
        'NV' => "Nevada",
        'NH' => "New Hampshire",
        'NJ' => "New Jersey",
        'NM' => "New Mexico",
        'NY' => "New York",
        'NC' => "North Carolina",
        'ND' => "North Dakota",
        'OH' => "Ohio",
        'OK' => "Oklahoma",
        'OR' => "Oregon",
        'PA' => "Pennsylvania",
        'RI' => "Rhode Island",
        'SC' => "South Carolina",
        'SD' => "South Dakota",
        'TN' => "Tennessee",
        'TX' => "Texas",
        'UT' => "Utah",
        'VT' => "Vermont",
        'VA' => "Virginia",
        'WA' => "Washington",
        'WV' => "West Virginia",
        'WI' => "Wisconsin",
        'WY' => "Wyoming"
    );
    public $provinces = array(
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland',
        'NS' => 'Nova Scotia',
        'NT' => 'Northwest Territories',
        'NU' => 'Nunavut',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'YT' => 'Yukon Territory'
    );
    public $countries = array(
        // fix later with correct country codes
        'USA' => 'USA',
        'Canada' => 'Canada'
    );
    public $fields = array(
        'street_address_1',
        'street_address_2',
        'city',
        'state',
        'country',
        'zip_code',
        'phone_number',
        'website_url'
    );

    public function convertToStateName($abbr) {
        return $this->states[$abbr];
    }

    public function __construct() {

        /* set it up! */
        add_action('init', array($this, 'registerPostType'));

        // icon for admin menu
        add_action('admin_head', array($this, 'addCustomAdminIcon'));

        // save the custom fields on publish/update
        add_action('save_post', array($this, 'saveMetaData',), 1, 2);

        // change post display column names
        add_filter('manage_edit-' . $this->cptName . '_columns', array($this, 'adminColumns'));

        // change post display content
        add_action('manage_' . $this->cptName . '_posts_custom_column', array($this, 'columnContent'), 10, 2);

        // add JS to 
        add_action('admin_print_scripts-post-new.php', array($this, 'addAdminScript'), 11);
        add_action('admin_print_scripts-post.php', array($this, 'addAdminScript'), 11);

        //add_action('admin_init', array($this, 'addAdminScript'),11);

        register_activation_hook(__FILE__, 'activatePlugin');
        register_deactivation_hook(__FILE__, 'deactivatePlugin');
    }

    public function addAdminScript($hook) {
        global $post_type;
        if ($post_type == $this->cptName) {
            wp_enqueue_script('retail_outlets-admin-script', plugins_url('/js/admin.js', __FILE__), '', '', true); //"TRUE"-ADDS JS TO FOOTER        
        }
    }

    public function addCustomAdminIcon() {
        // Add a custom icon for the retail_outlets CPT menu item
        echo <<< STYLE
        <style>
            #adminmenu #menu-posts-{$this->cptName} div.wp-menu-image:before { content: "\\f174"; }
        </style>
STYLE;
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
        $lbl_text = 'Retail Outlet';
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
                'name' => $lbl_text . 's',
                'singular_name' => $lbl_text,
                'add_new' => 'Add New ' . $lbl_text,
                'add_new_item' => 'Add New ' . $lbl_text,
                'edit_item' => 'Edit ' . $lbl_text,
                'new_item' => 'New ' . $lbl_text,
                'view_item' => 'View ' . $lbl_text,
                'search_items' => 'Search ' . $lbl_text . 's',
                'not_found' => 'No ' . $lbl_text . 's Found',
                'not_found_in_trash' => 'No ' . $lbl_text . 's Found In Trash'
            )
        );

        /* Register the retail_outlets post type */
        register_post_type($this->cptName, $retail_outlet_args);
    }

// Save the Metabox Data    

    public function saveMetaData($post_id, $post) {

// verify this came from the our screen and with proper authorization,
// because save_post can be triggered at other times
        if (!wp_verify_nonce($_POST[$this->cptName . '_noncename'], plugin_basename(__FILE__))) {
            return $post->ID;
        }

// Is the user allowed to edit the post or page?
        if (!current_user_can('edit_post', $post->ID)) {
            return $post->ID;
        }
// OK, we're authenticated: we need to find and save the data
// We'll put it into an array to make it easier to loop though.

        $retail_outlet_meta = array();
        foreach ($this->fields as $f) {
            $retail_outlet_meta[$f] = $_POST[$f];
        }

// Add values as custom fields
        foreach ($retail_outlet_meta as $key => $value) {
            if ($post->post_type == 'revision') {
                return; // Don't store custom data twice
            }
            $value = implode(',', (array) $value); // If $value is an array, make it a CSV (unlikely)
            if (get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
                update_post_meta($post->ID, $key, $value);
            } else { // If the custom field doesn't have a value
                add_post_meta($post->ID, $key, $value);
            }
            if (!$value) {
                delete_post_meta($post->ID, $key); // Delete if blank
            }
        }
    }

    public function addMetaBoxes() {
        add_meta_box('rooster_' . $this->cptName . '_form', 'Retail Outlet', array($this, 'metaForm'), $this->cptName, 'normal', 'high');
    }

    public function metaForm() {

        global $post;
// Noncename needed to verify where the data originated
        echo '<input type="hidden" name="' . $this->cptName . '_noncename" id="' . $this->cptName . '_noncename" value="' .
        wp_create_nonce(plugin_basename(__FILE__)) . '" />';
// Get the location data if its already been entered

        $meta_form_data = array();
        foreach ($this->fields as $f) {
            $meta_form_data[$f] = get_post_meta($post->ID, $f, true);
        }

        // state select box        
        $select_state = '<select name="state">';
        foreach ($this->states as $key => $value) {
            $select_state .= '<option ' . (($meta_form_data['state'] == $key ) ? 'selected="selected"' : '') . 'value="' . $key . '">' . $value . '</option>';
        }
        $select_state .= '</select>';

        // country select box
        $select_country = '<select name="country">';
        foreach ($this->countries as $key => $value) {
            $select_country .= '<option ' . (($meta_form_data['country'] == $key) ? 'selected="selected"' : '') . 'value="' . $key . '">' . $value . '</option>';
        }
        $select_country .= '</select>';

        // country select box
        $select_provinces = '<select name="province">';
        foreach ($this->provinces as $key => $value) {
            $select_provinces .= '<option ' . (($meta_form_data['province'] == $key) ? 'selected="selected"' : '') . 'value="' . $key . '">' . $value . '</option>';
        }
        $select_provinces .= '</select>';        
        
        echo <<< FORM_INPUT
    <table class="form-table">
        <tr>
            <td><label>Street Address 1</label></td>
            <td><input type="text" name="street_address_1" value="{$meta_form_data['street_address_1']}" class="widefat" /></td>
        </tr>
        <tr>
            <td><label>Street Address 2</label></td>
            <td><input type="text" name="street_address_2" value="{$meta_form_data['street_address_2']}" class="widefat" /></td>
        </tr>
        <tr>
            <td><label>City</label></td>
            <td><input type="text" name="city" value="{$meta_form_data['city']}" class="widefat" /></td>
        </tr>
        <tr>
            <td><label>Country</label></td>
            <td>{$select_country}</td>
        </tr>                            
        <tr id="state_row">
            <td><label>State</label></td>
            <td>{$select_state}</td>
        </tr>
        <tr id="province_row">
            <td><label>Province</label></td>
            <td>{$select_provinces}</td>
        </tr>
        <tr>
            <td><label>Zip Code</label></td>
            <td><input type="text" name="zip_code" size="30" value="{$meta_form_data['zip_code']}" /></td>
        </tr>                
        <tr>
            <td><label>Phone #</label></td>
            <td><input type="tel" name="phone_number" class="widefat" value="{$meta_form_data['phone_number']}" /></td>
        </tr>
        <tr>
            <td><label>Website URL</label></td>
            <td><input type="text" name="website_url" class="widefat" value="{$meta_form_data['website_url']}" /><br /></td>
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
                if (empty($state)) {
                    echo __('Unknown');
                }
                /* If there is a duration, append 'minutes' to the text string. */ else {
                    printf(__('%s'), $state);
                }
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
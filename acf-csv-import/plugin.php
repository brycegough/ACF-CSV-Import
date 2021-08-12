<?php
/**
 * Plugin Name
 *
 * @package     ACF CSV Import
 * @author      Bryce Gough
 *
 * @wordpress-plugin
 * Plugin Name: ACF CSV Import
 * Description: Import ACF Repeater values from a CSV file.
 * Version:     1.3
 * Author: Bryce Gough
 * Author URI: https://freeform.com.au/
 * Text Domain: freeform
 */

define('ACF_CSV', __DIR__);

class ACF_CSV {

    public $menu = null;

    public function __construct() {
        // Include the Import button
        add_action('acf/render_field/type=repeater', [$this, 'render_field']);

        // Include the tool
        add_action('acf/include_admin_tools', function() {
            require(__DIR__ . '/tool.php');
        });

        // Add POST ID to field object
        add_filter('acf/pre_render_fields', function($fields, $post_id) {
            return array_map(function($field) use ($post_id) {
                $field['_acf_post_id'] = $post_id;
                return $field;
            }, $fields);
        }, 10, 2);

        add_action('init', function() {
            set_transient( 'acf_csv_repeaters', acf_csv()->get_repeaters() );
        });
    }

    public function check_header(&$header, $field_key, $post_id) {
        if (!is_array($header)) return false;

        $field = get_field_object($field_key, $post_id, false, false);

        if ($field['type'] !== 'repeater' || !is_array($field['sub_fields'])) return false;

        // Check valid fields and find keys
        $valid_fields = [];
        foreach ($field['sub_fields'] as $field) {
            $valid_fields[$field['name']] = $field['key'];
        }

        // Check header rows
        foreach ($header as $index => $name) {
            if (array_key_exists($name, $valid_fields)) {
                $header[$index] = $valid_fields[$name];
            } else {
                return false;
            }
        }

        return true;
    }

    public function render_field( $field ) {
        // Only show on fields that have been added to functions.php in theme
        if (!in_array($field['key'], apply_filters('acf/csv_import_fields', []))) return;

        $edit_link = acf_get_admin_tool_url('import-csv');
        $edit_link .= '&acf_field=' . $field['key'];
        $edit_link .= '&post=' . $field['_acf_post_id'];
        echo "<div style=\"margin-top: 8px;\" class=\"acf-actions\"><a class=\"acf-button button button-primary\" href=\"{$edit_link}\" data-event=\"import-csv\">Import Values from CSV</a></div>";
    }

    public function get_repeaters() {
        $repeaters = [];

        $groups = acf_get_field_groups();

        foreach ($groups as $group) {
            $fields = acf_get_fields($group);
            $group_name = $group['title'];
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if ($field['type'] === 'repeater') {
                        $repeaters[$field['key']] = $field['label'] . " (Group: $group_name)";
                    }
                }
            }
        }

        return $repeaters;
    }

}

add_action('acf/init', function() {
    global $acf_csv;
    // Init plugin
    $acf_csv = new ACF_CSV();
});
   
function acf_csv() { global $acf_csv; return $acf_csv; }

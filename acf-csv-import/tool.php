<?php

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists('ACF_Admin_Tool_CSV') ) :

class ACF_Admin_Tool_CSV extends ACF_Admin_Tool {


	/**
	*  initialize
	*
	*  This function will initialize the admin tool
	*
	*  @date	10/10/17
	*  @since	5.6.3
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function initialize() {

		// vars
		$this->name = 'import-csv';
		$this->title = __("Import Repeater Values from CSV", 'acf');
    	$this->icon = 'dashicons-upload';

	}


	/**
	*  html
	*
	*  This function will output the metabox HTML
	*
	*  @date	10/10/17
	*  @since	5.6.3
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function html() {

        $field = @$_GET['field'];
        $post = @$_GET['post'];

		?>
		<p><?php _e('Select the Repeater CSV file you would like to import. When you click the import button below, <b>ACF CSV Import</b> will import the field values based on the column names.', 'acf'); ?></p>
		<div class="acf-fields">
			<?php

            acf_render_field_wrap(array(
				'label'		=> __('Field', 'acf'),
				'type'		=> 'select',
                'wrapper'   => [
                    'style' => is_string($field) ? "display:none;" : ""
                ],
				'name'		=> 'acf_field',
				'value'		=> $field,
                'choices'   => array_merge( ['' => 'Select Field'], acf_csv()->get_repeaters() )
			));

            acf_render_field_wrap(array(
				'label'		=> __('Post ID', 'acf'),
				'type'		=> 'text',
                'wrapper'   => [
                    'style' => is_string($post) ? "display:none;" : ""
                ],
				'name'		=> 'post_id',
				'value'		=> $post
			));

			acf_render_field_wrap(array(
				'label'		=> __('Select CSV File', 'acf'),
				'type'		=> 'file',
				'name'		=> 'acf_import_file',
				'value'		=> false,
				'uploader'	=> 'basic',
			));

			?>
		</div>
		<p class="acf-submit">
			<input type="submit" class="button button-primary" value="<?php _e('Import File', 'acf'); ?>" />
		</p>
		<?php

	}


	/**
	*  submit
	*
	*  This function will run when the tool's form has been submit
	*
	*  @date	10/10/17
	*  @since	5.6.3
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function submit() {

		// Increase timeout to avoid script failing
		set_time_limit(0);

		// Check file size.
		if( empty($_FILES['acf_import_file']['size']) ) {
			return acf_add_admin_notice( __("No file selected", 'acf'), 'warning' );
		}

		// Get file data.
		$file = $_FILES['acf_import_file'];

		// Check errors.
		if( $file['error'] ) {
			return acf_add_admin_notice( __("Error uploading file. Please try again", 'acf'), 'warning' );
		}

		// Check file type.
		if( pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv' ) {
			return acf_add_admin_notice( __("Incorrect file type", 'acf'), 'warning' );
		}

		// Read CSV.
		$csv = file( $file['tmp_name'] );
		$csv = array_map('str_getcsv', $csv);

		// Check if empty.
    	if( !$csv || !is_array($csv) ) {
    		return acf_add_admin_notice( __("Import file empty", 'acf'), 'warning' );
    	}

        $field_key = $_POST['acf_field'];
        $post_id = $_POST['post_id'];

        if (empty($post_id)) {
    		return acf_add_admin_notice( __("Post ID is invalid!", 'acf'), 'warning' );
        }

        // Find header row and sanitize names
        $head_row = array_map(function($name) {
            return str_replace("'", '', preg_replace('/[^a-zA-Z0-9\']/', '_', strtolower( $name )));
        }, array_shift($csv));

        // Check if header row is accurate.
        // The check_header function will convert the header names into keys (row passed by reference)
    	if( !$head_row || !is_array($head_row) || !acf_csv()->check_header($head_row, $field_key, $post_id) ) {
    		return acf_add_admin_notice( __("Header row does not corrospond to the repeater sub-fields.", 'acf'), 'warning' );
    	}

        // Loop rows
        foreach ($csv as $row) {
            $values = [];

            // Loop columns
            foreach ($row as $col_index => $column) {
                $field = $head_row[$col_index];
                $values[$field] = trim($column);
            }

            add_row($field_key, $values, $post_id);
        }


    	// Count number of imported field groups.
		$total = count($csv);

		// Generate text.
		$text = sprintf( _n( 'Imported 1 row', 'Imported %s rows', $total, 'acf' ), $total );

		// Add notice
		acf_add_admin_notice( $text, 'success' );
	}
}

// initialize
acf_register_admin_tool( 'ACF_Admin_Tool_CSV' );

endif; // class_exists check

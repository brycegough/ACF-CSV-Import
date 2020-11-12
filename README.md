# ACF-CSV-Import
Import CSV Fields into ACF Repeaters

Tool to import repeater values from CSV files into ACF

### Usage
* Install the plugin by copying the folder to your plugins directory, or instal via a ZIP file.
* Go to Custom Fields -> Tools.
* Choose the repeater field from the dropdown and enter the post ID or "options" for option fields.
* Select the CSV file and click on the import button.

*This could take a while if you have a large CSV file, the plugin will automatically set the timeout to infinite to avoid errors.*

### Add "Import from CSV" button to fields
You can use the following filter to add a button to the fields:

    add_filter('acf/csv_import_fields', function($fields) {
        $fields[] = 'field_key';
        return $fields;
    });
    
*Just replace field_key with the ACF field key of the field you would like the button to appear on.*

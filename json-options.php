<?php
/**
 * @package json-options
 * @version 0.0.1
 */
/*
Plugin Name: JSON Options
Plugin URI: http://wordpress.org/extend/plugins/json-options/
Description: Import and Export Wordpress Options to JSON with filters
Author: JeremyJanrain
Version: 0.0.1
Author URI: https://github.com/Jeremy-Janrain/
*/
class jsonOptions {
  public static $name = 'json_options';
  protected $fields;
  protected $postMessage;
  protected $action;
  protected $filters;
  protected $filter;
  protected $json;
  protected $queries;
  /**
   * Initializes plugin, builds array of fields to render.
   */
  function  __construct() {
    $this->postMessage = array( 'class' => '', 'message' => '' );
    $this->fields = array(
      // main fields
      array(
        'name' => self::$name . '_filter_fields',
        'title' => 'Manage Filters',
        'type' => 'title',
        'screen' => 'main',
      	'description' => 'Manage Filters for Imports and Exports.',
      ),
      array(
		'name' => self::$name . '_filter_remove',
		'title' => 'Remove Filter',
		'description' => 'Choose a filter to remove.',
		'type' => 'select',
		'default' => array('janrain_capture','rpx'),
      	'selected' => 'disabled',
		'options' => get_option(self::$name . '_filters_available'),
		'screen' => 'main',
	   ),
       array(
		'name' => self::$name . '_filter_add',
		'title' => 'Add Filter',
		'description' => 'Type a filter to add',
		'type' => 'text',
		'default' => '',
		'screen' => 'main',
	   ),
     // import screen
      array(
        'name' => self::$name . '_filter_fields',
        'title' => 'Import Filter Settings',
        'type' => 'title',
        'screen' => 'import',
      ),
      array(
        'name' => self::$name . '_filters_enabled',
        'title' => 'Enable Filters',
        'description' => 'This enables import filters. Disabling filters imports <b>all</b> options from the JSON.',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'import',
      ),
      array(
		'name' => self::$name . '_filters',
		'title' => 'Available Filters',
		'description' => 'Choose filters to apply to your Import',
		'type' => 'multiselect',
		'default' => array('janrain_capture'),
		'options' => get_option(self::$name . '_filters_available'),
		'screen' => 'import',
	   ),
      array(
        'name' => self::$name . '_filter_combine',
        'title' => 'Combine Type',
        'description' => 'How to Combine multiple filters',
        'type' => 'select',
        'default' => 'AND',
        'options' => array('AND', 'OR'),
        'screen' => 'import',
      ),
     array(
        'name' => self::$name . '_filter_compare',
        'title' => 'Compare Method',
        'description' => 'How to Compare filter(s)',
        'type' => 'select',
        'default' => 'contains',
        'options' => array('contains', 'starts with', 'ends with', 'equal to',),
        'screen' => 'import',
      ),
      array(
        'name' => self::$name . '_import_fields',
        'title' => 'Import Settings',
        'type' => 'title',
        'screen' => 'import',
      ),
      array(
        'name' => self::$name . '_upload',
        'title' => 'Import File',
        'description' => 'Upload a settings file to import',
        'type' => 'file',
        'default' => '',
        'options' => '',
        'screen' => 'import',
      ),
     // export screen
      array(
        'name' => self::$name . '_filter_fields',
        'title' => 'Export Filter Settings',
        'type' => 'title',
        'screen' => 'export',
      ),
      array(
        'name' => self::$name . '_filters_enabled',
        'title' => 'Enable Filters',
        'description' => 'This enables export filters. Disabling filters exports <b>all</b> WordPress options (very handy for moving a site).',
        'default' => '1',
        'type' => 'checkbox',
        'screen' => 'export',
      ),
      array(
		'name' => self::$name . '_filters',
		'title' => 'Available Filters',
		'description' => 'Choose filters to apply to your Import or Export',
		'type' => 'multiselect',
		'default' => array('janrain_capture'),
		'options' => get_option(self::$name . '_filters_available'),
		'screen' => 'export',
	   ),
      array(
        'name' => self::$name . '_filter_combine',
        'title' => 'Combine Type',
        'description' => 'How to Combine multiple filters',
        'type' => 'select',
        'default' => 'AND',
        'options' => array('AND', 'OR'),
        'screen' => 'export',
      ),
     array(
        'name' => self::$name . '_filter_compare',
        'title' => 'Compare Method',
        'description' => 'How to Compare filter(s)',
        'type' => 'select',
        'default' => 'contains',
        'options' => array('contains', 'starts with', 'ends with', 'equal to',),
        'screen' => 'export',
      ),
       array(
        'name' => self::$name . '_export_fields',
        'title' => 'Export Settings',
        'type' => 'title',
        'screen' => 'export',
      ),
       array(
        'name' => self::$name . '_action',
        'title' => 'Action',
        'description' => 'Export a Preview to the screen or Download the JSON file.',
        'type' => 'select',
        'default' => 'Preview',
        'options' => array('Preview', 'Download' ),
        'screen' => 'export',
      ),
    );
    if ( $_POST ) {
    	$this->on_post();
    }
    if ( is_multisite() ) {
      add_action( 'network_admin_menu', array( &$this, 'admin_menu' ) );
      if ( ! is_main_site() )
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
    } else {
      add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
    }
  }
  /**
   * Method bound to register_activation_hook.
   */
  function activate() {
    foreach ( $this->fields as $field ) {
      if ( ! empty( $field['default'] ) ) {
        if ( get_option( $field['name'] ) === false)
          update_option( $field['name'], $field['default'] );
      }
    }
  }
  /**
   * Method bound to the admin_menu action.
   */
  function admin_menu() {
    $optPage = add_menu_page(
    	__( 'JSON Options' ), __( 'JSON Options' ),
    	'manage_options', self::$name . '', array( &$this, 'main' )
    );
    $exportPage = add_submenu_page(
          self::$name . '', __( 'JSON Options' ), __( 'Export' ),
          'manage_options', self::$name . '_export', array( &$this, 'export' )
    );
    $importPage = add_submenu_page(
          self::$name . '', __( 'JSON Options' ), __( 'Import' ),
          'manage_options', self::$name . '_import', array( &$this, 'import' )
    );
  }
  /**
   * Method bound to the JSON Options main menu.
   */
  function main() {
    $args = new stdClass;
    $args->title  = 'JSON Options Settings';
    $args->action = 'main';
    $this->print_admin( $args );
  }
  /**
   * Method bound to the JSON Options import menu.
   */
  function import() {
    $args = new stdClass;
    $args->title  = 'Import WP Options from JSON';
    $args->action = 'import';
    $this->print_admin( $args );
  }
  /**
   * Method bound to the JSON Options export menu.
   */
  function export() {
    $args = new stdClass;
    $args->title  = 'Export WP Options to JSON';
    $args->action = 'export';
    $this->print_admin( $args );
  }
  /**
   * Method to print the admin page markup.
   *
   * @param stdClass $args Object with page title and action variables
   */
  function print_admin( $args ) {
  	$name = self::$name;
    $nonce = wp_nonce_field( $name . '_action' );
    echo <<<HEADER
<div id="message" class="{$this->postMessage['class']} fade">
  <p><strong>
    {$this->postMessage['message']}
  </strong></p>
</div>
<div class="wrap">
  <h2>{$args->title}</h2>
  <form method="post" id="{$name}_{$args->action}" enctype="multipart/form-data">
    <table class="form-table">
      <tbody>
HEADER;
    foreach ( $this->fields as $field ) {
      if ( $field['screen'] == $args->action ) {
        $this->print_field( $field );
      }
    }
    echo <<<FOOTER
		</tbody>
	</table>
	$nonce
	<p class="submit">
		<input type="submit" class="button-primary" value="Submit" />
	</p>
	</form>
</div>
FOOTER;
  }
  /**
   * Method to print field-level markup.
   *
   * @param array $field
   *   A structured field definition with strings used in generating markup.
   */
  function print_field( $field ) {
    if ( is_multisite() && ! is_main_site() )
      $value = ( get_option( $field['name'] ) !== false ) ? get_option( $field['name'] ) : $field['default'];
    else {
	    $default = isset( $field['default'] ) ? $field['default'] : false;
	    $value   = get_option( $field['name'] );
	    $value   = ( $value !== false ) ? $value : $default;
    }
    $r = ( isset( $field['required'] ) && $field['required'] == true ) ? ' <span class="description">(required)</span>' : '';
    switch ( $field['type'] ) {
    	case 'title':
    		echo@ <<<TITLE
        <tr>
          <th>
            <h3 class="title">{$field['title']}</h3>
          </th>
         <td>
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
TITLE;
        break;
      case 'text':
        echo <<<TEXT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:200px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
TEXT;
        break;
      case 'long-text':
        echo <<<LONGTEXT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="text" name="{$field['name']}" value="$value" style="width:400px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
LONGTEXT;
        break;
      case 'textarea':
        echo <<<TEXTAREA
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <span class="description">{$field['description']}</span><br/>
            <textarea name="{$field['name']}" rows="10" cols="80">$value</textarea>
          </td>
        </tr>
TEXTAREA;
        break;
      case 'password':
        echo <<<PASSWORD
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="password" name="{$field['name']}" value="$value" style="width:150px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
PASSWORD;
        break;
        case 'file':
        echo <<<FILE
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="file" name="{$field['name']}" style="width:300px" />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
FILE;
        break;
      case 'select':
      	if ( ! $field['options'] ) { $field['options'] = $value; }
        if (isset($field['selected']) && $field['selected'] == 'disabled') {
        	$field['options'][] = '';
        }
        sort( $field['options'] );
        echo <<<SELECT
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
              <select name="{$field['name']}">
SELECT;
            foreach ( $field['options'] as $option ) {
        	  if ($field['selected'] != 'disabled') {
        	  	 $selected = ( $value == $option ) ? ' selected="selected"' : '';
        	  } else {
        	  	 $selected = ( $option == '' ) ? ' selected="selected" disabled="disabled"' : '';
        	  }
              echo "<option value=\"{$option}\"{$selected}>$option</option>";
            }
            echo <<<ENDSELECT
              </select>
              <span class="description">{$field['description']}</span>
          </td>
        </tr>
ENDSELECT;
        break;
      case 'multiselect':
      	if ( ! $field['options'] ) { $field['options'] = $value; }
        sort( $field['options'] );
        $size = count( $field['options'] );
        echo <<<MSELECT
        <tr>
          <th><label for="{$field['name']}[]">{$field['title']}$r</label></th>
          <td valigequal to"top">
              <select name="{$field['name']}[]" multiple="multiple" size="$size" >
MSELECT;
            foreach ( $field['options'] as $option ) {
              $selected = in_array( $option, $value ) !== false ? ' selected="selected"' : '';
              echo "<option value=\"{$option}\"{$selected}>$option</option>";
            }
            echo <<<MENDSELECT
              </select>
              {$field['description']}
          </td>
        </tr>
MENDSELECT;
        break;
      case 'checkbox':
        $checked = ($value == '1') ? ' checked="checked"' : '';
        echo <<<CHECKBOX
        <tr>
          <th><label for="{$field['name']}">{$field['title']}$r</label></th>
          <td>
            <input type="checkbox" name="{$field['name']}" value="1"$checked />
            <span class="description">{$field['description']}</span>
          </td>
        </tr>
CHECKBOX;
        break;
      case 'hidden':
        echo <<<HIDDEN
            <input type='hidden' name="{$field['name']}" value="$value"></input>
HIDDEN;
        break;
    }
  }
  /**
   * Method to import/export, receive, and store submitted options when posted.
   */
  public function on_post() {
  	global $wpdb;
  	$this->filters = isset( $_POST[self::$name . '_filters'] ) ? $_POST[self::$name . '_filters'] : '';
  	$this->filter = isset( $_POST[self::$name . '_filters_enabled'] ) ? true : false;
  	// importing
  	if( $_FILES && $_FILES[self::$name .'_upload'] ) {
  		$this->json_import();
  	}
  	// exporting
  	if ( isset($_POST[self::$name . '_action']) ) {
  		$this->action = $_POST[self::$name . '_action'];
  		if( $this->action ) {
  			$this->json_export();
  		}
  	// managing filters
  	} else {
  		$this->filters = get_option( self::$name . '_filters_available' );
  		if ( ! is_array( $this->filters ) ) {
  			$this->filters = array('janrain_capture','rpx');
  		}
  		if (isset($_POST[self::$name . '_filter_add']) && $_POST[self::$name . '_filter_add'] != '') {
  			//validate
  			$filter = $_POST[self::$name . '_filter_add'];
  			$valid = array( '-', '_' );
  			if( ! ctype_alnum( str_replace( $valid, '0', $filter ) ) ){
  				$this->postMessage = array( 'class' => 'error', 'message' => "Invalid filter format [a-zA-Z09-_]" );
  			} else {
  				// valid filter lets add it
  				array_push( $this->filters, $filter );
  				update_option( self::$name . '_filters_available', $this->filters );
  				$this->postMessage = array( 'class' => 'updated', 'message' => "Filter Added: $filter" );
  			}
  		}
  	  	if ( isset( $_POST[self::$name . '_filter_remove'] )
  	  	         && $_POST[self::$name . '_filter_remove'] != '' ) {
  			//remove filter
  			$filter = $_POST[self::$name . '_filter_remove'];
  			$this->filters = array_diff($this->filters, array($filter));
  			update_option(self::$name . '_filters_available', $this->filters);
  			$this->postMessage = array( 'class' => 'updated', 'message' => "Filter Removed: $filter" );
  		}
  	}
  }
  /**
   * Save the settings/filters used
   */
  private function save_fields() {
      foreach ( $this->fields as $field ) {
        if ( isset( $_POST[$field['name']] ) ) {
          $value = $_POST[$field['name']];
          update_option( $field['name'], $value );
        } else {
          if ( $field['type'] == 'checkbox' ) {
            $value = '0';
            update_option( $field['name'], $value );
          } else {
            if (get_option( $field['name'] ) === false
              && isset($field['default'])
              && (!is_multisite() || is_main_site()))
              update_option( $field['name'], $field['default'] );
          }
        }
      }
    }
    /**
     * Import json into database
     */
    private function json_import() {
    	global $wpdb;
    	$where = '';
    	if ( $_FILES[self::$name . "_upload"]["error"] > 0 ) {
    		$this->postMessage = array( 'class' => 'error', 'message' => 'Import Failed - ' . $_FILES["file"]["error"] );
	 	} else {
			$this->json = file_get_contents($_FILES[self::$name . "_upload"]['tmp_name']);
	  	}
		$this->json = json_decode( $this->json, true );
		if ( $this->json == null ) {
			$this->json = 'Invalid JSON';
		}
  		$count = 0;
    	$err = false;
    	if ( is_string( $this->json ) ) {
    		$err = $this->json;
    	} else {
    		foreach ( $this->json as $k => $v ) {
	      		$k = mysql_real_escape_string($k);
	   	  		$v = mysql_real_escape_string($v);
	   	  		$combine = $_POST[self::$name . '_filter_combine'];
	   		 	$compare = $_POST[self::$name . '_filter_compare'];
	   		 	$comp = $this->get_compare( $k, $compare );
	   			if ( $this->filter == true ) {
	   				foreach ( $this->filters as $filter ) {
	   					$comp = $this->get_compare( $filter, $compare, $k );
	   					if ( $comp === true ) {
	   						$comp = $this->get_compare( $k, $compare );
	   					}
	   		 		}
    		 	}
    		 	if ( $comp ) {
	    		 	$query = "UPDATE wp_options
	  			  			  SET option_value='$v'
	  			  			  WHERE option_name $comp";
	    		 	$this->queries[] = $query;
	  			  	$success = $wpdb->query( $query );
	  			  	if ( $success === false ) {
	  			  		echo $wpdb->last_error;
	  			  		$err = true;
	  			  	}
    		 	}
    		}
    	}
    	if ( ! $err && $this->queries != null ) {
    		$count = count( $this->queries );
   			$this->postMessage = array( 'class' => 'updated', 'message' => "Import Success - $count Fields Updated" );
   		} elseif ( ! $err ) {
   			$this->postMessage = array( 'class' => 'updated', 'message' => "Nothing to Import - Check Filters" );
   		} else {
   			$this->postMessage = array( 'class' => 'updated', 'message' => 'Import Failed - '.$wpdb->last_error.$err);
   		}
     	$this->save_fields();
  	}
  	/**
  	 * Export to json for screen and file output
  	 */
  	private function json_export() {
  		global $wpdb;
  		$where = "";
 		if($this->filter == true) {
 			$combine = $_POST[self::$name . '_filter_combine'];
 			$compare = $_POST[self::$name . '_filter_compare'];
 			$comp = $this->get_compare($this->filters[0], $compare);
  			$where .= " WHERE ";
  			$where .=  "option_name $comp";
	  		if ( count( $this->filters ) > 1 ) {
	  		  	foreach ( $this->filters as $filter ) {
	 			  	if ($this->filters[0] != $filter) {
	 			  		$comp = $this->get_compare($filter, $compare);
 			  	 		$where .=  " $combine option_name $comp";
	  			  	}
	  		  	}
	  		}
  		}
	    $options = $wpdb->get_results( "SELECT * FROM $wpdb->options$where ORDER BY option_name;" );
	    if ( ! $options ) {
	    	$arr = array( 'error'=>'no results. check filters. AND?' );
	    }
    	foreach($options as $option) {
    		$arr[$option->option_name] = $option->option_value;
    	}
    	$this->json =  $this->indent( json_encode( $arr ) );
		switch ($this->action) {
	    	case 'Download':
	    		header( 'Content-Disposition: attachment; filename="' . self::$name . '_' . date('Y-m-d_h.i.s', time()) . '_WP-' . get_bloginfo('version') . '.json"');
	    		header( 'Content-type: text/json');
	    		header( 'Content-Length: ' . mb_strlen( $this->json ) );
	    		header( 'Connection: close');
	    	case 'Preview':
	    		if ( $this->action == 'Preview' ){
	    			echo "<pre>";
	    		}
	    		echo $this->json;
	    		$this->save_fields();
	    		exit;
	    		break;
		}
  	}
	/**
	 * Indents a flat JSON string to make it more human-readable.
	 *
	 * @author dave perrett
	 * @see http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
	 *
	 * @param string $json The original JSON string to process.
	 *
	 * @return string Indented version of the original JSON string.
	 */
	private function indent( $json ) {
	    $result      = '';
	    $pos         = 0;
	    $strLen      = strlen( $json );
	    $indentStr   = '  ';
	    $newLine     = "\n";
	    $prevChar    = '';
	    $outOfQuotes = true;
	    for ( $i=0; $i<=$strLen; $i++ ) {
	        // Grab the next character in the string.
	        $char = substr( $json, $i, 1 );
	        // Are we inside a quoted string?
	        if ($char == '"' && $prevChar != '\\') {
	            $outOfQuotes = !$outOfQuotes;
	        // If this character is the end of an element,
	        // output a new line and indent the next line.
	        } else if ( ( $char == '}' || $char == ']' ) && $outOfQuotes ) {
	            $result .= $newLine;
	            $pos --;
	            for ($j=0; $j<$pos; $j++) {
	                $result .= $indentStr;
	            }
	        }
	        // Add the character to the result string.
	        $result .= $char;
	        // If the last character was the beginning of an element,
	        // output a new line and indent the next line.
	        if ( ( $char == ',' || $char == '{' || $char == '[' ) && $outOfQuotes ) {
	            $result .= $newLine;
	            if ( $char == '{' || $char == '[' ) {
	                $pos ++;
	            }
	            for ( $j = 0; $j < $pos; $j++ ) {
	                $result .= $indentStr;
	            }
	        }
	        $prevChar = $char;
	    }
	    return $result;
	}
	private function get_compare( $filter, $compare, $type = 'sql' ) {
		switch ( $compare ) {
			case 'ends with':
				if ($type == 'sql') {
					$compare = "LIKE '%$filter'";
				} else {
					$compare = ( substr_compare( $type, $filter, -strlen( $filter ), strlen( $filter ) ) === 0 ) ? true : false;
				}
				break;
			case 'starts with':
				if ($type == 'sql') {
					$compare = "LIKE '$filter%'";
				} else {
					$compare = ( stripos( $type, $filter ) == 0 )  ? true : false;
				}
			 	break;
			case 'equal to':
				if ($type == 'sql') {
					$compare = " = '$filter'";
				} else {
					$compare = ($type == $filter);
				}
			 	break;
			default:
				if ($type == 'sql') {
					$compare = "LIKE '%$filter%'";
				} else {
					$compare = (stripos($type, $filter) !== false) ? true : false;
				}
				break;
		}
		return $compare;
	}
}
add_action( 'init', jsonOptions::$name . '_init' );
function json_options_init() {
	$jsonOptions = new jsonOptions;
}
?>

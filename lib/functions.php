<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MDSM_FX_Error {
	
	var $errors = array();
	var $error_data = array();

	function __construct($code = '', $message = '', $data = '')
	{
		if (empty($code))	return;
		$this->errors[$code][] = $message;
		if (!empty($data)) $this->error_data[$code] = $data;
	}

	function get_error_codes()
	{
		if (empty($this->errors)) return array();
		return array_keys($this->errors);
	}

	function get_error_code()
	{
		$codes = $this->get_error_codes();
		if (empty($codes)) return '';
		return $codes[0];
	}

	function get_error_messages($code = '')
	{
		if (empty($code))
		{
			$all_messages = array();
			foreach((array)$this->errors as $code => $messages)
				$all_messages = array_merge($all_messages, $messages);

			return $all_messages;
		}

		if (isset($this->errors[$code])) return $this->errors[$code];
		else return array();
	}

	function get_str_error_messages($code = '')
	{
		if (empty($code))
		{
			$str_messages = '';
			$all_messages = array();
			foreach((array)$this->errors as $code => $messages) {
				$str_messages = implode('<br>', $messages);
				$all_messages = array_merge($all_messages, $messages);
			}
			return $str_messages;
		}

		if (isset($this->errors[$code])) return $this->errors[$code];
		else return array();
	}

	function get_error_message($code = '')
	{
		if (empty($code)) $code = $this->get_error_code();
		$messages = $this->get_error_messages($code);
		if (empty($messages)) return '';
		return $messages[0];
	}

	function get_error_data($code = '')
	{
		if (empty($code)) $code = $this->get_error_code();

		if (isset($this->error_data[$code])) return $this->error_data[$code];
		return null;
	}

	function add($code, $message, $data = '')
	{
		$this->errors[$code][] = $message;
		if (!empty($data)) $this->error_data[$code] = $data;
	}

	function add_data($data, $code = '')
	{
		if (empty($code)) $code = $this->get_error_code();
		$this->error_data[$code] = $data;
	}
	
	function is_empty()
	{
		if(count($this->errors)) return false;
		else return true;
	}
}

function mdsm_print($a,$title = false) 	{
	if($title) echo '<p><strong>'.$title.'</strong></p>';
	echo '<pre>'.print_r($a,true).'</pre>';
}

function mdsm_show_select_options($options,$value,$label,$selected=0,$echo=true,$show_default=true) 	{
	$out = '';
	
	if (count($options)) {
		if ($show_default) {
						$s = $selected === 0 ? ' selected="selected"' : '';
							$out .= '<option value="0"'.$s.' >Please select</option>';
					}

		foreach($options as $k=>$v)
		{
			$opt_value = $value == '' ? $k : $v[$value];
			$opt_label = $label == '' ? $v : $v[$label];
			$s = $selected == $opt_value ? ' selected="selected"' : $s = '';
			$out .= '<option value="'.esc_attr($opt_value).'"'.$s.'>'.esc_attr($opt_label).'</option>';
		}			
		
	} else $out .= '<option value="0">No items</option>';
	
	if($echo) echo $out;
	else return $out;
}

function mdsm_is_fx_error($object) {
	if (is_object($object) && is_a($object,'MDSM_FX_Error')) return true;
	return false;
}

function mdsm_sitemap_array_merge_recursive_ex(array & $array1, array & $array2) {
  $merged = $array1;

  foreach ($array2 as $key => & $value) {
    if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
      $merged[$key] = mdsm_sitemap_array_merge_recursive_ex($merged[$key], $value);
    } else {
      $merged[$key] = $value;
    }
  }

  return $merged;
}

function mdsm_array_sanitize($array) {
  $new_array = [];
  foreach ( $array as $key => $value ) {
    if (is_array($value)) {
      mdsm_array_sanitize($value);
    } else {
      $key = sanitize_key($key);
      switch ($key) {
        case 'color':
          $value = sanitize_hex_color_no_hash($value);
          break;
        default:
          $value = sanitize_text_field($value);
			}
			if ($key) {
				$new_array[$key] = $value;
			}
    }
  }
  return $new_array;
}
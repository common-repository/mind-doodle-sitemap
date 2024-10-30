<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('MDSM_DFX_API_OBJECT')) {
  class MDSM_DFX_API_OBJECT {
    function __construct($token='', $token_type='', $base_url) {
      $this -> http_codes = array(100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
      );

      $this-> api_url = $base_url;
      if ($token && $token_type) {
        $this -> set_token($token, $token_type);
      }
    }

    public function set_token($token, $token_type) {
      $auth_string = $token_type . ' ' . $token;
      $this -> httpHeader[] = 'Authorization: '.$auth_string;
      $this -> httpHeader[] = 'FlexiDB-Request-Encryption: disabled';
    }

    private function initCurlObject($additional_params) {
      $this -> curlObj = curl_init();

      curl_setopt($this -> curlObj, CURLOPT_HEADER, false);
      curl_setopt($this -> curlObj, CURLOPT_AUTOREFERER, true);
      curl_setopt($this -> curlObj, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($this -> curlObj, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($this -> curlObj, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($this -> curlObj, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($this -> curlObj, CURLOPT_CONNECTTIMEOUT, 120);


      if ($additional_params) {
        foreach ($additional_params as $key => $value) {
          curl_setopt($this -> curlObj, $key, $value);
        }
      }
    }

    public function execRequest($endpoint, $httpMethod, $httpData = '', $curl_params = '') {
      $endpoint = str_replace('\\', '/', $endpoint);

      // if($httpURL[0] != '/') {
      //   $endpoint = '/'.$endpoint;
      // }
      
      $httpURL = $this->api_url.$endpoint;

      $httpMethod = strtolower($httpMethod);

      if (!in_array($httpMethod, array('get', 'post', 'put', 'delete'))) {
        return new MDSM_FX_Error(__METHOD__, 'Invalid HTTP method');
      }

      $this -> initCurlObject($curl_params);

      if (!is_array($httpData)) {
        parse_str($httpData, $httpData);
      }
      
      $files = array();

      foreach($httpData as $key => $value) {
        $httpData[$key] = is_array($value) ? json_encode($value) : $value;
      }

      if ($httpMethod != 'post') {
        $httpData = http_build_query($httpData);
      }
      
      if ($httpMethod == 'post' && !empty($files)) {
        $httpData = array_merge($httpData, $files);
      }
      
      switch ($httpMethod) {
        case 'get':
          $this -> _get($httpData, $httpURL);			
          break;
        case 'post':
          $this -> _post($httpData);
          break;
        case 'put':
          $this -> _put($httpData);
          break;
        case 'delete':
          $this -> _delete($httpData);
          break;
      }

      curl_setopt($this -> curlObj, CURLOPT_HTTPHEADER, $this -> httpHeader);
      
      curl_setopt($this -> curlObj, CURLOPT_URL, $httpURL);
      $raw_result = curl_exec($this -> curlObj);
      $info = curl_getinfo($this -> curlObj);

      if ($info['http_code'] !== 200) {
        if (array_key_exists($info['http_code'], $this -> http_codes)) {
          $errors = json_decode($raw_result, true);
            $error = new MDSM_FX_Error('http_code', '<b>'.$this -> http_codes[$info['http_code']].'</b>');
          if (isset($errors['errors'])) {
            $errors = $errors['errors'];
            foreach($errors as $err) {
              $error -> add('http_code', $err[0]);
            }
          }
        } else {
            $error = new MDSM_FX_Error('http_code', 'Can\'t get response from server');
        }
        return $error;
    }

      $result = json_decode($raw_result, true);
      if (isset($result['errors'])) {
        foreach ($result['errors'] as $key=>$value) {
            $result = new MDSM_FX_Error($key, $value[0]);
        }
      }

      if (mdsm_is_fx_error($result)) {
          return $result;
      }

      if ($curl_error = curl_error($this -> curlObj)) {
        add_log_message('curl_error', $curl_error);
        return new MDSM_FX_Error('curl_error', $curl_error);
        die();
      }

      if ($raw_result === NULL || $raw_result === '') {
          return new MDSM_FX_Error(__METHOD__, _('Empty result'));
      }

      return $result;
    }

    private function _get($data = NULL, &$url) {
      curl_setopt($this -> curlObj, CURLOPT_HTTPGET, true);
  
      if($data != NULL) {
        if(is_array($data)) {

          $data = http_build_query($data, 'arg');
        }
        else {
          parse_str($data, $tmp);
          $data = "";
          $first = true;
          foreach($tmp as $k => $v) {
            if(!$first) {
              $data .= "&";
            }
            $data .= $k . "=" . urlencode($v);
            $first = false;
          }
        }
        $url .= "?".$data;
      }
    }
  
    private function _post($data = NULL) {
      curl_setopt($this -> curlObj, CURLOPT_POST, true);
      curl_setopt($this -> curlObj, CURLOPT_BINARYTRANSFER, true);
      curl_setopt($this -> curlObj, CURLOPT_POSTFIELDS, $data);
    }
  
    private function _put($data = NULL) {
      curl_setopt($this -> curlObj, CURLOPT_PUT, true);
      $resource = fopen('php://temp', 'rw');
      $bytes = fwrite($resource, $data);
      rewind($resource);

      if($bytes !== false) {
        $this -> httpHeader[] = 'X-HTTP-Method-Override: PUT';
        curl_setopt($this -> curlObj, CURLOPT_INFILE, $resource);
        curl_setopt($this -> curlObj, CURLOPT_INFILESIZE, $bytes);
      }
      else {
        throw new Exception('Could not write PUT data to php://temp');
      }
    }
  
    private function _delete($data = null) {
      curl_setopt($this -> curlObj, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($this -> curlObj, CURLOPT_PUT, true);

      if($data != null) {
        $resource = fopen('php://temp', 'rw');
        $bytes = fwrite($resource, $data);
        rewind($resource);

        if($bytes !== false) {
          curl_setopt($this -> curlObj, CURLOPT_INFILE, $resource);
          curl_setopt($this -> curlObj, CURLOPT_INFILESIZE, $bytes);
        }
        else {
          throw new Exception('Could not write DELETE data to php://temp');
        }
      }

    }
  }
}
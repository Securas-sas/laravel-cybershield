<?php

namespace Securas\LaravelCyberShield\Api;
use Securas\LaravelCyberShield\Helpers\Utils;


/**
 *
 * @package CyberShield\Api
 *
 * @version 1.0.0
 * @author  Securas
 *
 * @license GPLv3
 * @license https://gnu.org/licenses/gpl.html
 *
 */
class CyberShieldApi
{
  /** @var object Curl connection */
  public $hr;

  /** @var array Last HTTP request type */
  public $request_data = null;

  /** @var integer|null HTTP status code if different from 200 and 201 */
  public $errorCode = null;

  /** @var boolean True if HTTP status code is 200 or 201 */
  public $request_ok = false;

  /** @var boolean Set to false  get an object instead of an array */
  public $return_array = true;

  /**
   * @var array|mixed|string
   */
  public $apiKey;

  /** @var string API location */
  public $uri;

  /** @var string Last HTTP request type */
  public $request_type = '';

  /** @var string Last HTTP request resource */
  public $request_resource = '';
  /**
   * @var string
   */
  public $response = '';

  /**
   *
   */
  public function __construct()
  {
    $this->apiKey  = Utils::getApiKey();
    $this->uri      = Utils::getApiUrl();
    $this->hr = new  HttpRequest('Securas-Laravel-WAF');
    $this->hr->setHeaders('Content-Type', 'application/json');
    $this->hr->setHeaders('User-Agent', 'Securas-WAF');
  }

  /**
   * @return string
   */
  public function getUri()
  {

    return $this->uri;
  }

    /**
     * @param string $resource
     * @param null $data
     * @return array|string
     * @throws \Exception
     */
  public function get($resource = '', $data = null)
  {

    $this->request_type = 'GET';
    $this->request_resource = $this->uri . $resource;


    $response = $this->hr->get($this->request_resource);
    return $this->_handleRequest($response);

  }

  /**
   * @param string $result
   *
   * @return array|null
   * @internal
   *
   */
  protected function _handleLegacyRequest($result)
  {
    $this->errorCode = null;
    $code = $this->hr->getCode();
    if ($code === 200 or $code === 201) {
      return json_decode($result);
    } else {
      $this->errorCode = $this->hr->getCode();

      return null;
    }
  }

  /**
   *
   * @param string $resource
   * @param array|object $data
   *
   * @return array|null
   * @uses CyberShieldApi::_handleLegacyRequest
   */
  public function post($resource = '', $data = null, $files = null)
  {
    if (strpos($resource, '/') !== 0) {
      $resource = "$resource";
    }

    $this->request_type = 'POST';
    $this->request_resource = $this->uri . $resource;
    $this->request_data = $this->setRequestData($data);
    if ($files === null) {
      return $this->_handleRequest($this->hr->post($this->uri . $resource, ($data), true));
    } else {
      return $this->_handleRequest($this->hr->postFile($this->uri . $resource, $data, $files));
    }
  }

  /**
   * @param string $resource
   * @param array|object $data
   *
   * @return array|null
   * @uses CyberShieldApi::_handleLegacyRequest
   */
  public function put($resource = '', $data = null)
  {
    if (strpos($resource, '/') !== 0) {
      $resource = "/$resource";
    }
    $this->request_type = 'PUT';
    $this->request_resource = $this->uri . $resource;
    $this->request_data = $data;

    return $this->_handleRequest($this->hr->put($this->uri . $resource, $data));
  }

  /**
   * @param string $resource
   *
   * @return array|null
   * @uses CyberShieldApi::_handleLegacyRequest
   */
  public function delete($resource = '')
  {

    if (strpos($resource, '/') !== 0) {
      $resource = "/$resource";
    }
    $this->request_type = 'DELETE';
    $this->request_resource = $this->uri . $resource;
    $this->request_data = null;

    return $this->_handleRequest($this->hr->delete($this->uri . $resource));
  }

  /**
   * @param $token
   */
  public function oauth($token)
  {

    $this->hr->setOAuthToken($token);
  }

  /**
   * @param $data
   * @return mixed
   */
  public function setRequestData($data)
  {
    $data['api'] = $this->apiKey;
    return $data;
  }


  /**
   * @param string $result JSON data object
   *
   * @return array|string
   * @internal
   *
   */
  protected function _handleRequest($result)
  {

    $this->request_ok = false;
    if (empty($this->hr->error)) {
      $code = intval($this->hr->getCode());
      $response = [
        'http_code' => $code,
        'response' => json_decode($result, true)
      ];
    } else {
      $response = [
        'error' => 'curl_error',
        'http_code' => $this->hr->error['error']['code'],
        'error_description' => $this->hr->error['error']['error_description'],
        'details' => []
      ];
    }

    return $response;
  }


  /**
   * Repeat the last HTTP request
   *
   * Convenience method to repeat the last request made
   *
   * @return array|string
   * @uses CyberShieldApi::_handleRequest
   */
  public function handleRequest($request_type)
  {
    switch ($request_type) {
      case 'GET':
        return $this->_handleRequest($this->hr->get($this->request_resource, $this->request_data));
        break;
      case 'POST':
        return $this->_handleRequest($this->hr->post($this->request_resource, $this->request_data));
        break;
      case 'PUT':
        return $this->_handleRequest($this->hr->put($this->request_resource, $this->request_data));
        break;
      case 'DELETE':
        return $this->_handleRequest($this->hr->delete($this->request_resource));
        break;
      default:
        trigger_error('No request has been made to repeat', E_USER_ERROR);
    }
  }

  /**
   * Returns an array with HTTP request's complete status information
   *
   * @return array
   */
  public function getStatus()
  {
    return $this->hr->status();
  }

  /**
   * Get an HTTP field or all of them
   *
   * Returns an array with HTTP request's headers if called without argument.
   * Supply an argument - an HTTP header field - to get its value. NULL is
   * returned if the field is invalid.
   *
   * @param string|null $field Http field name
   *
   * @return array|string|null
   */
  public function headers($field = null)
  {
    return $this->hr->getHeader($field);
  }

    /**
     * @return mixed
     * @throws \Exception
     */
  public function get_options()
  {
    $this->request_type = 'GET';
    $this->request_resource = $this->uri . 'api/options/get-options/' . $this->apiKey;
    $response = $this->hr->get($this->request_resource);
    $apiResponse = $this->_handleRequest($response);
    if (in_array($apiResponse['http_code'], [200, 201])) {
      return $apiResponse['response'];

    }
    return array();

  }

    /**
     * @return mixed
     * @throws \Exception
     */
  public function get_settings()
  {

    $this->request_type = 'GET';
    $this->request_resource = $this->uri . 'api/settings/get-settings/' . $this->apiKey;
    $response = $this->hr->get($this->request_resource);
    $apiResponse = $this->_handleRequest($response);
    if (in_array($apiResponse['http_code'], [200, 201])) {
      return $apiResponse['response'];

    }
    return array();
  }

  /**
   * @return mixed|void
   */
  public function log($type)
  {
    $query_string = $_SERVER['QUERY_STRING'];
    $decoded_query_string = urldecode($query_string);

    $agent = Utils::useragent($_SERVER['HTTP_USER_AGENT']);
    $ip = Utils::psec_get_realip();

    if ($ip == "::1") {
      $ip = "127.0.0.1";
    }

    $ip_data = Utils::ip_details($ip, $_SERVER['HTTP_USER_AGENT']);

    $country_code = Utils::ip_country($ip, $_SERVER['HTTP_USER_AGENT']);
    $country = Utils::ip_country_name($country_code);
    $region = $ip_data['region'];
    $city = $ip_data['city'];
    $latitude = $ip_data['latitude'];
    $longitude = $ip_data['longitude'];
    $isp = $ip_data['isp'];
    $page = $_SERVER['REQUEST_URI'];
    // Vérifie si la chaîne de requête contient "wp-cron.php"
    if (strpos($_SERVER['REQUEST_URI'], 'wp-cron.php') !== false) {
      return;
    }

    // Ajoutez cette condition pour vérifier l'URL
    if (strpos($_SERVER['REQUEST_URI'], 'wc-ajax=get_refreshe') !== false) {
      return;
    }

    $site_url = Utils::get_site_url();
    $domain = str_replace(['http://', 'https://'], '', $site_url);
    $logData = array(
      "apiKey" => $this->api_key,
      'ip' => $ip,
      'page' => $page,
      'query' => strip_tags(addslashes($decoded_query_string)),
      'type' => $type,
      'browser' => $agent->browser['title'],
      'browser_code' => $agent->browser['code'],
      'os' => $agent->os['title'],
      'os_code' => $agent->os['code'],
      'country' => $country,
      'country_code' => $country_code,
      'region' => $region,
      'city' => $city,
      'latitude' => $latitude,
      'longitude' => $longitude,
      'isp' => $isp,
      'useragent' => $_SERVER['HTTP_USER_AGENT'],
      'referer_url' => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null,
      'domain' => $domain,

    );

    $resource = 'api/log/logEntry';
    if (strpos($resource, '/') !== 0) {
      $resource = "$resource";
    }

    $this->request_type = 'POST';
    $this->request_resource = $this->uri . $resource;
    $this->request_data = $this->setRequestData($logData);
    $response = $this->_handleRequest($this->hr->post($this->uri . $resource, $logData, true));


    return $response;

  }


}

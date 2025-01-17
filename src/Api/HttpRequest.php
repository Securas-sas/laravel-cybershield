<?php

namespace  Securas\LaravelCyberShield\Api;

/**
 * @package Securas\LaravelCyberShield\Api\HttpRequest
 *
 * @version 1.0.0
 * @author  Securas
 *
 * @license GPLv3
 * @license https://gnu.org/licenses/gpl.html
 *
 */
class HttpRequest implements HttpRequestInterface
{
    /** @var array Headers of the last request made */
    public $current_headers = [];
    /** @var array Headers to be sent with the next request. Shape: $key => $value */
    public $send_headers = [];
    /** @var object Curl connection */
    public $curl;
    /** @var array Default curl options */
    public $curl_opts_default = [
        CURLOPT_AUTOREFERER => false,
        CURLOPT_FORBID_REUSE => false,
        CURLOPT_FRESH_CONNECT => false,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    ];
    /** @var array Curl options */
    public $curl_opts = [];
    /** @var array Curl error */
    public $error = [];


    /**
     * Default constructor
     *
     * @param string $ua User agent
     * @param null $opts
     * @throws \ErrorException
     */
    public function __construct($ua = '-', $opts = null)
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }
        $this->curl = curl_init();
        $this->curl_opts_default[CURLOPT_DNS_USE_GLOBAL_CACHE] = false;
        $this->curl_opts_default[CURLOPT_DNS_CACHE_TIMEOUT] = 2;

        $this->curl_opts_default[CURLOPT_SSL_VERIFYPEER] = false;
        $this->curl_opts_default[CURLOPT_SSL_VERIFYHOST] = 2;


        $this->curl_opts_default[CURLOPT_USERAGENT] = $ua;
        $this->curl_opts_default[CURLOPT_HEADERFUNCTION] = $this->_makeCallback();
        if (is_array($opts)) {
            $this->curl_opts_default = array_merge($this->curl_opts_default, $opts);
        }
        curl_setopt_array($this->curl, $this->curl_opts_default);
    }


    /**
     * Default destructor
     *
     * @return integer
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }


    /**
     * Reset curl
     *
     * @return object HttpRequest ($this)
     */
    public function resetCurl()
    {
        if (function_exists('curl_reset')) {
            curl_reset($this->curl);
        } else {
            curl_close($this->curl);
            $this->curl = curl_init();
        }
        curl_setopt_array($this->curl, $this->curl_opts_default);
        return $this;
    }


    /**
     * Make a get request
     *
     * @param string $url
     * @param array|null $data
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    public function get($url, $data = null)
    {

        /**
         * PHP versions inferior to 5.5.11 have a bug what does not reset the
         * 'CURLOPT_CUSTOMREQUEST' when setting it to null, but to an empty string.
         * Bad request error ensues.
         */

        if ((version_compare(PHP_VERSION, '5.5.11', '<') or defined('HHVM_VERSION'))) {
            if (isset($this->curl_opts[CURLOPT_CUSTOMREQUEST])) {
                $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'GET';
            }
        }
        $this->curl_opts[CURLOPT_URL] = $data === null ? $url : $url . '?' . http_build_query($data, '', '&');
        $this->curl_opts[CURLOPT_HTTPGET] = true;
        if (!empty($this->send_headers)) {
            $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();
        }

        $this->_setOptions();

        return $this->_exec();
    }


    /**
     * Make a post request
     *
     * This method does not support file uploads.
     * 'Content-Type' is either 'application/json' or
     * 'application/x-www-form-urlencoded'
     *
     * @param string $url
     * @param array|object|null $data
     * @param boolean $is_json
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    public function post($url, $data = null, $is_json = false)
    {
        /**
         * If $data is "url encoded" the content type is "application/x-www-form-
         * urlencoded", otherwise, if the data passed is an array the content type
         * will be "multipart/form-data;boundary=------------------------a83e...."
         */
        if ($is_json) {
            $this->setHeaders('Content-Type', 'application/json');
            $send_data = json_encode($data);
        } else {
            if (is_array($data) or is_object($data)) {
                $send_data = http_build_query($data, '', '&');
            } else {
                $send_data = http_build_query([$data], '', '&');
            }
        }

        /**
         * PHP versions inferior to 5.5.11 have a bug what does not reset the
         * 'CURLOPT_CUSTOMREQUEST' when setting it to null, but to an empty string.
         * Bad request error ensues.
         */
        if ((version_compare(PHP_VERSION, '5.5.11', '<') or defined('HHVM_VERSION'))) {
            if (isset($this->curl_opts[CURLOPT_CUSTOMREQUEST])) {
                $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'POST';
            }
        }
        $this->curl_opts[CURLOPT_URL] = $url;
        $this->curl_opts[CURLOPT_POST] = true;
        $this->curl_opts[CURLOPT_POSTFIELDS] = $send_data;
        if (!empty($this->send_headers)) {
            $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();
        }

        $this->_setOptions();

        return $this->_exec();
    }


    /**
     * Make a put request
     *
     * This method does not support file uploads.
     * 'Content-Type' is either 'application/json' or
     * 'application/x-www-form-urlencoded'
     *
     * @param string $url
     * @param array|object|null $data
     * @param boolean $is_json
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    public function put($url, $data = null, $is_json = false)
    {
        if ($is_json) {
            $this->setHeaders('Content-Type', 'application/json');
            $send_data = json_encode($data);
        } else {
            if (is_array($data) or is_object($data)) {
                $send_data = http_build_query($data, '', '&');
            } else {
                $send_data = http_build_query([$data], '', '&');
            }
        }
        $this->curl_opts[CURLOPT_URL] = $url;
        $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $this->curl_opts[CURLOPT_POSTFIELDS] = $send_data;
        if (!empty($this->send_headers)) {
            $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();
        }

        $this->_setOptions();
        $this->curl_opts[CURLOPT_CUSTOMREQUEST] = null;

        return $this->_exec();
    }


    /**
     * Make a delete request
     *
     * @param string $url
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    public function delete($url)
    {
        $this->curl_opts[CURLOPT_URL] = $url;
        $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if (!empty($this->send_headers)) {
            $this->curl_opts[CURLOPT_HTTPHEADER] = $this->_prepareHeaders();
        }

        $this->_setOptions();
        $this->curl_opts[CURLOPT_CUSTOMREQUEST] = null;

        return $this->_exec();
    }


    /**
     * Upload a file
     *
     * @param string $url
     * @param array $data Post data in key => value form
     * @param array $files Files to upload in the following form:
     * [
     *   'image' => [
     *     [
     *       'file' => '/path/to/file',
     *       'type' => 'image/jpeg'
     *     ],
     *     [
     *       'file' => '/path/to/second/file',
     *       'type' => 'image/jpeg'
     *     ]
     *   ]
     * ]
     * The 'type' key is optional. If not set, 'type' will be 'application/octet-stream'.
     * @return string
     * @throws \Exception
     * @throws \Exception
     */
    public function postFile($url, $data, $files)
    {
        if ((version_compare(PHP_VERSION, '5.5.11', '<') or defined('HHVM_VERSION'))) {
            if (isset($this->curl_opts[CURLOPT_CUSTOMREQUEST])) {
                $this->curl_opts[CURLOPT_CUSTOMREQUEST] = 'POST';
            }
        }

        $files_array = [];
        foreach ($files as $name => $input) {
            if (count($input) > 1) {
                foreach ($input as $key => $file) {
                    if (!file_exists($file['file'])) {
                        $this->error = $this->_handleError('file_not_found', 'The file to upload was not found');
                        return '';
                    }
                    $files_array["{$name}[$key]"] = $this->_createCurlFile($file);
                }
            } else {
                if (!file_exists($input[0]['file'])) {
                    $this->error = $this->_handleError('file_not_found', 'The file to upload was not found');
                    return '';
                }
                $files_array["{$name}"] = $this->_createCurlFile($input[0]);
            }
        }

        if (version_compare(PHP_VERSION, '5.5.0', '>') and version_compare(PHP_VERSION, '5.6.0', '<')) {
            $this->curl_opts[CURLOPT_SAFE_UPLOAD] = true;
        }

        $this->curl_opts[CURLOPT_URL] = $url;
        $this->curl_opts[CURLOPT_POST] = true;
        if (is_array($data)) {
            $send_data = array_merge($data, $files_array);
        } else {
            $send_data = $files_array;
        }
        $this->curl_opts[CURLOPT_POSTFIELDS] = $send_data;

        $this->_setOptions();

        return $this->_exec();
    }


    /**
     * @param array $file Format:
     * [
     *   'file' => '/path/to/file',
     *   'type' => 'image/jpeg'
     * ]
     * The 'type' key is optional.
     *
     * @return string|CURLFile
     * @internal
     *
     * Returns a file to upload
     *
     */
    protected function _createCurlFile($file)
    {
        // PHP < 5.5.0 does not have the 'CURLFile' class, the '@' tag is used to prefix file paths
        if ((version_compare(PHP_VERSION, '5.5.0', '<'))) {
            return "@{$file['file']}" . (isset($file['type']) ? ";type={$file['type']}" : '');
        } else {
            $cfile = new \CURLFile($file['file']);
            if (isset($file['type'])) {
                $cfile->setMimeType($file['type']);
            }
            $cfile->setPostFilename(basename($file['file']));
            return $cfile;
        }
    }


    /**
     * Execute a curl request
     *
     * @return string
     */
    protected function _exec()
    {
        $result = curl_exec($this->curl);

        if ($result === false) {
            $this->error = $this->_handleError(curl_errno($this->curl), curl_error($this->curl));
            return '';
        } else {
            $this->error = [];
            return $result;
        }
    }


    /**
     * Get request status
     *
     * @return array
     */
    public function status()
    {
        print_r(curl_errno($this->curl));die;
        $return_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if ($return_code == 0) {
            return $this->_handleError(curl_errno($this->curl), curl_error($this->curl));
        } else {
            return curl_getinfo($this->curl);
        }
    }


    /**
     * @return array
     * @internal
     *
     * Check curl errors
     *
     */
    protected function _handleError($code, $error_description)
    {

        return [
            'error' => [
                'code' => $code,
                'error_description' => !empty($error_description) ? $error_description : $this->getCurlError($code)
            ]
        ];
    }


    /**
     * Set user agent
     *
     * @param string $ua User agent name
     * @return object HttpRequest ($this)
     */
    public function setUserAgent($ua)
    {
        curl_setopt($this->curl, CURLOPT_USERAGENT, $ua);
        return $this;
    }


    /**
     * Get all or a header field
     *
     * @param string $field Optional
     * @return array|string
     */
    public function getHeader($field = null)
    {
        if ($field !== null) {
            if (isset($this->current_headers[$field])) {
                return $this->current_headers[$field];
            } else {
                return null;
            }
        } else {
            return $this->current_headers;
        }
    }


    /**
     * Set headrs to send
     *
     * @param string $header_name
     * @param string $header_value
     * @return object HttpRequest ($this)
     */
    public function setHeaders($header_name = '', $header_value = '')
    {
        $this->send_headers[$header_name] = $header_value;
        return $this;
    }


    /**
     * @return array
     * @internal
     *
     * Convert headers array to right format
     *
     * The headers are stored in an array shaped as $key => $value pairs. This
     * enables replacement.
     * The Curl class expects the shape of the array to be:
     * <code>
     *   [
     *     'Content-type: application/json',
     *     'Cache-Control: no-cache, must-revalidate'
     *   ];
     * </code>
     * Hence this method.
     *
     */
    protected function _prepareHeaders()
    {
        $headers = [];
        foreach ($this->send_headers as $key => $value) {
            $headers[] = "$key: $value";
        }
        return $headers;
    }


    /**
     * Set basic authentication
     *
     * @param string $user
     * @param string $password
     * @return object HttpRequest ($this)
     */
    public function basicAuth($user, $password)
    {
        $this->curl_opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->curl_opts[CURLOPT_USERPWD] = "$user:$password";
        return $this;
    }


    /**
     * Unset basic authentication
     *
     * @return object HttpRequest ($this)
     */
    public function stopBasicAuth()
    {
        curl_setopt_array($this->curl, [
            CURLOPT_HTTPAUTH => null,
            CURLOPT_USERPWD => null
        ]);
        return $this;
    }


    /**
     * Set OAuth token
     *
     * @param string $token
     * @return object HttpRequest ($this)
     */
    public function setOAuthToken($token)
    {
        /**
         * Note: CURLOPT_XOAUTH2_BEARER doesn't work for now
         */
        $this->setHeaders('Authorization', "Bearer $token");
        return $this;
    }


    /**
     * Get last HTTP code
     *
     * @return integer
     */
    public function getCode()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }


    /**
     * @return function
     * @internal
     *
     * Get request's headers
     *
     * This method is called after the request is complete and builds an array with
     * headers fields.
     *
     */
    protected function _makeCallback()
    {
        return function ($ch, $header_data) {
            if (stripos($header_data, 'HTTP/') === false and $header_data !== "\r\n") {
                preg_match('/([A-z0-9-]+):\s*(.*)$/', $header_data, $matches);
                $this->current_headers[$matches[1]] = $matches[2];
            }
            return strlen($header_data);
        };
    }


    /**
     * Send result to file
     *
     * @param strinf $destination Path\to\file
     * @return object HttpRequest ($this)
     */
    public function toFile($destination)
    {
        $file = fopen($destination, 'w');
        curl_setopt($this->curl, CURLOPT_FILE, $file);
        return $this;
    }


    /**
     * Set a curl option
     *
     * @return integer
     */
    public function setOpt($key, $value)
    {
        curl_setopt($this->curl, $key, $value);
    }


    /**
     * @return void
     * @throws \Exception
     * @throws \Exception
     * @internal
     *
     * Sets headers in the Curl object
     *
     */
    protected function _setOptions()
    {
        if (!curl_setopt_array($this->curl, $this->curl_opts)) {
            throw new \Exception('Class CyberShield\HttpRequest: invalid option;');
        }
        $this->curl_opts = [];
    }

    public function getCurlError($code){

        $error_codes=array(
            1 => 'CURLE_UNSUPPORTED_PROTOCOL',
            2 => 'CURLE_FAILED_INIT',
            3 => 'CURLE_URL_MALFORMAT',
            4 => 'CURLE_URL_MALFORMAT_USER',
            5 => 'CURLE_COULDNT_RESOLVE_PROXY',
            6 => 'CURLE_COULDNT_RESOLVE_HOST',
            7 => 'CURLE_COULDNT_CONNECT',
            8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
            9 => 'CURLE_REMOTE_ACCESS_DENIED',
            11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
            13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
            14=>'CURLE_FTP_WEIRD_227_FORMAT',
            15 => 'CURLE_FTP_CANT_GET_HOST',
            17 => 'CURLE_FTP_COULDNT_SET_TYPE',
            18 => 'CURLE_PARTIAL_FILE',
            19 => 'CURLE_FTP_COULDNT_RETR_FILE',
            21 => 'CURLE_QUOTE_ERROR',
            22 => 'CURLE_HTTP_RETURNED_ERROR',
            23 => 'CURLE_WRITE_ERROR',
            25 => 'CURLE_UPLOAD_FAILED',
            26 => 'CURLE_READ_ERROR',
            27 => 'CURLE_OUT_OF_MEMORY',
            28 => 'CURLE_OPERATION_TIMEDOUT',
            30 => 'CURLE_FTP_PORT_FAILED',
            31 => 'CURLE_FTP_COULDNT_USE_REST',
            33 => 'CURLE_RANGE_ERROR',
            34 => 'CURLE_HTTP_POST_ERROR',
            35 => 'CURLE_SSL_CONNECT_ERROR',
            36 => 'CURLE_BAD_DOWNLOAD_RESUME',
            37 => 'CURLE_FILE_COULDNT_READ_FILE',
            38 => 'CURLE_LDAP_CANNOT_BIND',
            39 => 'CURLE_LDAP_SEARCH_FAILED',
            41 => 'CURLE_FUNCTION_NOT_FOUND',
            42 => 'CURLE_ABORTED_BY_CALLBACK',
            43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
            45 => 'CURLE_INTERFACE_FAILED',
            47 => 'CURLE_TOO_MANY_REDIRECTS',
            48 => 'CURLE_UNKNOWN_TELNET_OPTION',
            49 => 'CURLE_TELNET_OPTION_SYNTAX',
            51 => 'CURLE_PEER_FAILED_VERIFICATION',
            52 => 'CURLE_GOT_NOTHING',
            53 => 'CURLE_SSL_ENGINE_NOTFOUND',
            54 => 'CURLE_SSL_ENGINE_SETFAILED',
            55 => 'CURLE_SEND_ERROR',
            56 => 'CURLE_RECV_ERROR',
            58 => 'CURLE_SSL_CERTPROBLEM',
            59 => 'CURLE_SSL_CIPHER',
            60 => 'CURLE_SSL_CACERT',
            61 => 'CURLE_BAD_CONTENT_ENCODING',
            62 => 'CURLE_LDAP_INVALID_URL',
            63 => 'CURLE_FILESIZE_EXCEEDED',
            64 => 'CURLE_USE_SSL_FAILED',
            65 => 'CURLE_SEND_FAIL_REWIND',
            66 => 'CURLE_SSL_ENGINE_INITFAILED',
            67 => 'CURLE_LOGIN_DENIED',
            68 => 'CURLE_TFTP_NOTFOUND',
            69 => 'CURLE_TFTP_PERM',
            70 => 'CURLE_REMOTE_DISK_FULL',
            71 => 'CURLE_TFTP_ILLEGAL',
            72 => 'CURLE_TFTP_UNKNOWNID',
            73 => 'CURLE_REMOTE_FILE_EXISTS',
            74 => 'CURLE_TFTP_NOSUCHUSER',
            75 => 'CURLE_CONV_FAILED',
            76 => 'CURLE_CONV_REQD',
            77 => 'CURLE_SSL_CACERT_BADFILE',
            78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
            79 => 'CURLE_SSH',
            80 => 'CURLE_SSL_SHUTDOWN_FAILED',
            81 => 'CURLE_AGAIN',
            82 => 'CURLE_SSL_CRL_BADFILE',
            83 => 'CURLE_SSL_ISSUER_ERROR',
            84 => 'CURLE_FTP_PRET_FAILED',
            84 => 'CURLE_FTP_PRET_FAILED',
            85 => 'CURLE_RTSP_CSEQ_ERROR',
            86 => 'CURLE_RTSP_SESSION_ERROR',
            87 => 'CURLE_FTP_BAD_FILE_LIST',
            88 => 'CURLE_CHUNK_FAILED');
        return isset($error_codes[$code]) ?$error_codes[$code] :  'Curl Error';
    }
}

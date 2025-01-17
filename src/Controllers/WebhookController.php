<?php
namespace Securas\LaravelCyberShield\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Securas\LaravelCyberShield\Helpers\Utils;

/**
 * @author Securas
 * @since 1.0
 */
class WebhookController extends BaseController
 {

     /**
      * @param $provider
      * @return JsonResponse
      */
     public function siteInfo($provider) {

         $apikey = Utils::getApiKey();

         if ($apikey !== $provider) {
             header("HTTP/1.1 404 Not Found");
             $data =[
                 'method' => 'GET',
                 'success' => false,
                 'message' => trans('API key was incorrect')
             ];
             return new JsonResponse($data);
         }

         $serverInfo = [];
         $groups = [];
         if (function_exists('posix_getgroups') && function_exists('posix_getuid')) {
             $groups = posix_getgroups();
             $serverInfo['posix_getuid'] = posix_getuid();
         }
         $phpinfo    = Utils::phpInfoStructured();
         $extensions = Utils::getPhpExtensions();
         $memoryInfo = Utils::getServerMemory();
         $serverInfo['temp_dir'] = sys_get_temp_dir();  // Ajout de sys_get_temp_dir ici
         $serverInfo['php_in'] = ini_get_all();
         $storageInfo = Utils::getServerStorage();
         $host = gethostname();
         $serverInfo['url'] = Utils::getSiteUrl();
         $serverInfo['posix_getgroups'] = $groups;
         $serverInfo['ip'] = gethostbyname($host);
         $serverInfo['os'] = PHP_OS;
         $serverInfo['ver'] = phpversion();
         $serverInfo['mysql'] = mysqli_get_client_info();
         $serverInfo['soft'] = $_SERVER['SERVER_SOFTWARE'];
         $serverInfo['port'] = $_SERVER['SERVER_PORT'];
         if (isset($_SERVER['HTTPS']) &&
             ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
             isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
             $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
             $serverInfo['protocol'] = 'https://';
         } else {
             $serverInfo['protocol'] = 'http://';
         }
         // Checking the PHP SAPI
         $isCGI = (substr(php_sapi_name(), 0, 3) === 'cgi');
         $serverInfo['is_cgi'] = $isCGI;
         // Checking the cgi.force_redirect directive
         $cgiForceRedirect = ini_get('cgi.force_redirect');
         $serverInfo['cgi_force_redirect'] = $cgiForceRedirect;
         $_server = $_SERVER;
         $data= [
             'api_key' => $apikey,
             'PHP_INFO' => $phpinfo,
             'SERVER_INFO' => $serverInfo,
             '_SERVER' => $_server,
             'EXTENSION' => $extensions,
             'MEMORY_INFO' => $memoryInfo,
             'STORAGE_INFO' => $storageInfo,
             'audit' => [
                 'test_writable_document_root'   => Utils::writableDocumentRoot(),
                 'test_include_path_writable'    => Utils::includePathWritable(),
                 'test_sendmail_writable'        => Utils::sendMailWritable()
             ]
         ];
         $code= 200;
         $httpStatusText = "HTTP/1.1 $code";

         return new JsonResponse($data);
     }
 }

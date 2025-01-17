<?php
namespace  Securas\LaravelCyberShield\Helpers;
use Illuminate\Support\Facades\URL;
use  Securas\LaravelCyberShield\Api\CyberShieldApi;
use Symfony\Component\HttpFoundation\Request as globalRequest;

class Utils
{

  /**
   * @return string
   */
  public static function getApiUrl(){

    if ( config('cybershield.sandbox',false) ) {
         return 'https://cyber-test.securas.cloud/api/';
    }
    return 'https://shield.securas.cloud/api/';
  }
  /**
   * @param $value
   * @return void
   */
  public static function setRequestUnderAttack($value)
  {

    $request = globalRequest::createFromGlobals();
    $request->query->set('_isUnderAttack', $value);
  }

    /**
     * @return void
     */
  public static function getRequestUnderAttack()
  {

    $request = globalRequest::createFromGlobals();
    $request->query->get('_isUnderAttack');
  }

  /**
   * @return bool
   */
  public static function isApiKeySet()
  {
    return !empty(self::getApiKey());
  }

  /**
   * @return array|mixed|string
   */
  public static function getApiKey()
  {
    return config('cybershield.api_key') ?? '';
  }

    /**
     * @return array|mixed|string
     */
    public static function getEmailAddress()
    {
        return config('cybershield.email_address') ?? '';
    }
  /**
   * @return bool
   */
  public static function isAjax()
  {
    // Usage of ajax parameter is deprecated
    $request = request();
    $isAjax = $request->isXmlHttpRequest();
    if (isset($_SERVER['HTTP_ACCEPT'])) {
      $isAjax = $isAjax || preg_match(
          '#\bapplication/json\b#',
          $_SERVER['HTTP_ACCEPT']
        );
    }
    return $isAjax;
  }

  /**
   * @param $input
   * @return array|string|string[]|null
   */
  public static function cleanInput($input)
  {
    $search = array(
      '@<script[^>]*?>.*?</script>@si', // Strip out javascript
      '@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags
      '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
      '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments
    );

    $output = preg_replace($search, '', $input);
    return $output;
  }

  /**
   * @param $input
   * @return mixed
   */
  public static function sanitize($input)
  {
    if (is_array($input)) {
      $output = [];
      foreach ($input as $var => $val) {
        $output[$var] = self::sanitize($val);
      }
    } else {
      $output = '';
      if($input == NULL) {
        $input = '';
      }
      $input  = str_replace('"', "", $input);
      $input  = str_replace("'", "", $input);
      $input  = self::cleanInput($input);
      $output = htmlentities($input, ENT_QUOTES);
    }
    return $output;
  }

  /**
   * @return string
   */
  public static function getRealIp()
  {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
      $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
      $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
      $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
      $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
      $ipaddress = getenv('REMOTE_ADDR');
    else
      $ipaddress = $_SERVER['REMOTE_ADDR'];

    // Convertir ::1 (localhost IPv6) en 127.0.0.1
    if ($ipaddress == '::1') {
      $ipaddress = '127.0.0.1';
    }

    // Vérifier si l'adresse IP contient plusieurs adresses séparées par des virgules
    if (strpos($ipaddress, ',') !== false) {
      // Séparer les adresses IP et prendre seulement la première
      $ipaddress = explode(',', $ipaddress)[0];
    }

    // Supprimer les espaces blancs potentiels autour de l'adresse IP
    $ipaddress = trim($ipaddress);

    return $ipaddress;
  }

  /**
   * @param $url
   * @return void
   */
  public static function redirect($url)
  {
    if (self::isAjax()) {
      $errors[] = ('CyberShield Security Alert'); ?>
      <script>
        var redirect_url = "<?php echo $url ?>";
        console.log('CyberShield Security Alert....', redirect_url);
        alert('CyberShield Security Alert....');
        setTimeout(() => {
          window.location.href = redirect_url;
        }, 200);

      </script>
      <?php die('CyberShield Security Alert....');
    }
    header('Location: ' . $url);
  }

  /**
   * @return mixed
   */
  public static function getSiteUrl()
  {
    return URL::to('/');
  }

  /**
   * @return bool
   */
  public static function verifyApiKey()
  {
    $api_key      = self::getApiKey();
    $email_address= self::getEmailAddress();
    $site_url     = self::getSiteUrl();

    // Building the URL with query parameters
    $query_params = http_build_query([
      'api_key'       => $api_key,
      'email_address' => $email_address,
      'site_url'      => $site_url
    ]);
    $cyberShieldApi = new CyberShieldApi();
    $response       = $cyberShieldApi->get("api/checkApiKeyPlugin?" . $query_params);

    $response_code = $response['http_code'];
    if (!in_array($response_code, [200, 201])) {
      return false;
    } else {
      if ($response_code === 200) {
        return true;
      } else if ($response_code === 401) {
        return false;
      } else {
        return false;
      }
    }
    return false;
  }

  /**
   * @return string[]
   */
  public static function getServerMemory()
  {
    $free   = shell_exec('free -g | grep Mem | awk \'{print $4}\'');
    $total = shell_exec('free -g | grep Mem | awk \'{print $2}\'');

    // Assurer que les valeurs sont numériques
    $free = is_numeric($free) ? $free : 0;
    $total = is_numeric($total) ? $total : 0;

    return [
      'free' => $free . ' GB',
      'total' => $total . ' GB',
    ];
  }

  /**
   * @return string[]
   */
  public static function getServerStorage()
  {
    $directory = '/'; // racine du système de fichiers
    $free = disk_free_space($directory) / (1024 * 1024 * 1024); // convertir en Go
    $total = disk_total_space($directory) / (1024 * 1024 * 1024); // convertir en Go

    return [
      'free' => round($free, 2) . ' GB',
      'total' => round($total, 2) . ' GB',
    ];
  }

    /**
     * @return array
     */
  public static function phpInfoStructured(): array
  {
    ob_start();
    phpinfo();
    $info_arr = array();
    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
    $cat = "General";
    foreach ($info_lines as $line) {
      // new cat?
      preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
      if (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
        $info_arr[$cat][$val[1]] = $val[2];
      } elseif (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
        $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
      }
    }
    return $info_arr;
  }

  /**
   * @return string[]
   */
  public static function getPhpExtensions()
  {
    $output = shell_exec('php -m');
    $extensions = explode("\n", trim($output)); // Convertir la sortie en tableau
    return $extensions;
  }

  /**
   * @param $fn
   * @return bool
   */
  public static function isWritableOrChmodable($fn)
  {
    if (!extension_loaded("posix")) {
      return is_writable($fn);
    }
    $stat = stat($fn);
    if (!$stat) {
      return false;
    }
    $myuid = posix_getuid();
    $mygids = posix_getgroups();
    if ($myuid == 0 || $myuid == $stat['uid'] || in_array($stat['gid'], $mygids) && $stat['mode'] & 0020 || $stat['mode'] & 0002) {
      return true;
    }
    return false;
  }

  // Writable document root?

  /**
   * @return false[]
   */
  public static function writableDocumentRoot()
  {
    $arr = [
      'isWritable' => false,
      'isChmodable' => false
    ];

    if (is_writable($_SERVER['DOCUMENT_ROOT'])) {
      $arr['isWritable'] = true;
    } else if (self::isWritableOrChmodable($_SERVER['DOCUMENT_ROOT'])) {
      $arr['isChmodable'] = true;
    }

    return $arr;
  }

  /**
   * @return false[]
   */
  public static function includePathWritable()
  {
    $arr = [
      'isWritable' => false,
      'isChmodable' => false
    ];

    $result = 0;
    $checked = 0;
    foreach (explode(':', ini_get('include_path')) as $dir) {
      if ($dir === "") {
        continue;
      }
      $checked++;
      $absdir = realpath($dir);
      if ($absdir === false) {
        continue;
      } // path does not exist? -> ignore
      if (is_writable($absdir)) {
        $arr['isWritable'] = true;
        break;
      } else if (self::isWritableOrChmodable($absdir)) {
        $arr['isChmodable'] = true;
        break;
      }
    }

    return $arr;
  }

  /**
   * @return false[]
   */
  public static function sendMailWritable()
  {
    $arr = [
      'isWritable' => false,
      'isChmodable' => false
    ];

    $sm = ini_get('sendmail_path');
    if ($sm == "" || $sm === NULL) {
      return $arr;
    }
    $sm_chunks = explode(' ', $sm);

    $sm_executable = $sm_chunks[0];
    if (is_file($sm_executable) || is_link($sm_executable)) {
      if (is_writable($sm_executable)) {
        $arr['isWritable'] = true;
      }
    }
    if (self::isWritableOrChmodable(dirname($sm_executable)) || self::isWritableOrChmodable(dirname(realpath($sm_executable)))) {
      $arr['isChmodable'] = true;
    }

    return $arr;
  }

    public static function shaHash($string, $key, $check_hash = null)
    {
        $hmac = hash_hmac('sha256', $string, $key);

        if ($check_hash === null) {
            return $hmac;
        }

        // Preventing timing attacks
        if (function_exists('\hash_equals'/* notice the namespace */)) {
            return hash_equals($check_hash, $hmac);
        }

        // Preventing timing attacks for PHP < v5.6.0
        $len_hash = strlen($hmac);
        $len_hash_rcv = strlen($check_hash);
        if ($len_hash !== $len_hash_rcv) {
            return false;
        }
        $equal = true;
        for ($i = $len_hash - 1; $i !== -1; $i--) {
            if ($hmac[$i] !== $check_hash[$i]) {
                $equal = false;
            }
        }

        return $equal;
    }
}

?>

<?php

namespace Securas\LaravelCyberShield\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use cybershield\src\Api\CyberShieldApi;
use cybershield\src\Exceptions\CyberShieldProtectionException;
use cybershield\src\Helpers\Utils;


/**
 * Base CyberShield middleware that will detect and block
 * malicious requests coming to your application.
 *
 * @author: Securas
 */
class CyberShieldMiddleware {
    /**
     * @var cybershield\src\Api\CyberShieldApi
     */
    public $cyberShieldApi;

    public function __construct(CyberShieldApi $cyberShieldApi) {
        /**
         *
         */
        $this->cyberShieldApi = $cyberShieldApi;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next) {
        if ( !config('cybershield.enabled', false) ) {
            return $next($request);
        }
        $uri        = $request->path();
        $params     = $request->input();

        // Skip Patterns
        $patterns = config('cybershield.patterns', []);
        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }
        $siteUrl    = Utils::getSiteUrl();

        $domain     = str_replace(['http://', 'https://'], '', $siteUrl);
        $page       = $request->getRequestUri();
        $queryString = $request->getQueryString();
        $queryString = urldecode($queryString);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $request->getClientIp();

        $requestMethod = $request->getMethod();
        $postData = ($requestMethod === 'POST') ? $_POST : null;
        // Collect HTTP headers
        $headers = [];
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[$key] = $value;
            }
        }
        // Preparing data to send to the CyberShield API
        $request_data = [
            'ip'                => $ip,
            'user_agent'        => $userAgent,
            'query_string'      => $queryString,
            'request_method'    => $requestMethod,
            'body'              => $postData,
            'headers'           => $headers,
            'site_url'          => $siteUrl,
            'domain'            => $domain,
            'page'              => $page,

        ];
        $cyberShieldApi = new CyberShieldApi();
        $apiResponse    = $cyberShieldApi->post('log/logEntryTest', $request_data);


        if (!in_array($apiResponse['http_code'], [200, 201])) {
            return $next($request);
        }
        $result = $apiResponse['response'];
        // Check that result contains valid data
        if (!isset($result) || !is_array($result)) {
            error_log('Unexpected response from API.');
            return $next($request);
        }
        // Add security headers if options are enabled
        if (isset($result['option']['xss_protection']) && (int)$result['option']['xss_protection'] == 1) {
            header("X-XSS-Protection: 1");
        }

        if (isset($result['option']['hide_php_ver']) && (int)$result['option']['hide_php_ver'] == 1) {
            header('X-Powered-By: SECURAS');
        }
        if (isset($result['option']['clickjacking_protection']) && (int)$result['option']['clickjacking_protection'] == 1) {
            header("X-Frame-Options: sameorigin");
        }
        if (isset($result['option']['mimemis_protection']) && (int)$result['option']['mimemis_protection'] == 1) {
            header("X-Content-Type-Options: nosniff");
        }
        if (isset($result['option']['force_secure_conn']) && (int)$result['option']['force_secure_conn'] == 1) {
            header("Strict-Transport-Security: max-age=15552000; preload");
        }
        if (isset($result['option']['sanitize_input']) && (int)$result['option']['sanitize_input'] == 1) {
            // GET data disinfection
            $_POST      = Utils::sanitize($_POST);
            $_GET       = Utils::sanitize($_GET);
            $_REQUEST   = Utils::sanitize($_REQUEST);
            $_COOKIE    = Utils::sanitize($_COOKIE);
            //There is a problem with the sanitize  session
            if (isset($_SESSION)) {
                // $_SESSION = Utils::sanitize($_SESSION);
            }
        }

        if (isset($result['option']['allow_search_bots']) && (int)$result['option']['allow_search_bots'] == 0) {
            $botLists = array(
                'Googlebot', 'Baiduspider', 'YandexBot', 'ia_archiver',
                'R6_FeedFetcher', 'NetcraftSurveyAgent',
                'Sogou web spider', 'bingbot', 'Yahoo! Slurp',
                'facebookexternalhit', 'PrintfulBot', 'msnbot',
                'Twitterbot', 'UnwindFetchor', 'urlresolver'
            );

            foreach ($botLists as $botList) {
                if (stripos($userAgent, $botList) !== false) {
                    // return 404 before sending content to block indexing
                    header("X-Robots-Tag: noindex, nofollow", true);
                }
            }
        }
        // Manage restrictions if IP or country is blocked
        if (
            (isset($result['banIP']) && (int)$result['banIP'] == 1) ||
            (isset($result['blockCountry']) && (int)$result['blockCountry'] == 1) ||
            (isset($result['threat_detected']) && (int)$result['threat_detected'] == 1 &&
                (!isset($result['whiteIP']) || (int)$result['whiteIP'] === 0) &&
                (!isset($result['whiteCountry']) || (int)$result['whiteCountry'] == 0)
            )
        ) {
            $page_redirect_url = $result['setting'];
            // Settings Redirect
            if (!empty($page_redirect_url)) {
                return redirect()->away($page_redirect_url);
            }
        }
        return $next($request) ;
    }

}

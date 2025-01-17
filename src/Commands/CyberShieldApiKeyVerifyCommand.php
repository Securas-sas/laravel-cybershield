<?php

namespace Securas\LaravelCyberShield\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use cybershield\src\Api\CyberShieldApi;
use cybershield\src\Exceptions\CyberShieldProtectionException;
use cybershield\src\Helpers\Utils;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * This command checks the CyberShield API Key.
 *
 * @author: Securas
 */
class CyberShieldApiKeyVerifyCommand extends Command {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cyberchield:apikey_verify' ;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Verify the CyberShield API Key' ;

	/**
	 * Global command status.
	 *
	 * @var int
	 */
	protected $status ;

	/**
	 * Build the command instance and inject dependencies.
	 *
	 * @param cybershield\src\Api\CyberShieldApi $cyberShieldApi
	 */
	public function __construct(CyberShieldApi $cyberShieldApi) {
		parent::__construct() ;

        $this->cyberShieldApi = $cyberShieldApi ;
		// Command result status.
		$this->status = null ;
	}

	/**
	 * Report an error to the user.
	 *
	 * @param string $message
	 * @return void
	 */
	protected function report(string $message) {
		// Display the error to the CLI.
		$this->components->error($message) ;
		// And return an error status code.
		$this->status = CommandAlias::FAILURE ;
	}

    /**
     * @param string $message
     * @return void
     */
    protected function success(string $message) {
        // Display the success to the CLI.
        $this->components->success($message) ;
        // And return an success status code.
        $this->status = CommandAlias::SUCCESS ;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     * @throws \Exception
     */
	public function handle() {
        $config = config('cybershield');
        $api_key        = $config['api_key'];
        $email_address  = $config['email_address'];
        $site_url       = Utils::getSiteUrl();
        $query_params = http_build_query([
            'api_key'       => $api_key,
            'email_address' => $email_address,
            'site_url'      => $site_url
        ]);

        $response = $this->cyberShieldApi->get("api/checkApiKeyValide?" . $query_params);
        $response_code  = $response['http_code'];
        $result         = $response['response'];
        if ($response_code === 200 && isset($result['valid']) && (int)$result['valid'] == 1) {
           $config['key_verification'] = 'valid';
            $this->success("API key Valid") ;
        } else {
            $config['key_verification'] = 'invalid';
            $this->report($result['message']) ;
        }
        $contents = "<?php\n\nreturn " . var_export($config, true) . ";";
        if( File::put(base_path() . '/config/cybershield.php', $contents) ) {
            Artisan::call('config:clear');
        }

        return  $this->status;

    }

}

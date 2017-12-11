<?php 

namespace Shopify;
use GuzzleHttp\Client;


/**
* class Shopify
*/
class Shopify
{
	
	const AUTHORIZE_URL_FORMAT = 'https://{shop_domain}/admin/oauth/authorize?client_id={api_key}&scope={scopes}&redirect_uri={redirect_uri}&state={state}';
	const ACCESS_TOKEN_URL_FORMAT = 'https://{shop_domain}/admin/oauth/access_token';
	protected $shop_domain;
	protected $api_key;
	protected $app_secret;
  	private $access_token = '';
  	private $code;
  	private $state = '';
	

	function __construct($shop_domain, $api_key, $app_secret)
	{
		$this->shop_domain = $shop_domain;
		$this->api_key = $api_key;
		$this->app_secret = $app_secret;
		$this->http = new Client();
	}

	public function setState($state)
	{
		$this->state = $state;
	}

	public function getAccessToken($refresh = FALSE) {
		if (!$refresh && isset($this->access_token) && !empty($this->access_token)) {
			return $this->access_token;
		}

		if (!$this->validateInstall()) {
			return FALSE;
		}
		$data = [
			'client_id' => $this->api_key,
			'client_secret' => $this->app_secret,
			'code' => $this->code,
		];

		$domain_path = strtr(self::ACCESS_TOKEN_URL_FORMAT, ['{shop_domain}' => $this->shop_domain]);

		try {
			$response = $this->http->post( $domain_path,[
				'form_params' => $data,
		        'headers' => [
		            'encoding' => 'application/x-www-form-urlencoded'
		        ],
		        'verify'  =>  false
			]);
		} catch (ClientException $e) {
		  return FALSE;
		}
		$token = json_decode((string) $response->getBody(),true);
		return $token['access_token'];
	}

	public function hmacSignatureValid($params = [])
	{
		$hmac = $params['hmac'];
		unset($params['hmac']);
        $message = http_build_query($params);
		return hash_hmac('sha256', $message,$this->app_secret) == $hmac;
	}

	public function validateInstall($params = [])
	{
		if (empty($params)) {
			$params = $_GET;
		}

	    if (empty($params['state'])) {
			$this->state = '';
	    }

	    if (!empty($this->state) && $this->state != $params['state']) {
			return FALSE;
		}
		if (!$this->hmacSignatureValid($params)) {
			return FALSE;
		}
		$this->params = $params;
		$this->code = $this->params['code'];
		return TRUE;
	}

	public function authorizeUser($redirect_uri, array $scopes, $state) {
		$url = $this->formatAuthorizeUrl($this->shop_domain, $this->api_key, $scopes, $redirect_uri, $state);
		header("Location: $url");
		return $url;
	}

	private function formatAuthorizeUrl($shop_domain, $api_key, $scopes, $redirect_uri, $state) {
		return strtr(self::AUTHORIZE_URL_FORMAT, [
			'{shop_domain}' => $shop_domain,
			'{api_key}' => $api_key,
			'{scopes}' => implode(',', $scopes),
			'{redirect_uri}' => urlencode($redirect_uri),
			'{state}' => $state,
		]);
	}

}

?>
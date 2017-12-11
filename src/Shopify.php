<?php 

namespace Shopify;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
* class Shopify
*/
class Shopify
{
	
	const AUTHORIZE_URL_FORMAT = 'https://{shop_domain}/admin/oauth/authorize?client_id={api_key}&scope={scopes}&redirect_uri={redirect_uri}&state={state}';
	const ACCESS_TOKEN_URL_FORMAT = 'admin/oauth/access_token';
	protected $shop_domain;
	protected $api_key;
	protected $app_secret;
  	private $access_token = '';
  	private $code;
  	private $state = '';
  	public $base_url;


	

	function __construct($shop_domain, $api_key, $app_secret)
	{
		$this->shop_domain = $shop_domain;
		$this->base_url = 'https://'.$shop_domain;
		$this->api_key = $api_key;
		$this->app_secret = $app_secret;
		$this->http = new Client([
			'verify'  =>  false,
			'base_uri' => $this->base_url
		]);
	}

	public function setState($state)
	{
		$this->state = $state;
	}

	public function setAccessToken($token)
	{
		$this->access_token = $token;
		$this->http = new Client([
			'verify'  =>  false,
			'base_uri' => $this->base_url,
			'headers' => [
	            'X-Shopify-Access-Token' => $this->access_token
	        ]
	    ]);		
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

		$token = $this->responseFormPost(self::ACCESS_TOKEN_URL_FORMAT,$data);
		$this->setAccessToken($token['access_token']);
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

	public function getResponseBody($response)
	{
		return json_decode((string) $response->getBody(),true);
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

	/**
	 * getProducts
	 *
	 * @return Array
	 * @author vincenth520
	 **/
	public function getProducts()
	{
		return $this->returnGetPublicData('/admin/products.json');
	}

	/**
	 * getShopInfo
	 *
	 * @return Array
	 * @author vincenth520
	 **/
	public function getShopInfo()
	{		
		return $this->returnGetPublicData('/admin/shop.json');
	}

	/**
	 * getWebhooks
	 *
	 * @return Array
	 * @author vincenth520
	 **/
	public function getWebhooks()
	{
		return $this->returnGetPublicData('/admin/webhooks.json');
	}

	/**
	 * addWebhook
	 *
	 * add webhook
	 * 
	 * @return Array
	 * @author 
	 **/
	public function addWebhook($type,$address,$format='json')
	{
		$data_json = [
			'webhook' => [
				'topic' => $type,
				'address' => $address,
				'format' => $format
			]
		];
		return $this->responseJsonPost('/admin/webhooks.json',$data_json);
	}


	public function responseJsonPost($url,$data)
	{
		$body = [
			RequestOptions::JSON => $data,
	        'headers' => [
	            'encoding' => 'application/json'
	        ]
	    ];
	    return $this->returnPostPublicData($url,$body);
	}

	public function responseFormPost($url,$data)
	{
		$body = [
			'form_params' => $data,
	        'headers' => [
	            'encoding' => 'application/x-www-form-urlencoded'
	        ]
	    ];
	    return $this->returnPostPublicData($url,$body);
	}


	/**
	 * returnPostPublicData
	 * 
	 * @return Array
	 * @author 
	 **/
	public function returnPostPublicData($url,$body)
	{	
		try {			
			$response = $this->http->post($url,$body);	
		} catch (ClientException $e) {
		  return FALSE;
		}
		return $this->getResponseBody($response);
	}

	/**
	 * returnGetPublicData
	 * @params url
	 * @return Array
	 * @author 
	 **/
	public function returnGetPublicData($url)
	{
		try {			
			$response = $this->http->get($url);
		} catch (ClientException $e) {
		  return FALSE;
		}
		return $this->getResponseBody($response);
	}

}

?>
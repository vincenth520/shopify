# Shopify

## Setup/Installation
You can install it on packgist

`composer require vincenth520/shopify`


You must first setup a public app. View documentation. You need an authorization URL.

```php
require 'vendor/autoload.php';
session_start();
$APP_API_KEY = ''; //api key
$APP_SECRET = ''; //app secret

$client = new Shopify\Shopify($_GET['shop'], $APP_API_KEY, $APP_SECRET);
$_SESSION['client_id'] = time();
$random_state = $_SESSION['client_id'];

$rediect_url = 'http://localhost/shopify/shopify/redirect.php';  //this is your redirect url

$client->authorizeUser($rediect_url, [
  'read_products',
  'write_products',
], $random_state);

```

At this point, the user is taken to their store to authorize the application to use their information.
If the user accepts, they are taken to the redirect URL.

```php
session_start();
require 'vendor/autoload.php';
$APP_API_KEY = ''; //api key
$APP_SECRET = ''; //app secret


$client = new Shopify\Shopify($_GET['shop'], $APP_API_KEY, $APP_SECRET);
$client->setState($_SESSION['client_id']);
if ($token = $client->getAccessToken()) {
  $_SESSION['shopify_access_token'] = $token;
  $_SESSION['shopify_shop_domain'] = $_GET['shop'];
}
else {
  die('invalid token');
}
```

### Method

- get shop info
```php
$client = new Shopify\Shopify($_SESSION['shopify_shop_domain'], $APP_API_KEY, $APP_SECRET);
$client->getShopInfo();
```

- get webhook
```php
$client = new Shopify\Shopify($_SESSION['shopify_shop_domain'], $APP_API_KEY, $APP_SECRET);
$client->getWebhooks();
```

- add webhook
```php
$client = new Shopify\Shopify($_SESSION['shopify_shop_domain'], $APP_API_KEY, $APP_SECRET);
$type = 'collections/create'; //topics  [see:https://help.shopify.com/api/reference/webhook]
$address = 'https://baidu.com'; //address URIs
$format = 'json'; //json or xml
$client->addWebhook($type,$address,$format);
```
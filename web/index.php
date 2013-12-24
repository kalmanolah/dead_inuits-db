<?php
// Define root of the application as a named constant
define('APP_ROOT', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);

require_once APP_ROOT.'vendor/autoload.php';

$app = new Silex\Application();
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(APP_ROOT.'config/config.yml'));

// Initialize Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'    => APP_ROOT.'views',
    'twig.options' => array(
        'cache'        => APP_ROOT.'cache/twig/',
    ),
));

// Set debug to true if viewing locally
if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1'))) {
	$app['debug'] = true;
}

/**
 * Route: / => Front page
 */
$app->get('/', function () use ($app) {
	$json = file_get_contents('http://wallpapers.carroarmato0.be/v4/api/?request=getRandomImage&format=json');

	if ($json) {
		$obj  = json_decode($json);
		$imgs = $obj->Images;
		$img = $imgs[0]->Image;
		$img_url = $img->imageURL;

		$app->wallpaper_url = '//wallpapers.carroarmato0.be/'.$img_url;
	}

	if (isset($_GET['success'])) {
		$app->success = true;
	}

	$app->submit_url = preg_replace('/\?.*$/', 'submit', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

	return $app['twig']->render('index.html.twig');
});

/**
 * Route: /submit => Submit page
 */
$app->get('/submit', function () use ($app) {
	$fp = fopen(APP_ROOT.'output/drink_count.txt', 'c+');
	flock($fp, LOCK_EX);

	$count = (int)fread($fp, filesize(APP_ROOT.'output/drink_count.txt'));
	ftruncate($fp, 0);
	fseek($fp, 0);
	fwrite($fp, $count + 1);

	flock($fp, LOCK_UN);
	fclose($fp);

	$return_url = preg_replace('/\/submit.*$/', '?success=true', "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

	return $app->redirect($return_url);
});

$app->run();
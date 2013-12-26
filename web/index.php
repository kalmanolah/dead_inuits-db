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

	$app->submit_url = preg_replace('/\?.*$/', 'submit', "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

	return $app['twig']->render('index.html.twig');
});

/**
 * Route: /submit => Submit page
 */
$app->get('/submit', function () use ($app) {
	$user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;

	if (!$user) {
		$user = 'generic';
	}

	$file = APP_ROOT.'output/drink_count.txt';

	$contents = file_get_contents($file);

	$counts = explode("\n", $contents);
	if (!$counts) {
		$counts = array();
	}

	$user_counts = array();

	foreach ($counts as $count) {
		$exploded = explode('=', $count);

		if (count($exploded) != 2) {
			continue;
		}

		$user_counts[$exploded[0]] = intval($exploded[1]);
	}

	if (!array_key_exists($user, $user_counts)) {
		$user_counts[$user] = 0;
	}

	$user_counts[$user]++;

	$contents = '';

	foreach ($user_counts as $key => $user_count) {
		$contents .= $key."=".$user_count."\n";
	}

	file_put_contents($file, $contents, LOCK_EX);

	$return_url = preg_replace('/\/submit.*$/', '?success=true', "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

	return $app->redirect($return_url);
});

$app->run();
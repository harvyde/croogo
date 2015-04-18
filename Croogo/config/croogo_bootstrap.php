<?php

namespace Croogo\Croogo\Config;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

use Croogo\Croogo\Croogo;
use Croogo\Croogo\Cache\CroogoCache;
use Croogo\Croogo\Configure\CroogoJsonReader;
use Croogo\Croogo\CroogoStatus;
use Croogo\Croogo\Event\CroogoEventManager;
use Croogo\Extensions\CroogoPlugin;

/**
 * Default Acl plugin.  Custom Acl plugin should override this value.
 */
Configure::write('Site.acl_plugin', 'Acl');

/**
 * Default API Route Prefix. This can be overriden in settings.
 */
Configure::write('Croogo.Api.path', 'api');

/**
 * Admin theme
 */
//Configure::write('Site.admin_theme', 'sample');

/**
 * Cache configuration
 */
//debug(Configure::read());exit();
$defaultEngine = Cache::config('default')['className'];
$defaultPrefix = Configure::read('Cache.defaultPrefix');
$cacheConfig = array(
	'duration' => '+1 hour',
	'path' => CACHE . 'queries' . DS,
	'engine' => $defaultEngine,
	'prefix' => $defaultPrefix,
);
Configure::write('Croogo.Cache.defaultConfig', $cacheConfig);

/**
 * Settings
 */
Configure::config('settings', new CroogoJsonReader());
if (file_exists(APP . 'Config' . DS . 'settings.json')) {
	Configure::load('settings', 'settings');
}

/**
 * Locale
 */
Configure::write('Config.language', Configure::read('Site.locale'));

/**
 * Assets
 */
if (Configure::check('Site.asset_timestamp')) {
	$timestamp = Configure::read('Site.asset_timestamp');
	Configure::write(
		'Asset.timestamp',
		is_numeric($timestamp) ? (bool) $timestamp : $timestamp
	);
	unset($timestamp);
}

// CakePHP Acl
Plugin::load(['Acl' => ['autoload' => true]]);

$croogoPath = Plugin::path('Croogo/Croogo');

/**
 * Extensions
 */
Plugin::load(['Croogo/Extensions' => [
	'autoload' => true,
	'bootstrap' => true,
	'routes' => true,
	'path' => realpath($croogoPath . '..' . DS . 'Extensions') . DS,
]]);
Configure::load('Croogo/Extensions.events');

/**
 * List of core plugins
 */
$corePlugins = [
	'Settings', 'Acl', 'Blocks', 'Comments', 'Contacts', 'Menus', 'Meta',
	'Nodes', 'Taxonomy', 'Users', 'Wysiwyg', 'Ckeditor',
];
Configure::write('Core.corePlugins', $corePlugins);

/**
 * Plugins
 */
$aclPlugin = Configure::read('Site.acl_plugin');
$pluginBootstraps = Configure::read('Hook.bootstraps');
$plugins = array_filter(explode(',', $pluginBootstraps));

$plugins[] = 'Croogo/Users';

if (!in_array($aclPlugin, $plugins)) {
	$plugins = Hash::merge((array)$aclPlugin, $plugins);
}
foreach ($plugins as $plugin) {
	$pluginName = Inflector::camelize($plugin);
	$pluginPath = APP . 'Plugin' . DS . $pluginName;
	if ((!file_exists($pluginPath)) && (!strstr($plugin, 'Croogo/'))) {
		$pluginFound = false;
		foreach (App::path('Plugin') as $path) {
			if (is_dir($path . $pluginName)) {
				$pluginFound = true;
				break;
			}
		}
		if (!$pluginFound) {
			Log::error('Plugin not found during bootstrap: ' . $pluginName);
			continue;
		}
	}
	$option = array(
		$pluginName => array(
			'autoload' => true,
			'bootstrap' => true,
			'ignoreMissing' => true,
			'routes' => true,
		)
	);
	if (in_array($pluginName, $corePlugins)) {
		$option[$pluginName]['path'] = CroogoPlugin::path($plugin);
	}
	CroogoPlugin::load($option);
}
CroogoEventManager::loadListeners();
Croogo::dispatchEvent('Croogo.bootstrapComplete');
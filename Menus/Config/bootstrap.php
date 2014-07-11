<?php

use Cake\Core\Configure;
use Croogo\Croogo\Cache\CroogoCache;
use Croogo\Croogo\Croogo;

CroogoCache::config('croogo_menus', array_merge(
	Configure::read('Cache.defaultConfig'),
	array('groups' => array('menus'))
));

Croogo::hookComponent('*', 'Menus.Menus');

Croogo::hookHelper('*', 'Menus.Menus');

Croogo::mergeConfig('Translate.models.Link', array(
	'fields' => array(
		'title' => 'titleTranslation',
		'description' => 'descriptionTranslation',
	),
	'translateModel' => 'Menus.Link',
));

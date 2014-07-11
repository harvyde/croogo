<?php

use Cake\Core\Configure;
use Croogo\Croogo\Cache\CroogoCache;
use Croogo\Croogo\Croogo;

CroogoCache::config('contacts_view', array_merge(
	Configure::read('Cache.defaultConfig'),
	array('groups' => array('contacts'))
));

Croogo::mergeConfig('Translate.models.Contact', array(
	'fields' => array(
		'title' => 'titleTranslation',
		'body' => 'bodyTranslation',
	),
	'translateModel' => 'Contacts.Contact',
));

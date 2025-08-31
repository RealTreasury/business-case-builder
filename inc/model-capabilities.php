<?php
defined( 'ABSPATH' ) || exit;

/**
	* Model capability configuration.
	*
	* Lists models that lack support for specific features.
	*
	* @return array
	*/
return [
	'temperature' => [
		'unsupported' => [
			'gpt-4.1',
			'gpt-4.1-mini',
			'gpt-5',
			'gpt-5-mini',
		],
	],
];

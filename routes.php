<?php

return [
	// 'index.php' => 'view_index',
	'a/{id:[0-9]+}/' => ['inputfunc', 'index'],
	'users/{id:[0-9]+}/' => ['view1fun', 'index'],
	'u/:id/' => ['view1fun', 'index'],
	'' => ['view_index', 'index'],
	'view1' => ['view1fun', 'view1'],
	'part2' => ['view2fun', 'view2'],
	'view3' => ['inputfunc', 'view3'],
	'viewcls' => ['ViewCls', 'view3']
];

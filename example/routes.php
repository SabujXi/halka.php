<?php

return [
	// 'index.php' => 'view_index',
	'a/{id:[0-9]+}/' => ['inputfunc', 'index'],
	'users/{id:[0-9]+}/' => ['ViewCls', 'index'],
	'u/:id/' => ['view1fun', 'index'],
//	 '' => ['view_index', 'index'],
	'' => ['HomeViewer2', 'index'],
	'2' => ['HomeViewer2', 'index2'],
	'view1' => ['view1fun', 'view1'],
	'part2' => ['view2fun', 'view2'],
	'view3' => ['inputfunc', 'view3'],
	'viewcls' => ['ViewCls', 'view3']
];

<?php

function view_index($req, $resp){
	echo "this is view index";
}

function view1fun(HalkaRequest $req, HalkaResponse $resp){
	halka_load_view('view1');
	
}

function view2fun(HalkaRequest $req, HalkaResponse $resp){
	$fn = halka_get_view_file("view2");
	$name = "Somebody";
	include $fn;
	
}

function inputfunc(HalkaRequest $req, HalkaResponse $resp){
	$fn = halka_get_view_file("view3");
	include $fn;
}

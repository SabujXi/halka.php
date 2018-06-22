<?php

function view_index($req, $resp){
	var_dump($req);
    var_dump($resp);
	echo "this is view index";
}

function view1fun(HalkaRequest $req, HalkaResponse $resp){
	$fn = halka_get_view_file("view1");
	
	include $fn;
	
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
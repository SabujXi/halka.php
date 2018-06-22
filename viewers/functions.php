<?php

function view_index(Request $r){
	
	echo "this is view index";
}

function view1fun(Request $r){
	$fn = halka_get_view_file("view1");
	
	include $fn;
	
}


function view2fun(Request $r){
	$fn = halka_get_view_file("view2");
	$name = "Somebody";
	include $fn;
	
}
function inputfunc(Request $r){
	$fn = halka_get_view_file("view3");
	include $fn;
}
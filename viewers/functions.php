<?php

function view_index(Request $r){
	
	echo "this is view index";
}

function view_forbidden(Request $r){
	
	echo "You have entered into area 51 - don't access php files directly";
}

function view404(Request $r){
	
	echo "404 not found - u have entered wrong url";
}

function view1fun(Request $r){
	$fn = get_view_file("view1");
	
	include $fn;
	
}


function view2fun(Request $r){
	$fn = get_view_file("view2");
	$name = "Somebody";
	include $fn;
	
}
function inputfunc(Request $r){
	$fn = get_view_file("view3");
	include $fn;
}
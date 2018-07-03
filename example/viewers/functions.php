<?php

function view_index($req, $resp, $app){
    //var_dump($app);
	//echo "this is view index";
    $app->load_view('home');
}

function view1fun(HalkaRequest $req, HalkaResponse $resp){
	halka_load_view('view1');
	
}

function view2fun(HalkaRequest $req, HalkaResponse $resp){
	$name = "Somebody";
    halka_load_view('view2', $ctx=[
        'name' => $name
    ]);
	
}

function inputfunc(HalkaRequest $req, HalkaResponse $resp){
	halka_load_view("view3");
}

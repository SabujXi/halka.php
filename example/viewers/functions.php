<?php

function view_index($req, $resp, $template){
    //var_dump($app);
	//echo "this is view index";
    $template->load_view('home');
}

function view1fun(HalkaRequest $req, HalkaResponse $resp, $template){
	$template->load_view('view1');
}

function view2fun(HalkaRequest $req, HalkaResponse $resp, $template){
	$name = "Somebody";
    $template->load_view('view2', $ctx=[
        'name' => $name
    ]);
	
}

function inputfunc(HalkaRequest $req, HalkaResponse $resp, $template){
    $template->load_view("view3");
}

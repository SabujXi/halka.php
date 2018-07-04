<?php


class HomeViewer2 extends HalkaViewer{
	function get($req, $resp, $template){
        $template->load_view('home');
	    var_dump($req->get_route_params());
		echo "Calling from the class";
	}
}

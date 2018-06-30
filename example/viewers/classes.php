<?php

class ViewCls extends HalkaView{
	function get($req, $resp){
	    var_dump($req->get_route_params());
		echo "Calling from the class";
	}
}

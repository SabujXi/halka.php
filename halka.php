<?php

/*
halka.php (HP/Halka) is a simple and lightening fast php micro framework.
Developed by: Md. Sabuj Sarker
Website: www.sabuj.me
Email: md.sabuj.sarker@gmail.com

This framework was developed out of frustration of bulkiness of popular frameworks.
*/


//define('HALKA_BASEDIR', __DIR__);
//define('HALKA_FRONTSCRIPT', basename(__FILE__));


require_once "routes.php";
require_once BASEDIR . "/views/view_callables/view_functions.php";
require_once BASEDIR . "/views/view_callables/view_classes.php";

// util functions
function get_view_file($name){
	return BASEDIR . "/views/$name.php";
}

//

$base_uri = "/";
$base_uri = trim($base_uri, '/');


$uri = $_SERVER['REQUEST_URI'];
$uri = trim($uri, "/");

//echo "Initial URI: ";
// print_r($uri); echo PHP_EOL;

$base_match = substr($uri, 0, strlen($base_uri));
if($base_match === $base_uri && $base_match !== ''){
	$uri = substr($uri, strlen($base_match));
	$uri = ltrim($uri, "/");
	
	//echo "After base match URI: ";
	//print_r($uri); echo PHP_EOL;
}

$indexphp_match = substr($uri, 0, strlen("index.php"));
if($indexphp_match === "index.php" && $indexphp_match !== ''){
	$uri = substr($uri, strlen($indexphp_match));
	$uri = ltrim($uri, "/");
	
	//echo "After index php match URI: ";
	//print_r($uri); echo PHP_EOL;
}


function to_uri_parts($uri){
	$uri = trim($uri, '/');
	return explode("/", $uri);
}

$uri_parts = to_uri_parts($uri);

//echo "URI: ";
// print_r($uri); echo PHP_EOL;
//echo "URI PARTs:";
// print_r($uri_parts); echo PHP_EOL;

$uri_last_part = $uri_parts[count($uri_parts) - 1];

// print_r($uri_last_part);


function starts_with($str, $with){
	$str_len = strlen($str);
	$with_len = strlen($with);
	
	if ($str_len === 0 || $with_len === 0){
		return false;
	}
	if($with_len > $str_len){
		return false;
	}
	
	if(substr($str, 0, $with_len) === $with){
		return true;
	}
	
	return false;
}


function ends_with($str, $with){
	$str_len = strlen($str);
	$with_len = strlen($with);
	
	if ($str_len === 0 || $with_len === 0){
		return false;
	}
	if($with_len > $str_len){
		return false;
	}
	
	//echo "End str: " . substr($str, -$with_len, $with_len) . PHP_EOL;
	//echo "With: " . $with . PHP_EOL;
	
	if(substr($str, -$with_len, $with_len) === $with){
		return true;
	}
	
	return false;
}


function get_view_fun(){
	global $routes, $uri_parts, $uri_last_part;
	$fun = 'view404';
	
	$ends_with = substr($uri_last_part, -4, 4);
	if($ends_with === ".php"){ // assuming that .php will come in lowercase
		$fun = 'view_forbidden';
		return [$fun, []];
	}
	
	foreach($routes as $pattern => $fun_name){
		if (is_array($fun_name)){
			$fun = $fun_name[0];
			$route_name = $fun_name[1];
		}else{
			$fun = $fun_name;
			$route_name = '';
		}
			
		$pat_uri_parts = to_uri_parts($pattern);
		if ($uri_parts == $pat_uri_parts){
			return [$fun, []];
		}else{
			// match pattern			
			// break incoming urls into patterns and match them
			if(count($uri_parts) === count($pat_uri_parts)){
				// there is no point to match them if they do not contain the same amount of elements.
				$match_params = [];
				$all_matched = true;
				for($i = 0; $i < count($uri_parts); $i++){
					$uri_part = $uri_parts[$i];
					$pat_part = $pat_uri_parts[$i];
					//echo $uri_part . PHP_EOL;
					//echo $pat_part . PHP_EOL;
					
					if ($uri_part != $pat_part){
						// do pattern matchin
						$re_pat = '/^(?P<start>.+?)?(?:{(?P<name>[a-zA-Z0-9]+):(?P<regex>[^}]+)})?(?P<end>.*?)$/';
						$match_res = preg_match($re_pat, $pat_part, $matches);
						
						if ($match_res !== 0){
							if(starts_with($uri_part, $matches['start']) && ends_with($uri_part, $matches['end'])){
								$mid = substr($uri_part, strlen($matches['start']) - 1, strlen($uri_part) - strlen($matches['start']) - strlen($matches['end']));
								//echo "Mid: $mid" . PHP_EOL;
								if(preg_match('/'.$matches['regex'].'/', $uri_part) !== 0){
									//echo 'Url pattern matched.' . PHP_EOL;
									$match_params[$matches['name']] = $mid;
								}else{
									$all_matched = false;
									$match_params = [];
									break;
								}
							}else{
								$all_matched = false;
								$match_params = [];
								break;	
							}
							// print_r($matches);
						}else{
							$all_matched = false;
							$match_params = [];
							break;
						}
					}else{
						continue;
					}
				}
				
				if ($all_matched){
					//echo "All matched" . PHP_EOL;
					return [$fun, $match_params];
				}
			}
		}
	}
	return [$fun, []];
}


class Request{
	public $route_params = [];	
	private $session = [];
	private $defered = [];
	function __construct($route_params){
		$this->route_params = $route_params;
	}
	
	function set_session($key, $value){
		$this->session[$key] = $value;
	}
	
	function defer($cal){
		$defered[] = $cal;
	}
	
	function _get_deferreds(){
		return $this->defered;
	}
	
	function _get_session(){
		return $this->session;
	}
}

class View{
	protected $request;
	function __construct($request){
		$this->request = $request;
	}
	
	function _method_handler_missing($method){
		die("Method $method: hendler missing");
	}
}

$fun_pars = get_view_fun();
$fun = $fun_pars[0];
$params = $fun_pars[1];

if(function_exists($fun) || class_exists($fun)){
	ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE);
	$req = new Request($params);
	
	// try: to catch non-publicly showable errors
	if(function_exists($fun)){
		$fun($req);
	}else{
		$cls = $fun;
		if( !is_subclass_of($cls, 'View') ){
			die("View class must be a subclass of View");
		}
		$view_obj = new $cls($req);
		$method = strtolower($_SERVER['REQUEST_METHOD']); 
		if(!method_exists($view_obj, $method)){
			call_user_func([$view_obj, '_method_handler_missing'], strtoupper($method));
		}else{
			call_user_func([$view_obj, $method]);
		}
	}
	$buffer_contents = ob_get_contents();
	ob_clean();
	ob_end_clean();
	// do some middleware stuffs.
	// process the session & header stuffs
	echo $buffer_contents;
	// execute deferreds 
	$deferreds = $req->_get_deferreds();
	foreach($deferreds as $defer){
		$defer();
	}
	
	// catch: the errors and process.
}else{
	echo "View function called $fun does not exist.";
}

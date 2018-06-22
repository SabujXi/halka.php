<?php

/*
halka.php (HP/Halka) is a simple and lightening fast php micro framework.
Developed by: Md. Sabuj Sarker
Website: www.sabuj.me
Email: md.sabuj.sarker@gmail.com

This framework was developed out of frustration of bulkiness of popular frameworks.
*/


if (!defined('HALKA_BASEDIR')){
    define('HALKA_BASEDIR', __DIR__);
}

if (!defined('HALKA_FRONTSCRIPT')){
    define('HALKA_FRONTSCRIPT', basename(__FILE__));
}

define('HALKA_VIEWS_DIR', HALKA_BASEDIR . '/' . 'views');
define('HALKA_VIEWERS_DIR', HALKA_BASEDIR . '/' . 'viewers');

define('HALKA_ASSETS_DIR', HALKA_BASEDIR . 'assets');

$halka_routes = [];
$halka_settings = [];

function halka_require_if_exists($fn){
    // caution: variables are not imported into global space when imported from a function.
    // works perfectly for functions and classes.
    if(file_exists('routes.php')){
        return require_once $fn;
    }
    return null;
}


// util functions
function halka_get_view_file($name, $file_ext=''){
    if($file_ext !== ''){
        $fn_php = HALKA_VIEWS_DIR . "/$name.php";
        $fn_html = HALKA_VIEWS_DIR . "/$name.html";
        if(file_exists($fn_php)){
            return $fn_php;
        }elseif(file_exists($fn_html)){
            return $fn_html;
        }else{
            throw new Exception("View $name not found");
        }
    }else{
        $fn_any = HALKA_VIEWS_DIR . "/$name.$file_ext";
        if(!file_exists($fn_any)){
            throw new Exception("View $name with ext $file_ext not found");
        }
        return $fn_any;
    }
}

function halka_load_view($name, $ctx=[], $file_ext=''){
    $view_file = halka_get_view_file($name, $file_ext);
    var_export($ctx);
    require $view_file;
}


function load_asset_content($name){
    $fn = HALKA_ASSETS_DIR . '/' . $name;
    $cnt = file_get_contents($fn);
    echo $cnt;
    return $cnt;
}


function halka_trim_url($url){
    return trim($url, '/');
}

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


function to_uri_parts($uri){
    $uri = halka_trim_url($uri);
    return explode("/", $uri);
}

// require necessary files.
$routes = halka_require_if_exists(HALKA_BASEDIR . '/routes.php');
$settings = halka_require_if_exists(HALKA_BASEDIR . '/settings.php');
halka_require_if_exists(HALKA_BASEDIR . '/viewers/functions.php');
halka_require_if_exists(HALKA_BASEDIR . '/viewers/classes.php');  // classes can have dependency of functions required above.

// require post processing
if($routes){
    $halka_routes = array_merge($halka_routes, $routes);
}
unset($routes);

if($settings){
    $halka_settings = array_merge($halka_settings, $settings);
}
unset($settings);

// set base url & current url
if(isset($halka_settings['base_url'])){
    define('HALKA_BASE_URL', halka_trim_url($halka_settings['base_url']));
}else{
    define('HALKA_BASE_URL', halka_trim_url('/'));
}

if(isset($halka_settings['clean_url'])){
    define('HALKA_CLEAN_URL', $halka_settings['clean_url']);
}else{
    define('HALKA_CLEAN_URL', false);
}

define('HALKA_CURRENT_URL', halka_trim_url($_SERVER['REQUEST_URI']));



class HalkaRequest{
    public $route_params = [];
    function __construct($route_params){
        $this->route_params = $route_params;
    }

    function get_route_params(){
        return $this->route_params;
    }
}

class_alias('HalkaRequest', 'Request');


class HalkaResponse{
    private $session = [];
    private $defered = [];
    function __construct(){
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
class_alias('HalkaResponse', 'Response');


class HalkaView{
	protected $request;
	function __construct(){
	}
	
	function _method_handler_missing($method, $req, $resp){
		die("Method $method: hendler missing");
	}
}

class_alias('HalkaView', 'View');



// Url
class HalkaURI{
    private $components=[];
    private $params = [];
    private $params_set = false;
    function __construct($uri) {
        $uri = halka_trim_url($uri);
        $this->components = explode('/', $uri);
    }

    function length(){
        return  count($this->components);
    }

    function get_components(){
        return $this->components;
    }

    function get_params(){
        return $this->params;
    }

    function set_params($params){
        if($this->params_set){
            throw new Exception('Params cannot be re-set once set');
        }
        $this->params = $params;
        $this->params_set = true;
    }
}

// Route

define('ROUTE_COMP_VALUE_IDX', 0);
define('ROUTE_COMP_TYPE_IDX', 1);
define('ROUTE_NAME_IDX', 2);

define('ROUTE_START_IDX', 3);
define('ROUTE_REGEX_IDX', 4);
define('ROUTE_END_IDX', 5);

define('ROUTE_COMP_TYPE_COLON', 10);
define('ROUTE_COMP_TYPE_REGEX', 11);
define('ROUTE_COMP_TYPE_STRING', 12);


class HalkaRoute{
    private $structured_components = [];
    private $components = [];
    private $param_name_2_comp_idx = [];

    private static $colon_pat = '/^:(?P<name>[a-zA-Z0-9]+)$/';
    private static $re_pat = '/^(?P<start>[^{]*)?(?:{(?P<name>[a-zA-Z0-9]+):(?P<regex>[^}]+)})(?P<end>.*)?$/';

    private function parse($pattern){
        $pat_uri_parts = to_uri_parts($pattern);
        for($i = 0; $i < count($pat_uri_parts); $i++) {
            $pat_part = $pat_uri_parts[$i];
            $this->components[] = $pat_part;
            if (preg_match(static::$colon_pat, $pat_part, $matches) !== 0){
                $name = $matches['name'];
                $this->structured_components[$i] = [
                    null,
                    ROUTE_COMP_TYPE_COLON,
                    $name
                ];
                $this->param_name_2_comp_idx[$name] = $i;
            }
            elseif (preg_match(static::$re_pat, $pat_part, $matches) !== 0) {
                $start = $matches['start'];
                $name = $matches['name'];
                $regex = $matches['regex'];
                $end = $matches['end'];

                $this->structured_components[$i] = [
                    null,
                    ROUTE_COMP_TYPE_REGEX,
                    $name,
                    $start,
                    $regex,
                    $end
                ];
                $this->param_name_2_comp_idx[$name] = $i;
            }else{
                // they are ordinary url components.
                $this->structured_components[$i] = [
                    $pat_part,
                    ROUTE_COMP_TYPE_STRING,
                ];
            }
        }
    }

    function __construct($route_str) {
        $this->parse($route_str);
    }

    function make_url($params=[]){
        if(sort(array_keys($params)) != sort(array_keys($this->param_name_2_comp_idx))){
            /*
             * This test also covers: count($this->param_name_2_comp_idx) !== count($params)
             */
            throw new Exception('Number of parameters needed for url construction and the number of provided parameters are not equal.');
        }

        if(count($this->param_name_2_comp_idx) == 0){
            return join('/', $this->components);
        }else{
            $new_url_comps = [];
            $struct_comp_s = $this->structured_components;
            foreach ($struct_comp_s as $struct_comp){
                if      ( $struct_comp[ROUTE_COMP_TYPE_IDX] === ROUTE_COMP_TYPE_STRING){
                    $comp_value = $struct_comp[ROUTE_COMP_VALUE_IDX];
                    $new_url_comps[] = $comp_value;
                }elseif ( $struct_comp[ROUTE_COMP_TYPE_IDX] === ROUTE_COMP_TYPE_COLON){
                    $comp_value = $params[$struct_comp[ROUTE_NAME_IDX]];
                    $new_url_comps[] = $comp_value;
                }else{  // regex
                    $start = $struct_comp[ROUTE_START_IDX];
                    $name = $struct_comp[ROUTE_NAME_IDX];
                    $regex = $struct_comp[ROUTE_REGEX_IDX];
                    $end = $struct_comp[ROUTE_END_IDX];

                    $param_value = $params[$name];

                    if(!preg_match('/^' . $regex .'$/', $param_value)){
                        throw new Exception('Provided parameter value did not match with regex pattern in the route');
                    }

                    $comp_value = $start . $param_value . $end;
                    $new_url_comps[] = $comp_value;
                }
            }
        }
        $new_url = join('/', $new_url_comps);
        if(!HALKA_CLEAN_URL){
            $new_url = HALKA_BASE_URL . '/' . HALKA_FRONTSCRIPT . '/' . $new_url;
        }
        return $new_url;
    }

    function has_params(){
        return count($this->param_name_2_comp_idx) > 0 ? true : false;
    }

    function matches_url(HalkaURI $url){
        $url_comps = $url->get_components();
        $route_comps = $this->components;

        $params=[];

        if($this->length() !== $url->length()){
            return false;
        }elseif(!$this->has_params()){
            if ($url_comps == $route_comps){
                $url->set_params($params);
                return true;
            }else{
                return false;
            }
        }else{
            $matched = true;

            $route_struct_comp_s = $this->structured_components;
            $_no_of_comps = count($url_comps);
            for($i = 0; $i < $_no_of_comps; $i++){
                $url_comp = $url_comps[$i];
                $route_struct_comp = $route_struct_comp_s[$i];

                if($route_struct_comp[ROUTE_COMP_TYPE_IDX] === ROUTE_COMP_TYPE_STRING){
                    if($url_comp != $route_struct_comp[ROUTE_COMP_VALUE_IDX]){
                        $matched = false;
                        break;
                    }else continue;
                }elseif($route_struct_comp[ROUTE_COMP_TYPE_IDX] === ROUTE_COMP_TYPE_COLON) {
                    $name = $route_struct_comp[ROUTE_NAME_IDX];
                    $params[$name] = $url_comp;
                    continue;
                }
                else{
                    $start = $route_struct_comp[ROUTE_START_IDX];
                     $name = $route_struct_comp[ROUTE_NAME_IDX];
                    $regex = $route_struct_comp[ROUTE_REGEX_IDX];
                    $end = $route_struct_comp[ROUTE_END_IDX];


                    if($start !== ''){
                        if(!starts_with($url_comp, $start)){
                            $matched = false;
                            break;
                        }
                    }
                    if ($end !== ''){
                        if(!ends_with($url_comp, $end)){
                            $matched = false;
                            break;
                        }
                    }

                    $url_comp_mid = substr($url_comp, strlen($start) === 0 ? 0 : strlen($start), strlen($url_comp) - strlen($start) - strlen($end));
                    if(preg_match('/^'. $regex . '$/', $url_comp_mid) !== 0){
                        $params[$name] = $url_comp_mid;
                        continue;
                    }else{
                        $matched = false;
                        break;
                    }
                }

            }
            if ($matched){
                $url->set_params($params);
            }
            return $matched;
        }
    }

    function get_components(){
        return $this->components;
    }

    function get_structured_components(){
        return $this->structured_components;
    }

    function length(){
        return  count($this->components);
    }
}

// halka default views.


function halka_view_forbidden(HalkaRequest $r){

    echo "You have entered into area 51 - don't access php files directly";
}

function halka_view404(HalkaRequest $r){

    echo "404 not found - u have entered wrong url";
}
// Router

class HalkaRouter{
    /**
     * Routes are not lazily parsed. If this causes significant perfomace penalty I will make it lazy later.
     * For many routes with a lot of regular expression patterns this may cause noticeable performance issue.
     */

    private $routes = [];
    private $route_name_2_route_idx = [];

    private function parse_routes($routes){
        $idx = -1;
        foreach ($routes as $route_str => $route_config){
            $idx++;
            $route_obj = new HalkaRoute($route_str);
            $route_name = null;
            $viewer = $route_config;
            if(is_string($route_config)){
            }elseif (is_callable($route_config)){
            }elseif(is_array($route_config)){
                if(count($route_config) === 0){
                    throw new Exception('Route value must not be an empty array');
                }
                $viewer = $route_config[0];
                if(count($route_config) > 1){
                    $route_name = $route_config[1];
                }
            }

            $this->routes[] = [

                'route' => $route_obj,
                'viewer' => $viewer
            ];

            if($route_name){
                $this->route_name_2_route_idx[$route_name] = $idx;
            }
        }
    }

    function __construct($routes) {
        $this->parse_routes($routes);
    }

    function get_routes(){
        return $this->routes;
    }

    function make_url($route_name, $params=[]){
        $route = $this->routes[$this->route_name_2_route_idx[$route_name]]['route'];
        return $route->make_url($params);
    }

    private function get_viewer($uri){
        if(starts_with($uri, HALKA_BASE_URL)){
            $uri = substr($uri, strlen(HALKA_BASE_URL));
            $uri = halka_trim_url($uri);
        }
        if(starts_with($uri, HALKA_FRONTSCRIPT)){  // eg. index.php
            $uri = substr($uri, strlen(HALKA_FRONTSCRIPT));
            $uri = halka_trim_url($uri);
        }

        $uri_parts = to_uri_parts($uri);
        $uri_last_part = $uri_parts[count($uri_parts) - 1];

        if(function_exists('view_404') || class_exists('View404')){
            if(function_exists('view_404')){
                $viewer = 'view404';
            }else{
                $viewer = 'View404';
            }
        }else{
            $viewer = 'halka_view404';
        }

        if(ends_with($uri_last_part, ".php")){ // assuming that .php will come in lowercase
            if(function_exists('view_forbidden') || class_exists('ViewForbidden')){
                if(function_exists('view_forbidden')){
                    $viewer = 'view_forbidden';
                }else{
                    $viewer = 'ViewForbidden';
                }
            }else{
                $viewer = 'halka_view_forbidden';
            }
        }

        // now find the defined viewer
        $uri_obj = new HalkaURI($uri);
        foreach ($this->routes as $route){
            if(($route['route'])->matches_url($uri_obj)){
                $viewer = $route['viewer'];
            }
        }

        return [$viewer, $uri_obj->get_params()];
    }

    function exec_viewer($uri){
        $uri = halka_trim_url($uri);
        // start request processing.

        $viewer_n_params = $this->get_viewer($uri);
        $viewer = $viewer_n_params[0];
        $route_params = $viewer_n_params[1];

        if(function_exists($viewer) || class_exists($viewer)){
            ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE);
            $req = new HalkaRequest($route_params);
            $resp = new HalkaResponse();

            // try: to catch non-publicly showable errors
            if(function_exists($viewer)){
                $viewer($req, $resp);
            }else{
                $cls = $viewer;
                if( !is_subclass_of($cls, 'HalkaView') ){
                    die("View class must be a subclass of View");
                }
                $view_obj = new $cls();
                $method = strtolower($_SERVER['REQUEST_METHOD']);
                if(!method_exists($view_obj, $method)){
                    call_user_func_array([$view_obj, '_method_handler_missing'], [strtoupper($method), $req, $resp]);
                }else{
                    call_user_func_array([$view_obj, $method], [$req, $resp]);
                }
            }
            $buffer_contents = ob_get_contents();
            ob_clean();
            //ob_end_clean();
            // do some middleware stuffs.
            // process the session & header stuffs
            echo $buffer_contents;
            // execute deferreds
            $deferreds = $resp->_get_deferreds();
            foreach($deferreds as $defer){
                $defer();
            }

            // catch: the errors and process.
        }else{
            die("View function called $viewer does not exist.");
        }
    }
}
class_alias('HalkaRouter', 'Router');

$_router = new HalkaRouter($halka_routes);

function get_url($route_name, $params=[]){
    global $_router;
    return $_router->make_url($route_name, $params);
}

function load_url($route_name, $params=[]){
    $url = get_url($route_name, $params);
    echo $url;
    return $url;
}

function asset_url($name){
    // no need of thinking about clean url here as it is not dynamic content.
    return '/' . HALKA_BASE_URL . '/' . $name;
}

function load_asset_url($name){
    $url = asset_url($name);
    echo $url;
    return $url;
}

$_router->exec_viewer(HALKA_CURRENT_URL);

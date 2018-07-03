<?php

/*
halka.php (HP/Halka) is a simple and lightening fast php micro framework.
Developed by: Md. Sabuj Sarker
Website: www.sabuj.me
Email: md.sabuj.sarker@gmail.com

This framework was developed out of frustration of bulkiness of popular frameworks.
*/



function halka_require($fn){
	return require_once $fn;
}

function halka_require_if_exists($fn){
    // caution: variables are not imported into global space when imported from a function.
    // works perfectly for functions and classes.
    if(file_exists($fn)){
        return halka_require($fn);
    }
    return null;
}


// util functions
function halka_get_view_file($name, $file_ext=''){
    if($file_ext === ''){
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
    if(isset($ctx['context'])){
        // no messing with context variable from template or view. It will be set from
        // halka_load_view.
        unset($ctx['context']);
    }
    unset($name);
	$context = $ctx;
    extract($ctx);
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

function url_without_query($url){
	$_url_part = explode('?', $url, 2);
	$url = $_url_part[0];
	// var_dump($_url_part);
	// $url = strtok($url,'?');
	return $url;
}


// Exceptions

class DoneException extends Exception{}
class InternalErrorException extends Exception{}


class HalkaRequest{
    public $route_params = [];
    function __construct($route_params){
        $this->route_params = $route_params;
    }

    function get_route_params(){
        return $this->route_params;
    }
}


class HalkaResponse{
    private $session = [];
    private $headers = [];
    private $response_code = null;
	
	protected $discard_headers = false;
	protected $discard_content = false;
	protected $discard_session = false;
	
	protected $buffering_started = false;  // once set to true it must never be set to false even if when ending it.
	protected $buffering_stopped = false;
	
	protected $committed = false;
	
	function start_buffering(){
		if($this->buffering_started === true){
			throw new Exception('Must not call start_buffering twice');
		}
		$this->buffering_started = true;
		ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
	}
	
	private function _stop_buffering(){
		if ($this->buffering_started === false){
			throw new Exception('Cannot stop buffering when it never started.');
		}
		
		if($this->buffering_stopped){
			throw new Exception('Cannot stop buffer twice');
		}
		$this->buffering_stopped = true;
	}
	
	private	function commit(){
		if ($this->committed === true){
			throw new Exception('Cannot commit twice.');
		}
		
		
		
            // request pre-output processing
			
			
		
		// send response code
		$code = $this->_get_response_code();
            if($code){
                http_response_code($code);
            }
		$this->response_code = null;
		
		// add all session key and other processing.
		$sessions = $this->_get_session();
            if($sessions){
                // do session processing
                //session_start();
                // remove all session variables
                //session_unset();
                // destroy the session
                //session_destroy();
            }
		
		$this->session = [];
		
		// add all headers key
		$headers = $this->_get_headers();
            if($headers){
                foreach ($headers as $key => $value){
                    header("$key: $value");
                }
            }
		$this->headers = [];
		
		// turn off buffering if not off and flush content.
		$this->_stop_buffering();
		
		$buffer_contents = ob_get_contents();
            // ob_clean();
            // ob_end_clean();
            // do some middleware stuffs in where it goes.
            // process the session & header stuffs: done before.
		
		ob_end_clean();
		
		$this->committed = true;
		
		return $buffer_contents;
	}
	
	function stop_buffering(){
		if($this->buffering_stopped !== true){
			return $this->commit();
		}
		return '';
	}
	
	function buffering_stopped(){
		return $this->buffering_stopped;
	}
	
	function discard_headers(){
		// can be discarded as many times as needed.
		$this->headers = [];
		$this->discard_header = true;
	}
	
	function discard_content(){
		// can be discarded as many times as needed.
		$this->discard_content = true;
		if($this->buffering_started !== true){
			throw new Exception('Cannot discard buffer when it never started.');
		}
		ob_clean();
	}
	
	function discard_session(){
		$this->session = [];
		$this->discard_session = true;
	}
	
	
	function discard_all(){
		$this->discard_headers();
		$this->discard_content();
		$this->discard_session();
	}

    function __construct(){}

    function done(){
        throw new DoneException();
    }

    function response_done(){
        return $this->done();
    }

    function stop(){
        return $this->done();
    }

    function session_add($key, $value){
		if($this->buffering_stopped === true){
			throw new Exception('Cannot add session in unbufferred mode');
		}
        $this->session[$key] = $value;
    }

    function session_delete($key){
		if($this->buffering_stopped === true){
			throw new Exception('Cannot delete session in unbufferred mode');
		}
		
		// TODO: real processing here
    }

    function session_destroy(){
		if($this->buffering_stopped === true){
			throw new Exception('Cannot destroy session in unbufferred mode');
		}
		
		// TODO: real processing here.
    }

    function set_header($key, $value){
		if($this->buffering_stopped === true){
			throw new Exception('Cannot set header in unbufferred mode');
		}
		
        $this->headers[$key] = $value;
    }

    function set_response_code($code){
		if($this->buffering_stopped === true){
			throw new Exception('Cannot set response in unbufferred mode');
		}
		
        $this->response_code = $code;
    }
	
    private function _get_session(){
        return $this->session;
    }

    private function _get_headers(){
        return $this->headers;
    }

    private function _get_response_code(){
        return $this->response_code;
    }
}

define('TEMPLATE_TYPE_TEXT', 'T');
define('TEMPLATE_TYPE_SECTION', 'S');

class HalkaSectionTemplate{  // almost sole existance of this class is for local stack.
    private $viewer;
    private $view_loaded = false;

    function __construct(HalkaViewer $viewer) {
        $this->viewer = $viewer;
    }
    private $local_current_section = null;

    final function section_declare($name){
        $_prev_content = ob_get_contents();
        ob_clean();
        $this->viewer->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $_prev_content]);

        // section declaration works in the global space - not in the local space.
        try{
            $this->viewer->__section_declare($name);//, $this->template_local_stack);
        }catch (Exception $e){
            ob_end_flush();
            // rethrow the exception
            throw $e;
        }
    }

    final function section_start($name){
        // start and end section works in local space.
        /*
        if(in_array($name, $this->viewer->template_sections())){
            // ending section specific buffer
            ob_end_flush();
            // this check is global.
            throw new Exception("Duplicate section $name detected.");
        }
        This code is not needed as now template declaration and start end are indepenednt. Error will be checked otherwise.
        */
        if(!is_null($this->local_current_section)){
            // ending section specific buffer
            ob_end_flush();
            throw new Exception("A section with name $this->local_current_section started but never ended. You started another section with name $name. What is this, man!");
        }

        // if there is any previous content, push that to stack.
        // content from template buffer.
        $__prev_content = ob_get_contents();
        // cleaning template specific buffer.
        ob_clean();
        $this->viewer->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $__prev_content]);
        $this->local_current_section = $name;

        // specific buffer for a specific section.
        ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE);
    }

    final function section_end($name){
        if($this->local_current_section !== $name){
            // ending section specific buffer.
            ob_end_flush();
            throw new Exception("Current section $this->local_current_section did not end when you want to end section $name. Too err is human, fix it!");
        }
        // content section buffer.
        $section_content = ob_get_contents();
        $this->local_current_section = null;
        // ending section specific buffer.
        ob_end_clean();
        $this->viewer->__add_to_section_map($name, $section_content);
    }

    final function load_view($name, $ctx=[], $file_ext=''){
        if ($this->view_loaded === true){
            // push any orphan content
            $_prev_content = ob_get_contents();
            ob_clean();
            $this->viewer->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $_prev_content]);
            // throw new Exception("Cannot be loaded twice.");
            $template = new self($this->viewer);
            $ctx['template'] = $template;
            $template->load_view($name, $ctx, $file_ext);
            return;
        }
        $this->view_loaded = true;

        // buffering for templating.
        ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE);

        $ctx['template'] = $this;
        halka_load_view($name, $ctx, $file_ext);
        // if a section was not ended, throw here.
        if(!is_null($this->local_current_section)){
            ob_end_flush(); // for buffer started in section_/start/end/declare.
            ob_end_flush(); // for buffer template buffer/this load_view function.
            throw new Exception("A section with name $this->local_current_section never ended.");
        }else{
            $__end_contents = ob_get_contents();
            $this->viewer->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $__end_contents]);
        }
        // completely clean the content as we no longer need them.
        ob_end_clean();
        $this->local_current_section = null;
        $this->viewer = null;
    }

    final function load_child($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }

    final function extend_view($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }

    final function extend($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }
}

class HalkaViewer{
	protected $request;
	
	protected $truth_store = []; // context store.

    private $global_template_stack = [];
    // private $cuttent_section = null; -- it is a local stuff.

    private $global_section_map = []; // this->global_section_map[$section_name] = $section_content.

    private $declared_sections = [];
	
	function __construct(){}
	
	function before($req, $resp, $app){}
	
	function after($req, $resp, $app){}

	function template_sections(){
	    return array_keys($this->global_template_stack);  // TODO: filter out numeric index.
    }
	
	final function _method_handler_missing($method, $req, $resp, $app){
		die("Method $method: hendler missing");
		// return false when not dying. That result will be used to determine whether after and deferred should be executed.
	}
	
	final function getValue($key){
		return $this->truth_store[$key];
	}

	final function get_context($key){
	    return $this->getValue($key);
    }
	
	final function setValue($key, $value){
		$this->truth_store[$key] = $value;
	}

	final function set_context($key, $value){
	    return $this->setValue($key, $value);
    }

    final function __section_declare($name){
        if(in_array($name, $this->declared_sections)){
            throw new Exception("Duplicate section declaration: $name");
        }
        /*
        if(!array_key_exists($name, $this->global_section_map)){
            throw new Exception("Section $name must already be populated in global section map before the declaration found in the child tree.");
        }
        // no, most of the time load can happen before the sections are declared. Check for error in viewer->load_view.
        */

        $this->declared_sections[] = $name;
        // push null value to global scope to be populated later.
        $this->global_template_stack[$name] = [TEMPLATE_TYPE_SECTION, null];
    }

    final function __add_to_section_map($name, $content){
        $this->global_section_map[$name] = $content;
    }

    final function __push_content_to_stack($name, $content){
        if(!is_null($name)){
            $this->global_template_stack[$name] = $content;
        }else{
            $this->global_template_stack[] = $content;
        }
    }

    final function load_view($name, $ctx=[], $file_ext=''){
        $template = new HalkaSectionTemplate($this);
        $template->load_view($name, $ctx, $file_ext);
        // process the templates and output.

        // error checking: unwanted section.
        foreach ($this->global_section_map as $section_name => $section_value){
            // a declared section can be left empty - no value provided by starting and ending section.
            if(!array_key_exists($section_name, $this->global_template_stack)){
                throw new Exception("$section_name was not declared in template but used.");
            }
            // but a section started and ended cannot be present without being declared.
        }

        foreach ($this->global_template_stack as $name => $content_array){
            $content_type = $content_array[0];
            $content = $content_array[1];
            if ($content_type === TEMPLATE_TYPE_SECTION){
                if(!is_null($content)){
                    throw new Exception("Content in global template map must be null");
                }
                $content = $this->global_section_map[$name];
                echo $content;
            }elseif ($content_type === TEMPLATE_TYPE_TEXT){
                echo $content;
            }else{
                throw new Exception('Unknown error for template processing.');
            }
        }
        // reset template stack for another load.
        $this->template_local_stack = [];
	}
	
	protected final function load_views($view_names, $ctx=[], $file_ext=''){
		foreach($view_names as $view_name){
			$this->load_view($view_name, $ctx=$ctx, $file_ext=$file_ext);
		}
	}
}


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

// Route Indexes
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
		$param_keys = array_keys($params);
		$params_keys_sorted = sort($param_keys);
		
		$comp_keys = array_keys($this->param_name_2_comp_idx);
		$comp_keys_sorted = sort($comp_keys);
		
        if($params_keys_sorted != $comp_keys_sorted){
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

    function make_url($route_name, $params=[], $query_params=[]){
        $route = $this->routes[$this->route_name_2_route_idx[$route_name]]['route'];
        $url = $route->make_url($params);
	
		$url_with_query = $query_params ? $url . '?' . http_build_query($query_params) : $url;
		
		$new_url = $url_with_query;
		
		if(!HALKA_CLEAN_URL){
            $new_url = '/' . HALKA_BASE_URL . '/' . HALKA_FRONTSCRIPT . '/' . $new_url;
        }else{
			$new_url = '/' . HALKA_BASE_URL . '/' . $new_url;
		}
		
		return $new_url;
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

    private function process_request_function($callable, $req, $resp, $app){
        try{
            return $callable($req, $resp, $app);
        }catch(DoneException $e){
            return false;
        }
    }

    private function process_request_method($method_array, $req, $resp, $app){
        try{
            return call_user_func_array($method_array, [$req, $resp, $app]);
        }catch(DoneException $e){
            return false;
        }
    }

    function exec_viewer($uri){
        $uri = halka_trim_url($uri);
        // start request processing.

        $viewer_n_params = $this->get_viewer($uri);
        $viewer = $viewer_n_params[0];
        $route_params = $viewer_n_params[1];

        if(function_exists($viewer) || class_exists($viewer)){
            $req = new HalkaRequest($route_params);
            $resp = new HalkaResponse();
			$resp->start_buffering();

            // try: to catch non-publicly showable errors
			
			// function viewer processing
            if(function_exists($viewer)){
                $app = new HalkaApp($this, new HalkaViewer());
                $this->process_request_function($viewer, $req, $resp, $app);
			// class viewer processing
            }else{
                $cls = $viewer;
                if( !is_subclass_of($cls, 'HalkaViewer') ){
                    die("View class must be a subclass of View");
                }
                $viewer_obj = new $cls();
                $app = new HalkaApp($this, $viewer_obj);
                $method = strtolower($_SERVER['REQUEST_METHOD']);
				
				// before processing.
				$before_returned = $this->process_request_method([$viewer_obj, 'before'], $req, $resp, $app);
				$method_returned = null;
				if($before_returned !== false){
					if(!method_exists($viewer_obj, $method)){
						$method_returned = call_user_func_array([$viewer_obj, '_method_handler_missing'], [strtoupper($method), $req, $resp, $app]);
					}else{
                        $method_returned = $this->process_request_method([$viewer_obj, $method], $req, $resp, $app);
					}
				}
				if($method_returned !== false){
                    $after_returned = $this->process_request_method([$viewer_obj, 'after'], $req, $resp, $app);
				}
				// no use of $after returned values.
            }
            // catch: the errors and process.
			
			// echo buffered cnts.
			
			$buffered_contents = $resp->stop_buffering();
			
			if($buffered_contents !== ''){
				// do some middleware stuffs with the content if needed.
				echo $buffered_contents;
			}
        }else{
            die("View function called $viewer does not exist.");
        }
    }
}

class HalkaApp{
    private $router;
    private $viewer;
    function __construct(HalkaRouter $router, HalkaViewer $viewer) {
        $this->router = $router;
        $this->viewer = $viewer;
    }

    function get_url($route_name, $params=[], $query_params=[]){
        $url = $this->router->make_url($route_name, $params, $query_params);
        return $url;
    }

    function echo_get_url($route_name, $params=[], $query_params=[]){
        $url = $this->get_url($route_name, $params, $query_params);
        echo $url;
        return $url;
    }

    function asset_url($name){
        // no need of thinking about clean url here as it is not dynamic content.
        return '/' . HALKA_BASE_URL . '/' . 'assets' . '/' . $name;
    }

    function echo_asset_url($name){
        $url = asset_url($name);
        echo $url;
        return $url;
    }

    function load_view($name, $ctx=[], $file_ext=''){
        $this->viewer->load_view($name, $ctx, $file_ext);
    }
}

function halka_boot_app(){
    // future plan for multi app/ multi domain/ multi directory.
}

function start_halka(){
    if (!defined('HALKA_BASEDIR')){
        define('HALKA_BASEDIR', __DIR__);
    }

    if (!defined('HALKA_FRONTSCRIPT')){
        define('HALKA_FRONTSCRIPT', basename(__FILE__));
    }

    define('HALKA_UTILS_DIR', HALKA_BASEDIR . '/' . 'utils');
    define('HALKA_VIEWS_DIR', HALKA_BASEDIR . '/' . 'views');
    define('HALKA_VIEWERS_DIR', HALKA_BASEDIR . '/' . 'viewers');
    define('HALKA_MODELS_DIR', HALKA_BASEDIR . '/' . 'models');

    define('HALKA_ASSETS_DIR', HALKA_BASEDIR . 'assets');

    $halka_routes = [];
    $halka_settings = [];

    // require necessary files.
    $routes = halka_require_if_exists(HALKA_BASEDIR . '/routes.php');
    $settings = halka_require_if_exists(HALKA_BASEDIR . '/settings.php');
    halka_require_if_exists(HALKA_UTILS_DIR . '/__autoload__.php');
    halka_require_if_exists(HALKA_BASEDIR . '/viewers/functions.php');
    halka_require_if_exists(HALKA_BASEDIR . '/viewers/classes.php');  // classes can have dependency of functions required above.
    halka_require_if_exists(HALKA_VIEWERS_DIR . '/__autoload__.php');
    halka_require_if_exists(HALKA_VIEWS_DIR . '/__autoload__.php');
    halka_require_if_exists(HALKA_MODELS_DIR . '/__autoload__.php');


    // require post processing
    if($routes){
        $halka_routes = array_merge($halka_routes, $routes);
    }
    unset($routes);

    if($settings){
        $halka_settings = array_merge($halka_settings, $settings);
    }
    unset($settings);

    // require from settings
    if(isset($halka_settings['require'])){
        $to_require = $halka_settings['require'];
        if (is_string($to_require)){
            halka_require(HALKA_BASEDIR . '/' . $to_require);
        }elseif(is_array($to_require)){
            foreach($to_require as $fn){
                halka_require(HALKA_BASEDIR . '/' . $to_require);
            }
        }
    }

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

    define('HALKA_CURRENT_URL', halka_trim_url(url_without_query($_SERVER['REQUEST_URI'])));

    $_router = new HalkaRouter($halka_routes);
    $_router->exec_viewer(HALKA_CURRENT_URL);
}

<?php

/*
halka.php (HP/Halka) is a simple and lightening fast php micro framework.
Developed by: Md. Sabuj Sarker
Website: www.sabuj.me
Email: md.sabuj.sarker@gmail.com

This framework was developed out of frustration of bulkiness of popular frameworks.
*/

$__halka_welcome = <<<M
<!doctype html>
<html>
    <head>
        <title> Welcome to Halka.php - lightening fast php micro framework </title>
    </head>
    <body>
        <h1> Welcome to Halka.php - lightening fast php micro framework </h1>
        <pre style='color:purple;'> This page is being displayed as there is no home (/) route set for your application.</pre>
        <pre style='color:purple;'> Or maybe you just started out.</pre>
        <code>
            - full featured routing system.
            - very powerful templating system without leaving php and sacrificing its power.
             - still having parent/child and section oriented templating.
            - viewers (people call it controller) can be a function or class.
        </code>
        ... incomplete home page. Incomplete doc.
        
        <blockquote>
            Halka.php is developed by Md. Sabuj Sarker.
            Email: md.sabuj.sarker@gmail.com
            Website: www.sabuj.me
            Github: github.com/SabujXi
        </blockquote>
    </body>
</html>
M;

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

function halka_trim_url($url){
    return trim($url, '/');
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


function halka_require($fn, $once=false){
    if($once){
        return require_once $fn;
    }else{
        return require $fn;
    }
}

function halka_require_if_exists($fn, $once=false){
    // caution: variables are not imported into global space when imported from a function.
    // works perfectly for functions and classes.
    if(file_exists($fn)){
        return halka_require($fn, $once);
    }
    return null;
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

    private $buffer;
	
	function start_buffering(){
	    /*
		if($this->buffering_started === true){
			throw new Exception('Must not call start_buffering twice');
		}
		$this->buffering_started = true;
		// ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
	    */
        $this->buffer->start();
        $this->buffering_started = true;
    }
	
	private function _stop_buffering(){
		if ($this->buffering_started === false){
			throw new Exception('Cannot stop buffering when it never started.');
		}
		
		if($this->buffering_stopped){
			throw new Exception('Cannot stop buffer twice');
		}
		$this->buffering_stopped = true;
        return $this->buffer->get_all_content();
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
        if($this->buffer->is_on()){
            $buffer_contents = $this->_stop_buffering();
            // do some middleware stuffs in where it goes.
            // process the session & header stuffs: done before.
            echo $buffer_contents;
        }
        $this->committed = true;
	}
	
	function stop_buffering(){
		if($this->buffering_stopped !== true){
			$this->commit();
		}
	}

	function disable_content_buffering(){
	    // discards and disables.
	    $this->buffer->disable();
        $this->buffering_stopped = true;
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
		$this->buffer->clean_all();
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

    function __construct(HalkaBuffer $buffer){$this->buffer = $buffer;}

    function done(){
        $this->buffer->flush_all();
        throw new DoneException();
    }

    function response_done(){
        return $this->done();
    }

    function stop(){
        return $this->done();
    }

    final function redirect($url, $code){
        // TODO: incomplete function.
        $this->set_header('Location', $url);
        $this->done();
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
		// if buffer is disabled - set immediately
		
        $this->headers[$key] = $value;
    }

    function set_response_code($code){
        // if buffer is disabled - set immediately
		if($this->buffering_stopped === true){
			throw new Exception('Cannot set response in unbufferred mode');
		}
		
        $this->response_code = $code;
    }
	
    private function _get_session(){
        // if buffer is disabled - throw exception
        return $this->session;
    }

    private function _get_headers(){
        return $this->headers;
    }

    private function _get_response_code(){
        return $this->response_code;
    }
}


trait _ExtraTemplateMethods{
    final function load_child($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }

    final function extend_view($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }

    final function extend($name, $ctx=[], $file_ext=''){
        $this->load_view($name, $ctx, $file_ext);
    }

    function get_url($route_name, $params=[], $query_params=[]){
        return $this->router->get_url($route_name, $params, $query_params);
    }
    function echo_url($route_name, $params=[], $query_params=[]){
        return $this->router->echo_url($route_name, $params, $query_params);
    }
    function get_asset($name, $as_base_64=false){
        return $this->app->get_asset($name, $as_base_64);
    }
    function echo_asset($name, $as_base_64=false){
        $this->app->echo_asset($name, $as_base_64);
    }
    function asset_url($name){
        return $this->app->echo_asset_url($name);
    }
    function echo_asset_url($name){
        $this->app->echo_asset_url($name);
    }
}

final class HalkaBuffer{
    private $initial_level;
    private $current_level;
    function __construct($initial_level=0) {
        $this->initial_level = $initial_level;
        $this->current_level = $this->initial_level;
    }

    function start(){
        // start at any nesting.
        ob_start(null, 0, PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE);
        $this->current_level++;
        return $this->current_level;
    }
    function is_on(){
        return $this->current_level > $this->initial_level ? true : false;
    }
    function is_disabled(){
        return $this->current_level < 0 ? true : false;
    }
    function clean_current(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("clean current is not allowed when there is no buffering.");
        }
        ob_clean();
    }

    function clean_all(){
        $levels = $this->current_level - $this->initial_level;
        for($i = 0; $i < $levels; $i++){
            if($i === 1){
                ob_clean();
            }
            ob_end_clean();
            $this->current_level--;
        }
    }

    function end_current(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("end current is not allowed when there is no buffering.");
        }
        ob_end_clean();
        $this->current_level--;
    }
    function flush_current(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("flush current is not allowed when there is no buffering.");
        }
        ob_flush();
    }
    function flush_all(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("flush current is not allowed when there is no buffering.");
        }
        echo $this->get_all_content();
    }
    function end_all(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("end all is not allowed when there is no buffering.");
        }
        $levels = $this->current_level - $this->initial_level;
        for($i = 0; $i < $levels; $i++){
            ob_end_clean();
            $this->current_level--;
        }
    }
    function get_current_content(){
        if($this->current_level <= $this->initial_level){
            throw new Exception("get content is not allowed when there is no buffering.");
        }
        return ob_get_contents();
    }
    function get_all_content(){
        // ends all the level to gather content.
        if($this->current_level <= $this->initial_level){
            throw new Exception("get all content is not allowed when there is no buffering.");
        }
        $content = '';
        $levels = $this->current_level - $this->initial_level;
        for($i = 0; $i < $levels; $i++){
            $content = ob_get_contents() . $content;
            ob_end_clean();
            $this->current_level--;
        }
        return $content;
    }
    function get_end_current(){
        $this->end_current();
        return $this->get_current_content();
    }

    function disable(){
        $this->end_all();
        $this->current_level = PHP_INT_MIN;
    }
}


define('TEMPLATE_TYPE_TEXT', 'T');
define('TEMPLATE_TYPE_SECTION', 'S');


class HalkaRootTemplate{
    /*
     * Only viewer_exec can use root template to pass ...
     */

    private $global_template_stack = [];
    // private $cuttent_section = null; -- it is a local stuff.

    private $global_section_map = []; // this->global_section_map[$section_name] = $section_content.

    private $declared_sections = [];

    private $app;
    private $router;

    use _ExtraTemplateMethods;

    function __construct(HalkaRouter $router, HalkaApp $app) {
        $this->app = $app;
        $this->router = $router;
    }

    function template_sections(){
        return array_keys($this->global_template_stack);  // TODO: filter out numeric index.
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
        if(array_key_exists($name, $this->global_section_map)){
            throw new Exception("Cannot add a section value twice.");
        }
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
        $template = new HalkaSectionTemplate($this, $this->router, $this->app);
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

    final function load_views($view_names, $ctx=[], $file_ext=''){
        foreach($view_names as $view_name){
            $this->load_view($view_name, $ctx, $file_ext);
        }
    }

    final function populate_section($name, $value){
        /*
         * Convenient method for pre-populating section value from viewers get/post/before/after/etc. method.
         */
        $this->__add_to_section_map($name, $value);
    }
}


class HalkaSectionTemplate{  // almost sole existance of this class is for local stack.
    private $app;
    private $root_template;
    private $router;
    private $buffer;
    private $view_loaded = false;

    use _ExtraTemplateMethods;

    function __construct(HalkaRootTemplate $root_template, HalkaRouter $router, HalkaApp $app) {
        $this->app = $app;
        $this->root_template = $root_template;
        $this->router = $router;
        $this->buffer = new HalkaBuffer();
    }
    private $local_current_section = null;

    final function section_declare($name){
        $_prev_content = $this->buffer->get_current_content();
        $this->buffer->clean_current();
        $this->root_template->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $_prev_content]);

        // section declaration works in the global space - not in the local space.
        try{
            $this->root_template->__section_declare($name);//, $this->template_local_stack);
        }catch (Exception $e){
            $this->buffer->flush_all();
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
            $this->buffer->flush_all();
            throw new Exception("A section with name $this->local_current_section started but never ended. You started another section with name $name. What is this, man!");
        }

        // if there is any previous content, push that to stack.
        // content from template buffer.
        $__prev_content = $this->buffer->get_current_content();
        $this->buffer->clean_current();
        // cleaning template specific buffer.
        if($__prev_content !== ''){
            $this->root_template->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $__prev_content]);
        }
        $this->local_current_section = $name;

        // specific buffer for a specific section.
        $this->buffer->start();
    }

    final function section_end($name){
        if($this->local_current_section !== $name){
            // ending section specific buffer.
            $this->buffer->flush_all();
            throw new Exception("Current section $this->local_current_section did not end when you want to end section $name. Too err is human, fix it!");
        }
        // content section buffer.
        $section_content = $this->buffer->get_current_content();
        $this->buffer->clean_current();
        $this->local_current_section = null;
        // ending section specific buffer.
        $this->root_template->__add_to_section_map($name, $section_content);
    }

    final function section_value($name, $value){
        /*
         * This is equivalent to:
         *  this->section_start($name);
         *      echo $value;
         *  this->section_end($name)
         *
         * Caution: if ever the implementation of start end method changes, those must be reflected in this method to.
         *      For the sake of lesser code I am just calling __add_to_section from the root template.
         *      + No, I am not taking the shortcut as that would discard the previous content and fixing that would add up lines of code.
         *      + Keeping it dry and also keeping it bug free until those two functions are.
         */
        $this->section_start($name);
            echo $value;
        $this->section_end($name);
    }

    final function load_view($name, $ctx=[], $file_ext=''){
        if ($this->view_loaded === true){
            // push any orphan content
            $_prev_content = $this->buffer->get_current_content();
            $this->buffer->clean_current();
            $this->root_template->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $_prev_content]);
            // throw new Exception("Cannot be loaded twice.");
            $template = new self($this->root_template, $this->router, $this->app);
            $ctx['template'] = $template;
            $template->load_view($name, $ctx, $file_ext);
            return;
        }
        $this->view_loaded = true;

        // buffering for templating.
        $this->buffer->start();

        $ctx['template'] = $this;
        $this->router->_halka_load_view($name, $ctx, $file_ext);
        // if a section was not ended, throw here.
        if(!is_null($this->local_current_section)){
            /*
            ob_end_flush(); // for buffer started in section_/start/end/declare.
            ob_end_flush(); // for buffer template buffer/this load_view function.
            */
            $this->buffer->flush_all();
            throw new Exception("A section with name $this->local_current_section never ended.");
        }else{
            // $__end_contents = ob_get_contents();
            $__end_contents = $this->buffer->get_current_content();
            $this->buffer->clean_current();
            $this->root_template->__push_content_to_stack(null, [TEMPLATE_TYPE_TEXT, $__end_contents]);
        }
        // completely clean the content as we no longer need them.
        $this->buffer->end_all();
        $this->local_current_section = null;
        unset($this->root_template);
        unset($this->router);
        unset($this->app);
        unset($this->buffer);
    }

}

class HalkaViewer{
	protected $request;
	
	protected $truth_store = []; // context store.
	
	function __construct(){}
	
	function before(HalkaRequest $req, HalkaResponse $resp, $template){}
	
	function after(HalkaRequest $req, HalkaResponse $resp, $template){}
	
	final function _method_handler_missing($method, $req, $resp, $template){
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

//    final function call_view_function($function_name, $req, $resp, $template){
//        return $function_name($req, $resp, $template);
//    }
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
    //        var_dump($pattern);
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
    private $viewer_executed = false;

    private $app;
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
            //var_dump($route_obj);
            //var_dump($viewer);
            $this->routes[] = [
                'route' => $route_obj,
                'viewer' => $viewer
            ];

            if($route_name){
                $this->route_name_2_route_idx[$route_name] = $idx;
            }
        }
    }

    function __construct(HalkaApp $app, $routes) {
        $this->app = $app;
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

        $is_clean_url = $this->app->is_clean_url();
        $base_url = $this->app->get_base_url();
        $front_script = $this->app->get_frontscript();
		
		if(!$is_clean_url){
            $new_url = '/' . $base_url . '/' . $front_script . '/' . $new_url;
        }else{
			$new_url = '/' . $base_url . '/' . $new_url;
		}
		
		return $new_url;
    }

    private function get_viewer($uri){ // TODO: this should be renamed $path or url_path
        $base_url = $this->app->get_base_url();
        $front_script = $this->app->get_frontscript();
        //var_dump($base_url);
        //var_dump($front_script);
        if(starts_with($uri, $base_url)){
            $uri = substr($uri, strlen($base_url));
            $uri = halka_trim_url($uri);
        }
        if(starts_with($uri, $front_script)){  // eg. index.php
            $uri = substr($uri, strlen($front_script));
            $uri = halka_trim_url($uri);
        }

        $uri_parts = to_uri_parts($uri);
        $uri_last_part = $uri_parts[count($uri_parts) - 1];

        // TODO: try to autoload view_404 function or View404 class.
        // also they are viewer - not views anymore.
        if(function_exists('view_404') || class_exists('View404')){
            if(function_exists('view_404')){
                $viewer = 'view404';
            }else{
                $viewer = 'View404';
            }
        }else{
            $viewer = 'halka_view404';
        }

        // TODO: try to autoload forbidden function or class.
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

    private function process_request_method($method_array, array $arguments){
        try{
            return call_user_func_array($method_array, $arguments);
        }catch(DoneException $e){
            return false;
        }
    }

    function exec_viewer($uri){  // TODO: this should be path not uri.
        if($this->viewer_executed === true){throw new Exception("Viewer is already in execution");}else{$this->viewer_executed = true;}

        $uri = halka_trim_url($uri);
        // start request processing.

        $viewer_n_params = $this->get_viewer($uri);
        $viewer = $viewer_n_params[0];
        $route_params = $viewer_n_params[1];
        $class_or_function_exists = function_exists($viewer) || class_exists($viewer);

        if(!$class_or_function_exists){
            // try to load a function or class depending on it's name.
            $class_or_function_file_name = $this->app->get_viewers_dir() . '/' . $viewer . '.php';
            halka_require_if_exists($class_or_function_file_name, true);
            $class_or_function_exists = function_exists($viewer) || class_exists($viewer);
        }

        // autoloader for viewer classes that act as base and thus not referenced in routes
        // as a result not loaded in any way.
        // this is registered here to keep it local so that in future multi app env it does not
        // create conflict. e.g. for the POS app, BootstrapPos class will now be auto loaded without that being included in __autoload__.php
        spl_autoload_register(function($viewer_class_name){
            halka_require_if_exists($this->app->get_viewers_dir() . '/' . $viewer_class_name . '.php', true);
        });

        if($class_or_function_exists){
            $response_buffer = new HalkaBuffer();
            $req = new HalkaRequest($route_params);
            $resp = new HalkaResponse($response_buffer);
            $root_template = new HalkaRootTemplate($this, $this->app);
			$resp->start_buffering();

            // try: to catch non-publicly showable errors
            try{
                // function viewer processing
                if(function_exists($viewer)){
                    $this->process_request_function($viewer, $req, $resp, $root_template);
                // class viewer processing
                }else{
                    $cls = $viewer;
                    if( !is_subclass_of($cls, 'HalkaViewer') ){
                        die("View class must be a subclass of View");
                    }
                    $viewer_obj = new $cls();
                    $method = strtolower($_SERVER['REQUEST_METHOD']);

                    // before processing.
                    $before_returned = $this->process_request_method([$viewer_obj, 'before'], [$req, $resp, $root_template]);
                    $method_returned = null;
                    if($before_returned !== false){
                        if(!method_exists($viewer_obj, $method)){
                            $method_returned = call_user_func_array([$viewer_obj, '_method_handler_missing'], [strtoupper($method), $req, $resp, $root_template]);
                        }else{
                            $method_returned = $this->process_request_method([$viewer_obj, $method], [$req, $resp, $root_template]);
                        }
                    }
                    if($method_returned !== false){
                        $after_returned = $this->process_request_method([$viewer_obj, 'after'], [$req, $resp, $root_template]);
                    }
                    // no use of $after returned values.
                }
                // catch: the errors and process.
            }catch(DoneException $de){
                // go on to the next step: stop buffer/commit.
                // throw $de;? - no
            }
			$resp->stop_buffering();
        }else{
            die("View function/class called $viewer does not exist.");
        }
    }

    function _halka_load_view($name, array $ctx=[], $file_ext=''){
        $view_file = $this->app->get_view_file($name, $file_ext);unset($name);unset($file_ext);
        if(isset($ctx['context'])){
            // no messing with context variable from template or view. It will be set from
            // halka_load_view.
            unset($ctx['context']);
        }
        $context = $ctx;
        extract($ctx);
        require $view_file;
    }

    function get_url($route_name, $params=[], $query_params=[]){
        return $this->make_url($route_name, $params, $query_params);
    }

    function echo_url($route_name, $params=[], $query_params=[]){
        return $this->get_url($route_name, $params, $query_params);
    }
}


class HalkaRootApp extends HalkaApp{
    private static $_instance_count = 0;
    public $is_root_app = true;

    function __construct($base_dir, $front_script, $config=[]) {
        if(static::$_instance_count > 0){
            throw new Exception("Root app cannot exist more than once");
        }else{
            static::$_instance_count++;
        }
        parent::__construct($base_dir, $front_script, $config);
    }
}


class HalkaApp{
    protected $default_config = [
        'UTILS_DIR' => 'utils',
        'VIEWS_DIR' => 'views',
        'VIEWERS_DIR' => 'viewers',
        'MODELS_DIR' => 'models',
        'ASSETS_DIR' => 'assets'
    ];
    protected $real_config = []; // app specific and thus not static

    protected $basedir;
    protected $front_script;
    protected $utils_dir;
    protected $views_dir;
    protected $viewers_dir;
    protected $models_dir;
    protected $assets_dir;
    /*
     * HALKA_BASEDIR
     * HALKA_FRONTSCRIPT
     * HALKA_UTILS_DIR
     * HALKA_VIEWS_DIR
     * HALKA_VIEWERS_DIR
     * HALKA_MODELS_DIR
     * HALKA_ASSETS_DIR
     */

    protected $routes = [];
    protected $settings = [];
    protected $base_url;
    protected $current_url;
    protected $is_clean_url;

    protected $is_root_app = false;
    private $booted = false;

    final function is_root(){return $this->is_root_app;}
    function is_booted(){return $this->booted;}
    function is_clean_url(){return $this->is_clean_url;}

    function get_base_url(){return $this->base_url;}
    function get_current_url(){return $this->current_url;}

    function get_routes(){return $this->routes;}
    function get_settings(){return $this->settings;}

    function get_basedir(){return $this->basedir;}
    function get_frontscript(){return $this->front_script;}
    function get_utils_dir(){return $this->utils_dir;}
    function get_views_dir(){return $this->views_dir;}
    function get_viewers_dir(){return $this->viewers_dir;}
    function get_models_dir(){return $this->models_dir;}
    function get_assets_dir(){return $this->assets_dir;}

    function __construct($basedir, $front_script, $config=[]) {
        $this->basedir = $basedir;
        $this->front_script = basename($front_script);
        // setting up the directories according to the config.
        if(array_key_exists('UTILS_DIR', $config)){
            $this->utils_dir = $this->basedir . '/' . $config['UTILS_DIR'];
            $this->real_config['UTILS_DIR'] = $config['UTILS_DIR'];
        }else{

            $this->utils_dir =
                $this->basedir . '/'
                . ($this->default_config['UTILS_DIR']);
            $this->real_config['UTILS_DIR'] = $this->default_config['UTILS_DIR'];
        }
        if(array_key_exists('VIEWS_DIR', $config)){
            $this->views_dir = $this->basedir . '/' . $config['VIEWS_DIR'];
            $this->real_config['VIEWS_DIR'] = $config['VIEWS_DIR'];
        }else{
            $this->views_dir = $this->basedir . '/' . $this->default_config['VIEWS_DIR'];
            $this->real_config['VIEWS_DIR'] = $this->default_config['VIEWS_DIR'];
        }
        if(array_key_exists('VIEWERS_DIR', $config)){
            $this->viewers_dir = $this->basedir . '/' . $config['VIEWERS_DIR'];
            $this->real_config['VIEWERS_DIR'] = $config['VIEWERS_DIR'];
        }else{
            $this->viewers_dir = $this->basedir . '/' . $this->default_config['VIEWERS_DIR'];
            $this->real_config['VIEWERS_DIR'] = $this->default_config['VIEWERS_DIR'];
        }
        if(array_key_exists('MODELS_DIR', $config)){
            $this->models_dir = $this->basedir . '/' . $config['MODELS_DIR'];
            $this->real_config['MODELS_DIR'] = $config['MODELS_DIR'];
        }else{
            $this->models_dir = $this->basedir . '/' . $this->default_config['MODELS_DIR'];
            $this->real_config['MODELS_DIR'] = $this->default_config['MODELS_DIR'];
        }
        if(array_key_exists('ASSETS_DIR', $config)){
            $this->assets_dir = $this->basedir . '/' . $config['ASSETS_DIR'];
            $this->real_config['ASSETS_DIR'] = $config['ASSETS_DIR'];
        }else{
            $this->assets_dir = $this->basedir . '/' . $this->default_config['ASSETS_DIR'];
            $this->real_config['ASSETS_DIR'] = $this->default_config['ASSETS_DIR'];
        }
    }

    final function boot(){
        if($this->booted === true){
            throw new Exception("Cannot boot an app twice.");
        }

        // require necessary files.
        $routes = $this->require_if_exists('routes.php', true);
        $settings = $this->require_if_exists('settings.php', true);
        halka_require_if_exists($this->default_config['UTILS_DIR'] . '/__autoload__.php', true);
        halka_require_if_exists($this->default_config['VIEWERS_DIR'] . '/functions.php', true);
        halka_require_if_exists($this->default_config['VIEWERS_DIR'] . '/classes.php', true);  // classes can have dependency of functions required above.
        halka_require_if_exists($this->default_config['VIEWERS_DIR'] . '/__autoload__.php', true);
        halka_require_if_exists($this->default_config['VIEWS_DIR'] . '/__autoload__.php', true);
        halka_require_if_exists($this->default_config['MODELS_DIR'] . '/__autoload__.php', true);

        // require post processing
        if($routes){
            foreach ($routes as $key => $value){
                $this->routes[$key] = $value; // avoiding array_merge to avoid number string (e.g. that 2) to 0 number bug.
            }
            // $this->routes = array_merge($this->routes, $routes);
        }
        if($settings){
            $this->settings = array_merge($this->settings, $settings);
        }

        // require from settings
        if(isset($this->settings['require'])){
            $to_require = $this->settings['require'];
            if (is_string($to_require)){
                $this->require_($to_require);
            }elseif(is_array($to_require)){
                foreach($to_require as $fn){
                    $this->require_($fn);
                }
            }
        }

        // set base url & current url
        if(isset($this->settings['base_url'])){
            $this->base_url = halka_trim_url($this->settings['base_url']);
        }else{
            $this->base_url = halka_trim_url('/');
        }

        if(isset($this->settings['clean_url'])){
            $this->is_clean_url = (boolean)$this->settings['clean_url'];
        }else{
            $this->is_clean_url = false;
        }

        // TODO: it will be current_path not current_url - fix it later.
        $this->current_url = halka_trim_url(url_without_query($_SERVER['REQUEST_URI']));
//        var_dump($routes);
        $_router = new HalkaRouter($this, $this->routes);
        $_router->exec_viewer($this->current_url);
    }

    function require_($fn, $once=false){
        $fn = $this->basedir . '/' . $fn;
        return halka_require($fn, $once);
    }

    function require_if_exists($fn, $once=false){
        $fn = $this->basedir . '/' . $fn;
        return halka_require_if_exists($fn, $once);
    }

    // util functions
    function get_view_file($name, $file_ext=''){
        if($file_ext === ''){
            $fn_php = $this->views_dir . "/$name.php";
            $fn_html = $this->views_dir . "/$name.html";
            if(file_exists($fn_php)){
                return $fn_php;
            }elseif(file_exists($fn_html)){
                return $fn_html;
            }else{
                throw new Exception("View $name not found");
            }
        }else{
            $fn_any = $this->views_dir . "/$name.$file_ext";
            if(!file_exists($fn_any)){
                throw new Exception("View $name with ext $file_ext not found");
            }
            return $fn_any;
        }
    }

    function get_asset($name, $as_base_64=false){
        $fn = $this->assets_dir . '/' . $name;
        $cnt = file_get_contents($fn);
        if($as_base_64 === true){
            $cnt =  base64_encode($cnt);
        }
        return $cnt;
    }

    function echo_asset($name, $as_base_64=false){
        echo $this->get_asset($name, $as_base_64);
    }

    function asset_url($name){
        // no need of thinking about clean url here as it is not dynamic content.
        return '/' . $this->base_url . '/' . $this->real_config['ASSETS_DIR'] . '/' . $name;
    }

    function echo_asset_url($name){
        $url = $this->asset_url($name);
        echo $url;
        return $url;
    }
}


function start_halka($basedir, $front_script, $config=[]){
    $app = new HalkaRootApp($basedir, $front_script, $config);
    $res = $app->boot();
    return $res;
}


final class Halka{
    final static function start($basedir, $front_script, $config=[]){
        start_halka($basedir, $front_script, $config);
    }
}


<?php

/**
* Inicializa la configuracion basica de la aplicaciÃ³n
* debe ser llamado al principio, ya que realiza "session_start"
*/


function get_db(){
    $services_json = json_decode(getenv("VCAP_SERVICES"),true);
    $mongo_config = $services_json["mongodb-1.8"][0]["credentials"];
    $username = $mongo_config["username"];
    $password = $mongo_config["password"];
    $hostname = $mongo_config["hostname"];
    $port = $mongo_config["port"];
    $db = $mongo_config["db"];
    $name = $mongo_config["name"]; 
    $connect = "mongodb://${username}:${password}@${hostname}:${port}/${db}";
    $m = new Mongo($connect);
    $db = $m->selectDB($db); 
    return $db;
}

function get_db_local(){
    $m = new Mongo();
    $db = $m->ereselchef;
    return $db;
}


class DB{
	static $db;
	static function init($key){
        if ($_SERVER['HTTP_HOST'] == 'localhost')
            DB::$db = get_db_local();
        else
            DB::$db = get_db();
	}
}

function normalize($string){
    $string = utf8_decode($string);
    $string = strtr($string, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ´`',
                           'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby\'\'');
    return utf8_encode($string);	
}

function stemm_text($string){
	include_once('stemm_es.php');
	return implode(" ", array_filter(array_map('stemm_es::stemm', explode(" ", idfy($string)))));
}

function idfy($title){
	return urlfy2idfy(urlfy($title) );
}

function idfy2urlfy($title){
	return str_replace ( ' ' , '-' , $title );
}
function urlfy2idfy($title){
	return str_replace ( '-' , ' ' , $title );
}

function urlfy($title){
	$title = str_replace('&', '-and-', $title);
    $title = normalize($title);
    $title = preg_replace('/[^A-Za-z0-9]/si', '-', $title);//remove all illegal chars
    $title = preg_replace('/-+/', '-', $title); //mas de un ---- (no: un mas o un menos)
    $title = trim($title, '-');
	$title = strtolower ( $title );
	return $title;
}

function new_id(){
	$dic = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$mx = strlen($dic)-1;
	return $dic[mt_rand(0, $mx)].$dic[mt_rand(0, $mx)].$dic[mt_rand(0, $mx)].$dic[mt_rand(0, $mx)];
}

function stopword($word){
	return $word and !in_array(trim($word), array_map('trim',file('stop.txt')));
}


function call_method($obj, $method, $params){
	$ro = new ReflectionObject($obj);
	$rc = $ro;
	//$rc = new ReflectionClass($class);
	if($rc->hasMethod($method)){
		$rf = new ReflectionMethod($obj, $method);
		$ps = $rf->getParameters();
		$new_params = array();
		foreach($ps as $p)
			$new_params[] = $params[$p->name];
		return $rf->invokeArgs($obj, $new_params);
	}
	
}



function twig_desc($str){
	$str = trim(preg_replace("/((<.*?>)|\r|\n|\ )+/"," ",$str));
	return (strlen($str)>139) ? substr($str, 0, strrpos(substr($str, 0, 141), " ")) : $str;
}
function twig_summary($str){
	$str = trim(preg_replace("/((<.*?>)|\r|\n|\ )+/"," ",$str));
	return (strlen($str)>139) ? substr($str, 0, strrpos(substr($str, 0, 141), " "))."&hellip;" : $str;
}

function twig_nl2p($text){
	$t = trim($text);
	return $t?("<p>" . preg_replace("/[\r\n]+/", "</p>\n<p>",  $t ) . "</p>") : $t;
}

function create_twig($func, $params){
	return call_user_func_array($func, $params);
}

class Nuts_Nuts{
    public $modules = array();
    public $objs = array();
    public $config = array();
    public $errors = array();
    public $error404 = false;
    static public $redirects = true;
	
	public function __construct($key){
		$this->key = $key;
	}
	
	public function mod($name){
		return $this->objs[$name];
	}
	
	function stats(){

		$st = array(
			'REQUEST_TIME' => new MongoDate($_SERVER['REQUEST_TIME'])
			,'HTTP_ACCEPT' => $_SERVER['HTTP_ACCEPT']
			,'HTTP_ACCEPT_CHARSET' => $_SERVER['HTTP_ACCEPT_CHARSET']
			,'HTTP_ACCEPT_ENCODING' => $_SERVER['HTTP_ACCEPT_ENCODING']
			,'HTTP_ACCEPT_LANGUAGE' => $_SERVER['HTTP_ACCEPT_LANGUAGE']
			,'HTTP_CACHE_CONTROL' => $_SERVER['HTTP_CACHE_CONTROL']
			,'HTTP_CONNECTION' => $_SERVER['HTTP_CONNECTION']
			,'HTTP_HOST' => $_SERVER['HTTP_HOST']
			,'HTTP_PRAGMA' => $_SESSION['HTTP_PRAGMA']
			,'HTTP_REFERER' => $_SERVER['HTTP_REFERER']
			,'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT']
			,'HTTP_X_WAP_PROFILE' => $_SESSION['HTTP_X_WAP_PROFILE']
			//,'HTTPS' => $_SERVER['HTTPS']
			,'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR']
			,'REMOTE_HOST' => $_SERVER['REMOTE_HOST']
			,'REQUEST_URI' => $_SERVER['REQUEST_URI']
			,'REDIRECT_URL' => $_SERVER['REDIRECT_URL']
			,'HTTP_COOKIE' => $_SERVER['HTTP_COOKIE']
			,'UNIQUE_ID' => $_SERVER['UNIQUE_ID']
			,'GET' => $_GET
			,'POST_ACTION' => $_POST['action']
			,'SESSION_USER' => $_SESSION['user']
		);
		//dump($st);
		DB::$db->statistics->insert($st);
	}
	
	
	function processURL($mods=array()){
		$this->uri = isset($_SERVER['REDIRECT_URL'])? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
		$this->base = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\")."/";
		$this->config['base'] = $this->base;
		$this->config['request'] = substr($this->uri,strlen($this->base));
		$this->uri = explode('/', $this->config['request']);
		if (substr($this->config['request'], -1) == "/"){header("Location: ".substr($_SERVER['REDIRECT_URL'],0, -1), TRUE, 301);exit();} // redireccionar direcciones acabadas en "/"
		$this->config['page'] = $this->uri[0];
				
		try{
			DB::init($this->key);
		}
		catch(MongoConnectionException $e){
			die("<br><blockquote><h1>No se ha podido conectar con MongoDB");
		}
		
		session_start();
		$this->config['session'] = &$_SESSION;
		$this->config['get'] = &$_GET;
		$this->config['post'] = &$_POST;
		$this->config['server'] = &$_SERVER;
		//$this->errors = array();
		
		@$this->stats();
		
		foreach($mods as $mod)
			$this->addModule($mod);
		$this->execActions();
		
	}
	
	public function addModule($cls){
		$this->modules[] = $cls;
		Nuts_Autoloader::autoload($cls);
		$obj = new $cls($this, @$_SESSION['user']['id']);
		$config[$cls] = $obj;
		$this->objs[$cls] = $obj;
	}
	
	public function execActions(){
		if (isset($_POST['action'])){
			foreach ($this->objs as $o){
				call_method($o,'public_'.$_POST['action'], $_POST);
			}
		}
	}

	function process_pattern($pattern){
		$pattern = preg_replace("@([^/\.]+)@", "($1)", $pattern);
		$pattern = preg_replace("/%([a-z0-9\_]+)/i", "?P<$1>[^\\/\.]{4,}", $pattern);
		$pattern = preg_replace("/#([a-z0-9\_]+)/i", "?P<$1>[\d]+", $pattern);
		$pattern = str_replace("%", "[^\\/\.]{4,}", $pattern);
		$pattern = str_replace("#", "[\d]+", $pattern);
		$pattern = str_replace(".", '\.', $pattern);
		if(!$pattern)$pattern="()";
		return "@^$pattern$@";
	}

	function create(&$config, $item){
		$cp = array();
		foreach($item['params'] as $k=>$v) $cp[$k] = $this->config[$v];
		return call_user_func_array($item['func'], $cp);
	}

	function route($pattern, $tpl=null, $create='{}'){
		$req = $this->config['request'];
		preg_match($this->process_pattern($pattern), $req, $match);
		$this->config['routes'][] = array($pattern, $tpl);
		if ($match){
			$match = array_map("urldecode", $match);
			foreach ($match as $k => $v) if (!is_numeric($k)){$this->config[$k] = $v;}
			foreach ( json_decode($create, true) as $key => $item ){
				$this->config[$key] = $this->create($this->config, $item);
				if(!$this->config[$key] and !(!isset($item['optional']) or $item['optional'])) return ; // A->B, No A o B
			}
			if ($tpl) $this->config['page'] = $tpl;
			array_shift($match);
			return $match;
		}
	}

	function needLogin($msg){
		if (!$_SESSION['user'])
		{
			header("location: login?msg=".urlencode($msg)."&continue=".urlencode($_SERVER['REQUEST_URI']));;
			exit();
		}else{
			return True;
		}
	}

	function error404(){
		$this->error404 = true;
		fwrite(fopen("log.log",'a'),print_r($_SERVER, true));
		header('HTTP/1.1 404 Not Found');
		#header('Location: ' . $config['base'] . '404');
		#exit();
	}
	
	function addsTwig(){			
		$this->twig->addGlobal('error', $this->errors);
		$this->twig->addGlobal('base', $this->base);
		$this->twig->addGlobal('static', $this->base.'static/');
		$this->twig->addGlobal('cssdir', $this->base.'static/css/');
		$this->twig->addGlobal('jsdir', $this->base.'static/js/');
		$this->twig->addGlobal('imagedir', $this->base.'static/image/');
		$this->twig->addGlobal('uri', $this->uri);
		$this->twig->addGlobal('session', $_SESSION);
		$this->twig->addGlobal('get', $_GET);
		$this->twig->addGlobal('post', $_POST);
		$this->twig->addGlobal('server', $_SERVER);
		
		$this->twig->addFilter('normalize', new Twig_Filter_Function('normalize'));
		$this->twig->addFilter('idfy', new Twig_Filter_Function('idfy'));
		$this->twig->addFilter('urlfy', new Twig_Filter_Function('urlfy'));
		$this->twig->addFilter('idfy2urlfy', new Twig_Filter_Function('idfy2urlfy'));
		$this->twig->addFilter('urlfy2idfy', new Twig_Filter_Function('urlfy2idfy'));

		$this->twig->addFilter('summary', new Twig_Filter_Function('twig_summary'));
		$this->twig->addFilter('desc', new Twig_Filter_Function('twig_desc'));
		$this->twig->addFilter('nl2p', new Twig_Filter_Function('twig_nl2p'));

		foreach ($this->objs as $cls => $o){
			$this->twig->addGlobal($cls, $o);
		}
	}
	
	function initTwig(){
		require('./Twig/Autoloader.php');
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem('templates');
		$this->twig = new Twig_Environment($loader 
		//,LOCAL?array():array( 'cache' => 'store') 
		);
		$this->addsTwig();
		return $this->twig;
	}
	
	function render(){
		$template = $this->twig->loadTemplate( ($this->error404 ? '404' : $this->config['page']) . ".twig");
		echo $template->render($this->config);
		//trigger_error( dump($this->st), E_USER_NOTICE);
	}
	
	static function successfully($url=null){
		if(isset($_GET["continue"]))
			Nuts_Nuts::redirect(urldecode($_GET["continue"]));
		else
			Nuts_Nuts::redirect($url?$url:$_SERVER['REQUEST_URI']);
	}

	function loginSuccessfully($goIndex=TRUE){
		//echo $_SERVER['REQUEST_URI'];
		if(isset($_GET["continue"]))
			Nuts_Nuts::redirect(urldecode($_GET["continue"]));
		else //TODO no login ni register
			Nuts_Nuts::redirect($goIndex?$this->config['base']:$_SERVER['REQUEST_URI']);
	}
	
	function newError($k, $v){
		$this->errors[$k] = $v;
	}
    
    static public function redirect($url){
        if (Nuts_Nuts::$redirects)
            header("location: " . $url);
    }

}

?>
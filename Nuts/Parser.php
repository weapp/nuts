<?php
class Parse{
	var $str;
	var $name;
	var $error;

	function __construct($str, $name){
		$this->str = $str;
		$this->name = $name;
		$this->ok = true;
	}
	
	public static function is_error(){return isset($GLOBALS['config']['error']);}
	public static function post_error($key, $error){if($error) $GLOBALS['config']['error'][$key] = $error;}

	public function error($error = false){
		$this->ok = false;
		if($error) $GLOBALS['config']['error'][$this->name] = $error;
		if($error) $GLOBALS['nuts']->newError($this->name, $error);
	}
	
	public function minlen($int, $msg=false){
		if ($this->ok and $int > strlen($this->str))
			$this->error($msg?$msg:"El tamaño minimo del campo es $int");
		return $this;
	}

	public function maxlen($int, $msg=false){
		if ($this->ok and $int < strlen($this->str))
			$this->error($msg?$msg:"El tamaño maximo del campo es $int");
		return $this;
	}

	public function in_array(&$arr, $default=null){
		if (!in_array($this->str, $arr)) $this->str = $default;
		return $this;
	}

	/*
	public function exist($q, $arr, $msg=false){
		if ($this->ok and !query($q, $arr))
			$this->error($msg?$msg:"Debe existir un {$this->name}");
		return $this;
	}

	public function not_exist($q, $arr){
		if ($this->ok and query($q, $arr))
			$this->error("Ya existe un {$this->name}");
		return $this;
	}
	*/
	
	public function exist($q, $arr, $msg=false){
		if ($this->ok and !call_user_func_array($q, $arr))
			$this->error($msg?$msg:"Debe existir un {$this->name}");
		return $this;
	}

	public function not_exist($q, $arr, $msg=false){
		if ($this->ok and call_user_func_array($q, $arr))
			$this->error($msg?$msg:"Ya existe un {$this->name}");
		return $this;
	}

	public function match($pattern, $msg=false){
		if ($this->ok and !preg_match($pattern, $this->str))
			$this->error($msg?$msg:"El valor no es válido");
		return $this;
	}

	public function not_empty($msg=false){
		if ($this->ok and empty($this->str))
			$this->error($msg?$msg:"El campo no puede estar vacío");
		return $this;
	}
	
	public function not_empty_html($msg=false){
		$v = trim(strip_tags($this->str));
		if ($this->ok and empty($v))
			$this->error($msg?$msg:"El campo no puede estar vacío");
		return $this;
	}

	public function is_empty($msg=false){
		if ($this->ok and !empty($this->str))
			$this->error($msg?$msg:"El campo debe estar vacío");
		return $this;
	}

	public function eq($str, $error=false){
		if ($this->ok and $this->str!=$str)
			$this->error( ($error? $error : "El valor no es válido") );
		return $this;
	}

	public function __toString(){
		return (string) $this->str;
	}

}

//function parse($str, $var){return new Parse($str, $var);}

class Nuts_Parser{
	function __construct(){}
	public function parse($str, $name){
		return new Parse($str, $name);
	}
}
?>
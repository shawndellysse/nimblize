<?php
/**
 * @package NimbleSupport
 */
class Cycler {
	protected static $instance;
	
	public static function getInstance()
	{
	  if(self::$instance == NULL) {
		  self::$instance = new self();
	  }
	  return self::$instance;
	}

	public function __construct() {
		$this->scopes = array();
		$this->cycle_states = array();
	}
	
	
	public function cycler_exsits($key) {
		return (isset($this->scopes[$key]) && !empty($this->scopes[$key]));
	}
	
	public function set_cycler($key, $args) {
		$this->scopes[$key] = $args;
		$this->cycle_states[$key] = 0;
	}
	
	public function get_cycler($key) {
		if($this->cycle_states[$key] == (count($this->scopes[$key]))) {
			$this->cycle_states[$key] = 0;
		}
		$return = $this->scopes[$key][$this->cycle_states[$key]];
		$this->cycle_states[$key] = $this->cycle_states[$key] + 1;
		return $return;
	
		
	}
	
	public static function reset_cycler() {
    $class = self::getInstance();
		$class->scopes = array();
		$class->cycle_states = array();
	}


}
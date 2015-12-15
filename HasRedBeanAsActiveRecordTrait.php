<?php
namespace glasteel;

trait HasRedBeanAsActiveRecordTrait
{
	
	protected $primary_bean;
	protected $aux_beans = [];

	private $rb_class = 'RedBeanPHP\OODBBean';
	private $primary_bean_cols = null;
	
	public function setPrimaryBean($bean=null){
		if ( !property_exists($this, 'primary_bean_table') || !is_string($this->primary_bean_table) || !$this->primary_bean_table ){
			throw new \Exception('A [primary_bean_table] property must be defined as a string on ['. get_class($this) . ']');
		}
		$this->primary_bean = $this->getBean($bean);
	}//setPrimaryBean()

	public function setAuxBean($key,$bean,$table){
		$this->aux_beans[$key] = $this->getBean($table,$bean);
	}//setAuxBean()

	private function getBean($bean,$table=false){
		if ( $table === false ){
			$table = $this->primary_bean_table;
		}
		$rb_class = $this->rb_class;
		$model_class = 'Model_' . ucfirst($table);
		if ( is_object($bean) ){
			if ( $bean instanceof $rb_class ){
				$bean->box();	
			}
			if ( false === ($bean instanceof $rb_class) && false === ($bean instanceof $model_class) ){
				throw new \InvalidArgumentException(
					__METHOD__ . ' expects instance of ' . $rb_class . ' or ' . $model_class . ' 
					when passed an object, ' . get_class($bean) . ' given.'
				);
			}
		}elseif ((is_int($bean) || ctype_digit($bean)) && (int)$bean > 0){
			$bean = $this->db->load( $table, $bean );
			if ( $bean->id === 0 ){
				throw new \InvalidArgumentException(
					__METHOD__ . ' expects valid ' . $table . ' id 
					when passed an integer, ' . $bean . ' given.'
				);
			}
		}elseif (!is_null($bean)){
			throw new \InvalidArgumentException(
				__METHOD__ . ' expects instance of RedBeanPHP\OODBBean or ' . $model_class . ' 
				when passed an object, or valid ' . $model_class . ' id when passed an integer.'
			);
		}else{
			$bean = $this->db->dispense( $table );
		}
		return $bean;
	}//getBean()

	public function __call($method,$args){
		$cols = $this->getPrimaryBeanCols();
		if ( array_key_exists($method, $cols) && empty($args) ){
			return $this->$method;
		}
	}//__call()

	public function __get($key){
		$cols = $this->getPrimaryBeanCols();
		if ( array_key_exists($key, $cols) ){
			return $this->primary_bean->$key;
		}
		if ( array_key_exists($key, $this->aux_beans) ){
			return $this->aux_beans[$key];
		}
	}//__get()

	public function __set($key,$value){
		$cols = $this->getPrimaryBeanCols();
		if ( array_key_exists($key, $cols) ){
			$this->primary_bean->$key = $value;
		}
	}//__set()

	private function getPrimaryBeanCols(){
		if ( is_null($this->primary_bean_cols) ){
			$this->primary_bean_cols = $this->db->inspect($this->primary_bean_table);
		}
		return $this->primary_bean_cols;
	}//getPrimaryBeanCols()

}//trait HasRedBeanAsActiveRecordTrait
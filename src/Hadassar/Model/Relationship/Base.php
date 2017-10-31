<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\FetchType;

abstract class Base {

    protected $_name = NULL;
    protected $_type = NULL;
    protected $_src_model = NULL;
    protected $_model = NULL;
    protected $_columns = NULL;
    protected $_fetch = FetchType::LAZY;
    protected $_relational_table = NULL;
    protected $_filter_column = NULL;

    public function __construct($name, $_src_model, $metadata){
			$this->_name = $name;
			$this->_src_model = $_src_model;
      foreach ($metadata as $key => $value) {
        $field = "_$key";
        $this->$field = $value;
      }
			// $this->_model = $this->_model::instance();
			// xd($this->_model);
    }

    function f3(){
  		return @\Base::instance();
  	}

    public function getName(){
      return $this->_name;
    }

    public function getType(){
      return $this->_type;
    }

    public function getModel(){
      return $this->_model;
    }

    public function getColumns(){
      return $this->_columns;
    }

    public function getFetchType(){
      return $this->_fetch;
    }

    public function getRelationTable(){
      return $this->_relational_table;
    }

    public function getFilterColumn(){
      return $this->_filter_column;
    }

    public abstract function load(&$entity, $params = array(), $options = array());

    public abstract function set(&$entity, $params = array(), $options = array());

    public abstract function add(&$entity, $params = array(), $options = array());

    public abstract function remove(&$entity, $params = array(), $options = array());
}

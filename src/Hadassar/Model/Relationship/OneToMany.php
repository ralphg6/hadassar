<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\Relationship\Base;

class OneToMany extends Base {

    public function load(&$entity, $params = array(), $options = array()){
			$entity[$this->_name] = $this->f3()->call("{$this->_model}->get", array($entity[$this->_columns], $options));
		 	return $entity[$this->_name];
		}

    public function set(&$entity, $params = array(), $options = array()){
			$this->f3()->error(501);
		}

    public function add(&$entity, $params = array(), $options = array()){
			$this->f3()->error(501);
		}

    public function remove(&$entity, $params = array(), $options = array()){
			$this->f3()->error(501);
		}

}

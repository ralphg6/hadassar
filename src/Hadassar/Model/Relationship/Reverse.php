<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\Relationship\Base;

class Reverse extends Base {

    public function load(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];

			$rmetadata =  $this->f3()->call("{$this->_model}->getMetadata");
			$reverseRefMap = $rmetadata["relationships"];

			$tmodel = get_class($this->_src_model);

			$rrefs = array();

			foreach ($reverseRefMap as $rrefSpec) {
					if($rrefSpec->getModel() == $tmodel){
							$rrefs[] = $rrefSpec;
					}
			}

			if(count($rrefs) != 1){
				$this->f3()->error("500", "Couln't defined the reverse reference, retriveds: ".count($rrefs));
				exit();
			}

			$rrefSpec = $rrefs[0];

			$entity[$this->_name] = $this->f3()->call("{$this->_model}->fetchAll", array(
					$rrefSpec->getColumns() . " = {$id}", array(), $options
			));

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
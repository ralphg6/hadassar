<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\Relationship\Base;

class Reverse extends Base {

    public function load(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];

			$rrefSpec = $this->_getReverseRefSpec();

			$entity[$this->_name] = $this->f3()->call("{$this->_model}->fetchAll", array(
					$rrefSpec->getColumns() . " = {$id}", $params, $options
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

		public function join($alias, $parentAlias = FALSE){
			$targetRefSpec = $this->_getReverseRefSpec();
			if($targetRefSpec->getType() == "OneToMany"){
					$metadata = $targetRefSpec->getSrcModel()->getMetadata();
					$srcMetadata = $this->f3()->call($targetRefSpec->getModel()."->getMetadata");

					$parentAlias = $parentAlias ? $parentAlias : $srcMetadata['tableName'];

					return "LEFT OUTER JOIN {$metadata['tableName']} as $alias ON $alias.{$targetRefSpec->_columns}=$parentAlias.{$srcMetadata['primary']}";
			}else{
					return "";
			}
			return "";
		}

		private function _getReverseRefSpec(){
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

			return $rrefs[0];
		}
}

<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\Relationship\Base;

class OneToMany extends Base {

    public function load(&$entity, $params = array(), $options = array()){
			//xd_echo($entity, $this->_columns);
			$options["fetch_relation"] = true;
			$rel = NULL;
			if($entity[$this->_columns]){
				$rel = $this->f3()->call("{$this->_model}->get", array($entity[$this->_columns], $options));
			}
			$entity[$this->_name] = $rel;
			return $entity[$this->_name];
		}

    /*public function set(&$entity, $params = array(), $options = array()){
			$oldList = $this->load($entity, $params, $options);
			$list = $params["data"];
			$metadata = $this->f3()->call("{$this->_model}->getMetadata");
			$pkColumn = $metadata['primary'];

			foreach ($oldList as $oldItem) {
				$remains = false;
				foreach ($list as $k => $item) {
					$remains = $oldItem[$pkColumn] == $item->$pkColumn;
					if($remains){
						unset($list[$k]);
						break;
					}
				}
				if(!$remains){
					$this->remove($entity, array("subId" => $oldItem[$pkColumn]), $options);
				}
			}

			$this->add($entity, array("data" => $list), $options);

			return $this->load($entity, $params, $options);
		}

    public function add(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];

			$subEntity = $params["data"];
			//TODO fix for add support to composite keys
			$subEntity[$this->_columns] = $id;

			$subEntity = $this->f3()->call("{$this->_model}->create", array($subEntity))
			$entity[$this->_name][] = $subEntity;

		 	return $subEntity;
		}

    public function remove(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];
			$list = $params["data"];

			$metadata = $this->f3()->call("{$this->_model}->getMetadata");
			$pkColumn = $metadata['primary'];

			$this->f3()->call("{$this->_model}->remove", array("$pkColumn" => $params["subId"]));
		}*/

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

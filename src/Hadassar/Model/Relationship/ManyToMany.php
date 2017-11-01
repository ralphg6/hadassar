<?php

namespace Hadassar\Model\Relationship;

use
		Hadassar\Model\Relationship\Base;

class ManyToMany extends Base {

    public function load(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];

			$metadata = $this->f3()->call("{$this->_model}->getMetadata");

			$entity[$this->_name] = $this->f3()->call("{$this->_model}->fetchAll", array(
					"{$metadata['primary']} in (select {$this->_columns} from {$this->_relational_table} where {$this->_filter_column} = {$id})"
				, array() , $options));
			return $entity[$this->_name];
		}

    public function set(&$entity, $params = array(), $options = array()){
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
					$this->remove($entity, array("subId" => $oldItem[$pkColumn	]), $options);
				}
			}

			$this->add($entity, array("data" => $list), $options);

			return $this->load($entity, $params, $options);
		}

    public function add(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];
			$list = $params["data"];
			$db = $this->_src_model->getDB();
			foreach ($list as $key => $item) {
				$sql = "INSERT INTO {$this->_relational_table} ({$this->_filter_column}, {$this->_columns}) VALUES ($id, {$item->id})";
				//echo $sql . "\n";
				$db->exec($sql);
			}
		}

    public function remove(&$entity, $params = array(), $options = array()){
			$id = $entity[$this->_src_model->primary];
			$list = $params["data"];

			$metadata = $this->f3()->call("{$this->_model}->getMetadata");
			$pkColumn = $metadata['primary'];

			if(!$list){
				$list = array((object) array(
					$metadata['primary'] => $params["subId"]
				));
			}
			$db = $this->_src_model->getDB();
			//xd($list);
			foreach ($list as $item) {

				$sql = "DELETE FROM {$this->_relational_table} WHERE {$this->_filter_column}={$id} AND {$this->_columns}={$item->$pkColumn}";
				//echo $sql . "\n";
				$db->exec($sql);
			}
		}

}

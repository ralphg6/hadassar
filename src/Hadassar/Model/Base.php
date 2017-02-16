<?php

namespace Hadassar\Model;

class Base extends \Prefab{

	protected $_tableName = "";

	protected $_referenceMap = array();

	protected $_baseF3;

	function f3(){
		return @\Base::instance();
	}

	function get($params) {

		if(!is_array($params)){
			$params = array('param' => ':id', 'id' => $params);
		}

		if(!isset($params['where'])){
			$params['where'] = "{$this->_tableName}.id = {$params['param']}";
		}

		$where = $params['where'];

		unset($params['where']);

		return $this->fetchRow($where, $params);
	}

	function fetchRow($where, $params = array()) {
		return $this->fetchAll($where, $params)[0];
	}

	function fetchAll($where, $params = array()) {

		$params['where'] = $where;

		$items = $this->find($params);

		return $items;
	}

	function find($params) {

		$query = $params['query'] ? $params['query'] : array();

		$limit = $query['limit'] ? $query['limit'] : 10;
		unset($query['limit']);

		$page = $query['page'] ? $query['page'] : 1;
		unset($query['page']);

		$mode = $query['mode'] ? $query['mode'] : 'and';
		unset($query['mode']);

		$first = $limit*($page-1);

		$where = array();

		//xd($params);

		if(isset($params['where'])){
				if(is_array($params['where']))
						$where = array_merge($where, $params['where']);
				else
						array_push($where, $params['where']);
		}

		if(isset($query['where'])){
				array_push($where, $query['where']);
		}

		foreach ($query as $key => $value) {
			if(is_string($value)){
				$value = "'$value'";
			}
			array_push($where, "$key=$value");
		}

		$where = implode(" $mode ", $where);

		if(empty($where)){
				$where = "true=true";
		}

		/*if($this->_tableName == "tb_etapa")
			xd("select {$this->_tableName}.*
				 from {$this->_tableName}
				 where {$where}
				 limit $first,$limit ", $params);*/

		$items = $this->_execDB(
			"select {$this->_tableName}.*
		 	 from {$this->_tableName}
			 where {$where}
			 limit $first,$limit ", $params);

		return $items;
	}

	function create(&$params) {
			$columns = array_keys($params);
			$values = array();

			foreach ($params as $column => $value) {
				if(is_string($value)){
					$value = "'$value'";
				}
				if($value == NULL){
					$value = "NULL";
				}
				array_push($values, $value);
			}

			$columns = implode(", ", $columns);
			$values = implode(", ", $values);

			if(empty($columns))
				return true;

			$sql = "INSERT INTO {$this->_tableName} ({$columns}) VALUES ({$values})";

			if($this->_execDB($sql)){
				$params['id'] = $this->f3()->get('DB')->pdo()->lastInsertId();
				$params = $this->get($params['id']);
				return $params['id'];
			}

			return false;
	}

	function update($params) {
			$id = $params['id'];
			unset($params['id']);

			$updates = array();

			foreach ($params as $key => $value) {
				if(empty($value))
					continue;
				if(is_string($value)){
					$value = "'$value'";
				}
				array_push($updates, "$key=$value");
			}

			$updates = implode(", ", $updates);

			if(empty($updates))
				return true;

			$sql = "UPDATE {$this->_tableName} SET $updates WHERE id=$id";
			$this->_execDB($sql);
	}

	function remove($params) {
			$this->f3()->error(501, 'model remove');
	}

	protected function _execDB($sql, $args = array()){
			//echo($sql."\n".var_export($args, true)."\n-------------\n");

			//die($sql);
			preg_match_all('/:([a-zA-Z_]+)/', $sql, $matches);
			$values = array();

			foreach ($matches[1] as $arg) {
				$values[$arg] = $args[$arg];
			}

			// /var_dump($values);

			return $this->f3()->get('DB')->exec($sql, $values);
	}

	public function getReferenceMap(){
		return $this->_referenceMap;
	}
}

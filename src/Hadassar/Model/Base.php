<?php

namespace Hadassar\Model;

abstract class Base extends \Prefab{

	protected $_mapper;

	protected $_tableName = "";

	public $primary = "id";

	protected $_referenceMap = array();

	protected $_hasReferences = false;

	protected $_hasEagerLoadings = false;

	protected $_baseF3;

	protected $_db;

	function __construct(){

		$this->_hasReferences = sizeof($this->_referenceMap) > 0;

		foreach ($this->_referenceMap as $ref => $refSpec) {
			if($refSpec['fetch'] == FetchType::EAGER){
				$this->_hasEagerLoadings = true;
				break;
			}
		}

	}

	function f3(){
		return @\Base::instance();
	}

	function get($params) {

		if(!is_array($params)){
			$params = array('param' => ':id', 'id' => $params);
		}

		if(!isset($params['where'])){
			$params['where'] = "{$this->_tableName}.{$this->primary} = {$params['param']}";
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

		//xd_echo($query);

		$limit = isset($query['limit']) ? $query['limit'] : 10;
		unset($query['limit']);

		$page = $query['page'] ? $query['page'] : 1;
		unset($query['page']);

		$mode = $query['mode'] ? $query['mode'] : 'and';
		unset($query['mode']);

		$count = isset($query['count']);
		unset($query['count']);

		$order = $query['order'] ? $query['order'] : '';
		unset($query['order']);

		$order = str_replace(",", " ", str_replace(";", ",", $order));

		if(strlen($order) > 0){
			$order = "order by $order";
		}

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
		//if($this->_tableName == "tb_etapa")

			// xd("select {$this->_tableName}.*
			// 	 from {$this->_tableName}
			// 	 where {$where}
			// 	 offset $first limit $limit ", $params);


		if($count){
			$items = $this->_execDB(
				"select count({$this->_tableName}.{$this->primary}) as count
			 	 from {$this->_tableName}
				 where {$where}", $params);
			return intval($items[0]["count"]);
		}

		$limit_str = "offset $first limit $limit";
		if((is_bool($limit) && !$limit) || $limit < 1){
			$limit_str = "";
		}

	//	xd($limit, $limit_str);

		$items = $this->_execDB(
			"select {$this->_tableName}.*
		 	 from {$this->_tableName}
			 where {$where}
			 $order
			 $limit_str", $params);

		if($this->_hasEagerLoadings){
			foreach ($this->_referenceMap as $ref => $refSpec) {
				if($refSpec['fetch'] == FetchType::EAGER){
					foreach ($items as &$item) {
							$this->loadRef( array(
									"item" => &$item,
									"ref" => $ref,
							));
					}
				}
			}
		}

		foreach ($items as $item) {
			$this->prepare($item);
		}

		return $items;
	}

	function prepare(&$item){}

	function loadRef($params) {
		//xd_echo($params);

		$obj =& $params["item"];
		$id = $obj[$this->primary];

		$refName = $params["ref"];
		$refSpec = $this->getReferenceMap($refName);

		switch ($refSpec['type']) {
			case "one-to-many":
				$obj[$refName] = $this->f3()->call("{$refSpec['model']}->get", array($obj[$refSpec['columns']]));
				break;
			case "many-to-many":
				$obj[$refName] = $this->f3()->call("{$refSpec['model']}->fetchAll", array(
						"{$this->primary}  in (select {$refSpec['columns']} from {$refSpec['relational_table']} where {$refSpec['filter_column']} = {$id})"
					));
				break;
			case "reverse":
				$rmetadata =  $this->f3()->call("{$refSpec['model']}->getMetada");
				$reverseRefMap = $rmetadata["referenceMap"];

				$tmodel = get_class($this);

				$rrefs = array();

				foreach ($reverseRefMap as $rrefSpec) {
						if($rrefSpec["model"] == $tmodel){
								$rrefs[] = $rrefSpec;
						}
				}

				if(count($rrefs) != 1){
					$this->f3()->error("500", "Couln't defined the reverse reference, retriveds: ".count($rrefs));
					exit();
				}

				$rrefSpec = $rrefs[0];

				$obj[$refName] = $this->f3()->call("{$refSpec['model']}->fetchAll", array(
						"{$rrefSpec['columns']} = {$id}"
				));
				break;
			default:
				$this->f3()->error("405", "Reference Load Type '{$refSpec['type']}' not implemented");
				exit();
				break;
		}

		return $obj[$refName];
	}

	function create(&$params) {
			$columns = array_keys($params);
			$values = array();

			foreach ($params as $column => $value) {
				if(is_string($value)){
					$value = "'".addslashes($value)."'";
				}
				if(!is_numeric($value) && $value === NULL){
					$value = "NULL";
				}
				if(is_bool($value)){
					$value = $value ? 1 : 0;
				}
				array_push($values, $value);
			}

			$columns = implode(", ", $columns);
			$values = implode(", ", $values);

			if(empty($columns))
				return true;

			$sql = "INSERT INTO {$this->_tableName} ({$columns}) VALUES ({$values})";
		  //echo($sql."\n");

			if($this->_execDB($sql)){
				//echo "true\n";
				if(!isset($params[$this->primary])){
						$params[$this->primary] = $this->getDB()->pdo()->lastInsertId();
				}
				$params = $this->get($params[$this->primary]);
				return $params[$this->primary];
			}

			//echo "false\n";
			return false;
	}

	function update($params) {
			$id = $params[$this->primary];
			unset($params[$this->primary]);

			$updates = array();

			foreach ($params as $key => $value) {
				if(is_bool($value)){
					$value = $value ? 1 : 0;
				}
				if(is_resource($value)){
					$meta_data = stream_get_meta_data($value);
					$filename = $meta_data["uri"];
					$value = "LOAD_FILE('$filename')";
				}
				if(is_string($value)){
					$value = "'".addslashes($value)."'";
				}
				if(empty($value))
					continue;

				array_push($updates, "$key=$value");
			}

			$updates = implode(", ", $updates);

			if(empty($updates))
				return true;

			$sql = "UPDATE {$this->_tableName} SET $updates WHERE {$this->primary}=$id";
			//echo($sql);
			//exit();
			$this->_execDB($sql);
	}

	function remove($params) {
			$id = $params[$this->primary];
			unset($params[$this->primary]);

			$sql = "DELETE FROM {$this->_tableName} WHERE {$this->primary}=$id";
			$this->_execDB($sql);
	}

	protected function _execDB($sql, $args = array()){
			//xd_echo($sql."\n".var_export($args, true)."\n-------------\n");

			//die($sql);
			preg_match_all('/:([a-zA-Z_]+)/', $sql, $matches);
			$values = array();

			foreach ($matches[1] as $arg) {
				$values[$arg] = $args[$arg];
			}

			//var_dump($values);

			return $this->getDB()->exec($sql, $values);
	}

	public function fixAutoincrement(){
		$sql = "SELECT max({$this->primary}) AS max FROM {$this->_tableName}";
		$items = $this->_execDB($sql);
		$max = intval($items[0]["max"]);
		$prox = $max + 1;
		$sql = "ALTER TABLE {$this->_tableName} auto_increment = $prox";
		return $this->getDB()->exec($sql);
	}

	public function getMetada(){
		return array(
			"primary" => $this->primary,
			"tableName" => $this->_tableName,
			"referenceMap" => $this->getReferenceMap(),
		);
	}

	public function getReferenceMap($refName = NULL){
		foreach ($this->_referenceMap as &$refSpec) {
			if(!isset($refSpec['type'])){
				$refSpec['type'] = "one-to-many";
			}
		}

		return isset($refName) ? $this->_referenceMap[$refName] : $this->_referenceMap;
	}

	public function setDB($db){
		$this->_db = $db;
	}

	public function getDB(){
		if(!$this->_db){
			return $this->f3()->get('DB');
		}
		return $this->_db;
	}


}

<?php

namespace Hadassar\Model;

abstract class Base extends \Prefab{

	protected $_tableName = "";

	public $primary = "id";

	protected $_special_columns = array();

	protected $_referenceMap = array();

	protected $_relationships = array();

	protected $_hasReferences = false;

	protected $_hasEagerLoadings = false;

	protected $_baseF3;

	protected $_db;

	function __construct(){
    $this->_hasReferences = sizeof($this->_referenceMap) > 0;

		foreach ($this->_referenceMap as $refName => $refSpec) {
			$classname = "Hadassar\\Model\\Relationship\\{$refSpec['type']}";
			$this->_relationships[$refName] = new $classname($refName, $this, $refSpec);
			if($this->_hasEagerLoadings || $this->_relationships[$refName]->getFetchType() == FetchType::EAGER){
				$this->_hasEagerLoadings = true;
			}
		}
	}

	function f3(){
		return @\Base::instance();
	}

	function get($params, $options = array()) {

		//xd_echo($params);

		if(!is_array($params)){
			$params = array('param' => ':id', 'id' => $params);
		}

		if(!isset($params['where'])){
			$params['where'] = "{$this->_tableName}.{$this->primary} = {$params['param']}";
		}

		$where = $params['where'];

		unset($params['where']);

		return $this->fetchRow($where, $params, $options);
	}

	function fetchRow($where, $params = array(), $options = array()) {
    $options["_action"] = "fetchRow";

		return $this->fetchAll($where, $params, $options)[0];
	}

	function fetchAll($where, $params = array(), $options = array()) {

		$params['where'] = $where;

		if(!isset($options["_action"])){
			$options["_action"] = "fetchAll";
		}

		$items = $this->find($params, $options);

		return $items;
	}

	function find($params, $options = array()) {

		if(!isset($options["_action"])){
			$options["_action"] = "find";
		}

		$query = $params['query'] ?? array();

		$loads = $options['_load'] ?? $query['_load'] ?? array();
		unset($query["_load"]);

		$offset = $options['_offset'] ?? $query['_offset'] ?? false;
		unset($query['_offset']);

		$limit = $options['_limit'] ?? $query['_limit'] ?? 10;
		unset($query['_limit']);

		$page = $options['_page'] ?? $query['_page'] ?? 1;
		unset($query['_page']);

		if(!$offset)
			$offset = $limit*($page-1);

		$mode = $options['_mode'] ?? $query['_mode'] ?? 'and';
		unset($query['_mode']);

		$count = isset($query['_count']);
		unset($query['_count']);

		$order = $query['_order'] ? $query['_order'] : '';
		unset($query['_order']);

		$order = str_replace(",", " ", str_replace(";", ",", $order));

		if(strlen($order) < 1){
			$order = "{$this->primary} ASC";
		}

		$order = "order by $order";

		$where = array();

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
			// 	 offset $offset limit $limit ", $params);


		if($count){
			$items = $this->_execDB(
				"select count({$this->_tableName}.{$this->primary}) as count
			 	 from {$this->_tableName}
				 where {$where}", $params);
			return intval($items[0]["count"]);
		}

		$limit_str = "offset $offset limit $limit";
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


			 //xd_echo($options["_action"], $this->_special_columns);
		  foreach($this->_special_columns as $column => $colSpec){
				if($options["_action"] != "fetchRow"){
					//LIST FILTERS
					if($colSpec['list_fetch'] == FetchType::NO_FETCH){
						foreach ($items as &$item) {
							unset($item[$column]);
						}
					}
				}
			}

		unset($options["_action"]);

		if($this->_hasEagerLoadings || count($loads)){
			foreach ($this->_relationships as $ref => $refSpec) {

				if($refSpec->getFetchType() == FetchType::EAGER || in_array($ref, $loads)){

					foreach ($items as &$item) {


							$subLoads = array();
							foreach ($loads as $value) {
									$meta = explode(".", $value, 2);
									if(count($meta) == 2 && $meta[0] == $ref){
										$subLoads[] = $meta[1];
									}
							}

							$subOpts = array_merge($options, array(
								"_load" => $subLoads,
								'_limit' => -1
							));

							$this->processRef("load", array(
										"item" => &$item,
										"ref" => $ref,
								), $subOpts
							);
					}
				}
			}
		}

		foreach ($items as &$item) {
			$this->prepare($item);
		}

		return $items;
	}

	function prepare(&$item){}

	function processRef($action, $params, $options = array()) {

		$obj =& $params["item"];

		$refName = $params["ref"];
		$refSpec = $this->getRelationships($refName);

		return $refSpec->$action($obj, $params, $options);
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
					$value = $value ? "true" : "false";
				}
				if(is_object($value)){
					$value = "jsonb '".json_encode($value)."'";
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

				//xd($this->get($params[$this->primary]));

				return $this->get($params[$this->primary]);
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
				if(is_object($value)){
					$value = "'".json_encode($value)."'::jsonb";
				}
				if(empty($value))
					continue;

				array_push($updates, "$key=$value");
			}

			$updates = implode(", ", $updates);

			if(empty($updates))
				return true;

			$sql = "UPDATE {$this->_tableName} SET $updates WHERE {$this->primary}=$id";
		//	if($id == 5){
			//	echo($sql);
				//exit();
			//}
			$this->_execDB($sql);
	}

	function remove($params) {
			$id = $params[$this->primary];
			unset($params[$this->primary]);

			$sql = "DELETE FROM {$this->_tableName} WHERE {$this->primary}=$id";
			$this->_execDB($sql);
	}

	protected function _execDB($sql, $args = array()){
			xd_echo($sql."\n".var_export($args, true)."\n-------------\n");

			//die($sql);
			preg_match_all('/:([a-zA-Z_]+)/', $sql, $matches);
			$values = array();

			foreach ($matches[1] as $arg) {
				if(isset($args[$arg]))
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

	public function getMetadata(){
		return array(
			"primary" => $this->primary,
			"tableName" => $this->_tableName,
			"relationships" => $this->getRelationships(),
		);
	}

	public function getRelationships($refName = NULL){
		// foreach ($this->_relationships as &$refSpec) {
		// 	if(!isset($refSpec->getType())){
		// 		$refSpec->getType() = "one-to-many";
		// 	}
		// }

		return isset($refName) ? $this->_relationships[$refName] : $this->_relationships;
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

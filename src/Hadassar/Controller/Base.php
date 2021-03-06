<?php

namespace Hadassar\Controller;

abstract class Base extends \Prefab{

	protected $_model;

	protected $_baseF3;

	protected static $_REF_ACTIONS = array(
		"GET" => "load",
		"POST" => "add",
		"DELETE" => "remove",
		"PUT" => "set"
	);

	function f3(){
		return @\Base::instance();
	}

	function get($f3, $params) {

		$params['param'] = $params[$this->_model->primary];

		$item = $this->_model->get($params, $this->_handleOptions());

		if($item == null){
				$this->f3()->error(404);
				return;
		}

		$this->_echoJSON($item);
	}

	function getAll($f3, $params) {
		$getparts = array();
		$getparts = explode("&", $_SERVER["QUERY_STRING"]);

		$get = array();
		foreach ($getparts as $key => $value){
			if(empty($value))
				break;
			list($key, $value) = explode('=', $value);

			$value = urldecode($value);

			//array handler
			if(substr($key, -2)  == "[]"){
				$key = substr($key, 0, -2);
				if(!isset($get[$key])){
					$get[$key] = array();
				}
				$get[$key][] = $value;
			}else{
				$get[$key] = $value;
			}
		}

		//xd($get, $_GET);

		$params['query'] = $get;
		$items = $this->_model->find($params, $this->_handleOptions());
		$this->_echoJSON($items);
	}

	function create($f3, $params) {
		try{
			$data = (array) json_decode(file_get_contents('php://input'));
			if($data){
					$this->_echoJSON($this->_model->create($data));
					$this->f3()->status(201);
			}	else {
					$this->f3()->error(400);
			}
		}catch(Exception $e){
			xd($e);
		}
	}

	function update($f3, $params) {
		$data = (array) json_decode(file_get_contents('php://input'));
		$data[$this->_model->primary] = $params[$this->_model->primary];

		$this->_model->update($data);

		$this->get($f3, $params);
	}

	function delete($f3, $params) {
		$this->_model->remove($params);
		$this->f3()->status(204);
	}

	function subRoute($f3, $params){
		$route = $params['subRoute'];

		$item = (array) $this->_model->get($params["id"]);

		//xd($route, $item);

		$modelSubRoutes = array();

		foreach ($this->_model->getRelationships() as $ref => $refSpec) {

			$modelSubRoutes[$ref] = array(
					"method" => get_class($this->_model)."->processRef",
					"params" => array(
							"item" => &$item,
							"ref" => $ref,
					),
					"options" => $this->_handleOptions(),
			);
		}

		$routes = array_merge($modelSubRoutes, $this->subRoutes());

		if(!isset($routes[$route])){
			//xd($this->_model->getReferenceMap());
			$this->f3()->error(404);
			return;
		}

		$routeSpec = $routes[$route];

		if($item == null){
			$this->f3()->error(404);
			return;
		}

		if(isset($routeSpec['params'])){
			if(is_array($routeSpec['params'])){
				$params['query'] = $_GET;
				$params = array_merge($params, $routeSpec['params'], $item);
			}
		}

		$data = (array) json_decode(file_get_contents('php://input'));
		if(!empty($data)){
			// $params["data"] = array();
			// foreach ($data as $item) {
			// 	$params["data"][] = (array) $item;
			// }
			//xd($params);
			$params["data"] = $data;
		}

		//xd($params);

		$result = $this->f3()->call($routeSpec['method'], array(self::$_REF_ACTIONS[$_SERVER['REQUEST_METHOD']], $params, $routeSpec['options']));

		$this->_echoJSON($result);
	}

	public function subRoutes(){
		return array();
	}

	protected function _echoJSON($object){
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($object);
	}

	protected function _handleOptions(){
		$options = array();

	//	xd_echo($_GET);

		if(isset($_GET["_load"])){
			$options["_load"] = $_GET["_load"];
			if(!is_array($options["_load"])){
				$options["_load"] = array($options["_load"]);
			}
		}

		return $options;
	}
}

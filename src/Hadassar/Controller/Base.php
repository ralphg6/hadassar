<?php

namespace Hadassar\Controller;

abstract class Base extends \Prefab{

	protected $_model;

	protected $_baseF3;

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
		$params['query'] = $_GET;
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

		$item = $this->_model->get(array('param' => $params[$this->_model->primary]));

		$modelSubRoutes = array();

		foreach ($this->_model->getReferenceMap() as $ref => $refSpec) {

			$modelSubRoutes[$ref] = array(
					"method" => get_class($this->_model)."->loadRef",
					"params" => array(
							"item" => &$item,
							"ref" => $ref,
					),
					"options" => $this->_handleOptions(),
			);
		}

		$routes = array_merge($modelSubRoutes, $this->subRoutes());

		if(!isset($routes[$route])){
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

		//xd($params);

		$result = $this->f3()->call($routeSpec['method'], array($params, $routeSpec['options']));

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

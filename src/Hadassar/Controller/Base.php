<?php

namespace Hadassar\Controller;

abstract class Base extends \Prefab{

	protected $_model;

	protected $_baseF3;

	function f3(){
		return @\Base::instance();
	}

	function get($f3, $params) {
		$params['param'] = $params['id'];
		$item = $this->_model->get($params);

		if($item == null){
				$this->f3()->error(404);
				return;
		}

		$this->_echoJSON($item);
	}

	function getAll($f3, $params) {
		$params['query'] = $_GET;
		$items = $this->_model->find($params);
		$this->_echoJSON($items);
	}

	function create($f3, $params) {
		$this->f3()->error(501);
	}

	function update($f3, $params) {
		$data = (array) json_decode(file_get_contents('php://input'));
		$data['id'] = $params['id'];

		$this->_model->update($data);
	}

	function delete($f3, $params) {
		$this->f3()->error(501);
	}

	function subRoute($f3, $params){
		$route = $params['subRoute'];

		$item = $this->_model->get(array('param' => $params['id']));

		$modelSubRoutes = array();
		foreach ($this->_model->getReferenceMap() as $ref => $refSpec) {
			$modelSubRoutes[$ref] = array(
					"method" => get_class($this->_model)."->loadRef",
					"params" => array(
							"item" => &$item,
							"ref" => $ref,
					),
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

		$result = $this->f3()->call($routeSpec['method'], array($params));

		$this->_echoJSON($result);
	}

	public function subRoutes(){
		return array();
	}

	protected function _echoJSON($object){
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($object);
	}
}

<?php

namespace Hadassar;

class Base extends \Prefab{

  private $_f3;

  public function __construct(){
    $this->_f3 = \Base::instance();
    $this->_f3->concat("UI", ",".__DIR__."/");
  }

  public function rest($route, $controller){
    $this->_f3->route("GET /api/v1/$route/@id","{$controller}->get");
    $this->_f3->route("GET /api/v1/$route","{$controller}->getAll");
    $this->_f3->route("POST /api/v1/$route","{$controller}->create");
    $this->_f3->route("PUT /api/v1/$route/@id","{$controller}->update");
    $this->_f3->route("DELETE /api/v1/$route/@id","{$controller}->delete");
    //subRoutes
    $this->_f3->route("GET|POST|DELETE|PUT /api/v1/$route/@id/@subRoute","{$controller}->subRoute");
    $this->_f3->route("DELETE /api/v1/$route/@id/@subRoute/@subId","{$controller}->subRoute");

  }

  public function swagger($f3){

    $routes = $f3->get("ROUTES");
    ksort($routes);
    $paths = array();
    $tags = array();
    foreach ($routes as $route => $spec) {
      if (strpos($route, '/api') !== 0)
        continue;
      $path = str_replace('/api', '', $route);
      $path = preg_replace ('/(@([\w\d]+))/', '{${2}}', $path);
      $tag = explode("/", $path)[2];
      array_push($tags, $tag);
      $paths[$path] = array(
        "spec" => $spec[0],
        "tag" => $tag
      );
    }

    $f3->set("PATHS", $paths);

    sort($tags);
    $f3->set("TAGS", $tags);

    echo \Preview::instance()->render('swagger.json', "application/json");

  }

  public function run(){
    $this->_f3->route("GET /swagger.json", array($this, "swagger"));

    return $this->_f3->run();
  }

  function __call($method, $args) {
    if($this->$method){
      return call_user_func_array(array($this, $method), $args);
    }
    return call_user_func_array(array($this->_f3, $method), $args);
  }

}

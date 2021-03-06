<?php

/**
 * Quickly define a new route.
 * A new Route can be defined in two ways:
 *  @package NimblePack
 * either by passing all of the route parameters as parameters to R():
 *   R('','TestController','index','GET');
 *
 * or by using the methods of the Route class:
 *   R('')->controller("TestController")->action("index")->on("GET")->short_url('test_index');
 *
 * (thanks to:  Rafael S. Souza <rafael.ssouza [__at__] gmail.com>)
 */
function R() {
  $args = func_get_args();
  switch (count($args)) {
    case 0:
    case 2:
    case 3:
      throw new NimbleException("Incorrect number of parameters for R()");
    case 1:
      return new Route($args[0]);
    case 4:
      $r = new Route($args[0]);
      $r->controller($args[1])->action($args[2])->on($args[3]);
      return $r;
    case 5:
      $r = new Route($args[0]);
      $r->controller($args[1])->action($args[2])->on($args[3])->short_url($args[4]);
      return $r;
  }
}

function u($short_url) {
	$args = func_get_args();

	$route_info = Nimble::getInstance()->get_route_info_by_short_name($short_url);

	$args = array_merge(array($route_info->controller, $route_info->method), array_slice($args, 1));

	return call_user_func_array('UrlBuilder::url_for', $args);
}

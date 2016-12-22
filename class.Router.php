<?php

abstract class Router {

    use TLoggable;

    protected $controllersStart = [];
    protected $controllers = [];
    protected $controllersEnd = [];
    protected $request;
    protected $responseContentType;
    protected $action;
    protected $routePath;
    protected $route = [];


    public function __construct(Request $request) {
        $this->request = $request;
        $this->boot();
    }

    protected function boot() {
        if( isset($this->request->json) ) $this->responseContentType="json";
        $this->routePath = $this->request->_route_ ?? "/";
        $this->setup( $this->parseRoutePath() );
    }

    protected function parseRoutePath() {
        $routes = explode('/',$this->routePath);
        foreach( $routes as $v) {
            if (!empty($v)) $this->route[] = $v;
        }
        $this->route[0] = $this->route[0] ?? $this->getDefaultController();
        $this->route[1] =  $this->route[1] ?? 'index';
        $this->route[2] =  $this->route[2] ?? '';
        return $this->route;
    }

    public function getResponseContentType() {
        return $this->responseContentType ?? 'html';
    }


    public function addController($className, $action = NULL) {
        $className = $this->normalizeString($className);
        $action = $this->normalizeString($action);
        $this->log("Adding controller:", $className, $action);
        $this->controllers[] = [$className, $action];
        return $this;
    }

    public function getControllers() {
        return array_merge($this->controllersStart, $this->controllers, $this->controllersEnd);
    }

    public function getNextController() {
        if( count($this->controllersStart) ) return array_shift($this->controllersStart);
        else if( count($this->controllers) ) return array_shift($this->controllers);
        else if( count($this->controllersEnd) ) return array_shift($this->controllersEnd);
        else return NULL;


    }

    protected function normalizeString($string) {
        $parts = preg_split('/[-_ ]*/',$string);
        array_walk($parts,'ucwords');
        return implode('',$parts);

    }


    abstract protected function getDefaultController() : string;

    abstract protected function setup($route);



}

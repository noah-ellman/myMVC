<?php

abstract class Router extends Container {

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

    }

    public function run() {
        $response =  new Response();
        $response->prepare($this->request);
        while( NULL !==  $arr = $this->getNextController() ) {
            $controller = $arr[0];
            $action = $arr[1];
            if( !class_exists($controller) ) throw new Exception("Invalid Controller: $arr[0]");
            $controller = new $controller($this->request, $response, $action);
            if( !($controller instanceof Controller) ) throw new Exception("Invalid Controller: $arr[0] (Class exists but is not a Controller)");
            $result = $controller->run();
            if( $result instanceof Response ) $response = $result;
            if ( $result === FALSE ) {
               $this->log('!', get_class($controller), "returned false");
            }
        }
        $response->send();
    }

    protected function parseRoutePath() {
    }


    public function addController($className, $action = null) {
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
        if ( count($this->controllersStart) ) return array_shift($this->controllersStart);
        else if ( count($this->controllers) ) return array_shift($this->controllers);
        else if ( count($this->controllersEnd) ) return array_shift($this->controllersEnd);
        else return null;


    }

    protected function normalizeString($string) {
        $parts = preg_split('/[-_ ]*/', $string);
        array_walk($parts, 'ucwords');
        return implode('', $parts);

    }


    abstract protected function getDefaultController(): string;

    abstract protected function setup($route);

    abstract protected function getMiddleware();


}

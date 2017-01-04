<?php

abstract class Router extends Container {

    use TLoggable;

    protected $controllersStart = [];
    protected $controllers = [];
    protected $controllersEnd = [];
    protected $request;
    protected $action;
    protected $response;
    protected $routePath;
    protected $route = [];

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
        $this->boot();
    }

    protected function boot() {
        $middlewares = $this->getMiddleware();
        foreach( $middlewares as $v ) {
            $v[1] = $v[1] ?? 'after';
            if( $v[1] == 'before' ) $this->controllersStart[] = [$v[0]];
            else $this->controllersEnd[] = [$v[0]];
        }
    }

    protected function parseRoutePath() {  }

    abstract protected function getDefaultController() : string;

    abstract protected function setup($route);

    abstract protected function getMiddleware();

    public function run() : Response {
        $this->response->prepare($this->request);
        while (null !== $arr = $this->getNextController()) {
            $controller = $arr[0];
            $action = $arr[1] ?? '';
            if (!class_exists($controller)) throw new Exception("Invalid Controller/Middleware: $arr[0]");
            if( is_a($controller, Middleware::class, true) ) {
                $this->log("~Running Middleware: <var>$arr[0]</var>");
                $result = (new $controller())->run($this->request, $this->response, $action);
            }
            else {
                $controller = new $controller($this->request, $this->response, $action);
                if (!($controller instanceof Controller)) throw new Exception("Invalid Controller: $arr[0] (Class exists but is not a Controller)");
                $this->log("~Running Controller: <var>$arr[0]</var>");
                $result = $controller->run();
            }
            if ($result instanceof Response) $this->response = $result;
            if ($result === false) {
                $this->log('!', get_class($controller), "returned false");
            }
        }
        return $this->response;
    }

    public function getNextController() {
        if (count($this->controllersStart)) return array_shift($this->controllersStart);
        else if (count($this->controllers)) return array_shift($this->controllers);
        else if (count($this->controllersEnd)) return array_shift($this->controllersEnd);
        else return null;

    }

    public function addController($className, $action = null) {
        $className = $this->normalizeString($className);
        $action = $this->normalizeString($action);
        $this->log("Adding controller:", $className, $action);
        $this->controllers[] = [$className, $action];
        return $this;
    }

    protected function normalizeString($string) {
        $parts = preg_split('/[-_ ]*/', $string);
        array_walk($parts, 'ucwords');
        return implode('', $parts);

    }

    public function getControllers() {
        return array_merge($this->controllersStart, $this->controllers, $this->controllersEnd);
    }

    public function addMiddlewareAppended($class) {
        array_push($this->controllersEnd,$class);
    }

    public function addMiddlewarePrepended($class) {
        array_push($this->controllersStart,$class);

    }

}

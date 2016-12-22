<?php

class App {

    protected static $services = [];
    protected static $router = NULL;

    private static $instance = NULL;
    private static $request;


    public function __construct() {
        if ( self::$instance !== NULL ) throw new Exception("App is a singleton.");
        self::$instance = $this;
        $this->boot();
    }

    protected function boot() {
        $this->register(Debug::class);
        $this->register(Session::class);
        $this->register(DBDebug::class);
    }

    public function register($class) {
        if ( !class_exists($class) )
            throw new Exception($class . " can't register, doesn't exist");
        if ( !class_implements($class, 'IService') )
            throw new Exception ($class . " must implement IService");

        $args = [];
        $ref = new ReflectionClass($class);
        foreach( $ref->getConstructor()->getParameters() as $k => $v) {
            $arg = $this->findDependency($v->getType());
            if( $arg !== NULL ) $args[] = $arg;
            if( $v->allowsNull() ) $args[] = NULL;
            if( $v->isOptional() ) break;


        }
        self::$services[strtolower($class)] = new $class(...$args);

        App::log("Registered with dependency injection:  $class");
        return self::$services[strtolower($class)];

    }

    public function findDependency($type) {
        if( is_a($this,$type) ) return $this;
        return $this->getService($type);
    }

    public static function getInstance(): App {
        return self::$instance;
    }

    public static function __callStatic($name, $args) {
        $name = strtolower($name);
        if ( isset(self::$services[$name]) )
            if ( count($args) )
                return self::$services[$name](...$args);
            else return self::$services[$name];

    }

    public static function session(): Session {
        return self::$services['session'];
    }

    public static function db(): DB {
        return self::$instance->getService(DB::class);
    }

    public static function navigate($url, $code = '302') {
        ob_get_clean();
        $url = dirname($_SERVER['PHP_SELF']) . '/' . $url;
        $url = $code . ';' . $url;
        $url = str_replace('//','/',$url);
        Bootstrap::Goodbye($url);
        return new class() {  function now() {  Bootstrap::Goodbye($url); }};
    }

    public static function getRequest(): Request {
        if ( !( self::$request instanceof Request ) )
            self::$request = new WebRequest();
        return self::$request;
    }

    public static function getRouter(): Router {
        return self::$router;
    }

    public static function setRouter(string $class) {
        self::$router = new $class(self::getRequest());
    }

    public static function run() {
        $router = self::$router;
        while( NULL !==  $arr = $router->getNextController() ) {
            $controller = $arr[0];
            $action = $arr[1];
            if( !class_exists($controller) ) throw new Exception("Invalid Controller: $arr[0]");
            $controller = new $controller($action);
            if( !($controller instanceof Controller) ) throw new Exception("Invalid Controller: $arr[0] (Class exists but is not a Controller)");
            $result = $controller->run();
            if ( $result === FALSE ) {
                App::log(get_class($controller), "returned false");
            }
        }
    }

    public static function LOG() {
        call_user_func_array([self::debug(), 'log'], func_get_args());
    }

    public static function debug(): Debug {
        return self::$services['debug'];
    }

    public static function shutdown() {
        Bootstrap::Goodbye();
    }

    public static function getConfig($key = NULL, $default = NULL) {
        if ( $key === NULL ) return Bootstrap::$config;
        return Bootstrap::$config[$key] ?? $default;
    }

    public function getService(string $class)  {
        $class = strtolower($class);
        foreach ( self::$services as $k => $v ) {
            if ( is_a($v, $class) ) return $v;
        }
        return NULL;
    }


}
<?php

class App {

    protected static $services = [];
    protected static $router = null;

    private static $instance = null;
    private static $request;

    public function __construct() {
        if (self::$instance !== null) throw new Exception("App is a singleton.");
        self::$instance = $this;
        $this->boot();
    }

    public static function getInstance() : App {
        return self::$instance;
    }

    public static function __callStatic($name, $args) {
        $name = strtolower($name);
        if (isset(self::$services[ $name ])) if (count($args)) return self::$services[ $name ](...$args);
        else return self::$services[ $name ];

    }

    public static function session() : Session {
        return self::$services['session'];
    }

    public static function db() : DB {
        return self::$instance->getService(DB::class);
    }

    public static function navigate($url, $code = '302') {
        ob_get_clean();
        $url = dirname($_SERVER['PHP_SELF']) . '/' . $url;
        $url = $code . ';' . $url;
        $url = str_replace('//', '/', $url);
        Bootstrap::Goodbye($url);
        return new class() {

            function now() { Bootstrap::Goodbye($url); }
        };
    }

    public static function getRequest() : Request {
        if (!(self::$request instanceof Request)) self::$request = Request::getInstance();
        return self::$request;
    }

    public static function getRouter() : Router {
        return self::$router;
    }

    public static function setRouter(string $class) {
        self::$router = new $class(self::getRequest());
    }

    public static function run() {
        return self::$router->run();
    }

    public static function LOG() {
        call_user_func_array([self::debug(), 'log'], func_get_args());
    }

    public static function debug() : Debug {
        return self::$services['debug'];
    }

    public static function shutdown() {
        Bootstrap::Goodbye();
    }

    public static function getConfig($key = null, $default = null) {
        if ($key === null) return Bootstrap::$config;
        return Bootstrap::$config[ $key ] ?? $default;
    }

    public function register($class) {

        if (is_array($class)) {
            foreach ($class as $v) {
                $this->register($v);
            }
            $this->registerDone();
            return;
        }
        if (!class_exists($class)) throw new Exception($class . " can't register, doesn't exist");
        if (!class_implements($class, 'IService')) throw new Exception ($class . " must implement IService");

        $instance = $this->create($class);
        if ($instance) {
            self::$services[ strtolower($class) ] = $instance;
            App::log("Registered with dependency injection:  $class");
        }
        else {
            echo("!Could not register: $class");
            die();
        }

        return $instance;

    }

    public function registerDone() {

    }

    public function create($class) {
        $args = [];
        $ref = new ReflectionClass($class);
        if (!$ref) return false;
        foreach ($ref->getConstructor()
                     ->getParameters() as $k => $v) {
            $arg = $this->findDependency($v->getType());
            if ($arg !== null) $args[] = $arg;
            if ($v->allowsNull()) $args[] = null;
            if ($v->isOptional()) break;
        }
        return new $class(...$args);

    }

    public function getService(string $class) {
        foreach (self::$services as $k => $v) {
            if (is_a($v, $class)) return $v;
        }
        return null;
    }

    protected function boot() {
        $this->register([Debug::class, Session::class, DBDebug::class]);
    }

    protected function findDependency($type) {
        if (is_a($this, $type)) return $this;
        if (is_a('Request', $type, true)) return self::getRequest();
        $ret = $this->getService($type);
        if (!$ret && class_exists($type)) return $this->create($type);
        return $ret;
    }

}
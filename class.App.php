<?php

class App extends Container {

    protected static $services = [];
    protected static $router = null;

    private static $instance = null;
    private static $request;
    private $response;

    public function __construct() {
        if (self::$instance !== null) throw new Exception("App is a singleton.");
        self::$instance = $this;
        parent::__construct();
        $this->boot();
    }

    protected function boot() {
        $services = [Debug::class, Session::class, Cookies::class, DBDebug::class];

        $this->register([Debug::class, Session::class, Cookies::class, DBDebug::class]);
    }

    public function register($class) {

        if (is_array($class)) {
            foreach ($class as $v) {
                $this->register($v);
            }

            $this->registerDone();
            return;
        }
        if (!class_exists($class)) throw new Exception($class . " can't register, doesn't exist.");
        if (!class_implements($class, 'IService')) throw new Exception ($class . " must implement IService");

        $instance = $this->create($class);
        if ($instance) {
            if (is_callable($instance, 'provides')) {
                $provides = $instance->provides();
                self::$services[ $provides ] = $instance;
            }
            else {
                self::$services[ strtolower($class) ] = $instance;
            }
            App::log("Registered with dependency injection: <i>$class</i>", $provides ?? '');
        }
        else {
            throw new Exception("Could not register $class");
        }

        return $instance;

    }

    protected function registerDone($instance = null) {
        foreach (self::$services as $name => $instance) {
            $args = [];
            $ref = new ReflectionClass($instance);
            if (!$ref->hasMethod('requires')) continue;
            $requires = $ref->getMethod('requires');
            foreach ($requires->getParameters() as $k => $v) {
                $arg = $this->findDependency($v->getType());
                $args[] = $arg;
            }
            $instance->requires(...$args);
        }

    }

    public function create($class, ...$regular_args) {
        $args = [];
        $ref = new ReflectionClass($class);
        if (!$ref) return false;
        foreach ($ref->getConstructor()->getParameters() as $k => $v) {
            if ($v->hasType()) {
                $type = $v->getType();
                if (!Str::instr($type, '/int|string|array|bool/')) {
                    $arg = $this->findDependency($v->getType());
                    if ($arg !== null) {
                        $args[] = $arg;
                        continue;
                    }
                }
            }
            if ($v->isDefaultValueAvailable()) break;
            //    if ($v->allowsNull()) $args[] = null;
            //    if ($v->isOptional()) break;
        }
        return new $class(...$args);

    }

    public static function LOG() {
        call_user_func_array([self::debug(), 'log'], func_get_args());
    }

    protected function findDependency($type) {
        if (is_a($this, $type)) return $this;
        if (is_a('Request', $type, true)) return self::getRequest();
        if (is_a('Response', $type, true)) return $this->getResponse();
        $ret = $this->getService($type);
        if (!$ret && class_exists($type)) return $this->create($type);
        return $ret;
    }

    public static function debug() {
        return self::$services['debug'] ?? new Dummy();
    }

    public static function getRequest() : Request {
        if (!(self::$request instanceof Request)) self::$request = Request::getInstance();
        return self::$request;
    }

    public function getResponse() : Response {
        if (!($this->response instanceof Request)) $this->response = new Response();
        return $this->response;
    }

    public function getService(string $class) {
        foreach (self::$services as $k => $v) {
            if (is_a($v, $class) || $k == $class) return $v;
        }
        return null;
    }

    public function configure(Container $instance) {
        $ref = new ReflectionClass($instance);
        if ($ref->hasMethod('provides')) {
            $closure = $ref->getMethod('provides')->getClosure($instance);
            $provides = $closure->call($instance);
            self::$services[ $provides ] = $instance;
        }
        //        if ($ref->hasMethod('requires')) {
        //            $requires = $ref->getMethod('requires')->getStaticVariables();
        //            foreach ($requires->getParameters() as $k => $v) {
        //            $arg = $this->findDependency($v->getType());
        //            $args[] = $arg;
        //        }
        //        $instance->requires(...$args);

    }

    public static function __callStatic($name, $args) {
        $app = App::getInstance();
        if (is_callable($app, $name)) return $app->$name(...$args);
        $name = strtolower($name);
        if (isset(self::$services[ $name ])) if (count($args)) return self::$services[ $name ](...$args);
        else return self::$services[ $name ];

    }

    public static function getInstance() : App {
        return self::$instance;
    }

    public static function session() : Session {
        return self::$services['session'];
    }

    public static function db() : DB {
        return self::$instance->getService(DB::class);
    }

    public function setRouter(string $class) {
        self::$router = new $class(self::getRequest(), $this->getResponse());
    }

    public static function getConfig($key = null, $default = null) {
        if ($key === null) return Bootstrap::$config;
        return Bootstrap::$config[ $key ] ?? $default;
    }

    public function navigate($url, $code = '302') {
        ob_get_clean();
        $url = dirname($_SERVER['PHP_SELF']) . '/' . $url;
        $url = $code . ';' . $url;
        $url = str_replace('//', '/', $url);
        Bootstrap::Goodbye($url);
    }

    public function run() {
        return self::getRouter()->run()->send();
    }

    public static function getRouter() : Router {
        return self::$router;
    }

    public function shutdown() {
        Bootstrap::Goodbye();
    }

    private function __clone() { }

}
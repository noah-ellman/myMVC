<?php
ob_start();


include 'traits.php';
include 'interfaces.php';


class Bootstrap {

    public static $config;

    private static $instance = null;
    private static $app = null;

    public function __construct($config_file = "config.php") {

        self::getEnviroment();


        if ( file_exists($config_file) ) {
            $config = include $config_file;
            if ( is_callable($config) ) $config = $config();
            self::$config = $config;
        } else {
            throw new Exception("Can't find config.php");
        }
        if ( self::$instance !== null ) throw new Exception("Bootstrap is a singleton.");
        self::$instance = $this;


        register_shutdown_function(
            function() {
                if ( !defined('GOODBYE') ) {
                    if ( !defined('CRASHED') ) define('CRASHED', 1);
                    self::Goodbye();
                }
            });

        include 'class.Autoloader.php';
        ( new Autoloader() )->from($config['autoload'])->register();

        new App();

    }

    public static function getEnviroment() {
        if ( php_sapi_name() === "cli" && !defined('CONSOLE') ) define('CONSOLE', true);
        if ( !isset($_SERVER['GATEWAY_INTERFACE']) && !defined('CONSOLE') ) define('CONSOLE', true);
        define('CLI', php_sapi_name() === "cli" ? true : false);
        if ( !CLI ) {
            define('DOMAIN', strtolower($_SERVER['HTTP_HOST']));
        } else {
            define('DOMAIN','localhost');
        }
        if( !CLI && $_SERVER['SERVER_ADDR'] == '127.0.0.1' ) {
            define('LOCALHOST',TRUE);
        }
    }

    public static function Goodbye($redirect = null) {
        define('GOODBYE',1);
        static $done = false;
        if ( $done ) {
            if ( defined('CRASHED') ) exit(99);
            else exit(0);
            return;
        } else {
            $done = true;
        }
        if ( $redirect !== null ) $GLOBALS['goto'] = $redirect;
        if ( isset($GLOBALS['goto']) ) {
            if ( headers_sent() ) throw new Exception("Header's already sent! Can't redirect.");
            $redirect = $GLOBALS['goto'];
            if ( (string)$redirect == '404' ) {
                header("HTTP/1.1 404 Not Found");
                die();
            }
            if ( substr($redirect, 0, 2) == '30' ) list($status, $redirect) = explode(';', $redirect, 2);
            else $status = 302;
            if ( substr($redirect, 0, 4) != 'http' ) {
                if ( substr($redirect, 0, 2) == './' ) {
                    $path = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
                    $redirect = $path . substr($redirect, 1);
                }
                $redirect = 'http://' . $_SERVER['HTTP_HOST'] . ( substr($redirect, 0, 1) == '/' ? '' : '/' ) . $redirect;
            }
            $redirect = str_replace(' ', '+', $redirect);
            App::debug()->log('[' . $status . '] ' . $redirect);
            App::debug()->saveLog();
            while ( ob_get_level() ) {
                ob_end_clean();
            }
            if ( session_id() ) session_write_close();
            closelog();
            header("Location: {$redirect}", true, $status);
        } else {
            if ( defined('MINIFY') ) ob_minify();
            App::debug()->saveLog();
            while ( ob_get_level() ) {
                ob_end_flush();
            }
            if ( session_id() ) session_write_close();
            closelog();
        }
        if ( defined('CRASHED') ) exit(99);
        else exit(0);
    }

    public static function addAutoloadFolder($folder) {
        spl_autoload_register(function($class) {
            include $folder . '/' . $class . '.php';
        });

    }

    public static function __callStatic($func, $args) {
        $me = self::$instance;
        return call_user_func_array([$me, $func], $args);
    }

    public static function getApp(): App {
        if ( self::$app === null ) self::$app = App::getInstance();
        return self::$app;
    }

    public static function getConfig($key = null, $default = null) {
        if ( $key === null ) return self::$config;
        return self::$config[$key] ?? $default;
    }

}

?>
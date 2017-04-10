<?
class Cloud extends Data {

    private static $__self__;

    public function __construct() {
        if( !self::$__self__ )  self::$__self__ = $this;
        parent::__construct();
    }

    public static function __callStatic($name, $argv) {
        if( !self::$__self__ ) new Cloud();
        if( !count($argv) ) return (self::$__self__)->$name;
        else (self::$__self__)->$name = $argv[0];
        return self::$__self__;

    }

    public static function render() {
        return self::$__self__->toJSON();
    }

    public static function toString() {
        return json_encode(self::$__self__);
    }

}
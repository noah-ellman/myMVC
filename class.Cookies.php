<?

class Cookies extends Service implements IService, IteratorAggregate, Countable {

    protected $cookies = [];

    public function __construct() {
        foreach ($_COOKIE as $k => $v) {
            $this->cookies[ $k ] = self::decode($v);
        }
        parent::__construct();
    }

    protected static function decode($str) {
        if (strlen($str) % 4 !== 0 || Str::instr($str, '/[^A-Za-z0-9+\/=]')) return $str;
        //$str = str_replace(' ','+',$str);
        //$str = strtr($str,'-_~','=+/');
        $str = base64_decode($str);
        return $str;
    }

    public function __get($key) {
        if (isset($this->cookies[ $key ])) return $this->cookies[ $key ];
        else return false;

    }

    public function __set($key, $value) {
        $this->cookies[ $key ] = $value;
        $str = self::encode($value);
        setcookie($key, $str, strtotime('+1 month'), '/', $_SERVER["HTTP_HOST"]);
    }

    protected static function encode($str) {
        $str = base64_encode($str);
        //$str = strtr($str,'=+/','-_~');
        return $str;
    }

    public function getIterator() {
        return new ArrayIterator($this->cookies);
    }

    public function count() {
        return count($this->cookies);
    }

    public function __isset($k) {
        return isset($this->cookies[ $k ]) ? true : false;
    }

    public function __unset($key) {
        if (isset($this->cookies[ $key ])) {
            setcookie($key, false, 1, '/', $_SERVER["HTTP_HOST"] );
        }
        $this->log("Unset cookie: $key");
        unset($this->cookies[ $key ]);
        return true;
    }

    public function clear() {
        foreach ($this->all() as $k => $v) {
            unset($this->$k);
        }
    }

    public function & all() {
        return $this->cookies;
    }

}

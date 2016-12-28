<?

class Cookies extends Service implements IService {

    public function __construct() {
        foreach($_COOKIE as $k => $v) {
            $this->$k = self::decode($v);
        }
    }

    public function __set($key,$value) {
        $this->$key = $value;
        $str = self::encode($value);
        setcookie($key,$str,strtotime('+1 month'),'/',$_SERVER["HTTP_HOST"]);
    }

    public function __get($key) {
        if( isset($this->$key) ) return $this->$key;
        else return FALSE;

    }

    public function __unset($key) {
        if( isset($this->$key) ) {
            setcookie($key,$this->$key,strtotime('-1 month'),'/',$_SERVER["HTTP_HOST"]);
        }
        unset($this->$key);
        return true;
    }

    protected static function encode($str) {
        $str = base64_encode($str);
        $str = strtr($str,'=+/','-_~');
        return $str;
    }

    protected static function decode($str) {
        $str = str_replace(' ','+',$str);
        $str = strtr($str,'-_~','=+/');
        $str = base64_decode($str);
        return $str;
    }

}


?>
<?

class Session extends Service implements IService {

    use TLoggable;

    public $sessionID;
    protected static $instance = NULL;

    private $app;

    public function __construct(App $app, array $args = []) {
        if ( self::$instance !== NULL ) throw new Exception("Session can't have two instances");
        self::$instance = $this;
        $this->app = $app;
        $this->boot();
    }

    protected function boot() {
        $id = NULL;
        if ( defined('CONSOLE') ) return;
        if ( defined('ROBOT') || isset($_ENV['ROBOT']) ) {
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if ( strstr($agent, 'google') !== FALSE ) $id = 'GOOGLE';
            else if ( strstr($agent, 'slurp') !== FALSE ) $id = 'YAHOO';
            else if ( strstr($agent, 'msnbot') !== FALSE ) $id = 'MSN';
            else $id = 'ROBOT';
            $_COOKIE['wwwident3'] = $id;
        }
        $name = session_name();
        if ( $id !== NULL && !empty($id) ) {
            $this->log("~Session started with passed id <em>$id</em>");
            session_id($id);
        } else if ( isset($_COOKIE[$name]) && !empty($_COOKIE[$name]) ) {
            $this->log("~Session started with cookie <em>" . $_COOKIE[$name] . "</em>");
        } else if ( isset($_REQUEST[$name]) && !empty($_REQUEST[$name]) ) {
            $this->log("~Session started with GET/POST id <em>" . $_REQUEST[$name] . "</em>");
            session_id($_REQUEST[$name]);
        }
        session_start();
        $this->sessionID = session_id();
        // possible security hole below
        foreach ( $_GET as $k => $v ) {
            if ( $k{0} == '$' ) {
                $_SESSION[substr($k, 1)] = $v;
            }
        }
        if ( !isset($_SESSION['hello']) ) {
            $_SESSION['hello'] = "hello";
            define('NEWSESSION', 1);
        }
    }

    public function __destruct() {
        session_write_close();
    }

    public function __set($k, $v) {
        if ( $v === NULL ) unset($_SESSION[$k]);
        else $_SESSION[$k] = $v;
        if ( is_scalar($v) ) $this->log("<b>Session-&gt;</b>{$k} = <i>$v</i>");
        else $this->log("<b>Session-&gt;</b>{$k} = <i>" . json_encode($v) . "</i>");

    }

    public function __get($k) {
        if ( isset($_SESSION[$k]) && !empty($_SESSION[$k]) ) {
            return $_SESSION[$k];
        } else {
            return NULL;
        }
    }


    public function id($type = '') {
        if ( $type == 'form' || $type == 'input' ) return '<input type=hidden name=' . $this->name() . ' value="' . $this->id() . '" />';
        else if ( $type == 'query' || $type == 'url' ) return $this->name() . '=' . $this->id();
        else return $this->sessionID;

    }

    public function __call($f, $args) {
        if ( count($args) ) {
            $this->$f = $args[0];
        }
        return $this->$f;
    }

    public function __isset($k) {
        return isset($_SESSION[$k]) ? TRUE : FALSE;
    }

    public function __unset($k) {
        if ( isset($_SESSION[$k]) ) unset($_SESSION[$k]);
    }

    public function __toString() {
        return serialize($_SESSION);
    }

    public function start() {
        session_start();
    }

    public function name() {
        return session_name();
    }

}
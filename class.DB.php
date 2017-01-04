<?

class DB extends mysqli implements IService {

    use TLoggable;

    protected $result;
    protected $debug_qtime, $debug_qcount, $debug_timer;
    private $connected = false;
    private $credentials = ['host'   => "localhost", 'user' => 'test', 'pass', 'test', 'db' => 'test', 'port' => null,
                            'socket' => null];
    private $app;

    public function __construct(App $app) {
        $this->app = $app;
        $credentials = $this->app->getConfig('db');
        foreach ($credentials as $k => $v) {
            $this->credentials[ $k ] = $v;
        }
        parent::__construct($this->credentials['host'], $this->credentials['user'], $this->credentials['pass'], $this->credentials['db'], $this->credentials['port'], $this->credentials['socket']);
    }

    public static function escape($str = '') {
        if ($str instanceof DBLiteralString) {
            return $str;
        }
        else if (is_string($str)) return str_replace("\\\\\\", "\\", str_replace(['\"', "\\", "'"], ['"', "\\\\",
                                                                                                     "\\'"], $str));
        else if (is_array($str) || is_object($str)) foreach ($str as $k => $v) $str[ $k ] = self::escape($v);
        return $str;
    }

    public static function quote($str = '') {
        if ($str instanceof DBLiteralString) return (string)$str;
        else if (is_string($str)) if (is_numeric($str)) return $str;
        else return "'{$str}'";
        else if (is_array($str) || is_object($str)) foreach ($str as $k => $v) $str[ $k ] = self::quote($v);
        return $str;

    }

    public static function esapeAndQuote($str) {
        return self::quote(self::escape($str));
    }

    public function provides() {
        return 'db';
    }

    public function __destruct() {
        $this->close();
    }

    public function isConnected() {
        return $this->connected;
    }

    public function close() {
        if ($this->connected) parent::close();
        $this->connected = false;
    }

    public function connect($host = null, $user = null, $password = null, $database = null, $port = null, $socket = null) {
        $this->log($this->credentials, "Connecting To Database with:");
        parent::connect($this->credentials['host'], $this->credentials['user'], $this->credentials['pass']);
        if ($this->connect_error) throw new Exception("Database connection failed");
        $this->connected = true;
        $this->select_db($this->credentials['db']);
        return $this;

    }

    public function getInsertID() {
        return $this->insert_id;
    }

    public function getAffectedRows() {
        return $this->affected_rows;
    }

    public function query($query) {
        $query = ltrim($query);
        if (preg_match('/;\s*(?:delete|update|truncate|drop|alter|grant) /i', $query)) throw new Exception('SQL Injection Attempt: $query', 911);
        if (!$this->isConnected()) {
            $this->connect();
        }
        $result = parent::query($query);
        if ($this->error) {
            $error = $this->error;
            if (strstr($error, ' near') !== false) $error = 'SQL ERROR NEAR ' . strstr($error, "'");
            if (!defined('CONSOLE')) $error .= ' [' . (string)new SQLSyntaxHighlighter($query, $error) . ']';
            throw new Exception($error, $this->errorno);
        }
        if (strtolower($query{0}) != 's') return [$this->affected_rows, $this->insert_id];
        if (!is_object($result)) return $result;
        $count = $result->num_rows;
        $field_count = $result->field_count;
        if ($count === 0) {
            return false;
        }
        if ($count === 1) {
            $f = $result->fetch_object(Data::class);
            $result->free();
            if ($field_count > 1) return [0 => $f];
            else {
                $f = array_values(get_object_vars($f))[0];
                $this->result = is_numeric($f) ? (int)$f : $f;
                return $this->result;
            }
        }
        else {
            $this->result = [];
            do {
                $this->result[] = $result->fetch_object(Data::class);
            } while (--$count > 0);
            $result->free();
            return $this->result;
        }
        return false;
    }

    public function & getResult() {
        return $this->result;
    }

    public function getQueryCount() {
        return $this->debug_qcount;
    }

    public function getQueryTimeTotal() {
        return $this->debug_qtime;
    }

}
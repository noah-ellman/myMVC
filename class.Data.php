<?


class Data

    extends stdClass
    implements Expando, ArrayAccess, Countable {

    public function __construct() {

        $arg_count = func_num_args();
        $args = func_get_args();
        if ( !$arg_count ) return;
        if ( $arg_count === 1 ) {
            if ( is_object($args[0]) ) {
                if( $args[0] instanceof DoesDataStorage ) $args[0] = $args[0]->getData()->toArray();
                else if ( $args[0] instanceof Data ) { $args[0] = $args[0]->toArray(); }
                else $args[0] = get_object_vars($args[0]);
            }
            if ( is_array($args[0]) ) foreach ( $args[0] as $k => $v ) {
                if ( is_numeric($k) ) $this[$k] = $v; else $this->$k = $v;
            }
            else if ( is_string($args[0]) && $args[0]{0} == '{' ) {
                $o = json_decode($args[0]);
                if ( $o !== FALSE ) foreach ( $o as $k => $v ) $this->$k = $v;
            }
        } else {
            for ( $ii = 0; $ii < $arg_count; $ii += 2 ) $this->{$args[$ii]} = $args[$ii + 1];
        }
    }


    public function __destruct() {
    }

    public function & __get($k) {
        return isset($this->$k) ? $this->$k : NULL;
    }

    public function __set($k, $v) {
        $this->$k = $v;
    }

    public function __isset($k) {
        return isset($this->$k);
    }

    public function __unset($k) {
        unset($this->$k);
    }

    public function __toString() {
        return json_encode($this);
    }

    public function & offsetGet($k) {
        return isset($this->$k) ? $this->$k : NULL;
    }

    public function offsetSet($k, $v) {
        $this->$k = $v;
    }

    public function offsetExists($k) {
        return isset($this->$k) ? TRUE : FALSE;
    }

    public function offsetUnset($k) {
        unset($this->$k);
    }

    public function count() {
        return count(get_object_vars($this));
    }

    public function __call($method, $args) {
        $return = NULL;
        switch ( strtolower($method) ) {
            case 'tojson':
            case 'json':
                return json_encode(get_object_vars($this));
                break;
            case 'toarray':
            case 'array':
                return get_object_vars($this);
                break;
            case 'keys':
                return array_keys(get_object_vars($this));
                break;
            case 'values':
                return array_values(get_object_vars($this));
                break;
        }
        if( function_exists("array_$method") ) $method = "array_$method";
        if ( function_exists($method) ) {
            array_unshift($args, get_object_vars($this));
            return call_user_func_array($method, $args);
        }
        return NULL;
    }

    public function all() : array {
        return get_object_vars($this);
    }

    public function sendTo(DoesDataStorage $obj) {
        $obj-setData($this->toArray());
    }

}
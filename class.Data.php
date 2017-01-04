<?

class Data
    extends stdClass
    implements Expando, ArrayAccess, IteratorAggregate, Countable, JSONAble {

    const DATA_ADD_REPLACE_IF_EXISTS = 1;
    const DATA_ADD_SKIP_IF_EXISTS    = 2;

    use TLoggable;

    public function __construct() {
        if (count(get_class_vars(Data::class)) > 0) {
            throw new Exception("Data object can't have properties");
        }
        $arg_count = func_num_args();
        $args = func_get_args();
        if (!$arg_count) return;
        if ($arg_count === 1) {
            if (is_object($args[0])) {
                if ($args[0] instanceof DoesDataStorage) $args[0] = $args[0]->getData()->toArray();
                else if ($args[0] instanceof Data) {
                    $args[0] = $args[0]->toArray();
                }
                else $args[0] = get_object_vars($args[0]);
            }
            if (is_array($args[0])) foreach ($args[0] as $k => $v) {
                if (is_numeric($k)) $this[ $k ] = $v;
                else $this->$k = $v;
            }
            else if (is_string($args[0]) && ($args[0]{0} == '{' || $args[0]{0} == '[')) {
                $o = json_decode($args[0]);
                if ($o) foreach ($o as $k => $v) $this->$k = $v;
            }
        }
        else {
            for ($ii = 0; $ii < $arg_count; $ii += 2) $this->{$args[ $ii ]} = $args[ $ii + 1 ];
        }
    }

    public function __destruct() {
    }

    public function getIterator() {
        return new ArrayIterator(get_object_vars($this));
    }

    public function __get($k) {
        return isset($this->$k) ? $this->$k : null;
    }

    public function __set($k, $v) {
        $this->{"$k"} = $v;
    }

    public function __isset($k) {
        return isset($this->{"$k"});
    }

    public function __unset($k) {
        unset($this->$k);
    }

    public function __toString() {
        // return json_encode($this);
    }

    public function offsetExists($k) {
        return isset($this->$k) ? true : false;
    }

    public function & offsetGet($k) {
        if (is_int($k)) $k = (string)$k . '';
        return $this->$k;
    }

    public function offsetSet($k, $v) {
        if (is_int($k)) $k = (string)$k . '';
        $this->$k = $v;
    }

    public function offsetUnset($k) {
        unset($this->$k);
    }

    public function count() {
        return count(get_object_vars($this));
    }

    public function __call($method, $args) {
        $return = null;
        switch (strtolower($method)) {
            case 'toarray':
            case 'array':
                if (isset($args[0]) && $args[0] == true) return Arr::obj2array($this);
                $arr = get_object_vars($this);
                if (array_key_exists("0", $arr)) return Arr::numeric($arr);
                else return $arr;
                break;
            case 'keys':
                return array_keys(get_object_vars($this));
                break;
            case 'values':
                return array_values(get_object_vars($this));
                break;
            case 'numeric':
                return Arr::array_numeric($this->array());
                break;
        }
        if (function_exists("array_$method")) {
            $method = "array_$method";
            array_unshift($args, get_object_vars($this));
        }
        else if (function_exists($method)) {
            array_push($args, get_object_vars($this));
        }
        else {
            return null;
        }
        return new static(call_user_func_array($method, $args));
    }

    public function all() : array {
        return get_object_vars($this);
    }

    public function sendTo(DoesDataStorage $obj) {
        $obj->setData($this->toArray());
    }

    public function toJSON() {
        return json_encode(get_object_vars($this));
    }

    public function length() {
        return count($this);
    }

    public function add($data, $mode = 1) {
        foreach ($data as $k => $v) {
            if ($mode == self::DATA_ADD_SKIP_IF_EXISTS) {
                if (isset($this->$k)) continue;
            }
            $this->$k = $v;
        }
    }

    public function collection() {
        $this->log(__METHOD__);
        return new DataCollection($this);
    }

}
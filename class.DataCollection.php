<?php

class DataCollection implements ArrayAccess, IteratorAggregate, Countable, JSONAble {

    use TLoggable;

    protected $items = [];

    public function __construct($data) {

        if ($data instanceof Data) {
            if (count($data) && (!isset($data[0]) || !isset($data[ count($data) - 1 ]))) {
                throw new Exception ("DataCollection must be a numeric array");
            }
            $this->dump($data->numeric(), 'data->numeric');
            foreach ($data as $v) {
                $this->items[] = $v;
            }

        }
        else if ($data instanceof DataCollection) {
            $this->items = $data->copy()->all();;
        }
        else if (is_array($data)) {
            $this->items = $data;
        }

    }

    public function & all() {
        return $this->items;
    }

    public function copy() {
        return clone $this;
    }

    public function push($data) {
        $this->items[] = $data;
        return $this;
    }

    public function pop() {
        return array_pop($this->items);
    }

    public function only($keys) {
        $new = [];
        foreach ($this->items as $i => $item) {
            $new[ $i ] = new Data();
            foreach ($keys as $key) {
                if (Str::contains($key, ' as ')) {
                    list($key, $alias) = explode(' as ', $key);
                } else { $alias = $key; }
                $new[ $i ]->$alias = $this->items[ $i ]->$key;
            }
        }
        return new static($new);
    }

    public function except($keys) {
        $new = $this->items;
        foreach ($new as $i => $item) {
            foreach ($item as $k => $v) {
                if (in_array($k, $keys)) {
                    unset($new[ $i ]->$k);
                }
            }
        }
        return new static($new);

    }

    public function getIterator() {
        return new ArrayIterator($this->items);
    }

    public function __get($k) {
        return isset($this->items[ (int)$k ]) ? $this->items[ (int)$k ] : null;
    }

    public function __set($k, $v) {
        $this->items[ (int)$k ] = $v;
    }

    public function __isset($k) {
        return isset($this->items[ (int)$k ]);
    }

    public function __unset($k) {
        unset($this->items[ (int)$k ]);
    }

    public function __toString() {
        return json_encode($this);
    }

    public function offsetExists($k) {
        return isset($this->items[ $k ]) ? true : false;
    }

    public function offsetGet($k) {
        return isset($this->items[ $k ]) ? $this->items["$k"] : null;
    }

    public function offsetSet($k, $v) {
        $this->items[ $k ] = $v;
    }

    public function offsetUnset($k) {
        unset($this->items[ $k ]);
    }

    public function count() {
        return count($this);
    }

    public function length() {
        return count($this);
    }

    public function toJson() {
        return json_encode($this->items, JSON_OBJECT_AS_ARRAY);
    }

    public function toArray() {
        return $this->items;
    }

    public function __call($method, $args) {
        $return = null;
        if (function_exists("array_$method")) {
            $method = "array_$method";
            array_unshift($args, $this->items);
        }
        else if (function_exists($method)) {
            array_push($args, $this->items);
        }
        else {
            return null;
        }
        $items = call_user_func_array($method, $args);
        if (is_array($items)) $this->items = $items;
        return $this;
    }

    public function each(Closure $fn) {
        foreach ($this->items as $k => &$v) {
            $ret = $fn->call($v);
            if ($ret instanceof Data) $this->items[ $k ] = $ret;
        }
    }

}
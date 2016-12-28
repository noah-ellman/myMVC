<?php

class Model implements JSONAble, Expando, DoesDataStorage, IteratorAggregate {

    use TLoggable;

    protected $data;
    protected $name = "Model";

    protected $error;

    public function __construct() {
        $this->name = get_class($this);
        $this->data = new Data();
    }

    public function __get($k) {
        if ( isset($this->data->$k) ) return $this->data->$k;
        else return null;
    }

    public function __set($k, $v) {
        $this->data->$k = $v;
    }

    public function getIterator() {
        return new ArrayIterator($this->data);
    }

    public function find() {
        return $this;
    }

    public function getName() : string {
        return $this->name;
    }

    public function & getData() : Data {
        return $this->data;
    }

    public function setData($data) : Model {
        $this->data = new Data($data);
        $this->logDump($data, get_class($this) . '::setData');
        return $this;
    }

    public function addData($data) : Model {
        foreach ( $data as $k => $v ) $this->data[ $k ] = $v;
        $this->logDump($data, 'Model::addData');
        return $this;
    }

    public function getError() {
        return $this->error;
    }

    public function isValid() {
        return true;
    }

    public function clear() {
        $this->data = new Data();
        return $this;
    }

    public function toJSON() {
        return json_encode($this->data->toArray());
    }

}
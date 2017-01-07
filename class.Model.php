<?php

class Model extends Container implements JSONAble, Expando, DoesDataStorage, IteratorAggregate {

    use TLoggable;

    protected $data;
    protected $loaded = false;
    protected $error;
    protected $errorno;
    protected $messages = [];
    protected $valid = true;

    public function __construct() {
        $this->name = get_class($this);
        $this->data = new Data();
        parent::__construct();
    }

    public function __get($k) {
        if (isset($this->data->$k)) return $this->data->$k;
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

    public function & getData() : Data {
        return $this->data;
    }

    public function setData($data) : Model {
        $this->data = new Data($data);
        $this->logDump($data, get_class($this) . '::setData');
        return $this;
    }

    public function addData($data) : Model {
        foreach ($data as $k => $v) $this->data[ $k ] = $v;
        $this->logDump($data, 'Model::addData');
        return $this;
    }

    public function getError() {
        return $this->error;
    }

    public function hasMessages() {
        return count($this->messages);
    }

    public function hasError() {
        return !empty($this->error);
    }

    public function getErrorNo() {
        return $this->errorno;
    }

    public function isValid() {
        return $this->valid;
    }

    public function reset() {
        $this->data = new Data();
        $this->loaded = false;
        $this->error = '';
        $this->errorno = 0;
        $this->messages = [];
        $this->valid = true;
        return $this;
    }

    public function isLoaded() : bool {
        return $this->loaded;
    }

    public function toJSON() : string {
        return json_encode($this->data->toArray());
    }

    public function addError(string $msg, int $errorno) {
        $this->valid = false;
        $this->error = $msg;
        $this->errorno = $errorno;
        return $this;
    }

    public function addMessage($str = '') {
        $this->messages[] = $str;
        return $this;

    }

    public function getMessages() : array {
        return $this->messages;
    }

    public function found() {
        if (count($this->data)) return true;
        return false;
    }

    public function data($args = null) {
        if ($args) return $this->setData($args);
        else return $this->getData();
    }


}
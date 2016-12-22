<?php

class Request extends Data {

    use TLoggable;

    public function __construct($data) {
        parent::__construct($data);
        $this->log('REQUEST', $this);

    }


    public function has($what) {
        return isset($this->$what) ? true : false;
    }


}
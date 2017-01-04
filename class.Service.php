<?php

abstract class Service extends Container {

    use TLoggable;

    public function __construct() {
        $this->log(__METHOD__);
        parent::__construct();
    }


    public function register() {
        App::getInstance()->register(self::class);
        return $this;
    }

    public function configure() {
        return $this;
    }

    public function provides() {
        return strtolower(get_class($this));
    }


}
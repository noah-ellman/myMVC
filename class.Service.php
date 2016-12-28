<?php

class Service  {


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
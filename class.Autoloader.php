<?php

class Autoloader {

    private $locations = [];
    private $registered = false;

    public function __construct(array $locations = null) {
        if( $locations !== null ) {
            $this->locations = $locations;
        }
    }

    public function from(array $locations) {
        foreach($locations as $v) $this->locations[] = $v;
        return $this;
    }

    public function load($class) {
        $file = NULL;
        foreach ( $this->locations as $v ) {
            $file = "{$v}/class.{$class}.php";
            $file2 = "{$v}/{$class}.php";
            if ( file_exists($file2) ) $file = $file2;
            if ( !file_exists($file) ) $file = NULL;
            else break;
        }
        if ( is_null($file) ) return FALSE;
        include $file;
    }

    public function register() {
        if( $this->registered ) return;
        spl_autoload_register([$this,'load'],true);
        $this->registered = true;
        return $this;
    }

}
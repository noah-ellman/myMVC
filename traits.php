<?php

trait TLoggable {

    protected function log(...$args) {
        if ( is_string($args[0]) ) {
            $prefix = '';
            if ( $args[0][0] == '~' || $args[0][0] == '!' ) $prefix = $args[0][0];
            $classname = get_class($this);
            if( property_exists($this, 'name' ) ) $classname .= "({$this->name})";
            else if ( is_callable($this, 'name' )  ) $classname .= "({$this->name()})";
            else if ( is_callable($this, 'getName' ) ) $classname .= "({$this->getName()})";
            $args[0] = $prefix . '<b>[' . $classname . ']</b> ' . (empty($prefix) ? $args[0] : substr($args[0],1));
        }
        $allstrings = true;
        foreach ( $args as $k => $v ) {
            if ( is_object($v) || is_array($v) ) $allstrings = false;
        }
        if ( $allstrings ) $args = [join(" ", $args)];
        $debug = App::getInstance()->getService(Debug::class);
        if( $debug ) $debug->log(...$args);
    }

    protected function dump($o, string $l = null, bool $return = false) {
        $debug = App::getInstance()->getService(Debug::class)  ;
        if ( $debug ) return $debug->logDump($o, $l, $return);
    }

    protected function logDump($o, string $l = null, bool $return = false) {
        $debug = App::getInstance()->getService(Debug::class) ;
        if ( $debug ) return $debug->logDump($o, $l, $return);
    }

}

trait DBAccess {

    protected function query($query) {
        return App::getInstance()->getService(DB::class)->query($query);
    }

    protected function db() {
        return App::getInstance()->getService(DB::class);
    }

}

trait AppServicesAccess {

    protected function getService($service) {
        return App::getInstance()->getService($service);
    }
}
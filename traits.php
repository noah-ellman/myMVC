<?php

trait TLoggable {

    protected function log(...$args) {
        if( is_string($args[0]) ) {
            $prefix = '';
            if( $args[0][0] == '~' || $args[0][0] == '!' ) $prefix = $args[0][0];
            $args[0] = $prefix . '<b>[' . get_class($this) . ']</b> ' . $args[0];
        }
        $allstrings = true;
        foreach( $args as $k => $v) {
            if( is_object($v) || is_array($v) ) $allstrings = false;
        }
        if( $allstrings ) $args = [ join(" ", $args) ];
        App::getInstance()->getService(Debug::class)->log(...$args);
    }

    protected function logDump($o, string $l = NULL, bool $return = FALSE) {
        return App::getInstance()->getService(Debug::class)->logDump($o, $l, $return);
    }

}


trait DBAccess {

    protected function query($query) {
        return App::getInstance()->getService(DB::class)->query($query);
    }

}

trait AppServicesAccess {

    protected function getService($service)  {
        return App::getInstance()->getService($service);
    }
}
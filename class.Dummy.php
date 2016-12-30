<?php
class Dummy {
    public function __get($k) { return true; }
    public function __set($k, $v) { return true; }
    public function __invoke($k, $v) { return true; }
    public function __call($k, $v) { return true; }
}
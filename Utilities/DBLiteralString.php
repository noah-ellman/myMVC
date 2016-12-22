<?php
class DBLiteralString {
    private $value;
    public function __construct($string) {
        $this->value = $string;
    }
    public function __toString() { return $this->value; }
}
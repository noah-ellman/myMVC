<?php


interface LogInterface {

    function log($msg,...$blah);


}
interface Expando {
    function __get($k);
    function __set($k, $v);
}

interface IService {


}

interface DoesDataStorage {

    function getData();
    function setData($data);
    function addData($data);
}

interface JSONAble {
    public function toJSON();
}

interface Tool {
    public function input();
    public function output();
}


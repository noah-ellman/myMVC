<?php


interface LogInterface {

    function log($msg);
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
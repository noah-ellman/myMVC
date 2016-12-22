<?php


class WebRequest extends Request {


    public function __construct($data=NULL) {
        $data = array_merge($_GET,$_POST);
        $this->GET = $_GET;
        $this->POST = $_POST;
        parent::__construct($data);

    }

    public function isPost() {
        return count($_POST) > 0;
    }

    public function getHeaders($which=NULL) {
        $headers = apache_request_headers();
        if( $which !== NULL ) {
            return isset($headers[$which]) ? $headers[$which] : FALSE;
        }
        return $headers;
    }

    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false;
    }





}
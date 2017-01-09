<?php

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest {

    use TLoggable;

    protected static $instance = null;
    protected $_route_;

    public $session;



    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->_route_ = $this->query->get('_route_','/');
        $this->query->remove('_route_');
        $this->session = App::getInstance()->getService('session');
    }

    public function route() {
        return $this->_route_;
    }

    public static function getInstance() {
        if( self::$instance === null) self::$instance = static::createFromGlobals();
        return self::$instance;
    }

    public function __get($k) {
        return $this->request->get($k) ?: $this->query->get($k) ?: $this->session->get($k);
    }

    public function post($arg=null, $default=null) {
        if( $arg !== null ) { return $this->request->get($arg,$default); }
        else return $this->request;
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
        if( $this->isXmlHttpRequest() || isset($_SERVER['HTTP_X_REQUESTED_WITH']) )
            return true;
        else return false;
    }

    public function getUploadedFile() : \Symfony\Component\HttpFoundation\File\UploadedFile {
        $file = $this->files->keys();
        if( !count($file) ) return null;
        $file = $this->files->get($file[0]);
        return $file;
    }






}

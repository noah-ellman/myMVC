<?php

class View extends Container implements DoesDataStorage {

    use TLoggable;

    protected static $sharedData = [];
    protected $viewFile;
    protected $data;
    protected $helper;
    protected $parentView = null;
    protected $controller = null;
    protected $nestedViews = [];
    protected $rendered = false;
    private $isRendering = false;

    public function __construct($name, View $parentView = null, Controller $controller = null) {
        parent::__construct();
        $this->name = $name;
        $this->parentView = $parentView;
        $this->controller = $controller;
        $this->viewFile = $this->findViewFile($name);
        $this->data = new Data();
        $this->log("Controller", get_class($controller), 'loaded view:');
        if ($this->parentView) $this->log("Booting view <i>{$this->name}</i> from inside <i>{$this->parentView->name}</i>");
        else $this->log("Booting view <i>{$this->name}</i>");
    }

    public static function factory($name, $args = []) : View {
        $view = new View($name);
        $view->setData($args);
        return $view;
    }

    public static function share($key, $val) {
        self::$sharedData[ $key ] = $val;
    }

    public function hasParent() : bool {
        return $this->parentView === null ? false : true;
    }

    public function getParent() : ?View {
        return $this->parentView;
    }

    public function setModel(Model $args) : View {
        $this->setData($args->getData());
        return $this;
    }

    public function renderToString() : string {
        $lastState = $this->rendered;
        $this->rendered = false;
        ob_start();
        $this->render();
        $return = ob_get_clean();
        $this->rendered = $lastState;
        return $return;
    }

    public function render(Response $response = null) : View {
        $this->log(__METHOD__);
        if ($response !== null) return $this->renderTo($response);
        if( $this->isRendered() ) return $this;
        $this->isRendering = true;
        extract(self::$sharedData);
        extract($this->data->toArray());
        $action = $this->getController()->getAction();
        $controller = $this->getController()->getName();
        if ($this->type() == 'html' && $this->viewFile) {
            include($this->viewFile);
        }
        $this->rendered = true;
        $this->isRendering = false;
        return $this;
    }

    public function isRendered() {
        return $this->rendered;
    }

    public function isRendering() {
        if( $this->isRendering ) return true;
        if( $this->hasParent() )
            if( $this->getParent()->isRendering() ) return true;
        else return false;
    }

    public function renderTo(Response $response) {
        $response->setView($this);
        return $this;
    }

    public function getHelper() : ViewHelper {
        return $this->helper;
    }

    public function setHelper($helper) : View {
        $helper = new $helper($this);
        $this->helper = $helper;
        return $this;
    }

    public function & getData() : Data {
        return $this->data;
    }

    public function setData($args) : View {
        $this->data = new Data($args);
        return $this;
    }

    public function use () {
        $this->sendTo($this->app->getResponse());
    }

    public function sendTo(Response $response) {
        $response->setView($this);
        return $this;
    }

    protected function type() { return 'html'; }

    public function addData($args) : View {
        foreach ($args as $k => $v) $this->data[ $k ] = $v;
        return $this;
    }


    public function data($args = null) {
        if ($args) return $this->setData($args);
        else return $this->getData();
    }


    protected function addView(string $name, $args = []) {
        $view = new View($name, $this, $this->getController());
        $view->setData($this->data)
             ->addData($args)
             ->addData(self::$sharedData);
        $this->nestedViews[] = $view;
        if( !$this->isRendering() ) $this->render();
        return $view;
    }

    protected function getController() : Controller {
        return !is_null($this->controller) ? $this->controller : Controller::$activeController;
    }

    private function findViewFile($name = null) {
        if ($name === null) $name = $this->name;
        if ($name !== null) {
            $root = $this->app->getConfig('approot', '.');
            $views = $this->app->getConfig('viewsdir', 'views');
            $file = $root . DIRECTORY_SEPARATOR . $views . DIRECTORY_SEPARATOR . $name . '.php';
            if ($name != null && !file_exists($file)) throw new Exception("Can't find view: $file");
            $this->viewFile = $file;
            return $file;
        }
        return false;
    }

    public function __toString() {
        return $this->renderToString();
    }
}
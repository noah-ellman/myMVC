<?php

class View implements DoesDataStorage {

    use TLoggable;

    protected static $sharedData = [];
    public $name;
    protected $viewFile;
    protected $data;
    protected $helper;
    protected $parentView = null;
    protected $controller = null;
    protected $nestedViews = [];

    public function __construct($name, View $parentView = null, Controller $controller = null) {
        $this->name = $name;
        $this->parentView = $parentView;
        $this->controller = $controller;
        $this->viewFile = $this->findViewFile($name);
        $this->data = new Data();
        $this->log("Controller", get_class($controller), 'loaded view:');
        if ( $this->parentView ) $this->log("Booting view <i>{$this->name}</i> from inside <i>{$this->parentView->name}</i>");
        else $this->log("Booting view <i>{$this->name}</i>");
    }

    private function findViewFile($name = null) {
        if ( $name === null ) $name = $this->name;
        if( $name !== null ) {
            $root = App::getConfig('approot', '.');
            $views = App::getConfig('viewsdir', 'views');
            $file = $root . DIRECTORY_SEPARATOR . $views . DIRECTORY_SEPARATOR . $name . '.php';
            if ( $name != null && !file_exists($file) ) throw new Exception("Can't find view: $file");
            $this->viewFile = $file;
            return $file;
        }
        return false;
    }

    public function hasParent() {
        return $this->parentView === null ? false : true;
    }

    public static function factory($name, $args = []) : View {
        $view = new View($name);
        $view->setData($args);
        return $view;
    }

    public static function share($key, $val) {
        self::$sharedData[ $key ] = $val;
    }

    public function setModel(Model $args) : View {
        $this->setData($args->getData());
        return $this;
    }

    public function renderToString() : string {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    public function render(Response $response = null) : View {
        $this->log(__METHOD__);
        if ( $response !== null ) return $this->renderTo($response);
        extract(self::$sharedData);
        extract($this->data->toArray());
        $action = $this->getController()->getAction();
        $controller = $this->getController()->getName();
        if ( $this->type() == 'html' && $this->viewFile ) {
            include($this->viewFile);
        }
        return $this;
    }

    public function renderTo(Response $response) {
        $response->setView($this);
                // ->setContent($this->renderToString());
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

    public function sendTo(Response $response) {
        $response->setView($this);
        return $this;
    }

    public function type() { return 'html'; }

    public function addData($args) : View {
        foreach ( $args as $k => $v ) $this->data[ $k ] = $v;
        return $this;
    }

    public function __toString() {
        return $this->renderToString();
    }

    protected function addView(string $name, $args = []) {
        $view = new View($name, $this, $this->getController());
        $view->setData($this->data)
             ->addData($args)
             ->addData(self::$sharedData);
        $this->nestedViews[] = $view;
        return $view;
    }

    protected function getController() : Controller {
        return !is_null($this->controller) ? $this->controller : Controller::$activeController;
    }
}
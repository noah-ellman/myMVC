<?php

class View implements DoesDataStorage {

    use TLoggable;

    public $name;
    protected $viewFile;
    protected $data;

    protected $helper;
    protected $parentView = NULL;


    protected $controller = NULL;
    protected $nestedViews = [];
    protected static $sharedData = [];

    public function __construct(string $name, View $parentView = NULL, Controller $controller = NULL) {
        $this->name = $name;
        $this->parentView = $parentView;
        $this->controller = $controller;
        $root = App::getConfig('approot','.');
        $file = $root . '/views/' . $name . '.php';
        if ( $name != NULL && !file_exists($file) ) throw new Exception("Can't find view: $file");
        $this->viewFile = $file;
        $this->data = new Data();
        $this->log("Controller", get_class($controller), 'loaded view:');
        if ( $this->parentView ) $this->log("Booting view <i>{$this->name}</i> from inside <i>{$this->parentView->name}</i>");
        else $this->log("Booting view <i>{$this->name}</i>");
    }

    public static function factory($name, $args = []): View {
        $view = new View($name);
        $view->setData($args);
        return $view;
    }

    public static function share($key,$val) {
        self::$sharedData[$key] = $val;
    }

    public function setModel(Model $args): View {
        $this->setData($args->getData());
        return $this;
    }

    public function renderToString() : string {
        ob_start();
        $this->render();
        return ob_get_clean();
    }

    public function render(): View {
        extract(self::$sharedData);
        extract($this->data->toArray());
        if( !is_null($this->controller) ) $action = $this->controller->getAction();
        include( $this->viewFile );
        return $this;
    }

    public function getHelper(): ViewHelper {
        return $this->helper;
    }

    public function setHelper($helper): View {
        $helper = new $helper($this);
        $this->helper = $helper;
        return $this;
    }

    public function getController() : Controller {
        return !is_null($this->controller) ? $this->controller : Controller::$activeController;
    }

    public function addView(string $name, $args = []) {
        $view = new View($name, $this, $this->getController());
        $view->setData($this->data)
             ->addData($args)
             ->addData(self::$sharedData);
        $this->nestedViews[] = $view;
        return $view;
    }

    public function & getData(): Data {
        return $this->data;
    }

    public function setData($args): View {
        $this->data = new Data($args);
        return $this;
    }

    public function addData($args): View {
        foreach ( $args as $k => $v ) $this->data[$k] = $v;
        return $this;
    }


}
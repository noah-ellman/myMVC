<?php

abstract class Controller implements DoesDataStorage {

    use TLoggable;

    public static $activeController;
    protected $data;
    protected $request;
    protected $view;
    protected $viewName;
    protected $action;
    protected $defaultAction = 'index';
    protected $response;

    public function __construct(Request $request, Response $response, $action = null) {
        $this->request = $request;
        $this->response = $response;
        $this->action = $action;
        $this->data = new Data();
        Cloud::controller(get_class($this));
        Cloud::action($action);
        $this->log("Booting Controller");
        $this->boot();
    }

    public function run() {
        self::$activeController = $this;
        $action = $this->getAction();
        if ( !is_callable([$this,
                           $action])
        ) throw new Exception("Invalid action '<i>$action</i>' in " . get_class($this));
        return call_user_func([$this, $action]);

    }

    public function getName() {
        return get_class($this);
    }

    public function getAction() : string {
        $action = $this->action ?? $this->defaultAction;
        $action = "action" . ucwords($action);
        return $action;
    }

    public function & getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = new Data($data);
        return $this;
    }

    public function addData($data) {
        foreach ( $data as $k => $v ) $this->data[ $k ] = $v;
        return $this;
    }

    public function getDataAsArray() {
        return $this->data->toArray();
    }

    public function setModel(Model $model) {
        $this->model = $model;
    }

    public function getView($viewName = null) : View {
        if ( $viewName ) {
            if ( class_exists($viewName) && is_a($viewName, 'View', true) ) {
                $this->view = new $viewName($this->viewName, null, $this);
            }
        }
        if ( $viewName ) $this->viewName = $viewName;
        if ( !($this->view instanceof View) ) $this->view = new View($this->viewName, null, $this);
        return $this->view;

    }

    abstract protected function boot();

}


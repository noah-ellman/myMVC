<?php

abstract class Controller implements DoesDataStorage {

    use TLoggable;

    protected $data;
    protected $request;
    protected $view;
    protected $viewName;
    protected $action;
    protected $defaultAction = 'index';
    public static $activeController;

    public function __construct($action = NULL) {
        $this->request = App::getRequest();
        $this->action = $action;
        $this->data = new Data();
        $this->log("Booting Controller");
        $this->boot();
    }

    abstract protected function boot();


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

    public function getAction(): string {
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
        foreach ( $data as $k => $v ) $this->data[$k] = $v;
        return $this;
    }

    public function getDataAsArray() {
        return $this->data->toArray();
    }

    public function setModel(Model $model) {
        $this->model = $model;
    }

    public function getView($viewName = NULL): View {
        if ( $viewName ) $this->viewName = $viewName;
        if ( !( $this->view instanceof View ) ) $this->view = new View($this->viewName, NULL, $this);
        return $this->view;
    }


}


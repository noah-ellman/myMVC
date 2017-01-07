<?php

abstract class Controller extends Container implements DoesDataStorage {

    use TLoggable;

    public static $activeController;
    protected $data;
    protected $request;
    protected $viewName;
    protected $action;
    protected $defaultAction = 'index';
    protected $response;
    private $view;

    public function __construct(Request $request, Response $response, $action = null) {
        $this->request = $request;
        $this->response = $response;
        $this->action = $action;
        $this->data = new Data();
        Cloud::controller(get_class($this));
        Cloud::action($action);
        $this->log("Booting Controller", $this->getName());
        parent::__construct();
        $this->boot();
    }

    public function run() {
        self::$activeController = $this;
        $action = $this->getAction();
        if (!is_callable([$this, $action]))
            throw new Exception("Invalid action '<i>$action</i>' in " . get_class($this));
        $result = call_user_func([$this, $action]);
        return $this->afterAction($result);

    }

    public function getAction() : string {
        $action = $this->action ?: $this->defaultAction;
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
        foreach ($data as $k => $v) $this->data[ $k ] = $v;
        return $this;
    }

    public function getDataAsArray() {
        return $this->data->toArray();
    }

    public function setModel(Model $model) {
        $this->model = $model;
    }

    public function getView($viewName = null) : View {
        if ($viewName) {
            if (class_exists($viewName) && is_a($viewName, 'View', true)) {
                $this->view = new $viewName($this->viewName, null, $this);
                $this->viewName = $this->view->getName();
                return $this->view;
            }
            $this->viewName = $viewName;
        }
        if ($viewName === null && !$this->viewName) $this->viewName = $this->getDefaultViewName();
        if (!($this->view instanceof View)) $this->view = new View($this->viewName, null, $this);
        return $this->view;

    }

    protected function useView($viewName = null) {
        $this->getView($viewName ?? $this->viewName)->use();
    }

    protected function getDefaultViewName() {
        $name = get_class($this);
        $name = preg_replace_callback('/[A-Z]/', function($str) {
            return '-' . strtolower($str[0]);
        }, $name);
        if (substr($name, '0', '1') == '-') $name = substr($name, 1);
        $this->log("Defualt view name =", $name);
        return $name;

    }

    protected function afterAction($result = null) {
        if ($this->view && !$this->view->isRendered()) $this->view->sendTo($this->response);
        return $result;
    }

    protected function boot() {}

}


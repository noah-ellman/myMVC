<?php
abstract class Container {

    protected $name;
    protected $app;
    protected $provides;

    public function __construct() {
        $this->app = App::getInstance();
        $this->name = $this->getName();
    }

    public function getName() {
        return $this->name ?: get_class($this);
    }

    protected function app() : App {
        return $this->app;
    }

    public function provides() {
        return $this->provides ?: strtolower(get_class($this));
    }

    protected function requires() {}

}
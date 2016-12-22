<?php
class JSONView extends View {

    public function __construct($name = NULL,$parentView = NULL, $controller = NULL) {
        parent::__construct(NULL,NULL,$controller);
    }

    public function render() : View {
        echo json_encode($this->data);
        return $this;

    }


}
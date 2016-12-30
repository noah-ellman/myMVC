<?php
class JSONView extends View implements JSONable {

    use TLoggable;

    public function __construct($name = NULL,$parentView = NULL, $controller = NULL) {
        parent::__construct(NULL,NULL,$controller);
    }

    public function render(Response $response = NULL) : View {
        $this->log(__METHOD__);
        if( $response !== null ) {
             $response->setView($this);
             //$response->setContent($this->toJSON());
             $response->headers->replace(['Content-Type'=>'text/json']);
             return $this;

        } else {
            return parent::render();
        }
    }

    public function renderToString() : string {
        $this->log(__METHOD__);
        return $this->toJSON();
    }

    public function __toString() {
        $this->log(__METHOD__);
        return $this->toJSON();
    }

    public function type() { return 'json'; }

    public function toJSON() {
        $this->log(__METHOD__);
        $this->log($this->data->toJSON());
        return $this->data->toJSON();
    }


}
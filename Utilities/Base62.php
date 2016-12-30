<?php
class Base62 implements Tool {

    protected  $input;
    protected $output;

    public function input($str) {
        $this->input = $str;
        $this->output = urlencode(base64_encode($this->input));
        return $this;
    }

    public function output() {
        return $this->result;
    }

}
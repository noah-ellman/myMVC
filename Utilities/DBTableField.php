<?php
class DBTableField extends stdClass {

    public $name = '';
    public $key = FALSE;
    public $primary = FALSE;
    public $unique = FALSE;
    public $number = FALSE;
    public $string = FALSE;
    public $length = 0;
    public $label = '';
    public $comment = '';
    public $type = '';
    public $enums = '';
    public $default = '';
    public $value = '';
    public $custom = '';

    public function __tostring() { return $this->name; }

    public function __construct($o) {
        $this->name = $o->Field;
        $this->type = $o->Type;
        $this->comment = $o->Comment;
        if ( $this->comment ) $this->label = $this->comment;
        else $this->label = ucwords(str_replace('_', ' ', $this->name));
        if ( !is_null($o->Default) ) {
            $this->default = $o->Default;
        }
        if ( $o->Key == 'PRI' ) {
            $this->primary = TRUE;
            $this->unique = TRUE;
            $this->key = TRUE;
        } else if ( $o->Key == 'UNI' ) {
            $this->unique = TRUE;
            $this->key = TRUE;
        }
        if ( Str::instr($this->type, '/enum|set/') ) {
            $this->string = TRUE;
            $this->enums = Str::match('/\((.*?)\)/', $this->type);
            $this->enums = str_replace("'", '', $this->enums);
            $this->type = 'enum';
        }
        $this->length = Str::match('/[a-z]+\((\d+)\)/', $this->type);
        if ( Str::instr($this->type, '/int|dec|float/') ) {
            $this->number = TRUE;
        } else if ( Str::instr($this->type, '/char|binary|text|blob/') ) {
            $this->string = TRUE;
        }
    }
}

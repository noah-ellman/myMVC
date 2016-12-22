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
        if ( Str::instr($this->type, '/int|dec|float/i') ) {
            $this->number = TRUE;
        } else if ( Str::instr($this->type, '/char|binary|text|blob/') ) {
            $this->string = TRUE;
        }
    }
}


class DBTableFields extends stdClass {

    use DBAccess;

    protected $table;
    protected $strFields;
    protected $listFields;
    public $fields;


    public function __construct($tbl) {
        $this->table = $tbl;
        $result = $this->query("SHOW FULL COLUMNS FROM $tbl");
        $this->listFields = [];
        $this->fields = (object)[];
        $this->strFields = '';
        foreach ( $result as $k => $tmp ) {
            $this->{$tmp->Field} = new DBTableField($tmp);
            $this->listFields[] = $this->{$tmp->Field};
            $this->fields->{$tmp->Field} = $this->{$tmp->Field};
            if ( !empty($this->strFields) ) $this->strFields .= ', ';
            $this->strFields .= "`{$tmp->Field}`";
        }

    }

    public function __get($k) {
        if ( isset($this->fields[$k]) ) return $this->fields[$k]; else return NULL;
    }

    public function setHidden(...$fields) {
        foreach ($fields as $v) {
            unset($this->fields->$v);
        }
    }

    public function getPrimary() {
        foreach ( $this->listFields as $k => $v ) {
            if ( is_object($v) ) {
                if ( $v->primary ) return (string)$v;
            }
        }
    }

    public function getKeys() {
        $keys = [];
        foreach ( $this->listFields as $k => $v ) {
            if ( is_object($v) ) {
                if ( $v->key ) $keys[] = (string)$v;
            }
        }
        return $keys;
    }

    public function & getFieldsAndKeysData() {
        return $this->fields;
    }

    public function getFieldsData() {
        $fields = [];
        foreach($this->fields as $k => &$v) {
            if( $this->fields->$k->key ) continue;
            $fields[$k] = $v;
        }
        return $fields;
    }

    public function getFields() {
        $fields = [];
        foreach ( $this->listFields as $k => $v ) {
            if ( is_object($v) ) {
                if ( !$v->key ) $fields[] = $v->name;
            }
        }
        return $fields;
    }
}

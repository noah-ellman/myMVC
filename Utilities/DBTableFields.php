<?php



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

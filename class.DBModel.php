<?

class DBModel extends Model {

    use DBAccess;

    protected $dataSource = NULL;
    protected $keys = [];
    protected $columns = [];
    protected $columnsMetaData;
    protected $loaded = FALSE;

    protected $ignoredColumns = [];
    protected $orderBy = [];

    public function __construct($dataSource = NULL) {
        if ( !is_null($dataSource) ) $this->dataSource = $dataSource;
        parent::__construct();
        $this->boot();
    }

    protected function boot() {
        if ( !count($this->columns) ) {
            $this->columns = $this->getTableColumns()->getFields();
        }
        if ( !count($this->keys) ) {
            foreach ( $this->getTableColumns()->getKeys() as $v ) {
                $this->keys[$v] = '';
            }
        }
        $this->columns = array_diff($this->columns, $this->ignoredColumns);
        $this->logDump($this->columns, "{$this->dataSource} columns");
        $this->logDump($this->keys, "{$this->dataSource} keys");

    }


    public function __set($k, $v) {
        if ( isset($this->keys[$k])  ) {
            $this->log("setting key to $v");
            $this->keys[$k] = $v;
        }
        parent::__set($k, $v);
    }

    public function __get($k) {
        if( isset($this->keys[$k]) ) return $this->keys[$k];
        return parent::__get($k);
    }

    public function getTableColumns(): DBTableFields {
        if ( !$this->columnsMetaData )
            $this->columnsMetaData = new DBTableFields($this->dataSource);
        return $this->columnsMetaData;
    }


    public function where(array $keys) {
        $this->keys = $keys;
    }


    public function find() {
        if ( !count($this->keys) ) {
            throw new Exception("NO KEYS");
        }
        $fields = $this->columns;
        $fields_str = ' `' . implode('`,`', array_merge(array_keys($this->keys),$fields)) . '`';
        $result = $this->query("SELECT $fields_str FROM {$this->dataSource} " . $this->whereClause() . ' LIMIT 1');
        if ( $result ) {
            $this->setData($result[0]);
            $return = TRUE;
        } else {
            $return = FALSE;
        }
        $this->loaded = TRUE;
        return $return;
    }

    protected function whereClause() {
        $args = [];
        foreach ( $this->keys as $k => $v ) $args[] = " `$k` = '$v' ";
        $args = implode(' AND ', $args);
        $args = " WHERE $args ";
        return $args;
    }


    public function save() {
        if ( !count($this->keys) ) {
            throw new Exception("NO KEYS");
        }
        $primaryKey = $this->getTableColumns()->getPrimary();
        if ( !isset($this->keys[$primaryKey]) ) throw new Exception("No primary key in where clause in DBTable");
        $result = $this->query("SELECT count(*) FROM {$this->dataSource} " . $this->whereClause() . ' LIMIT 1');
        if ( !$result ) {
            $result = $this->insert();
        } else {
            $arr = [];
            $data = $this->getData()->toArray();
            foreach ( $data as $k => $v ) {
                if ( !in_array($k, $this->columns) ) {
                    unset($data[$k]);
                    continue;
                }
                $v = DB::escape($v);
                $arr[] = "`$k` = " . DB::quote($v);
            }
            $fields = implode(',', $arr);
            $result = $this->query("UPDATE {$this->dataSource} SET $fields " . $this->whereClause() . ' LIMIT 1');
        }
        return $result;
    }



    public function insert() {
        $fields_str = implode('`,`', $this->getData()->keys());
        $values = implode(',', DB::quote(DB::escape($this->getData()->values())));
        $result = $this->query("INSERT INTO {$this->dataSource} (`$fields_str`) VALUES ($values)");
        return $result;
    }

    public function delete($id) {
        return $this->query("delete from {$this->dataSource} where id = $id LIMIT 1");
    }

    public function loadAll() {
        $orderBy = implode(',',$this->orderBy);
        if( strlen($orderBy) ) $orderBy = 'ORDER BY ' . $orderBy;
        $fields = implode(',', array_merge(array_keys($this->keys), $this->columns));
        $result = $this->query("select {$fields} from {$this->dataSource} $orderBy");
        return $result;
    }

    public function getDataSource() {
        return $this->dataSource;
    }

    public static function DataSource() {
        $stub = get_called_class();
        $stub = new $stub();
        return $stub->getDataSource();

    }

}
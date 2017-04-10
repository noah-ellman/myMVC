<?

class DBModel extends Model {

    use DBAccess;

    protected $dataSource = null;
    protected $keys = [];
    protected $columns = [];
    protected $columnsMetaData;
    protected $ignoredColumns = [];
    protected $orderBy = [];
    protected $relations = [];
    protected $limit = [1, 0];
    protected $insertID;

    public function __construct($dataSource = null) {
        if (!is_null($dataSource)) $this->dataSource = $dataSource;
        parent::__construct();
        $this->boot();
    }

    protected function boot() {
        if (!count($this->columns)) {
            $this->columns = $this->getTableColumns()->getFieldsAndKeys();
        }
        if (!count($this->keys)) {
       //     foreach ($this->getTableColumns()->getKeys() as $v) {
       //         $this->keys[ $v ] = null;
       //     }
        }
        $this->columns = array_diff($this->columns, $this->ignoredColumns);

    }

    public function getTableColumns() : DBTableFields {
        if (!$this->columnsMetaData)
            $this->columnsMetaData = new DBTableFields($this->dataSource);
        return $this->columnsMetaData;
    }

    public static function DataSource() {
        $stub = get_called_class();
        return (new $stub())->getDataSource();
    }

    public function __get($k) {
        if (isset($this->keys[ $k ])) return $this->keys[ $k ];
        return parent::__get($k);
    }

    public function __set($k, $v) {
        if (in_array($k, $this->getTableColumns()->getKeys())) {
            $this->log("Setting Key $k to $v");
            $this->keys[ $k ] = $v;
        }
        parent::__set($k, $v);
    }

    public function find($what = null) {
        if ($what == '*') {
            return $this->loadAll();
        }
        if ($what !== null) $this->where([$this->getTableColumns()->getPrimary() => $what]);
        if (!count($this->keys)) {
            $this->log("!Warning - fetching without where");
        }
        $limit = ' LIMIT ' . implode(',', array_reverse($this->limit));
        $fields = $this->columns;
        $fields_str = ' `' . implode('`,`', array_merge(array_keys($this->keys), $fields)) . '`';
        $result = $this->query("SELECT $fields_str FROM {$this->dataSource} " . $this->whereClause() . $limit);
        if ($result) {
            if ($this->limit[0] > 1) {
                $this->setData($result);
            }
            else {
                $this->setData($result[0]);
            }
        }
        $this->loaded = true;
        return parent::find();
    }

    public function reset() {
        $this->keys = [];
        $this->loaded = false;
        return parent::reset();
    }

    public function loadAll() {
        $orderBy = implode(',', $this->orderBy);
        if (strlen($orderBy)) $orderBy = 'ORDER BY ' . $orderBy;
        $fields = implode(',', array_merge(array_keys($this->keys), $this->columns));
        $result = $this->query("select {$fields} from {$this->dataSource} {$this->whereClause()} $orderBy");
        $this->setData($result);
        return $this;
    }

    public function where(array $keys) {
        $this->keys = $keys;
        return $this;
    }

    protected function whereClause() {
        $args = [];
        if (!count($this->keys)) return '';
        foreach ($this->keys as $k => $v) {
            if( empty($v) ) continue;
            $args[] = " `$k` = '$v' ";
        }
        if( !count($args) ) return '';
        $args = implode(' AND ', $args);
        $args = " WHERE $args ";
        return $args;
    }

    public function getRelatedModel($class) {
        if (isset($this->relations[ $class ])) {
            $model = new $class();
            $localkey = $this->relations[ $class ][0] ?? 'id';
            $localkeyvalue = $this->{$localkey};
            if( !$localkeyvalue ) throw new Error("Something is wrong with model relationships.");
            $remotekey = $this->relations[ $class ][1] ?? 'id';
            $model->where([$remotekey => $localkeyvalue])->find();
            return $model;
        }
        else {
            $this->log("!no related model $class");
        }
        return null;

    }

    public function limit($count = 1, $start = 0) {
        $this->limit = [$count, $start];
        return $this;
    }

    public function getInsertID() {
        return $this->insertID;
    }

    public function save() {
        $primaryKey = $this->getTableColumns()->getPrimary();
        $exists = 0;
        if (isset($this->keys[ $primaryKey ]) && (int)$this->keys[$primaryKey ])
            $exists = $this->query("SELECT count(*) FROM {$this->dataSource} " . $this->whereClause() . ' LIMIT 1');
        if (!isset($this->keys[ $primaryKey ]) || !$exists) {
            return $this->insert();
        }
        $this->removeInvalidFields();
        $arr = [];
        $data = $this->getData()->toArray();
        if (!count($data)) {
            return $this->addError("Nothing to update!", 1);
        }
        foreach ($data as $k => $v) {
            $v = DB::escape($v);
            $arr[] = "`$k` = " . DB::quote($v);
        }
        $fields = implode(',', $arr);
        $this->query("UPDATE {$this->dataSource} SET $fields " . $this->whereClause() . ' LIMIT 1');
        return true;
    }

    public function insert() {
        $this->removeInvalidFields();
        if (!count($this->data)) {
            return $this->addError("Nothing to update!", 1);
        }
        $fields_str = implode('`,`', $this->getData()->keys());
        $values = implode(',', DB::quote(DB::escape($this->getData()->values())));
        $this->query("INSERT INTO {$this->dataSource} (`$fields_str`) VALUES ($values)");
        $this->insertID = $this->db()->insert_id;
        return $this;
    }

    public function count() {
        return count($this->data);
    }

    protected function removeInvalidFields() {
        foreach ($this->data as $k => $v) {
            if (!in_array($k, $this->columns)) {
                if (in_array($k, $this->getTableColumns()->getKeys())) {
                    $this->keys[ $k ] = $v;
                    continue;
                }
                unset($this->data->$k);
                $this->log("!Removing invalid field: $k");

            }
        }
    }

    public function delete($id) {
        $id = (int)$id;
        return $this->query("delete from {$this->dataSource} where id = $id LIMIT 1");
    }

    public function getDataSource() {
        return $this->dataSource;
    }
}
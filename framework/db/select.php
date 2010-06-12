<?php
class Ly_Db_Select {
    protected $adapter;

    protected $from;
    protected $cols = array();
    protected $where = array();
    protected $group;
    protected $having;
    protected $order;
    protected $limit;
    protected $offset;

    protected $return_as;

    public function __construct(Ly_Db_Adapter_Abstract $adapter) {
        $this->adapter = $adapter;
    }

    public function __call($fn, $args) {
        if (substr($fn, 0, 3) == 'get')
            $sth = $this->execute();
            return call_user_func_array(array($sth, $fn), $args);
    }

    public function from($table) {
        $this->from = $table;
        return $this;
    }

    public function handle() {
        return $this->adapter;
    }

    public function setCols($cols = null) {
        $this->cols = is_array($cols) ? $cols : func_get_args();
        return $this;
    }

    public function addCol($col = null) {
        $cols = $this->cols;
        if (!is_array($col)) $col = func_get_args();

        while (list(, $c) = each($col)) $cols[] = $c;
        $this->cols = array_unique($cols);
        return $this;
    }

    public function where($where, $params = null) {
        $args = func_get_args();
        $this->where[] = call_user_func_array(array($this->adapter, 'parsePlaceHolder'), $args);
        return $this;
    }

    public function group($group_by) {
        $this->group = $group_by;
        return $this;
    }

    public function having($having) {
        $this->having = $having;
        return $this;
    }

    public function order($order_by) {
        $this->order = $order_by;
        return $this;
    }

    public function limit($limit) {
        $this->limit = abs((int)$limit);
        return $this;
    }

    public function offset($offset) {
        $this->offset = abs((int)$offset);
        return $this;
    }

    public function compile() {
        $adapter = $this->adapter;

        $cols = implode(',', $adapter->qcol($this->cols));
        if (empty($cols)) $cols = '*';

        $sql = sprintf('SELECT %s FROM %s', $cols, $adapter->qtab($this->from));

        $where = $params = array();
        foreach ($this->where as $w) {
            list($where_sql, $where_params) = $w;
            $where[] = $where_sql;
            $params = array_merge($params, $where_params);
        }

        if ($where) $sql .= sprintf(' WHERE %s', '('. implode(') AND (', $where) .')');
        if ($this->group) {
            $sql .= ' GROUP BY '. $this->group;
            if ($this->having) $sql .= ' HAVING '. $this->having;
        }

        if ($this->order) $sql .= ' ORDER BY '. $this->order;
        if ($this->limit) $sql .= ' LIMIT '. $this->limit;
        if ($this->offset) $sql .= ' OFFSET '. $this->offset;

        return array($sql, $params);
    }

    public function execute() {
        list($sql, $params) = $this->compile();
        return $this->adapter->execute($sql, $params);
    }

    public function get($limit = null) {
        if (is_int($limit)) $this->limit($limit);

        $limit = $this->limit;
        $sth = $this->execute();

        if ($limit === 1) {
            return $sth->getRow();
        } else {
            return $sth->getAll();
        }
    }
}

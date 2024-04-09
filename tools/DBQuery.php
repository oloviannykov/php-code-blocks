<?php
/**
* Database query builder for MySQL to simplify queries writing and wrap values automaticly
*
Example:
    function update_domain()
    {
        $query = (new DBQuery('updates_registry')) //--> FROM 'updates_registry'
            ->fields(['id', 'name', 'summary']) //--> SELECT `id`, `name`, `summary`
            ->order_asc('name') //--> ORDER BY `name` ASC
            ->select(); //--> get the SQL
        $records = DBModel::fetchAssoc($query); //run the SQL
        $result = [];
        foreach($records as $r) {
            $result[$r['id']] = $r['name'] . ' - ' . $r['summary'];
        }
        return $result; //return the key-value pairs list
    }
*/

class DBQuery
{
    const
        TYPE_STRING = 'string',
        TYPE_INTEGER = 'integer',
        TYPE_DECIMAL = 'decimal';

    private
        $table = '', //FROM ..., INSERT INTO ..., UPDATE ...
        $table_alias = 'a',
        $fields = [], //SELECT ... / SET ...
        $field_funcs = [],
        $use_and = true,
        $block_started = false,
        $not_in_next_condition = false,
        $conditions = [], //[cond1, [cond2, cond3]] --> cond1 AND (cond2 OR cond3)
        $values = [],
        $join_type = 'JOIN',
        $join_table = '',
        $join_table_alias = 'b',
        $join_relations = [],
        $joined_fields = [],
        $limit = 0,
        $offset = 0,
        $order_fields = [],
        $group_fields = [],
        $errors = [],
        $last_query = '',
        $can_delete_without_conditions = false;

    public function __construct($table = '')
    {
        if (!empty($table) && is_string($table)) {
            $this->table = $this->escape_field($table);
        }
    }


    //FILTERS
    //private function escape($value) { return addslashes($value); }

    private function escape_field($field_name, $quote = true): string
    {
        $result = addslashes(trim($field_name, '` '));
        return $quote ? "`" . $result . "`" : $result;
    }

    //SETTERS
    public function table($table_name)
    {
        $this->table = $this->escape_field($table_name);
        return $this;
    }
    public function join_table($table_name, $join_type = 'JOIN')
    {
        $this->join_table = $this->escape_field($table_name);
        $this->join_type = $join_type;
        return $this;
    }
    public function left_join_table($table_name)
    {
        $this->join_table($table_name, 'LEFT JOIN');
        return $this;
    }
    public function join_relation($field_a, $field_b)
    {
        $this->join_relations[] = $this->table_alias . '.' . $this->escape_field($field_a, false)
            . ' = '
            . $this->join_table_alias . '.' . $this->escape_field($field_b, false);
        return $this;
    }

    public function field_func($field, $func, $alias = '')
    {
        if (empty($field) || empty($func)) {
            $this->errors[] = [__METHOD__ . ": wrong field function" => [$field, $func]];
        } else {
            $this->field_funcs[] = [
                'field' => $this->escape_field($field),
                'func' => addslashes($func),
                'alias' => $alias,
            ];
        }
        return $this;
    }

    public function can_delete_without_conditions($value = true)
    {
        $this->can_delete_without_conditions = $value;
        return $this;
    }

    private function parse_fields($fields)
    {
        if ($fields === false) {
            return false;
        }
        $fields_log = $fields;
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            return array_map(function ($value) {
                return $this->escape_field($value);
            }, $fields);
        }
        $this->errors[] = [__METHOD__ . ": wrong fields list" => $fields_log];
        return false;
    }
    public function fields($fields, $joined_fields = [])
    {
        $this->fields = $this->parse_fields($fields);
        $this->joined_fields = $this->parse_fields($joined_fields);
        return $this;
    }

    private function append_condition($condition): void
    {
        if ($this->not_in_next_condition) {
            $condition = 'NOT ' . $condition;
            $this->not_in_next_condition = false;
        }
        if ($this->block_started) {
            $last_cond_index = $this->conditions ? count($this->conditions) - 1 : -1;
            if ($last_cond_index < 0 || !is_array($this->conditions[$last_cond_index])) {
                $this->errors[] = [__METHOD__ . ': block started but subconditions array is wrong' => $this->conditions];
                return;
            }
            $this->conditions[$last_cond_index][] = $condition;
        } else {
            $this->conditions[] = $condition;

        }
    }

    private function add_order($fields, $direction): bool
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }
        if (!is_array($fields)) {
            $this->errors[] = [__METHOD__ . ": wrong order fields" => $fields];
            return false;
        }
        if (empty($direction) || !in_array($direction, ['ASC', 'DESC'])) {
            $this->errors[] = [__METHOD__ . ": wrong order direction" => $direction];
            return false;
        }
        $this->order_fields[$direction] = array_map([$this, 'escape_field'], $fields);
        return true;
    }
    public function order_asc($fields)
    {
        $this->add_order($fields, 'ASC');
        return $this;
    }
    public function order_desc($fields)
    {
        $this->add_order($fields, 'DESC');
        return $this;
    }

    public function group_by($fields)
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }
        if (!is_array($fields)) {
            $this->errors[] = [__METHOD__ . ": wrong grouping fields" => $fields];
            return $this;
        }
        $this->group_fields = array_map([$this, 'escape_field'], $fields);
        return $this;
    }

    public function limit($limit_number, $offset = 0)
    {
        if (!is_numeric($limit_number)) {
            $this->errors[] = [__METHOD__ . ": wrong limit" => $limit_number];
        }
        if (!is_numeric($limit_number)) {
            $this->errors[] = [__METHOD__ . ": wrong limit" => $limit_number];
        }
        $this->limit = $limit_number;
        $this->offset = $offset;
        return $this;
    }

    //CONDITION PARAMS ---------------------------------

    //use NOT for next condition only
    /**
     * @return self
     */
    public function not()
    {
        $this->not_in_next_condition = true;
        return $this;
    }

    //begin of =, !=, <, >, <=, >= comparision operators
    private function append_operator($field, $operator, $wrapped_value): void
    {
        $this->append_condition($this->escape_field($field) . " $operator " . $wrapped_value);
    }

    private function validate_operator($operator): bool
    {
        return in_array($operator, explode(' ', '= > >= < <= !='));
    }

    /**
     * @return self
     */
    public function field_comp($field1, $operator, $field2)
    {
        if (!$this->validate_operator($operator) || empty($field1) || empty($field2)) {
            $this->errors[] = "field_comp: invalid operator '$field1 $operator $field2'";
        } else {
            $this->append_condition(
                $this->escape_field($field1) . ' ' . addslashes($operator) . ' ' . $this->escape_field($field2)
            );
        }
        return $this;
    }
    /**
     * @return self
     */
    public function equals_string($field, $value)
    {
        $this->append_operator($field, "=", $this->wrapString($value));
        return $this;
    }
    /**
     * @return self
     */
    public function equals_number($field, $value)
    {
        $this->append_operator($field, "=", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function equals_bool($field, $value)
    {
        $this->append_operator($field, "=", $value ? 1 : 0);
        return $this;
    }
    /**
     * @return self
     */
    public function not_equals_string($field, $value)
    {
        $this->append_operator($field, "!=", $this->wrapString($value));
        return $this;
    }
    /**
     * @return self
     */
    public function not_equals_number($field, $value)
    {
        $this->append_operator($field, "!=", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function not_equals_bool($field, $value)
    {
        $this->append_operator($field, "!=", $value ? 1 : 0);
        return $this;
    }
    /**
     * @return self
     */
    public function lower($field, $value)
    {
        $this->append_operator($field, "<", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function higher($field, $value)
    {
        $this->append_operator($field, ">", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function lower_or_equals($field, $value)
    {
        $this->append_operator($field, "<=", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function higher_or_equals($field, $value)
    {
        $this->append_operator($field, ">=", $this->wrapValue($value, self::TYPE_INTEGER));
        return $this;
    }
    /**
     * @return self
     */
    public function between($field, $start_number, $end_number)
    {
        $field_name = $this->escape_field($field);
        $start_num = $this->wrapValue($start_number, self::TYPE_INTEGER);
        $end_num = $this->wrapValue($end_number, self::TYPE_INTEGER);
        $this->append_condition(
            '('
            . $field_name . " >= " . $start_num . ' AND ' . $field_name . " <= " . $end_num
            . ')'
        );
        return $this;
    }
    //end of comparision operators

    // ISNULL ---------------------------------
    /**
     * @return self
     */
    public function isnull($field)
    {
        $this->append_condition(
            'ISNULL(' . $this->escape_field($field) . ')'
        );
        return $this;
    }

    // IN ---------------------------------
    private function append_in($field, $values, $type): void
    {
        $this->append_condition(
            $this->escape_field($field) . " IN (" . $this->wrapValuesList($values, $type) . ")"
        );
    }
    /**
     * @return self
     */
    public function in_numbers($field, $values)
    {
        $this->append_in($field, $values, self::TYPE_INTEGER);
        return $this;
    }
    /**
     * @return self
     */
    public function in_strings($field, $values)
    {
        $this->append_in($field, $values, self::TYPE_STRING);
        return $this;
    }

    // LIKE ---------------------------------
    private function append_like($field, $escaped_mask)
    {
        $this->append_condition(
            $this->escape_field($field) . " LIKE '" . $escaped_mask . "'"
        );
    }
    /**
     * @return self
     */
    public function like($field, $mask)
    {
        $this->append_like($field, addslashes($mask));
        return $this;
    }
    /**
     * @return self
     */
    public function has_prefix($field, $prefix)
    {
        $this->append_like($field, $this->escapeLike($prefix) . "%");
        return $this;
    }
    /**
     * @return self
     */
    public function contents($field, $key_word)
    {
        $this->append_like($field, "%" . $this->escapeLike($key_word) . "%");
        return $this;
    }
    /**
     * @return self
     */
    public function has_suffix($field, $suffix)
    {

        $this->append_like($field, "%" . $this->escapeLike($suffix));
        return $this;
    }

    // AND | OR on first level: a=2 AND/OR b=3
    /**
     * @return self
     */
    public function use_or()
    {
        $this->use_and = false;
        return $this;
    }
    /**
     * @return self
     */
    public function use_and()
    {
        $this->use_and = true;
        return $this;
    }

    // AND | OR on second level: a=2 AND (b=3 OR b=5) / a=2 OR (b=3 AND c=5)
    /**
     * @return self
     */
    public function or_block()
    {
        $this->conditions[] = [];
        $this->block_started = true;
        $this->use_and = false;
        return $this;
    }
    /**
     * @return self
     */
    public function and_block()
    {
        $this->conditions[] = [];
        $this->block_started = true;
        $this->use_and = true;
        return $this;
    }
    /**
     * @return self
     */
    public function block_end()
    {
        $this->block_started = false;
        $this->use_and = !$this->use_and;
        return $this;
    }

    //SET fields values ---------------------------------
    /**
     * @return self
     */
    public function set_expression($field, $expression_string)
    {
        $this->values[$this->escape_field($field)] = $expression_string;
        return $this;
    }
    /**
     * @return self
     */
    public function set_field($field1, $field2)
    {
        $this->set_expression($field1, $this->escape_field($field2));
        return $this;
    }
    /**
     * @return self
     */
    public function set_value($field, $value, $type)
    {
        $this->values[$this->escape_field($field)] = $this->wrapValue($value, $type);
        return $this;
    }
    /**
     * @return self
     */
    public function set_string($field, $value)
    {
        $this->set_value($field, $value, self::TYPE_STRING);
        return $this;
    }
    /**
     * @return self
     */
    public function set_number($field, $value)
    {
        $this->set_value($field, $value, self::TYPE_INTEGER);
        return $this;
    }
    /**
     * @return self
     */
    public function set_decimal($field, $value)
    {
        $this->set_value($field, $value, self::TYPE_DECIMAL);
        return $this;
    }

    /**
     * @return self
     */
    public function substract_int($field, $number)
    {
        $field_name = $this->escape_field($field);
        $n = $this->wrapValue($number, self::TYPE_INTEGER);
        $this->values[$field_name] = $field_name . "-" . $n;
        return $this;
    }
    /**
     * @return self
     */
    public function increment_int($field, $number)
    {
        $field_name = $this->escape_field($field);
        $n = $this->wrapValue($number, self::TYPE_INTEGER);
        $this->values[$field_name] = $field_name . "+" . $n;
        return $this;
    }

    /**
     * @return self
     */
    public function substract_decimal($field, $number)
    {
        $field_name = $this->escape_field($field);
        $n = $this->wrapValue($number, self::TYPE_DECIMAL);
        $this->values[$field_name] = $field_name . "-" . $n;
        return $this;
    }
    /**
     * @return self
     */
    public function increment_decimal($field, $number)
    {
        $field_name = $this->escape_field($field);
        $n = $this->wrapValue($number, self::TYPE_DECIMAL);
        $this->values[$field_name] = $field_name . "+" . $n;
        return $this;
    }

    // FROM BUILDER ---------------------------------
    private function build_from(): string
    {
        if (!$this->has_table()) {
            $this->errors[] = 'table is required';
            return '';
        }

        if ($this->join_table) {
            if (empty($this->join_relations) || !is_array($this->join_relations)) {
                $this->errors[] = 'join relation is wrong';
                return '';
            }
            $table_a = $this->table . ' ' . $this->table_alias;
            $table_b = $this->join_table . ' ' . $this->join_table_alias;
            $join_relations = implode(', ', $this->join_relations);
            $join_block = ' ' . $this->join_type . ' ' . $table_b . ' ON ' . $join_relations;
        } else {
            $table_a = $this->table;
            $join_block = '';
        }

        return " FROM " . $table_a . $join_block;
    }

    // SELECT BUILDER ---------------------------------
    private function build_select(): string
    {
        $fields = [];

        if ($this->fields !== false) {
            $field_prefix = $this->join_table ? $this->table_alias . '.' : '';
            if (empty($this->fields)) {
                $fields[] = $field_prefix . '*';
            } else {
                foreach ($this->fields as $field) {
                    $fields[] = $field_prefix ? $field_prefix . trim($field, '`') : $field;
                }
            }
        }

        if (!empty($this->field_funcs)) {
            $field_prefix = $this->join_table ? $this->table_alias . '.' : '';
            foreach ($this->field_funcs as $item) {
                $alias = $item['alias'] ? ' as ' . $this->wrapString($item['alias']) : '';

                $fields[] = $item['func']
                    . '(' . ($field_prefix ? $field_prefix . trim($item['field'], '`') : $item['field']) . ')'
                    . $alias;
            }
        }

        if ($this->join_table && $this->joined_fields !== false) {
            $field_prefix = $this->join_table ? $this->join_table_alias . '.' : '';
            if (empty($this->joined_fields)) {
                $fields[] = $field_prefix . '*';
            } else {
                foreach ($this->joined_fields as $field) {
                    $fields[] = $field_prefix ? $field_prefix . trim($field, '`') : $field;
                }
            }
        }

        return "SELECT " . implode(', ', $fields);
    }

    // SET BUILDER ---------------------------------
    private function build_set(): string
    {
        if (empty($this->values)) {
            return '';
        }
        $pairs = [];
        foreach ($this->values as $field => $value) {
            $pairs[] = $field . ' = ' . $value;
        }
        return " SET " . implode(', ', $pairs);
    }

    // CONDITION BUILDER ---------------------------------
    private function build_where(): string
    {
        if (empty($this->conditions)) {
            return '';
        }
        if ($this->block_started) {
            $this->block_end();
        }
        $list_glue = $this->use_and ? ' AND ' : ' OR ';
        $sub_cond_glue = $this->use_and ? ' OR ' : ' AND ';
        foreach ($this->conditions as &$cond) {
            if (is_array($cond)) {
                $cond = '(' . implode($sub_cond_glue, $cond) . ')';
            }
        }
        unset($cond);
        return " WHERE " . implode($list_glue, $this->conditions);
    }

    // GROUP BY ---------------------------------
    private function build_groupby(): string
    {
        $groupby = '';
        if ($this->group_fields && is_array($this->group_fields)) {
            $groupby = ' GROUP BY ' . implode(', ', $this->group_fields);
        }
        return $groupby;
    }

    // ORDER AND LIMIT BUILDER ---------------------------------
    private function build_order_and_limit(): string
    {
        $order = $limit = '';
        if ($this->order_fields && is_array($this->order_fields)) {
            $orderGroups = [];
            //loop is required to save directions priority
            foreach ($this->order_fields as $direction => $fields) {
                $orderGroups[] = implode(' ' . $direction . ', ', $fields) . ' ' . $direction;
            }
            $order = ' ORDER BY ' . implode(', ', $orderGroups);
        }
        if (!empty($this->limit)) {
            if (empty($this->offset)) {
                $limit = ' LIMIT ' . (int) $this->limit;
            } else {
                $limit = ' LIMIT ' . (int) $this->offset . ', ' . ($this->limit - $this->offset);
            }
        }
        return $order . $limit;
    }

    //VALIDATION --------------------------
    private function has_table(): bool
    {
        if (empty($this->table)) {
            $this->errors[] = 'table is required';
            return false;
        }
        return true;
    }
    private function has_values(): bool
    {
        if (empty($this->values)) {
            $this->errors[] = 'values are required';
            return false;
        }
        return true;
    }
    private function has_conditions(): bool
    {
        if (empty($this->conditions)) {
            $this->errors[] = 'conditions are required';
            return false;
        }
        if ($this->join_table && empty($this->join_relations)) {
            $this->errors[] = 'join relation is required';
            return false;
        }
        return true;
    }

    //----------------------------------------------
    //------------ QUERY BUILDER -------------------
    //----------------------------------------------

    public function select(): string
    {
        $select = $this->build_select();
        $from = $this->build_from();
        $where = $this->build_where();
        $groupby = $this->build_groupby();
        $order_and_limit = $this->build_order_and_limit();
        if ($this->errors) {
            //errors_collect(__METHOD__, $this->errors);
            return '';
        }
        $this->last_query = $select . $from . $where . $groupby . $order_and_limit;
        return $this->last_query;
    }

    /**
     * @return string
     */
    public function delete(): string
    {
        $where = $this->build_where();
        if ($this->errors || !$this->has_table()) {
            //errors_collect(__METHOD__, $this->errors);
            return '';
        }
        if (!$this->can_delete_without_conditions && !$this->has_conditions()) {
            //errors_collect(__METHOD__, 'conditions required');
            return '';
        }
        $order_and_limit = $this->build_order_and_limit();
        $this->last_query = "DELETE FROM " . $this->table . $where . $order_and_limit;
        return $this->last_query;
    }

    public function update(): string
    {
        $set = $this->build_set();
        $where = $this->build_where();
        if ($this->errors || !$this->has_table() || !$this->has_values() || !$this->has_conditions()) {
            //errors_collect(__METHOD__, $this->errors);
            return '';
        }
        $order_and_limit = $this->build_order_and_limit();
        $this->last_query = "UPDATE " . $this->table . $set . $where . $order_and_limit;
        return $this->last_query;
    }

    public function insert(): string
    {
        $set = $this->build_set();
        //need set_number() or set_string() methods
        if ($this->errors || !$this->has_table() || !$this->has_values()) {
            //errors_collect(__METHOD__, $this->errors);
            return '';
        }
        $this->last_query = "INSERT INTO " . $this->table . $set;
        return $this->last_query;
    }

    //LOG
    public function last_query(): string
    {
        return $this->last_query;
    }

    // --- value wrappers ---

    public function wrapString($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }
        return "'" . addslashes($value) . "'";
    }
    public function wrapArray($array)
    {
        if (is_null($array)) {
            return 'NULL';
        }
        $value = json_encode($array, JSON_UNESCAPED_UNICODE);
        return "'" . addslashes($value) . "'";
    }

    private function wrapValue($value, $type)
    {
        //getTableFields() -> $type
        if (mb_strpos($type, '(')) {
            $type = strtolower(mb_substr($type, 0, mb_strpos($type, '(')));
        }
        if (is_array($value)) {
            if (in_array($type, ['float', 'real', 'decimal', 'int', 'integer', 'tinyint'])) {
                /*
                report_error(__METHOD__, [
                    'error' => 'trying to put array into numeric field',
                    'array' => $value,
                    'field_type' => $type
                ]);*/
                return false;
            }
            return $this->wrapArray($value);
        }
        if (is_null($value)) {
            return 'NULL';
        }
        switch ($type) {
            case 'float':
            case 'real':
            case 'decimal':
                $result = floatval($value);
                break;
            case 'int':
            case 'integer':
            case 'tinyint':
                $result = intval($value);
                break;
            case 'bool':
            case 'boolean':
                $result = $value > 0 ? 1 : 0;
                break;
            case 'string':
            case 'text':
            case 'blob':
            case 'varchar':
            case 'varchar2':
            default:
                $result = "'" . addslashes($value) . "'";
        }
        return $result;
    }

    private function wrapValuesList($array, $type): string
    {
        if (mb_strpos($type, '(')) {
            $type = strtolower(mb_substr($type, 0, mb_strpos($type, '(')));
        }
        $result = [];
        foreach ($array as $value) {
            $result[] = $this->wrapValue($value, $type);
        }
        return implode(',', $result);
    }

}

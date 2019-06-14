<?php

namespace lsb\App\models;

use lsb\Libs\DB;

class Query
{
    private $selectQurey = false;
    private $updateQurey = false;
    private $deleteQurey = false;
    private $insertQurey = false;

    private $table = [];
    private $where = [];

    private $column = [];
    private $set = [];
    private $value = [];

    private $map = [];

    private $q;
    private $p;

    public function __construct($map = [])
    {
        $this->map = $map;
    }

    public static function make()
    {
        return new static;
    }

    public function setQuery($q, $p)
    {
        $this->q = $q;
        $this->p = $p;
        return $this;
    }

    public function selectQurey()
    {
        $this->selectQurey = true;
        return $this;
    }

    public function updateQurey()
    {
        $this->updateQurey = true;
        return $this;
    }

    public function deleteQurey()
    {
        $this->deleteQurey = true;
        return $this;
    }

    public function insertQurey()
    {
        $this->insertQurey = true;
        return $this;
    }

    private function makeSelectQuery()
    {
        $selectClause = "SELECT " . implode(', ', $this->column);
        $fromClause = "FROM " . implode(', ', $this->table);
        $whereClause = "WHERE " . implode(' AND ', $this->where);
        $this->q = "{$selectClause} {$fromClause} {$whereClause}";
    }

    private function makeUpdateQuery()
    {
        $updateClause = "UPDATE " . implode(', ', $this->table);
        $setClause = "SET " . implode(', ', $this->set);
        $whereClause = "WHERE " . implode(' AND ', $this->where);
        $this->q = "{$updateClause} {$setClause} {$whereClause}";
    }

    private function makeDeleteQuery()
    {
        $deleteFromClause = "DELETE FROM " . implode(', ', $this->table);
        $whereClause = "WHERE " . implode(' AND ', $this->where);
        $this->q = "{$deleteFromClause} {$whereClause}";
    }

    private function makeInsertQuery()
    {
        $insertClause = "INSERT INTO " . implode(', ', $this->table);
        $columns = "(" . implode(', ', $this->column) . ")";
        $valueClause = "VALUE (" . implode(', ', $this->value) . ")";
        $this->q = "{$insertClause} {$columns} {$valueClause}";
    }

    public function run()
    {
        if ($this->selectQurey) {
            $this->makeSelectQuery();
        } elseif ($this->updateQurey) {
            $this->makeUpdateQuery();
        } elseif ($this->deleteQurey) {
            $this->makeDeleteQuery();
        } elseif ($this->insertQurey) {
            $this->makeInsertQuery();
        }
        return DB::runQuery($this->q, $this->p);
    }

    protected function setTable($table)
    {
        $this->table[] = $table;
        return $this;
    }

    /*****************************************************/

    public function select(array $columns)
    {
        foreach ($columns as $column) {
            $this->column[] = $this->map[$column];
        }
        return $this;
    }

    public function selectAll()
    {
        $this->column = ['*'];
        return $this;
    }

    public function from($table)
    {
        return $this->setTable($table);
    }

    public function whereEqual(array $conds)
    {
        $this->where($conds, '=');
        return $this;
    }

    public function whereLT(array $conds)
    {
        $this->where($conds, '<');
        return $this;
    }

    public function whereLTE(array $conds)
    {
        $this->where($conds, '<=');
        return $this;
    }

    public function whereGT(array $conds)
    {
        $this->where($conds, '>');
        return $this;
    }

    public function whereGTE(array $conds)
    {
        $this->where($conds, '>=');
        return $this;
    }

    private function where(array $conditions, string $eq)
    {
        foreach ($conditions as $column => $value) {
            $column = $this->map[$column];
            $bind = ":{$column}";
            $this->where[] = "{$column} {$eq} {$bind}";
            $this->p[$bind] = $value;
        }
        return $this;
    }

    /*******************************************************/

    public function update($table)
    {
        return $this->setTable($table);
    }

    public function set(array $sets)
    {
        $this->pset($sets);
        return $this;
    }

    public function setAdd(array $sets)
    {
        $this->pset($sets, '+');
        return $this;
    }

    public function setSub(array $sets)
    {
        $this->pset($sets, '-');
        return $this;
    }

    private function pset(array $sets, string $sign = '')
    {
        foreach ($sets as $column => $value) {
            $column = $this->map[$column];
            $bind = ":{$column}";
            $this->set[] = $sign === ''
                ? "{$column} = {$bind}"
                : "{$column} = {$column} {$sign} {$bind}";
            $this->p[$bind] = $value;
        }
        return $this;
    }

    /*****************************************************/

    public function insertInto($table)
    {
        $this->table[] = $table;
        return $this;
    }

    public function value(array $insertValues)
    {
        foreach ($insertValues as $column => $value) {
            $column = $this->map[$column];
            $bind = ":{$column}";
            $this->column[] = $column;
            $this->value[] = $bind;
            $this->p[$bind] = $value;
        }
        return $this;
    }
}

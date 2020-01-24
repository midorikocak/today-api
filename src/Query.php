<?php
declare(strict_types=1);

namespace MidoriKocak;

class Query
{
    private $query;
    private $statement;
    private $params;

    public function __construct()
    {
        $this->query = '';
        $this->statement = '';
        $this->params = [];
    }

    public function select($table, array $columns = ['*'])
    {
        $columns = implode(', ', $columns);
        $this->statement = "SELECT ".$columns." FROM ".$table;
        $this->query = "SELECT ".$columns." FROM ".$table;
        return $this;
    }

    public function update($table, array $values)
    {
        $this->statement = 'UPDATE '.$table.' SET ';
        $this->query = 'UPDATE '.$table.' SET ';
        $this->prepareParams($values, ', ');
        return $this;
    }

    public function where($field, $value)
    {
        $hasOperator = preg_match('~^(([<>=])+(=)*)~', (string)$value);
        if (!empty($hasOperator)) {
            $operator = '';
        } else {
            $operator = '=';
        }

        $this->statement .= ' WHERE '.$field.$operator.':'.$field;
        $this->query .= ' WHERE '.$field.$operator.'\''.$value.'\'';
        $this->params[$field] = $value;
        return $this;
    }

    public function and(array $values)
    {
        $this->query .= " AND ";
        $this->statement .= " AND ";
        $this->prepareParams($values, 'AND');
        return $this;
    }

    public function or(array $values)
    {
        $this->query .= " OR ";
        $this->statement .= " OR ";
        $this->prepareParams($values, 'OR');
        return $this;
    }

    public function between($field, $before, $after)
    {
        $this->query .= $field." BETWEEN $before AND $after";
        $this->statement .= $field.' BETWEEN :before AND :after';

        $this->params['before'] = $before;
        $this->params['after'] = $after;
        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function getParams()
    {
        return $this->params;
    }

    private function prepareParam($key, $value, $glue)
    {
        $queryValue = '';

        $hasOperator = preg_match('~^(([<>=])+(=)*)~', $value, $matches);
        if (!empty($hasOperator)) {
            $operator = reset($matches);
            $value = substr($value, strlen($operator));
        } else {
            $operator = '=';
        }

        if (!isset($this->params[$key])) {
            $value = $key.$operator.'\''.$value.'\'';
            $param = $key.$operator.':'.$key;

            $this->params[$key] = $value;
        } else {
            $uniqid = uniqid();
            $queryValue = $key.$operator.'\''.$value.'\'';
            $param = $key.$operator.':'.$key.$uniqid;

            $this->params[$key.$uniqid] = $value;
        }

        $this->query .= $glue.$queryValue;
        $this->statement .= $glue.$param;
    }

    private function prepareParams(array $values, string $glue)
    {
        $params = [];
        $queryValues = [];

        foreach ($values as $key => $value) {
            $hasOperator = preg_match('~^(([<>=])+(=)*)~', $value, $matches);
            if (!empty($hasOperator)) {
                $operator = reset($matches);
                $value = substr($value, strlen($operator));
            } else {
                $operator = '=';
            }

            if (!isset($this->params[$key])) {
                $queryValues[] = $key.$operator.'\''.$value.'\'';
                $params [] = $key.$operator.':'.$key;

                $this->params[$key] = $value;
            } else {
                $uniqid = uniqid();
                $queryValues[] = $key.$operator.'\''.$value.'\'';
                $params [] = $key.$operator.':'.$key.$uniqid;

                $this->params[$key.$uniqid] = $value;
            }

        }

        $this->query .= implode("$glue", $queryValues);
        $this->statement .= implode("$glue", $params);
    }

}

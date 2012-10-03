<?php

namespace TangramGridModule\Grid\DataSource;

/**
 * The column class keeps in track of all column details (including dynamic ones)
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Columns extends \ArrayObject {

    /**
     * If specified, only these columns will be visible in the grid
     *
     * @var array
     */
    public $showColumns = array();

    /**
     * Constructor
     *
     * @param array $columns
     */
    public function __construct(array $columns = array()) {
        foreach ($columns as $column) {
            $this->add($column);
        }
    }

    /**
     * Adds a column to the internal index
     *
     * @param mixed   $column   Either the name or an array with options
     * @param string  $label    The friendlyname used for this column as heading
     * @param string  $sidx     The fieldname to be used when sorting is isseud thru the grid
     * @param integer $position The position number, can be any number.
     */
    public function add($column, $label = null, $sidx = null, $position = null, $data = null) {
        if (!is_array($column)) {
            $name = $column;

            $column = array();
            $column['name'] = $name;
            $column['label'] = (is_null($label) ? $name : $label);
            if (null !== $sidx) {
                $column['index'] = $sidx;
            }

            $column['position'] = (is_null($position) ? $this->count() : $position);

            /**
             * Default data drawing callback
             */
            if (!is_callable($data)) {
                $column['data'] = function($row) use ($column, $data) {
                            if (null !== $data) {
                                return $data;
                            } else {
                                return isset($row[$column['name']]) ? $row[$column['name']] : null;
                            }
                        };
            } else {
                $column['data'] = $data;
            }
        }

        $this->offsetSet($column['name'], $column); //array object access

        return $this->offsetGet($column['name']);
    }

    /**
     * @param  string $name
     * @return boolean
     */
    public function __isset($name) {
        return $this->offsetExists($name);
    }

    /**
     * Retrieves a column like it's an object property
     *
     * @param  string $column
     * @return array
     */
    public function __get($column) {
        if ($this->offsetExists($column)) {
            return $this->offsetGet($column);
        } else {
            throw new \Exception('Unknown column (' . $column . ')');
        }
    }

    /**
     * Sorts items as we would expect them, just before the iterator is returned
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        $this->_sort();

        return parent::getIterator();
    }

    /**
     * Sorts the columns
     */
    protected function _sort() {
        $arrayCopy = $this->getArrayCopy();

        if (count($this->showColumns) > 0) {
            $columns = array();
            foreach ($arrayCopy as $name => $column) {
                if (!in_array($name, $this->showColumns)) {
                    $columns[$name] = $column;
                }
            }

            foreach ($this->showColumns as $name) {
                if ($this->offsetExists($name)) {
                    $columns[$name] = $this->offsetGet($name);
                }
            }
        } else {
            $columns = $arrayCopy;

            $columnNames = array();
            $columnPositions = array();

            foreach ($columns as $name => $column) {
                $columnNames[$name] = $name;
                $columnPositions[$name] = $column['position'];
            }

            array_multisort($columnPositions, SORT_ASC, $columnNames, SORT_ASC, $columns);
        }

        $this->exchangeArray($columns);
    }

    /**
     * Returns the columns
     *
     * @return array
     * @deprecated Use ->getAll() because of better naming
     */
    public function getColumns() {
        return $this->getAll();
    }

    /**
     * Returns the columns
     *
     * @return array
     */
    public function getAll() {
        $this->_sort();
        return $this->getArrayCopy();
    }

}
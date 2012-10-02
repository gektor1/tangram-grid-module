<?php

namespace TangramGridModule\Grid\DataSource;

/**
 * Abstract version of the data source. In his child is defined and retrieves data from
 * the specified data source for example Zend_Db, Doctrine2, etc.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class DataSourceAbstract
{
    /**
     * The columns container
     *
     * @var Grid_DataSource_Columns
     */
    public $columns;

    /**
     * If set, this column tells jqGrid how each row can be identified
     *
     * @var array
     */
    protected $_identifierColumn;

    /**
     * Event that fires on filtering
     *
     * @var Closesure
     */
    protected $_onFilter;

    /**
     * Event that fires on ordering the grid
     *
     * @var type Closure
     */
    protected $_onOrder;

    /**
     * Posted jqGrid params
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Limit per page
     *
     * @var integer
     */
    protected $_limitPerPage = 10;

    /**
     * Array container where the actual grid data will be loaded in
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Closure for auto escaping column strings in the grid result
     *
     * @var closure
     */
    protected $_autoEscapeClosure;

    /**
     * Columns that must not be escaped by the auto escape closure
     *
     * @var array
     */
    protected $_excludedColumnsFromEscaping = array();

    /**
     * Constructor
     *
     * @param mixed $source
     */
    public function __construct()
    {
        $this->setAutoEscapeClosure(function($string) {
            return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
        });
    }

    /**
     * Sets a column name which identifies every row in the grid.
     *
     * @param  string $column
     * @return Grid_DataSource_Abstract
     */
    public function setIdentifierColumn($column)
    {
        if (isset($this->columns[$column])) {
            $this->_identifierColumn = $this->columns[$column];
        } else {
            throw new \Exception('Cannot set identifier to an unknown column "' . $column . '"');
        }

        return $this;
    }

    /**
     * Sets the closure for auto escaping column strings in the grid result
     *
     * @param  Closure $closure
     * @return Grid_DataSource_Abstract
     */
    public function setAutoEscapeClosure(\Closure $closure)
    {
        $this->_autoEscapeClosure = $closure;
        return $this;
    }

    /**
     * Exludes columns from escaping
     *
     * The specified columns will not be escaped by the auto escape closure.
     * This is relevant for columns that contain an image for example. Make sure that you do
     * escaping for the content inside that column by yourself, otherwise you'll be vulnerable
     * for XSS attacks!
     *
     * @param array $columns
     */
    public function excludeColumnsFromEscaping(array $columns)
    {
        foreach ($columns as $column) {
            $this->_excludedColumnsFromEscaping[] = $column;
        }

        return $this;
    }

    /**
     * Resets the entire list of columns to be excluded from escaping. This will set
     * the datasource to normal behavior.
     *
     * @return Grid_DataSource_Abstract
     */
    public function resetExcludeColumnsFromEscaping()
    {
        $this->_excludedColumnsFromEscaping = array();

        return $this;
    }

    /**
     * Sets the parameters which probably come from jQuery
     *
     * @param  array $params
     * @return Grid_DataSource_Abstract
     */
    public function setParameters(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Returns the parameters which probably come from jQuery
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->_params;
    }

    /**
     * Sets the results to display per page
     *
     * @param  integer $number
     * @return Grid_DataSource_Abstract
     */
    public function setResultsPerPage($number)
    {
        $this->_limitPerPage = (int) $number;
        return $this;
    }

    /**
     * Renders a single row and adds it to the internal data array
     *
     * @param array $row
     * @param array $excludeColumns
     * @param mixed $closureResult
     * @return void
     */
    protected function _renderRow($row, $excludeColumns = array(), $closureResult = null)
    {
        $rowColumns = array();
        foreach ($this->columns as $index => $column) {
            if (array_key_exists('data', $column)) {
                if (is_callable($column['data'], false, $method)) {
                    $rowColumns[$index] = $this->_escape(
                        call_user_func($column['data'], $row, $closureResult),
                        $column['name']
                    );
                } else {
                    // Replace all column tokens that are possibly available in the column data
                    array_walk($row, function($value, $key) use (&$column) {
                        $column['data'] = str_replace('{' . strtolower($key) . '}', $value, $column['data']);
                    });

                    $rowColumns[$index] = $this->_escape($column['data'], $column['name']);
                }
            } elseif (array_key_exists($index, $row)) {
                continue;
            } else {
                throw new \Exception(sprintf('Failed rendering data for column "%s"', $index));
            }
        }

        $record = array();

        foreach ($excludeColumns as $excludeColumn) {
            $rowColumns[$excludeColumn] = null;
        }

        $record['cell'] = array_values($rowColumns);

        if (null !== $this->_identifierColumn) {
            $record['id'] = $this->_identifierColumn['data']($row);
        }

        $this->_data['rows'][] = $record;
    }

    /**
     * Escapes a string if the auto escape closure is defined
     *
     * @param  string $string
     * @param  string $columnName
     * @return string
     */
    protected function _escape($string, $columName = null)
    {
        if (null !== $this->_autoEscapeClosure) {
            if (null === $columName || !in_array($columName, $this->_excludedColumnsFromEscaping)) {
                $autoEscapeClosure = $this->_autoEscapeClosure;
                $string = $autoEscapeClosure($string);
            }
        }

        return $string;
    }
}
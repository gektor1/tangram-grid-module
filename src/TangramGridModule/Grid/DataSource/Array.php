<?php

/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Data source for arrays. You can use this data source with Grid which will both create all
 * neccasary javascript and JSON for drawing a grid with jqGrid.
 *
 * Dependecies: jqGrid, Zend Framework
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Grid_DataSource_Array extends Grid_DataSource_Abstract implements Grid_DataSource_Interface {

    /**
     * @var array
     */
    protected $_source;

    /**
     * Constructor
     *
     * @param mixed $source
     */
    public function __construct($source) {
        parent::__construct();

        if (!is_array($source)) {
            throw new Exception('The specified source must be an array');
        } else {
            $this->_source = $source;
        }

        $this->_setColumns();
    }

    /**
     * Analyses which fields are used. This is passed through the datagrid for displaying fieldnames.
     *
     * @return array
     */
    private function _setColumns() {
        $this->columns = new Grid_DataSource_Columns();

        foreach ($this->_source as $row) {
            foreach ($row as $columnName => $values) {
                $this->columns->add($columnName, null, $columnName);
            }
            break;
        }
    }

    /**
     * Defines what happends when the grid is sorted by the server. Must return a array
     * with query hints!
     *
     * @return array
     */
    public function setEventSort(Closure $function) {
        $this->_onOrder = $function;
    }

    /**
     * Defines what happends when the user filters data with jqGrid and send to the server. Must
     * return an array with query hints!
     *
     * @return array
     */
    public function setEventFilter(Closure $function) {
        $this->_onFilter = $function;
    }

    /**
     * No sorting by default for this data source
     *
     * @return array
     */
    public function getDefaultSorting() {
        return null;
    }

    /**
     * Returns a JSON string useable for JQuery Grid. This grids interprets this
     * data and is able to draw a grid.
     *
     * @param boolean $encode         If data must be JSON encoded. If false, you must encode the returned
     *                                data yourself through a JSON encode function.
     * @param array   $excludeColumns If you have columns in your query with the only purpose of
     *                                suplementing data for the construction of other columns and
     *                                you also probably set the column as hidden and you don't need
     *                                their value on the client side for manipulation, then you can
     *                                exclude the columns here to gain some performance.
     * @return string JSON data
     */
    public function getJson($encode = true, array $excludeColumns = array()) {
        $offset = $this->_limitPerPage * ($this->_params['page'] - 1);
        $source = $this->_source;

        if (isset($this->_params['filters'])
                && (!isset($this->_params['_search']) || 'true' == $this->_params['_search'])
        ) {
            $filters = json_decode($this->_params['filters'], true);
            $source = $this->_filter($source, $filters);
        }

        if (isset($this->_params['sord'])) {
            $source = $this->_sort($source);
        }

        $count = count($source);

        $this->_data = array();
        $this->_data['page'] = (int) $this->_params['page'];
        $this->_data['total'] = ceil($count / $this->_limitPerPage);
        $this->_data['records'] = $count;
        $this->_data['rows'] = array();

        foreach (array_slice($source, $offset, $this->_limitPerPage) as $row) {
            $this->_renderRow($row, $excludeColumns);
        }

        return $encode === true ? json_encode($this->_data) : $this->_data;
    }

    /**
     * Filters the data by the specified filter(s)
     *
     * @param  array  $source
     * @param  array  $filters
     * @param  string $filterType (start or contains)
     * @return array
     */
    protected function _filter(array $source, array $filters, $filterType = 'start') {
        $rules = $filters['rules'];
        $groupOp = $filters['groupOp'];
        $filterType = 'contains' == $filterType ? 'contains' : 'start';

        $columns = array();
        foreach ($rules as $rule) {
            $columns[] = array('field' => $rule['field'], 'data' => $rule['data']);
        }

        $results = array();

        foreach ($columns as $i => $column) {
            foreach ($source as $key => $value) {
                $strpos = stripos($value[$column['field']], $column['data']);
                if (($filterType == 'start' && $strpos === 0 || $filterType == 'contains' && $strpos !== false)
                        && ($groupOp != 'AND' || $i == 0)) {
                    if (!isset($results[$key])) {
                        $results[$key] = $value;
                    }
                } elseif (($filterType == 'start' && $strpos !== 0 || $filterType == 'contains' && $strpos === false)
                        && $groupOp == 'AND' && isset($results[$key])) {
                    unset($results[$key]);
                }
            }
        }

        return $results;
    }

    protected function _sort($source) {
        $sidx = $this->_params['sidx'];
        $sord = 'desc' == $this->_params['sord'] ? SORT_DESC : SORT_ASC;

        $indexArray = array();
        if ('' != $sidx) {
            foreach ($source as $key => $row) {
                $indexArray[$key] = $row[$sidx];
            }
            array_multisort($indexArray, $sord, $source);
        }

        return $source;
    }

}
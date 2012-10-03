<?php

namespace TangramGridModule\Grid\DataSource;

/**
 * This interface should be implemented by data sources for usage in Grid
 * 
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
interface DataSourceInterface {
    /*
     * Returns a JSON encoded string of the data to be send
     */

    public function getJson();

    /**
     * Returns an array indicating on which field and which order the grid is by default sorted on.
     */
    public function getDefaultSorting();

    /**
     * Sets a column name which identifies every row in the grid
     */
    public function setIdentifierColumn($column);

    /**
     * Sets the closure for auto escaping column strings in the grid result
     */
    public function setAutoEscapeClosure(\Closure $closure);

    /**
     * Sets the jqGrid posted params
     */
    public function setParameters(array $params);

    /**
     * Specifies how many data is returned per 'page'
     */
    public function setResultsPerPage($num);

    /**
     * Defines what happends when the grid is sorted by the server. Return value depends on the
     * type of data source.
     *
     */
    public function setEventSort(\Closure $function);

    /**
     * Defines what happends when the user filters data with jqGrid and send to the server. Return
     * value depends on the type of data source
     */
    public function setEventFilter(\Closure $function);
}
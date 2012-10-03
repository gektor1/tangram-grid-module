<?php

namespace TangramGridModule\Grid\DataSource\Doctrine;

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class CountWalker extends TreeWalkerAdapter {

    /**
     * @var SelectStatement
     */
    protected $_AST;

    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve a COUNT
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST) {
        $this->_AST = $AST;
        $this->_AST->selectClause->selectExpressions = array();
        $this->_addCountComponent();

        // ORDER BY is not needed, only increases query execution through unnecessary sorting.
        $this->_AST->orderByClause = null;
    }

    /**
     * Adds the count(field) component to the query
     */
    protected function _addCountComponent() {
        $parent = null;
        $parentName = null;

        /**
         * Find the identifier field of the root entity (at the FROM component)
         */
        foreach ($this->_getQueryComponents() AS $dqlAlias => $qComp) {

            // skip mixed data in query
            if (isset($qComp['resultVariable'])) {
                continue;
            }

            if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0) {
                $parent = $qComp;
                $parentName = $dqlAlias;
                break;
            }
        }

        /**
         * Add a aggegrate expression (COUNT) with the identifier field that was found to
         * count total amount of results.
         */
        $pathExpression = new PathExpression(
                        PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $parentName,
                        $parent['metadata']->getSingleIdentifierFieldName()
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $this->_AST->selectClause->selectExpressions[] = new SelectExpression(
                        new AggregateExpression('count', $pathExpression, true), null);
    }

}
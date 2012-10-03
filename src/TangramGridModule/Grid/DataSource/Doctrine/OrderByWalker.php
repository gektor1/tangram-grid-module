<?php

namespace TangramGridModule\Grid\DataSource\Doctrine;

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\OrderByItem,
    Doctrine\ORM\Query\AST\OrderByClause,
    Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * This class walks a selectstatement. It will cause a orderby to be replaced
 * with the field the user wants to sort on.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class OrderByWalker extends TreeWalkerAdapter {

    /**
     * Walks down a SelectStatement AST node, modify the orderby clause if the user
     * wants to sort his results.
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST) {
        $sidx = $this->_getQuery()->getHint('sidx');
        $sord = $this->_getQuery()->getHint('sord');

        if (strpos($sidx, '.') !== false) {
            $parts = explode('.', $sidx);
            $sidx = $parts[1];
            $alias = $parts[0];
        } else {
            $alias = null;
        }

        $pathExpression = new PathExpression(
                        PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                        $alias,
                        $sidx
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $orderByItem = new orderByItem($pathExpression);
        $orderByItem->type = $sord;

        $orderByItems = array($orderByItem);

        /**
         * Remove all other orderby items and add Grid orderfield.
         */
        if (null === $AST->orderByClause) {
            $AST->orderByClause = new OrderByClause($orderByItems);
        } else {
            $AST->orderByClause->orderByItems = $orderByItems;
        }
    }

}
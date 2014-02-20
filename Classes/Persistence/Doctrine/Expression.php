<?php

namespace TYPO3\DoctrineDbal\Persistence\Doctrine;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryException;
use TYPO3\DoctrineDbal\Persistence\Database\ExpressionInterface;

/**
 * Class Expression
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class Expression implements ExpressionInterface {

	/**
	 * The database connection via Doctrine
	 *
	 * @var \Doctrine\DBAL\Connection $connection
	 */
	private $connection;

	/**
	 * The platform abstraction
	 *
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	private $platform;

	/**
	 * @var \Doctrine\DBAL\Query\Expression\ExpressionBuilder
	 */
	private $expr;

	/**
	 * The constructor
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->expr = $this->connection->createQueryBuilder()->expr();
		$this->platform = $this->connection->getDatabasePlatform();
	}

	/**
	 * Returns a logical AND constraint by combining the given parameters together
	 *
	 * The method takes one or more constraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE (pid = 4) AND (pid = 5)<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where(<br>
	 *                        $expr->logicalAnd(<br>
	 *                            $expr->equals('pid', 4),<br>
	 *                            $expr->equals('uid', 5)<br>
	 *                        )<br>
	 *                    );<br>
	 * </code><br>
	 *
	 * @param mixed $constraint
	 *
	 * @return string
	 * @api
	 */
	public function logicalAnd($constraint) {
		if (is_array($constraint)) {
			$constraints = $constraint;
		} else {
			$constraints = func_get_args();
		}

		return call_user_func_array(array($this->expr, 'andX'), $constraints);
	}

	/**
	 * Returns a logical OR constraint by combining the given parameters
	 *
	 * The method takes one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE (pid = 4) OR (pid = 5)<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->logicalOr(<br>
	 *                            $expr->equals('pid', 4),<br>
	 *                            $expr->equals('pid', 5)<br>
	 *                        )<br>
	 *                    ');<br>
	 * </code><br>
	 *
	 * @param $constraint
	 *
	 * @return string
	 * @api
	 */
	public function logicalOr($constraint) {
		if (is_array($constraint)) {
			$constraints = $constraint;
		} else {
			$constraints = func_get_args();
		}

		return call_user_func_array(array($this->expr, 'orX'), $constraints);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * Example:
	 * <code>
	 * // SELECT * FROM pages WHERE NOT pid = 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->logicalNot(<br>
	 *                            $expr->equals('pid', 4),<br>
	 *                        )<br>
	 *                    ');<br>
	 * </code>
	 *
	 * @return string
	 * @api
	 */
	public function logicalNot() {
		// TODO: Implement logicalNot() method.
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * Example:
	 * <code>
	 *
	 * </code>
	 *
	 * @param $constraint
	 *
	 * @return string
	 * @api
	 */
	public function not($constraint) {
		return 'NOT (' . $constraint . ')';
	}

	/**
	 * Returns a "=" expression.
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid = 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->equals('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function equals($x, $y) {
		return $this->expr->eq($x, $y);
	}

	/**
	 * Returns a "<>" expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid <> 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->neq('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function notEquals($x, $y) {
		return $this->expr->neq($x, $y);
	}

	/**
	 * Returns a "<" expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid < 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->lessThan('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function lessThan($x, $y) {
		return $this->expr->lt($x, $y);
	}

	/**
	 * Returns a "<=" expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid <= 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->lessThanOrEquals('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function lessThanOrEquals($x, $y) {
		return $this->expr->lte($x, $y);
	}

	/**
	 * Returns a ">" expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid > 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->greaterThan('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function greaterThan($x, $y) {
		return $this->expr->gt($x, $y);
	}

	/**
	 * Returns a ">=" expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid >= 4<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->greaterThanOrEquals('pid', 4)
	 *                    );<br>
	 * </code>
	 *
	 * @param $x
	 * @param $y
	 *
	 * @return string
	 * @api
	 */
	public function greaterThanOrEquals($x, $y) {
		return $this->expr->gte($x, $y);
	}

	/**
	 * Returns a LIKE expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE title LIKE "News%"<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->like('title', 'News%')
	 *                    );<br>
	 * </code>
	 *
	 * @param $column
	 * @param $pattern
	 *
	 * @return string
	 * @api
	 */
	public function like($column, $pattern) {
		return $this->expr->like($column, $pattern);
	}

	/**
	 * Returns an IN expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid IN (0, 8, 15)<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->in('pid', array(0, 8, 15))
	 *                    );<br>
	 * </code>
	 *
	 * @param string $column
	 * @param array  $values
	 *
	 * @return string
	 * @api
	 */
	public function in($column, $values) {
		return $this->expr->in($column, $values);
	}

	/**
	 * Returns a NOT IN expression
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid NOT IN (0, 8, 15)<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->notIn('pid', array(0, 8, 15))
	 *                    );<br>
	 * </code>
	 *
	 * @param string $column
	 * @param array  $values
	 *
	 * @return string
	 * @api
	 */
	public function notIn($column, $values) {
		return $this->expr->notIn($column, $values);
	}

	/**
	 * Utilizes the database LOWER function to lowercase the given string
	 *
	 * Example:
	 * <code><br>
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->lower($value)
	 *                    );<br>
	 * </code>
	 *
	 * @param string $value
	 *
	 * @return string
	 * @api
	 */
	public function lower($value) {
		return $this->platform->getLowerExpression($value);
	}

	/**
	 * Utilizes the database UPPER function to uppercase the given string
	 *
	 * Example:
	 * <code><br>
	 * $query = $GLOBALS['TYPO3_DB']->getSelectQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->select('*')->from('pages')<br>
	 *                    ->where('<br>
	 *                        $expr->upper($value)
	 *                    );<br>
	 * </code>
	 *
	 * @param string $value
	 *
	 * @return string
	 * @api
	 */
	public function upper($value) {
		return $this->platform->getUpperExpression($value);
	}

	/**
	 * Returns a WHERE clause that can find a value ($value) in a list field ($field)
	 * For instance a record in the database might contain a list of numbers,
	 * "34,234,5" (with no spaces between). This query would be able to select that
	 * record based on the value "34", "234" or "5" regardless of their position in
	 * the list (left, middle or right).
	 * The value must not contain a comma (,)
	 * Is nice to look up list-relations to records or files in TYPO3 database tables.
	 *
	 * @param string $field Field name
	 * @param string $value Value to find in list
	 *
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @return string WHERE clause for a query
	 */
	public function findInSet($field, $value) {
		$value = (string)$value;

		if (strpos($value, ',') !== FALSE) {
			throw new QueryException('$value must not contain a comma (,) in $this->findInSet($field, $value) !', 1392849386);
		}

		return 'FIND_IN_SET(' . $GLOBALS['TYPO3_DB']->quote($value) . ',' . $GLOBALS['TYPO3_DB']->quote($field) . ')';
	}
}
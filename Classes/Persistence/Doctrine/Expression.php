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
	private $connection = NULL;

	/**
	 * The platform abstraction
	 *
	 * @var \Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	private $platform = NULL;

	/**
	 * The constructor
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
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
	 * // SELECT * FROM pages WHERE pid = 4 AND pid = 5<br><br>
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
	 * @return string
	 * @api
	 */
	public function logicalAnd() {
		$constraints = func_get_args();

		return $this->combine($constraints, ' AND ');
	}

	/**
	 * Returns a logical OR constraint by combining the given parameters
	 *
	 * The method takes one or more constraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * Example:
	 * <code><br>
	 * // SELECT * FROM pages WHERE pid = 4 OR pid = 5<br><br>
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
	 * @return string
	 * @api
	 */
	public function logicalOr() {
		$constraints = func_get_args();

		return $this->combine($constraints, ' OR ');
	}

	/**
	 * Combine an array of expressions by OR/AND.
	 *
	 * @param array  $constraints
	 * @param string $by
	 *
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @return string
	 */
	private function combine(array $constraints, $by) {
		$constraints = $this->flatten($constraints);

		$constraintCount = count($constraints);

		if ($constraintCount < 1) {
			throw new QueryException(
				'The "' . $by . '" expression expect at least 1 argument but none given.'
			);
		}

		if ($constraintCount === 1) {
			return $constraints[0];
		}

		return '(' . join(')' . $by . '(', $constraints) . ')';
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
		return $x . ' = ' . $y;
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
		return $x . ' <> ' . $y;
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
		return $x . ' < ' . $y;
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
		return $x . ' <= ' . $y;
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
		return $x . ' > ' . $y;
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
		return $x . ' >= ' . $y;
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
		return $column . ' LIKE ' . $pattern;
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
		return $this->connection->createQueryBuilder()->expr()->in($column, $values);
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
		return $this->connection->createQueryBuilder()->expr()->notIn($column, $values);
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
	 * Flattens a multi-dimensional array into a single one
	 *
	 * @param array $array The array to flatten
	 *
	 * @return array
	 */
	private function flatten(array $array) {
		$result = array();

		array_walk_recursive($array, function($a) use (&$result) {$result[] = $a; });

		return $result;
	}
}
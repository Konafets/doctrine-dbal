<?php
namespace TYPO3\DoctrineDbal\Persistence\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Stefano Kowalke <blueduck@gmx.net>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Interface SelectQueryInterface
 *
 * @package TYPO3\DoctrineDbal\Persistence\Database
 */
interface SelectQueryInterface extends QueryInterface {
	/**
	 * Constants for ascending ordering
	 */
	const ASC = 'ASC';

	/**
	 * Constant for descending ordering
	 */
	const DESC = 'DESC';

	/**
	 * Creates an alias for $name
	 *
	 * @param string $name
	 * @param string $alias
	 *
	 * @return string
	 */
	public function alias($name, $alias);

	/**
	 * Starts a SELECT query
	 *
	 * Example:
	 *
	 * Get the query with:
	 * <code>
	 * $query = $this->db->createSelectQuery();
	 * </code>
	 *
	 * <code>
	 * $query->select(*);
	 * </code>
	 *
	 * <code>
	 * $query->select('column1', 'column2');
	 * </code>
	 *
	 * <code>
	 * $columns[] = 'column1';
	 * $columns[] = 'column2';
	 * $query->select($columns);
	 * </code>
	 *
	 * <code>
	 * $query->select('column1')->select('column2');
	 * </code>
	 *
	 * The result will be: SELECT column1, column2
	 * 
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function select();

	/**
	 * Starts a SELECT DISTINCT query
	 *
	 * Example:
	 * <code>
	 * $query->selectDistinct('column1')->select('column2');
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function selectDistinct();

	/**
	 * Set the table to select from
	 *
	 * Example:
	 * <code>
	 * // SELECT uid FROM tt_content
	 * $query->select('uid')->from('tt_content');
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function from();

	/**
	 * Set the where constraint
	 *
	 * Example:
	 * <code>
	 * // SELECT * FROM tt_content WHERE uid < 5
	 * $query->select('*')->from('tt_content')->where($query->expr->equals('uid', 5));
	 *
	 * // SELECT * FROM tt_content WHERE (uid < 5) AND (pid = 10)
	 * $query->select('*')->from('tt_content')->where(
	 *     $query->logicalAnd(
	 *         $query->expr->lessThan('uid', 5),
	 *         $query->expr->equals('pid', 10)
	 *     )
	 * );
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function where();

	/**
	 * Set the GROUB BY clause
	 *
	 * Example:
	 * <code>
	 * // SELECT uid FROM tt_content GROUP BY tstamp ASC
	 * $query->select('uid')->from('tt_content')->groupBy('tstamp');
	 *
	 * // SELECT uid FROM tt_content GROUP BY tstamp DESC
	 * $query->select('uid')->from('tt_content')->groupBy('tstamp', 'DESC');
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function groupBy();

	/**
	 * Creates a HAVING clause
	 *
	 * Example:
	 * <code>
	 * $q->select( '*' )->from( 'table' )->groupBy( 'id' )
	 *                  ->having( $q->expr->eq('id',1) );
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function having();

	/**
	 * Creates an ORDER BY statement
	 *
	 * @param string $column
	 * @param string $type
	 *
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @return mixed
	 */
	public function orderBy($column, $type = self::ASC);

	/**
	 * Set the limit and the offset
	 *
	 * Example:
	 * <code>
	 * // SELECT uid FROM tt_content LIMIT 4
	 * $query->select('uid')->from('tt_content')->limit(4);
	 *
	 * // SELECT uid FROM tt_content LIMIT 3, 8
	 * $query->select('uid')->from('tt_content')->limit(8, 3);
	 * </code>
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function limit($limit, $offset = 0);
}
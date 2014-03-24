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

use TYPO3\DoctrineDbal\Persistence\Database\SelectQueryInterface;
use TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException;
use TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException;

class SelectQuery extends AbstractQuery implements SelectQueryInterface {
	/**
	 * The SELECT query parts
	 *
	 * @var array $parts
	 */
	private $parts = array(
		'select'  => array(),
		'from'    => array(),
		'where'   => array(),
		'groupBy' => array(),
		'having'  => array(),
		'orderBy' => array(),
	);

	/**
	 * Flag for SELECT DISTINCT queries
	 *
	 * @var bool $isDistinct
	 */
	private $isDistinct = FALSE;

	/**
	 * The limit
	 *
	 * @var int $limit
	 */
	private $limit;

	/**
	 * The offset
	 *
	 * @var int $offset
	 */
	private $offset;

	/**
	 * Returns the type of the query
	 *
	 * @return int
	 */
	public function getType() {
		return self::SELECT;
	}

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
	public function select() {
		$columns = $this->validateConstraints(func_get_args());

		foreach ($columns as $column) {
			$this->parts['select'][] = $column;
		}

		return $this;
	}

	/**
	 * Creates an alias for $name
	 *
	 * @param string $name
	 * @param string $alias
	 *
	 * @return string
	 */
	public function alias($name, $alias) {
		return $name . ' AS ' . $alias;
	}

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
	public function selectDistinct() {
		$this->isDistinct = TRUE;

		return call_user_func_array(array($this, 'select'), func_get_args());
	}

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
	public function from() {
		$tables = $this->validateConstraints(func_get_args());

		foreach ($tables as $tableName) {
			$this->parts['from'][] = array(
					'table' => $tableName,
					'type' => 'FROM'
			);
		}

		return $this;
	}

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
	 *         $query->expr->lessThan('uid', 5),
	 *         $query->expr->equals('pid', 10)
	 * );
	 * </code>
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function where() {
		$whereConstraints = $this->validateConstraints(func_get_args());

		if (count($whereConstraints) === 0) {
			throw new InvalidArgumentException('No where constraints given in SELECT query.');
		}

		foreach ($whereConstraints as $whereCondition) {
			if (!empty($whereCondition)) {
				$this->parts['where'][] = $whereCondition;
			}
		}

		return $this;
	}


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
	public function groupBy() {
		$arguments = $this->validateConstraints(func_get_args());

		foreach ($arguments as $groupByExpression) {
			$this->parts['groupBy'][] = $groupByExpression;
		}

		return $this;
	}

	/**
	 * Creates a HAVING clause
	 *
	 * Example:
	 * <code>
	 * $q->select( '*' )->from( 'table' )->groupBy( 'id' )
	 *                  ->having( $q->expr->eq('id',1) );
	 * </code>
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function having() {
		$arguments = func_get_args();

		if (count($arguments) === 0) {
			throw new InvalidArgumentException('No arguments for having given in SELECT query!');
		}

		foreach ($arguments as $havingExpression) {
			$this->parts['having'][] = $havingExpression;
		}

		return $this;
	}



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
	public function limit($limit, $offset = 0) {
		$this->limit = $limit;
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Creates an ORDER BY statement
	 *
	 * @param string $column
	 * @param string $type
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidArgumentException
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\SelectQuery
	 */
	public function orderBy($column, $type = self::ASC) {
		if ($type !== self::ASC && $type !== self::DESC) {
			throw new InvalidArgumentException('Invalid value for ordering the direction. Expects ASC or DESC; but got \'' . $type );
		}

		$this->parts['orderBy'][] = $column . ' ' . $type;

		return $this;
	}

	/**
	 * Returns the sql statement of this query
	 *
	 * @throws \TYPO3\DoctrineDbal\Persistence\Exception\InvalidQueryException
	 * @return string
	 */
	public function getSql() {
		if (count($this->parts['select']) === 0) {
			throw new InvalidQueryException('Missing SELECT parts to generate the SELECT query.');
		}

		$sql = 'SELECT ';

		if ($this->isDistinct) {
			$sql .= 'DISTINCT ';
		}

		$sql .= join(', ', $this->parts['select']) . ' FROM';

		if (count($this->parts['from']) === 0) {
			throw new InvalidQueryException('Missing FROM parts to generate the SELECT query.');
		}

		$renderedFromBefore = FALSE;

		foreach ($this->parts['from'] as $fromPart) {
			if ($fromPart['type'] === 'FROM') {
				if ($renderedFromBefore) {
					$sql .= ',';
				}

				$sql .= ' ' . $fromPart['table'];
				$renderedFromBefore = TRUE;
			} else {
				$sql .= ' ' . $fromPart['type'] . ' JOIN ' . $fromPart['table'];

				if ($fromPart['condition']) {
					$sql .= ' ON ' . $fromPart['condition'];
				}
			}

			if (count($this->parts['where']) > 0) {
				$sql .= ' WHERE ' . join(' AND ', $this->parts['where']);
			}

			if (count($this->parts['groupBy']) > 0) {
				$sql .= ' GROUP BY ' . join(', ', $this->parts['groupBy']);
			}

			if (count($this->parts['having']) > 0) {
				$sql .= ' HAVING ' . join(' AND ', $this->parts['having']);
			}

			if (count($this->parts['orderBy']) > 0) {
				$sql .= ' ORDER BY ' . join(', ', $this->parts['orderBy']);
			}

			if ($this->limit || $this->offset) {
				$sql = $this->connection->getDatabasePlatform()->modifyLimitQuery(
					$sql,
					$this->limit,
					$this->offset
				);
			}
		}

		return $sql;
	}
}


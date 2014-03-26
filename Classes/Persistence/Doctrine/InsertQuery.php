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

use Doctrine\DBAL\Query\QueryException;
use TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface;

/**
 * Class InsertQuery
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class InsertQuery extends AbstractQuery implements InsertQueryInterface{
	/**
	 * The table to insert in
	 *
	 * @var string $table
	 */
	private $table = '';

	/**
	 * The values to insert
	 * In key/value format
	 * array('column' => 'value')
	 *
	 * @var array
	 */
	private $values = array();

	/**
	 * Returns the type of the query
	 *
	 * @return int
	 */
	public function getType() {
		return self::INSERT;
	}

	/**
	 * Set the table name
	 *
	 * @param string $table
	 *
	 * @return InsertQueryInterface
	 */
	public function insertInto($table) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Set the columns
	 *
	 * The method take a string or arrays. Your are responsible for quoting by yourself if you
	 * not using Prepared Statements. In most cases it is sufficient to use $GLOBALS['TYPO3_DB']->executeInsertQuery() instead of
	 * this method. Use this in case you want to return the created SQL string.
	 *
	 * Example:
	 * <code><br>
	 * // No Prepared Statements; self quoted<br>
	 * $query = $GLOBALS['TYPO3_DB']->getInsertQuery();<br>
	 * $query->insertInto('pages')<br>
	 *       ->set('`column1`', '\'Foo\'')<br>
	 *       ->set('`column2`', '\'Bar\'');<br><br>
	 *
	 * // No Prepared Statements; quoted with methods from $GLOBALS['TYPO3_DB']<br>
	 * $query = $GLOBALS['TYPO3_DB']->getInsertQuery();<br>
	 * $query->insertInto('pages')<br>
	 *       ->set($GLOBALS['TYPO3_DB']->quoteColumn('column1'), $GLOBALS['TYPO3_DB']->quote('Foo'))<br>
	 *       ->set($GLOBALS['TYPO3_DB']->quoteColumn('column2'), $GLOBALS['TYPO3_DB']->quote('Bar'));<br><br>
	 *
	 * // No Prepared Statements; passing arrays; quoted with methods from $GLOBALS['TYPO3_DB']<br>
	 * $columns = array(<br>
	 *            $GLOBALS['TYPO3_DB']->quoteColumn('column1'),<br>
	 *            $GLOBALS['TYPO3_DB']->quoteColumn('column2')<br>
	 *           );<br>
	 * $values = array(<br>
	 *            $GLOBALS['TYPO3_DB']->quote('Foo'),<br>
	 *            $GLOBALS['TYPO3_DB']->quote('Bar'),<br>
	 *           );<br>
	 * $query = $GLOBALS['TYPO3_DB']->getInsertQuery();<br>
	 * $query->insertInto('pages')->set($columns, $values);<br>
	 * </code>
	 *
	 *
	 * @param mixed $column
	 * @param mixed $value
	 *
	 * @return InsertQueryInterface
	 */
	public function set($column, $value) {
		if (is_array($column) && is_array($value)) {
			for ($i = 0; $i < count($column); ++$i) {
				$this->values[$column[$i]] = $value[$i];
			}
		} else {
			$this->values[$column] = $value;
		}

		return $this;
	}

	/**
	 * Returns the sql statement of this query
	 *
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @return string
	 */
	public function getSql() {
		if ($this->table === '' || is_numeric($this->table)) {
			throw new QueryException('No table name found in INSERT statement.');
		}

		if (count($this->values) === 0) {
			throw new QueryException('No values found in INSERT statement.');
		}

		$columns = join(', ', array_keys($this->values));
		$values = join(', ', $this->values);

		return 'INSERT INTO ' . $this->table .
			' (' . $columns . ')' .
			' VALUES (' . $values . ')';
	}
}


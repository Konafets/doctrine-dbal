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
use TYPO3\DoctrineDbal\Persistence\Database\DeleteQueryInterface;

/**
 * Class DeleteQuery
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class DeleteQuery extends AbstractQuery implements DeleteQueryInterface {
	/**
	 * Returns the type of the query
	 *
	 * @return int
	 */
	public function getType() {
		return self::DELETE;
	}

	/**
	 * The table to delete from
	 *
	 * @var string $table
	 */
	private $table = '';

	/**
	 * The where clauses
	 *
	 * @var array $where
	 */
	private $where = array();

	/**
	 * Set the table
	 *
	 * Example:
	 * <code><br>
	 * // DELETE FROM pages<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->createDeleteQuery();<br>
	 * $query->delete('pages');<br>
	 * </code>
	 *
	 * @param string $table
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 * @api
	 */
	public function delete($table) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Set the where clauses
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 * @api
	 */
	public function where() {
		$constraints = $this->validateConstraints(func_get_args());

		foreach ($constraints as $constraint) {
			$this->where[] = $constraint;
		}

		return $this;
	}

	/**
	 * Returns the SQL statement of this query as a string
	 *
	 * @return string
	 * @throws \Doctrine\DBAL\Query\QueryException
	 * @api
	 */
	public function getSql() {
		if ($this->table === '' || is_numeric($this->table)) {
			throw new QueryException('No table name found in DELETE statement.');
		}

		$where = count($this->where) ? ' WHERE ' . join(' AND ', $this->where) . '' : '';

		return 'DELETE FROM ' . $this->table . $where;
	}
}
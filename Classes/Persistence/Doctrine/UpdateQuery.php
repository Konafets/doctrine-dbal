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
use TYPO3\DoctrineDbal\Persistence\Database\UpdateQueryInterface;

/**
 * Class UpdateQuery
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class UpdateQuery extends AbstractQuery implements UpdateQueryInterface {
	/**
	 * Returns the type of the query
	 *
	 * @return int
	 */
	public function getType() {
		return self::UPDATE;
	}

	/**
	 * The table to update
	 *
	 * @var string $table
	 */
	private $table = '';

	/**
	 * The where constraints
	 *
	 * @var array $where
	 */
	private $where = array();

	/**
	 * The values for update
	 *
	 * @var array $values
	 */
	private $values = array();

	/**
	 * Set the table to update
	 *
	 * @param string $table
	 *
	 * @return UpdateQuery|UpdateQueryInterface
	 */
	public function update($table) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Set the columns and values to update
	 *
	 * @param string     $column
	 * @param string|int $value
	 *
	 * @return UpdateQuery|UpdateQueryInterface
	 */
	public function set($column, $value) {
		if (is_array($column) && is_array($value)) {
			for ($i = 0; $i < count ($column); ++$i) {
				$this->values[$column[$i]] = $value[$i];
			}
		} else {
			$this->values[$column] = $value;
		}

		return $this;
	}

	/**
	 * Set the where constraint
	 *
	 * @return UpdateQuery|UpdateQueryInterface
	 */
	public function where() {
		$arguments = $this->validateConstraints(func_get_args());

		foreach ($arguments as $constraint) {
			if ($constraint !== '') {
				$this->where[] = $constraint;
			}
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
			throw new QueryException('No table name found in UPDATE statement.');
		}

		if (count($this->values) === 0) {
			throw new QueryException('No values found in UPDATE statement.');
		}

		$set = array();

		foreach($this->values as $column => $value) {
			$set[] = $column . ' = ' . $value;
		}

		$set = join(', ', $set);
		// WHERE clause is optional. Without a WHERE clause ALL entries will be updated.
		$where = count($this->where) ? ' WHERE ' . join(' AND ', $this->where) . '' : '';

		return 'UPDATE ' . $this->table .
				' SET ' . $set .
				$where;
	}
}


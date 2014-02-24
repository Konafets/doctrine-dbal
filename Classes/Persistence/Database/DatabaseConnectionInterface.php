<?php

namespace TYPO3\DoctrineDbal\Persistence\Database;

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

/**
 * Interface DatabaseConnectionInterface
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Database
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
interface DatabaseConnectionInterface {
	/**
	 * Creates a DELETE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\DeleteQueryInterface
	 * @api
	 */
	public function createDeleteQuery();

	/**
	 * Creates a TRUNCATE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\TruncateQueryInterface
	 * @api
	 */
	public function createTruncateQuery();

	/**
	 * Creates an UPDATE query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\UpdateQueryInterface
	 * @api
	 */
	public function createUpdateQuery();

	/**
	 * Creates an INSERT query object
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\InsertQueryInterface
	 * @api
	 */
	public function createInsertQuery();

	 * Returns the expressions instance
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Database\ExpressionInterface
	 * @api
	 */
	public function expr();

	/**
	 * Escaping and quoting values for SQL statements.
	 *
	 * @param string  $string    Input string
	 * @param boolean $allowNull Whether to allow NULL values
	 *
	 * @return string Output string; Wrapped in single quotes and quotes in the string (" / ') and \ will be backslashed (or otherwise based on DBAL handler)
	 * @api
	 */
	public function quote($string, $allowNull = FALSE);

	/**
	 * Returns a qualified identifier for $columnName in $tableName
	 *
	 * Example:
	 * <code><br>
	 * // if no $tablename is given it returns: `column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('column');<br><br>
	 *
	 * // if $tablename is given it returns: `pages`.`column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('column', 'pages');<br>
	 * </code>
	 *
	 * @param string $columnName
	 * @param string $tableName
	 *
	 * @return string
	 * @api
	 */
	public function quoteColumn($columnName, $tableName = NULL);

	/**
	 * Returns a qualified identifier for $tablename
	 *
	 * Example:
	 * <code><br>
	 * // returns: `pages`<br>
	 * $GLOBALS['TYPO3_DB']->quoteTable('pages');<br>
	 * </code>
	 *
	 * @param string $tableName
	 *
	 * @return string
	 * @api
	 */
	public function quoteTable($tableName);

	/**
	 * Custom quote identifer method
	 *
	 * Example:
	 * <code><br>
	 * // returns `column`<br>
	 * $GLOBALS['TYPO3_DB']->quoteIdentifier('column');<br>
	 * </code>
	 *
	 * @param string $identifier
	 *
	 * @return string
	 * @api
	 */
	public function quoteIdentifier($identifier);
}
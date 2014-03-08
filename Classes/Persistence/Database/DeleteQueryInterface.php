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
 * Interface DeleteQueryInterface
 *
 * This code is heavily inspired by the database integration of ezPublish
 * from Benjamin Eberlei.
 *
 * @package TYPO3\DoctrineDbal\Persistence\Database
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
interface DeleteQueryInterface extends QueryInterface {
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
	public function delete($table);

	/**
	 * Set the where clauses
	 *
	 * Example:
	 * <code><br>
	 * // DELETE FROM pages WHERE pid = 42<br><br>
	 *
	 * $query = $GLOBALS['TYPO3_DB']->createDeleteQuery();<br>
	 * $expr = $query->expr;<br>
	 * $query->delete('pages')->where(expr->equals('pid', 42);<br>
	 * </code>
	 *
	 * @return \TYPO3\DoctrineDbal\Persistence\Doctrine\DeleteQuery
	 * @api
	 */
	public function where();
}
?> 
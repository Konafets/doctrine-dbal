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
	 */
	public function createDeleteQuery();
}
<?php

namespace TYPO3\DoctrineDbal\Tests\Unit\Persistence\Doctrine;

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
 * Class TruncateQueryTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class TruncateQueryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\TruncateQuery
	 */
	private $subject = NULL;

	/**
	 * @var string
	 */
	protected $testTable;

	/**
	 * @var string
	 */
	protected $testField;

	/**
	 * @var string
	 */
	protected $testFieldSecond;

	/**
	 * Set the test up
	 *
	 * @return void
	 */
	public function setUp() {
		$GLOBALS['TYPO3_DB']->connectDatabase();
		$this->subject = $GLOBALS['TYPO3_DB']->createTruncateQuery();
		$this->testTable = 'test_t3lib_dbtest';
		$this->testField = 'fieldblob';
		$this->testFieldSecond = 'fieldblub';
		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . $this->testTable . ' (
			id int(11) unsigned NOT NULL auto_increment,' .
			$this->testField . ' mediumblob,' .
			$this->testFieldSecond . ' int,
			PRIMARY KEY (id)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	/**
	 * Tear the test down
	 *
	 * @return void
	 */
	public function tearDown() {
		$GLOBALS['TYPO3_DB']->sql_query('DROP TABLE ' . $this->testTable . ';');
		$GLOBALS['TYPO3_DB']->getDatabaseHandle()->close();
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function truncateTable() {
		$this->subject->truncate($this->testTable);
		$expectedSql = 'TRUNCATE TABLE ' . $this->testTable;
		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function truncateWithoutTableThrowsException(){
		$truncate = $this->subject->truncate('');
		$truncate->getSql();
	}


	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function truncateWithIntegerGivenAsTableThrowsException() {
		$query = $this->subject->truncate(0);
		$query->getSql();
	}

	/**
	 * @test
	 */
	public function executeDeleteReturnsAffectedRows() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'test'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'foo'));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'bar'));
		$this->subject->truncate($this->testTable);
		$expectedSql = 'TRUNCATE TABLE ' . $this->testTable;
		$this->assertSame($expectedSql, $this->subject->getSql());
		$this->assertSame(0, $this->subject->execute());
	}
}
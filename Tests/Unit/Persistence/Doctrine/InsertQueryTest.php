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
 * Class InsertQueryTest
 * 
 * @package TYPO3\DoctrineDbal\Persistence\Doctrine
 * @author  Stefano Kowalke <blueduck@gmx.net>
 */
class InsertQueryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\InsertQuery
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
		$GLOBALS['TYPO3_DB']->connectDB();
		$this->subject = $GLOBALS['TYPO3_DB']->createInsertQuery();
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
	public function insertTable() {
		$columns = array($this->testField, $this->testFieldSecond);
		$values = array('Foo', 'Bar');
		$this->subject->insertInto($this->testTable)->set($columns, $values);
		$expectedSql = 'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ', ' . $this->testFieldSecond . ') VALUES (Foo, Bar)';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function insertTableStringWithPreparedStatements() {
		$result = $this->subject->insertInto($this->testTable)
				->set($this->testField, $this->subject->bindValue('Foo'))
				->set($this->testFieldSecond, $this->subject->bindValue('Bar'))
				->getSql();
		$expectedSql = 'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ', ' . $this->testFieldSecond . ') VALUES (:placeholder1, :placeholder2)';

		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function insertTableArrayWithPreparedStatements() {
		$columns = array($this->testField, $this->testFieldSecond);
		$values = array($this->subject->bindValue('Foo'), $this->subject->bindValue('Bar'));
		$result = $this->subject->insertInto($this->testTable)->set($columns, $values)->getSql();

		$expectedSql = 'INSERT INTO ' . $this->testTable . ' (' . $this->testField . ', ' . $this->testFieldSecond . ') VALUES (:placeholder1, :placeholder2)';
		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function insertTableArrayWithCustomQuoting() {
		$columns = array($GLOBALS['TYPO3_DB']->quoteColumn($this->testField), $GLOBALS['TYPO3_DB']->quoteColumn($this->testFieldSecond));
		$values = array($GLOBALS['TYPO3_DB']->quote('Foo'), $GLOBALS['TYPO3_DB']->quote('Bar'));
		$result = $this->subject->insertInto($this->testTable)->set($columns, $values)->getSql();

		$expectedSql = 'INSERT INTO ' . $this->testTable . ' (`' . $this->testField . '`, `' . $this->testFieldSecond . '`) VALUES (\'Foo\', \'Bar\')';
		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function insertTableExecuteStringWithPreparedStatements() {
		$result = $this->subject->insertInto($this->testTable)
				->set($this->testField, $this->subject->bindValue('Foo'))
				->set($this->testFieldSecond, $this->subject->bindValue('Bar'))
				->execute();

		$this->assertSame(1, $result);
	}

	/**
	 * @test
	 */
	public function insertTableExecuteArrayWithPreparedStatements() {
		$columns = array($this->testField, $this->testFieldSecond);
		$values = array($this->subject->bindValue('Foo'), $this->subject->bindValue('Bar'));
		$result = $this->subject->insertInto($this->testTable)->set($columns, $values)->execute();

		$this->assertSame(1, $result);
	}

	/**
	 * @test
	 */
	public function insertTableExecuteArrayWithCustomQuoting() {
		$columns = array($GLOBALS['TYPO3_DB']->quoteColumn($this->testField), $GLOBALS['TYPO3_DB']->quoteColumn($this->testFieldSecond));
		$values = array($GLOBALS['TYPO3_DB']->quote('Foo'), $GLOBALS['TYPO3_DB']->quote('Bar'));
		$result = $this->subject->insertInto($this->testTable)->set($columns, $values)->execute();

		$this->assertSame(1, $result);
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function insertWithoutTableThrowsException() {
		$query = $this->subject->insertInto('');
		$query->getSql();
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function insertWithIntegerAsTableThrowsException() {
		$query = $this->subject->insertInto(0);
		$query->getSql();
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function insertWithoutSetThrowsException() {
		$query = $this->subject->insertInto($this->testTable);
		$query->getSql();
	}
}
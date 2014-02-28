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
class UpdateQueryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\DoctrineDbal\Persistence\Doctrine\UpdateQuery
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
		$this->subject = $GLOBALS['TYPO3_DB']->createUpdateQuery();
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
	public function updateTableWithoutWhere() {
		$this->subject->update($this->testTable)->set($this->testField, 'Test')->set($this->testFieldSecond, 3);
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = Test, ' . $this->testFieldSecond . ' = 3';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function updateTableWithEmptyWhere() {
		$this->subject->update($this->testTable)->set($this->testField, 'Test')->set($this->testFieldSecond, 3)->where('');
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = Test, ' . $this->testFieldSecond . ' = 3';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function updateTableStringWithPreparedStatements() {
		$result = $this->subject->update($this->testTable)
				->set($this->testField, $this->subject->bindValue('Foo'))
				->set($this->testFieldSecond, $this->subject->bindValue('Bar'))
				->getSql();
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = :placeholder1, ' . $this->testFieldSecond . ' = :placeholder2';

		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function updateTableArrayWithPreparedStatements() {
		$columns = array($this->testField, $this->testFieldSecond);
		$values = array($this->subject->bindValue('Foo'), $this->subject->bindValue('Bar'));
		$result = $this->subject->update($this->testTable)->set($columns, $values)->getSql();

		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = :placeholder1, ' . $this->testFieldSecond . ' = :placeholder2';
		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function updateTableArrayWithCustomQuoting() {
		$columns = array($GLOBALS['TYPO3_DB']->quoteColumn($this->testField), $GLOBALS['TYPO3_DB']->quoteColumn($this->testFieldSecond));
		$values = array($GLOBALS['TYPO3_DB']->quote('Foo'), $GLOBALS['TYPO3_DB']->quote('Bar'));
		$result = $this->subject->update($this->testTable)->set($columns, $values)->getSql();

		$expectedSql = 'UPDATE ' . $this->testTable . ' SET `' . $this->testField . '` = \'Foo\', `' . $this->testFieldSecond . '` = \'Bar\'';
		$this->assertSame($expectedSql, $result);
	}

	/**
	 * @test
	 */
	public function updateTableWithWhere() {
		$this->subject->update($this->testTable)
				->set($this->testField, 'Test')
				->set($this->testFieldSecond, 3)
				->where($this->testField . ' = 3');
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = Test, ' . $this->testFieldSecond . ' = 3 WHERE ' . $this->testField . ' = 3';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function updateTableWithMultipleWhere() {
		$this->subject->update($this->testTable)
				->set($this->testField, 'Test')
				->set($this->testFieldSecond, 3)
				->where(
						$this->testField . ' = 3',
						$this->testFieldSecond . ' = 5'
				);
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = Test, ' . $this->testFieldSecond . ' = 3 WHERE ' . $this->testField . ' = 3 AND ' . $this->testFieldSecond . ' = 5';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function updateTableWithMultipleWhereAndOneEmptyWhere() {
		$this->subject->update($this->testTable)
				->set($this->testField, 'Test')
				->set($this->testFieldSecond, 3)
				->where(
						$this->testField . ' = 3',
						$this->testFieldSecond . ' = 5',
						''
				);
		$expectedSql = 'UPDATE ' . $this->testTable . ' SET ' . $this->testField . ' = Test, ' . $this->testFieldSecond . ' = 3 WHERE ' . $this->testField . ' = 3 AND ' . $this->testFieldSecond . ' = 5';

		$this->assertSame($expectedSql, $this->subject->getSql());
	}

	/**
	 * @test
	 */
	public function updateTableExecuteStringWithPreparedStatements() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'test', $this->testFieldSecond => 1));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'foo', $this->testFieldSecond => 2));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'bar', $this->testFieldSecond => 3));
		$result = $this->subject->update($this->testTable)
				->set($this->testField, $this->subject->bindValue('xxx'))
				->set($this->testFieldSecond, $this->subject->bindValue(999))
				->execute();

		$this->assertSame(3, $result);
	}

	/**
	 * @test
	 */
	public function updateTableExecuteArrayWithPreparedStatements() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'test', $this->testFieldSecond => 1));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'foo', $this->testFieldSecond => 2));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'bar', $this->testFieldSecond => 3));
		$columns = array($this->testField, $this->testFieldSecond);
		$values = array($this->subject->bindValue('xxx'), $this->subject->bindValue(999));
		$result = $this->subject->update($this->testTable)->set($columns, $values)->execute();

		$this->assertSame(3, $result);
	}

	/**
	 * @test
	 */
	public function updateTableExecuteArrayWithCustomQuoting() {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'test', $this->testFieldSecond => 1));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'foo', $this->testFieldSecond => 2));
		$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->testTable, array($this->testField => 'bar', $this->testFieldSecond => 3));
		$columns = array($GLOBALS['TYPO3_DB']->quoteColumn($this->testField), $GLOBALS['TYPO3_DB']->quoteColumn($this->testFieldSecond));
		$values = array($GLOBALS['TYPO3_DB']->quote('xxx'), $GLOBALS['TYPO3_DB']->quote(999));
		$result = $this->subject->update($this->testTable)->set($columns, $values)->execute();

		$this->assertSame(3, $result);
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function updateWithoutTableThrowsException() {
		$query = $this->subject->update('');
		$query->getSql();
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function updateWithIntegerAsTableThrowsException() {
		$query = $this->subject->update(0);
		$query->getSql();
	}

	/**
	 * @test
	 * @expectedException \Doctrine\DBAL\Query\QueryException
	 */
	public function updateWithoutSetThrowsException() {
		$query = $this->subject->update($this->testTable);
		$query->getSql();
	}
}
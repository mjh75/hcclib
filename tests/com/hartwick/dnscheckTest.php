<?php
/* 
 * Copyright (C) 2016 Michael J. Hartwick <hartwick at hartwick.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace com\hartwick;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-03-04 at 17:05:11.
 */
class dnscheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var dnscheck
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new dnscheck;
				$this->object->setDomain("hartwick.com");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers com\hartwick\dnscheck::setDomain
     */
    public function testSetDomain()
    {
			$returned = $this->object->setDomain("hartwickservices.com");
			$this->assertEquals("hartwickservices.com", $returned);
    }

    /**
     * @covers com\hartwick\dnscheck::getDomain
     */
    public function testGetDomain()
    {
			$returned = $this->object->getDomain();
			$this->assertEquals("hartwick.com", $returned);
    }

    /**
     * @covers com\hartwick\dnscheck::checkDelegation
     */
    public function testCheckDelegation()
    {
			$returned = $this->object->checkDelegation();
			$this->assertEquals(\TRUE, $returned);
    }

		/**
     * @covers com\hartwick\dnscheck::checkDomain
     */
    public function testCheckDomain()
    {
			$this->object->checkDelegation();
			$returned = $this->object->checkDomain();
			$this->assertEquals(\TRUE, $returned);
    }

		/**
     * @covers com\hartwick\dnscheck::nagiosOutput
     */
    public function testnagiosOutput()
    {
			$this->object->checkDelegation();
			$this->object->checkDomain();
			$returned = $this->object->nagiosOutput();
			$this->assertEquals("OK: Tested 4", $returned);
    }

		
}

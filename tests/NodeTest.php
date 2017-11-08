<?php
namespace ParagonIE\Blakechain\UnitTests;

use ParagonIE\Blakechain\Node;
use PHPUnit\Framework\TestCase;

/**
 * Class NodeTest
 * @package ParagonIE\Blakechain\UnitTests
 */
class NodeTest extends TestCase
{
    public function testNode()
    {
        $first  = new Node('Testing 123');
        $second = new Node('Testing 456',  $first->getHash(true));
        $third  = new Node('Testing 789', $second->getHash(true));

        $fourth = clone $third;

        $this->assertSame(
            $third->getHash(),
            $fourth->getHash()
        );
        $fourth->setPrevHash($first->getHash(true));

        $this->assertNotSame(
            $third->getHash(),
            $fourth->getHash()
        );
    }
}

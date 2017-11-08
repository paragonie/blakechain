<?php
namespace ParagonIE\Blakechain\UnitTests;

use ParagonIE\Blakechain\{
    Blakechain,
    Node,
    Verifier
};
use PHPUnit\Framework\TestCase;

/**
 * Class VerifierTest
 * @package ParagonIE\Blakechain\UnitTests
 */
class VerifierTest extends TestCase
{
    /**
     * @covers Verifier::verifyLastHash()
     */
    public function testVerifyLastHash()
    {
        $verifier = new Verifier;
        $chainA = new Blakechain(
            new Node('abcdef'),
            new Node('abcdefg'),
            new Node('abcdefh'),
            new Node('abcde'),
            new Node('abcdefj')
        );

        $this->assertTrue(
            $verifier->verifyLastHash($chainA, $chainA->getLastHash())
        );
        $chainB = clone $chainA;
        $chainB->appendData('');

        $this->assertFalse(
            $verifier->verifyLastHash($chainB, $chainA->getLastHash())
        );
    }
}
<?php
namespace ParagonIE\Blakechain\UnitTests;

use ParagonIE\Blakechain\Blakechain;
use ParagonIE\Blakechain\Node;
use PHPUnit\Framework\TestCase;

class BlakechainTest extends TestCase
{
    public function testBlockchain()
    {
        $chainA = new Blakechain(
            new Node('abcdef'),
            new Node('abcdefg'),
            new Node('abcdefh'),
            new Node('abcde'),
            new Node('abcdefj')
        );

        $this->assertSame(
            $chainA->getLastHash(),
            $chainA->getLastNode()->getHash()
        );

        $chainB = new Blakechain(
            new Node('abcdef'),
            new Node('abcdefg'),
            new Node('abcdefh'),
            new Node('abcde'),
            new Node('abcdefj')
        );

        $this->assertSame(
            $chainA->getLastHash(),
            $chainB->getLastHash()
        );

        $chainB->appendData('');

        $this->assertNotSame(
            $chainA->getLastHash(),
            $chainB->getLastHash()
        );
        $this->assertEquals(
            json_encode($chainA->getPartialChain(0, 5)),
            json_encode($chainB->getPartialChain(0, 5))
        );
        $this->assertNotEquals(
            json_encode($chainA->getPartialChain(0, 6)),
            json_encode($chainB->getPartialChain(0, 6))
        );
    }

    /**
     * This verifies that you can start at any arbitrary node in the chain and continue to be verified going forward.
     */
    public function testChaining()
    {
        $oldChain = new Blakechain(
            new Node(\random_bytes(128))
        );
        for ($i = 0; $i < 100; ++$i) {
            $oldChain->appendData(random_bytes(128));
        }
        $prevHash = $oldChain->getLastNode()->getHash();

        $common = random_bytes(33);
        $node = new Node(
            $common,
            $oldChain->getLastHash(true)
        );
        $oldChain->appendData($common);

        $this->assertSame(
            $prevHash,
            $oldChain->getLastNode()->getPrevHash()
        );

        $this->assertSame(
            $node->getHash(),
            $oldChain->getLastNode()->getHash()
        );

        for ($i = 0; $i < 100; ++$i) {
            $common = random_bytes(33);
            $node = new Node(
                $common,
                $oldChain->getLastNode()->getHash(true)
            );
            $oldChain->appendData($common);
            $this->assertSame(
                $node->getHash(),
                $oldChain->getLastNode()->getHash()
            );
        }
    }
}
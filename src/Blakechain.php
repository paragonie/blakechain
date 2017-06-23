<?php
declare(strict_types=1);
namespace ParagonIE\Blakechain;

/**
 * Class Blakechain
 * @package ParagonIE\Blakechain
 */
class Blakechain
{
    const HASH_SIZE = 32;

    /**
     * @var array<int, Node>
     */
    protected $nodes;

    /**
     * Blakechain constructor.
     * @param array<int, Node> $nodes
     * @throws \Error
     */
    public function __construct(Node ...$nodes)
    {
        $num = \count($nodes);
        if ($num < 1) {
            throw new \Error('Nodes expected.');
        }
        $prevHash = '';
        for ($i = 0; $i < $num; ++$i) {
            $thisNodesPrev = $nodes[$i]->getPrevHash();
            if (empty($thisNodesPrev)) {
                $nodes[$i]->setPrevHash($prevHash);
            }
            $prevHash = $nodes[$i]->getHash(true);
        }
        $this->nodes = $nodes;
    }

    /**
     * @param bool $rawBinary
     * @return string
     */
    public function getLastHash(bool $rawBinary = false): string
    {
        return $this->getLastNode()->getHash($rawBinary);
    }

    /**
     * Append a new Node.
     *
     * @param string $data
     * @return self
     */
    public function appendData(string $data): self
    {
        $last = $this->getLastNode();
        $prevHash = $last->getHash(true);
        $this->nodes[] = new Node($data, $prevHash);
        return $this;
    }

    /**
     * @return array<int, Node>
     */
    public function getNodes(): array
    {
        return \array_values($this->nodes);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getPartialChain(int $offset = 0, int $limit = PHP_INT_MAX): array
    {
        $chain = [];
        $num = \count($this->nodes);
        for ($i = 0; $i < $limit && $i < $num; ++$i) {
            $chain[] = [
                'prev' => $this->nodes[$offset]->getPrevHash(),
                'data' => $this->nodes[$offset]->getData(),
                'hash' => $this->nodes[$offset]->getHash()
            ];
            ++$offset;
        }
        return $chain;
    }

    /**
     * @return Node
     */
    public function getLastNode(): Node
    {
        if (empty($this->nodes)) {
            throw new \Error('Blakechain has no nodes');
        }
        $keys = \array_keys($this->nodes);
        $last = \array_pop($keys);
        return $this->nodes[$last];
    }
}

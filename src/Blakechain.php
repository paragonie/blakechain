<?php
declare(strict_types=1);
namespace ParagonIE\Blakechain;

use ParagonIE_Sodium_Compat as SodiumCompat;
use ParagonIE_Sodium_Core_Util as Util;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * Class Blakechain
 * @package ParagonIE\Blakechain
 */
class Blakechain
{
    // Maximum is 64 byte // 512 bit
    const HASH_SIZE = 32; // 256 bit

    /** @var string $firstPrevHash */
    protected $firstPrevHash = '';

    /** @var string $summaryHashState */
    protected $summaryHashState = '';

    /** @var array<int, Node> */
    protected $nodes = [];

    /**
     * Blakechain constructor.
     *
     * @param Node ...$nodes
     *
     * @throws \Error
     * @throws \SodiumException
     */
    public function __construct(Node ...$nodes)
    {
        $this->firstPrevHash = '';
        $this->summaryHashState = '';
        $this->nodes = $nodes;
        $this->recalculate();
    }

    /**
     * Append a new Node.
     *
     * @param string $data
     * @return self
     *
     * @throws \SodiumException
     */
    public function appendData(string $data): self
    {
        if (empty($this->nodes)) {
            $prevHash = $this->firstPrevHash;
        } else {
            $last = $this->getLastNode();
            $prevHash = $last->getHash(true);
        }
        $newNode = new Node($data, $prevHash);
        $this->nodes[] = $newNode;

        SodiumCompat::crypto_generichash_update(
            $this->summaryHashState,
            $newNode->getHash(true)
        );
        return $this;
    }

    /**
     * @param bool $rawBinary
     * @return string
     *
     * @throws \SodiumException
     */
    public function getLastHash(bool $rawBinary = false): string
    {
        return $this->getLastNode()->getHash($rawBinary);
    }

    /**
     * @return Node
     * @throws \Error
     */
    public function getLastNode(): Node
    {
        $keys = \array_keys($this->nodes);
        $last = \array_pop($keys);
        return $this->nodes[$last];
    }

    /**
     * @return array<int, Node>
     */
    public function getNodes(): array
    {
        return \array_values($this->nodes);
    }

    /**
     * Get the summary hash
     *
     * @param bool $rawBinary
     * @return string
     *
     * @throws \Exception
     */
    public function getSummaryHash(bool $rawBinary = false): string
    {
        /* Make a XOR-encrypted copy of the hash state to prevent PHP's
         * interned strings from overwriting the hash state and causing
         * corruption. */
        /** @psalm-suppress InternalMethod */
        /** @var positive-int $len */
        $len = Util::strlen($this->summaryHashState);
        $pattern = \random_bytes($len);
        $tmp = $pattern ^ $this->summaryHashState;

        $finalHash = SodiumCompat::crypto_generichash_final($this->summaryHashState);

        /* Restore hash state */
        $this->summaryHashState = $tmp ^ $pattern;
        if ($rawBinary) {
            return $finalHash;
        }
        return Base64UrlSafe::encode($finalHash);
    }

    /**
     * Get a string representing the internals of a crypto_generichash state.
     *
     * @param bool $rawBinary
     * @return string
     */
    public function getSummaryHashState(bool $rawBinary = false): string
    {
        if ($rawBinary) {
            return '' . $this->summaryHashState;
        }
        return Base64UrlSafe::encode($this->summaryHashState);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     *
     * @throws \SodiumException
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
     * Recalculate the summary hash and summary hash state.
     * @return self
     *
     * @throws \SodiumException
     */
    public function recalculate(): self
    {
        $num = \count($this->nodes);
        $this->summaryHashState = SodiumCompat::crypto_generichash_init();
        $prevHash = $this->firstPrevHash;
        for ($i = 0; $i < $num; ++$i) {
            $thisNodesPrev = $this->nodes[$i]->getPrevHash();
            if (empty($thisNodesPrev)) {
                $this->nodes[$i]->setPrevHash($prevHash);
            }
            $prevHash = $this->nodes[$i]->getHash(true);
            SodiumCompat::crypto_generichash_update(
                $this->summaryHashState,
                $prevHash
            );
        }
        return $this;
    }

    /**
     * @param string $first
     * @return self
     *
     * @throws \SodiumException
     */
    public function setFirstPrevHash(string $first = ''): self
    {
        $this->firstPrevHash = $first;
        return $this->recalculate();
    }

    /**
     * @param string $hashState
     * @return self
     *
     * @throws \RangeException
     */
    public function setSummaryHashState(string $hashState): self
    {
        /** @psalm-suppress InternalMethod */
        $len = Util::strlen($hashState);
        if ($len !== 384 && $len !== 361) {
            throw new \RangeException(
                'Expected exactly 361 or 384 bytes, ' . $len . ' given.'
            );
        }
        $this->summaryHashState = $hashState;
        return $this;
    }
}

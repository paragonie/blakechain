<?php
declare(strict_types=1);
namespace ParagonIE\Blakechain;

use ParagonIE_Sodium_Compat as SodiumCompat;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * Class Verifier
 * @package ParagonIE\Blakechain
 */
class Verifier
{
    const FINAL_HASH_MISMATCH = 'The final hash for this Blakechain does not match what was expected';
    const HASH_DOES_NOT_MATCH = 'The hash for this item does not match its contents';
    const PREV_DOES_NOT_MATCH = 'The previous hash for this item does not match the previous hash';

    /**
     * @var array
     */
    protected $lastErrorData = [];

    /**
     * @return array
     */
    public function getLastError(): array
    {
        return $this->lastErrorData;
    }

    /**
     * Walk down the entire chain, recalculate the final hash, then
     * verify that it matches what we expect.
     *
     * @param Blakechain $chain
     * @param string $lastHash
     * @return bool
     *
     * @throws \SodiumException
     */
    public function verifyLastHash(
        Blakechain $chain,
        string $lastHash
    ): bool {
        /**
         * @var array<int, Node> $nodes
         */
        $nodes = $chain->getNodes();
        $count = \count($nodes);

        $prevHash = '';
        for ($i = 0; $i < $count; ++$i) {
            /** @var Node $curr */
            $curr = $nodes[$i];
            $actualHash = SodiumCompat::crypto_generichash(
                $curr->getData(),
                $prevHash
            );
            if (!\hash_equals($actualHash, $curr->getHash(true))) {
                $this->lastErrorData = [
                    'index' => $i,
                    'item' => [
                        'prev' => $curr->getPrevHash(),
                        'data' => $curr->getData(),
                        'hash' => $curr->getHash()
                    ],
                    'failure' => static::HASH_DOES_NOT_MATCH
                ];
                return false;
            }
            $prevHash = $curr->getHash(true);
        }
        $decoded = Base64UrlSafe::decode($lastHash);
        if (!\hash_equals($prevHash, $decoded)) {
            $this->lastErrorData = [
                'item' => null,
                'expected' => $lastHash,
                'calculated' => Base64UrlSafe::encode($prevHash),
                'failure' => static::FINAL_HASH_MISMATCH
            ];
            return false;
        }
        return true;
    }

    /**
     * This is a self-consistency check for a subset of a Blakechain.
     *
     * @param Blakechain $chain
     * @param int $offset
     * @param int $limit
     * @return bool
     *
     * @throws \SodiumException
     */
    public function verifySequenceHashes(
        Blakechain $chain,
        int $offset = 0,
        int $limit = PHP_INT_MAX
    ): bool {
        $subchain = $chain->getPartialChain($offset, $limit);

        /** @var string $prev */
        $prev = '';

        /**
         * @var int $idx
         * @var array<string, string> $item
         */
        foreach ($subchain as $idx => $item) {
            $prevHash = Base64UrlSafe::decode($item['prev']);
            $storedHash = Base64UrlSafe::decode($item['hash']);
            $actualHash = SodiumCompat::crypto_generichash(
                $item['data'],
                $prevHash
            );
            if (!\hash_equals($actualHash, $storedHash)) {
                $this->lastErrorData = [
                    'index' => $idx,
                    'item' => $item,
                    'failure' => static::HASH_DOES_NOT_MATCH
                ];
                return false;
            }
            if (!empty($prev)) {
                if (!\hash_equals($prev, $item['prev'])) {
                    $this->lastErrorData = [
                        'index' => $idx,
                        'prev' => $prev,
                        'item' => $item,
                        'failure' => static::PREV_DOES_NOT_MATCH
                    ];
                    return false;
                }
            }
            /** @var string $prev */
            $prev = $item['hash'];
        }
        return true;
    }
}

<?php
declare(strict_types=1);
namespace ParagonIE\Blakechain;

use ParagonIE_Sodium_Compat as SodiumCompat;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * Class Node
 * @package ParagonIE\Blakechain
 */
class Node
{
    /**
     * @var string
     */
    protected $prevHash = '';

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @var string
     */
    protected $hash = '';

    /**
     * Node constructor.
     *
     * @param string $data
     * @param string $prevHash
     */
    public function __construct(string $data, string $prevHash = '')
    {
        $this->data = $data;
        $this->prevHash = $prevHash;
    }

    /**
     * @param bool $rawBinary
     * @return string
     */
    public function getPrevHash(bool $rawBinary = false): string
    {
        if ($rawBinary) {
            return $this->prevHash;
        }
        return Base64UrlSafe::encode($this->prevHash);
    }

    /**
     * @param bool $rawBinary
     * @return string
     *
     * @throws \SodiumException
     */
    public function getHash(bool $rawBinary = false): string
    {
        if (empty($this->hash)) {
            $this->hash = SodiumCompat::crypto_generichash(
                $this->data,
                $this->prevHash,
                Blakechain::HASH_SIZE
            );
        }
        if ($rawBinary) {
            return $this->hash;
        }
        return Base64UrlSafe::encode($this->hash);
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $prevHash
     * @return self
     */
    public function setPrevHash(string $prevHash): self
    {
        $this->prevHash = $prevHash;
        $this->hash = '';
        return $this;
    }
}

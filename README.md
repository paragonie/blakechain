# Blakechain

[![Build Status](https://travis-ci.org/paragonie/blakechain.svg?branch=master)](https://travis-ci.org/paragonie/blakechain)
[![Latest Stable Version](https://poser.pugx.org/paragonie/blakechain/v/stable)](https://packagist.org/packages/paragonie/blakechain)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/blakechain/v/unstable)](https://packagist.org/packages/paragonie/blakechain)
[![License](https://poser.pugx.org/paragonie/blakechain/license)](https://packagist.org/packages/paragonie/blakechain)

Blakechain is a simple hash-chain data structure based on the BLAKE2b hash function.

Includes:

* The `Blakechain` implementation, which chains together `Node` objects
* A runtime `Verifier` class that validates the self-consistency of an entire chain

Blakechain is not a blockchain. You probably [don't need a blockchain](https://tonyarcieri.com/on-the-dangers-of-a-blockchain-monoculture).

### How Blakechain Works

The hash of each message is a keyed BLAKE2b hash, where the key of this message
is the hash of the previous message.

Recursively:

    $hash[$n] = sodium_crypto_generichash(
        $data[$n],
        $hash[$n - 1]
    );
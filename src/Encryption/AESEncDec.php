<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Encryption;

use SbiEpay\SbiEpay\Exceptions\EncryptionException;

class AESEncDec
{
    private const string ALGORITHM = 'aes-128-cbc';

    /**
     * Encrypt a plaintext string using AES-128-CBC with the given key.
     * The first 16 bytes of the key are used as the initialization vector (IV).
     *
     * @throws EncryptionException
     */
    public function encrypt(string $data, string $key): string
    {
        if (trim($key) === '') {
            throw EncryptionException::invalidKey();
        }

        $iv = substr($key, 0, 16);

        $cipherBinary = openssl_encrypt(
            $data,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($cipherBinary === false) {
            $error = openssl_error_string() ?: 'Unknown OpenSSL error';

            throw EncryptionException::encryptionFailed($error);
        }

        return base64_encode($cipherBinary);
    }

    /**
     * Decrypt a base64-encoded ciphertext string using AES-128-CBC with the given key.
     *
     * @throws EncryptionException
     */
    public function decrypt(string $cipherText, string $key): string
    {
        if (trim($key) === '') {
            throw EncryptionException::invalidKey();
        }

        $iv = substr($key, 0, 16);

        $decodedBinary = base64_decode($cipherText, true);

        if ($decodedBinary === false) {
            throw EncryptionException::decryptionFailed('Provided ciphertext is not valid base64.');
        }

        $plainText = openssl_decrypt(
            $decodedBinary,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($plainText === false) {
            $error = openssl_error_string() ?: 'Bad decryption key or corrupted data';

            throw EncryptionException::decryptionFailed($error);
        }

        return $plainText;
    }
}

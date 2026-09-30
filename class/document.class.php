<?php
class Document
{

    public static function encryptFile($sourceFile, $destinationFile)
    {
        $data = file_get_contents($sourceFile);

        if($data === false){
            return false;
        }

        // IV aleatorio de 16 bytes
        $iv = random_bytes(16);

        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            DOCUMENT_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        if($encrypted === false){
            return false;
        }

        // Guardamos:
        // IV + datos cifrados
        return file_put_contents(
            $destinationFile,
            $iv.$encrypted
        ) !== false;
    }

    public static function decryptFile($encryptedFile)
    {
        $content = file_get_contents($encryptedFile);

        if($content === false){
            return false;
        }

        $iv = substr($content,0,16);

        $encrypted = substr($content,16);

        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            DOCUMENT_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

}

?>
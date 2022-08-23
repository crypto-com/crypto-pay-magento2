<?php
namespace Cdcpay\CDCCheckout\Model;

class CDCSignature
{
    const EXPECTED_SCHEME = 'v1';

    private static $isMbstringAvailable = null;
    private static $isHashEqualsAvailable = null;

    public static function verify_header($payload, $header, $secret, $tolerance = null)
    {
        $timestamp = self::get_timestamp($header);
        $signatures = self::get_signatures($header, self::EXPECTED_SCHEME);
        if (-1 === $timestamp) {
            throw Crypto_Signature_Verification_Exception::factory(
                'Unable to extract timestamp and signatures from header',
                $payload,
                $header
            );
        }
        if (empty($signatures)) {
            throw Crypto_Signature_Verification_Exception::factory(
                'No signatures found with expected scheme',
                $payload,
                $header
            );
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = self::compute_signature($signedPayload, $secret);

        $signatureFound = false;
        foreach ($signatures as $signature) {

            // echo "secret: ".$secret."\n";
            // echo "expectedSignature: ".$expectedSignature."\n";
            // echo "signature: ".$signature."\n";

            if (self::compare_signature($expectedSignature, $signature)) {
                $signatureFound = true;
                break;
            }
        }

        if (!$signatureFound) {
            throw Crypto_Signature_Verification_Exception::factory(
                'No signatures found matching the expected signature for payload',
                $payload,
                $header
            );
        }

        if (($tolerance > 0) && (\abs(\time() - $timestamp) > $tolerance)) {
            throw Crypto_Signature_Verification_Exception::factory(
                'Timestamp outside the tolerance zone',
                $payload,
                $header
            );
        }

        return true;
    }

    private static function get_timestamp($header)
    {
        $items = \explode(',', $header);

        foreach ($items as $item) {
            $itemParts = \explode('=', $item, 2);
            if ('t' === $itemParts[0]) {
                if (!\is_numeric($itemParts[1])) {
                    return -1;
                }

                return (int) ($itemParts[1]);
            }
        }

        return -1;
    }

    private static function get_signatures($header, $scheme)
    {
        $signatures = [];
        $items = \explode(',', $header);

        foreach ($items as $item) {
            $itemParts = \explode('=', $item, 2);
            if (\trim($itemParts[0]) === $scheme) {
                \array_push($signatures, $itemParts[1]);
            }
        }

        return $signatures;
    }
    
    private static function compute_signature($payload, $secret)
    {
        return \hash_hmac('sha256', $payload, $secret);
    }

    private static function compare_signature($a, $b)
    {
        if (null === self::$isHashEqualsAvailable) {
            self::$isHashEqualsAvailable = \function_exists('hash_equals');
        }

        if (self::$isHashEqualsAvailable) {
            return \hash_equals($a, $b);
        }
        if (\strlen($a) !== \strlen($b)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < \strlen($a); ++$i) {
            $result |= \ord($a[$i]) ^ \ord($b[$i]);
        }

        return 0 === $result;
    }
}

class Crypto_Signature_Verification_Exception extends \Exception
{
    public static function factory($message, $payload, $header) {
        return new Crypto_Signature_Verification_Exception($message."\n".$payload."\n".$header);
    }

    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
<?php
namespace UUIDv9\UUIDv9;

class UUIDv9 {
    private static $uuidRegex = "/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/";
    private static $base16Regex = "/^[0-9a-fA-F]+$/";

    private static function calcChecksum(string $hexString): string {
        $data = str_split($hexString, 2);
        $polynomial = 0x07;
        $crc = 0x00;
    
        foreach ($data as $byte) {
            $byteValue = hexdec($byte);
            $crc ^= $byteValue;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x80) {
                    $crc = ($crc << 1) ^ $polynomial;
                } else {
                    $crc <<= 1;
                }
                // $crc &= 0xFF;
            }
        }
    
        return str_pad(dechex($crc & 0xFF), 2, '0', STR_PAD_LEFT);
    }
    
    public static function verifyChecksum(string $uuid): bool {
        $base16String = substr(str_replace('-', '', $uuid), 0, 30);
        $crc = self::calcChecksum($base16String);
        return $crc === substr($uuid, 34, 2);
    }
    
    public static function checkVersion($uuid, $version = null) {
        $versionDigit = $uuid[14];
        $variantDigit = $uuid[19];
        return (!$version || $versionDigit == $version) &&
               ($versionDigit == "9" || (in_array($versionDigit, ['1', '4']) && strpos("89abAB", $variantDigit) !== false));
    }

    public static function isUUID($uuid) {
        return !empty($uuid) && preg_match(self::$uuidRegex, $uuid);
    }

    public static function isValidUUIDv9($uuid, $options) {
        return self::isUUID($uuid) &&
               (!isset($options['checksum']) || $options['checksum'] === false || self::verifyChecksum($uuid)) &&
               (!isset($options['version']) || $options['version'] === false || self::checkVersion($uuid));
    }

    private static function randomBytes($count) {
        return implode('', array_map(function() {
            return dechex(rand(0, 15));
        }, range(1, $count)));
    }

    private static function randomChar($chars) {
        return $chars[rand(0, strlen($chars) - 1)];
    }

    private static function isBase16($str) {
        return preg_match(self::$base16Regex, $str);
    }

    private static function validatePrefix($prefix) {
        if ($prefix === null) throw new \InvalidArgumentException("Prefix must be a string");
        if (strlen($prefix) > 8) throw new \InvalidArgumentException("Prefix must be no more than 8 characters");
        if (!self::isBase16($prefix)) throw new \InvalidArgumentException("Prefix must be only hexadecimal characters");
    }

    private static function addDashes($str) {
        return substr($str, 0, 8) . '-' . substr($str, 8, 4) . '-' . substr($str, 12, 4) . '-' . substr($str, 16, 4) . '-' . substr($str, 20);
    }

    public static function generate($options = []) {
        $options = array_merge(['prefix' => '', 'timestamp' => true, 'checksum' => false, 'version' => false, 'legacy' => false], $options);
        
        $prefix = strtolower($options['prefix']);
        $timestamp = $options['timestamp'];
        $checksum = $options['checksum'];
        $version = $options['version'];
        $legacy = $options['legacy'];

        if (!empty($prefix)) {
            self::validatePrefix($prefix);
        }

        $center = '';
        if ($timestamp === true) {
            $center = dechex(time());
        } elseif (is_string($timestamp) || is_int($timestamp)) {
            $center = dechex(strtotime($timestamp));
        }

        $suffixLength = 32 - strlen($prefix) - strlen($center) - ($checksum ? 2 : 0) - ($legacy ? 2 : ($version ? 1 : 0));
        $suffix = self::randomBytes($suffixLength);
        $joined = $prefix . $center . $suffix;

        if ($legacy) {
            $joined = substr($joined, 0, 12) . ($timestamp ? '1' : '4') . substr($joined, 12, 3) . self::randomChar("89ab") . substr($joined, 15);
        } elseif ($version) {
            $joined = substr($joined, 0, 12) . '9' . substr($joined, 12);
        }

        if ($checksum) {
            $joined .= self::calcChecksum($joined);
        }

        return self::addDashes($joined);
    }
}
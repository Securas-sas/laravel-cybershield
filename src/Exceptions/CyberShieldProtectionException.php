<?php

namespace Securas\LaravelCyberShield\Exceptions;

/**
 * Exception raised when a blacklist matched the
 * associated HTTP parameter. For example, blacklisted
 * Accept-Language or IP address.
 *
 * @author: Securas
 */
class CyberShieldProtectionException extends BaseCyberShieldException {

    /**
     * Exception raised when a blacklist matched the
     * associated HTTP parameter. For example, blacklisted
     * Accept-Language or IP address.
     *
     * @param string $property
     * @param string $value
     * @return static
     */
    public static function http(string $property, string $value) {
        return new static("Invalid HTTP property: $property => $value") ;
    }

    /**
     * Exception raised when an abnormally
     * long URL was received.
     *
     * @return static
     */
    public static function long_url() {
        return new static("Long URL received") ;
    }
}

<?php

namespace  Securas\LaravelCyberShield\Exceptions;

use RuntimeException;

/**
 * Base exception class raised when an issue was found
 * with the CyberShield protections or configurations.
 *
 * @author: Securas
 */
abstract class BaseCyberShieldException extends RuntimeException {}

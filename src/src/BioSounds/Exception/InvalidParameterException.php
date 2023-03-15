<?php

namespace BioSounds\Exception;

use Exception;

/**
 * Class InvalidParameterException
 * @package BioSounds\Exception
 */
class InvalidParameterException extends Exception
{
    private const MESSAGE = 'Invalid Parameter.';

    /**
     * InvalidParameterException constructor.
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}

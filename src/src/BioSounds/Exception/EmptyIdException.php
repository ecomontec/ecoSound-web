<?php

namespace BioSounds\Exception;

use Exception;

/**
 * Class EmptyIdException
 * @package BioSounds\Exception
 */
class EmptyIdException extends Exception
{
    private const MESSAGE = 'The provided ID is empty.';

    /**
     * EmptyIdException constructor.
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}

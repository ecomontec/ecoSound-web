<?php


namespace BioSounds\Exception;


use Exception;

/**
 * Class ForbiddenException
 * @package BioSounds\Exception
 */
class ForbiddenException extends Exception
{
    const MESSAGE = 'Forbidden access.';

    /**
     * ForbiddenException constructor.
     */
    public function __construct()
    {
        parent::__construct(self::MESSAGE, 403);
    }
}

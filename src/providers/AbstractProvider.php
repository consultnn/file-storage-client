<?php

namespace consultnn\filestorage\client\providers;

/**
 * Class AbstractProvider
 * @package common\extension\filestorage
 */
abstract class AbstractProvider
{
    /**
     * @var \consultnn\filestorage\client\File
     */
    protected $owner;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }
}
<?php

namespace consultnn\filestorage\providers;

/**
 * Class AbstractProvider
 * @package common\extension\filestorage
 */
abstract class AbstractProvider
{
    /**
     * @var \consultnn\filestorage\File
     */
    protected $owner;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }
}
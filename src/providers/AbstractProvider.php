<?php

namespace serieznyi\filestorage\providers;

/**
 * Class AbstractProvider
 * @package common\extension\filestorage
 */
abstract class AbstractProvider
{
    /**
     * @var \serieznyi\filestorage\File
     */
    protected $owner;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }
}
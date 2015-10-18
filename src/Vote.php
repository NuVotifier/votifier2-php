<?php

namespace Imaginarycode\Votifier2;

/**
 * This class provides an implementation of a vote.
 * @package Imaginarycode\Votifier2
 */
class Vote
{
    public $serviceName;
    public $username;
    public $address;
    public $timestamp;

    public function __construct($serviceName, $username, $address, $timestamp)
    {
        $this->serviceName = $serviceName;
        $this->username = $username;
        $this->address = $address;
        // We lose some precision if we have to make this up, but that's okay.
        $this->timestamp = $timestamp ?: (int) (time() * 1000);
    }
}
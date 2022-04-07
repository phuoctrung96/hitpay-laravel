<?php

namespace App\Manager;

abstract class AbstractManager
{
    protected $repository;

    public function createNew()
    {
        $class = $this->getClass();

        return new $class();
    }

    public function save() {}

    //public function delete() {}
}
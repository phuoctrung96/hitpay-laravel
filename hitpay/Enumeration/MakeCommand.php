<?php

namespace HitPay\Enumeration;

use Illuminate\Console\GeneratorCommand;

class MakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:enumeration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enumeration class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Enumeration';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/enumeration.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Enumerations';
    }
}

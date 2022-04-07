<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filename & Format
    |--------------------------------------------------------------------------
    |
    | The filename (without extension) and the format (php or json) to be used.
    |
    */

    'filename' => '_ide_helper',
    'format' => 'php',
    'meta_filename' => '.phpstorm.meta.php',

    /*
    |--------------------------------------------------------------------------
    | Fluent Helpers
    |--------------------------------------------------------------------------
    |
    | Indicate whether to generate fluent methods.
    |
    */

    'include_fluent' => true,

    /*
    |--------------------------------------------------------------------------
    | Factory Builders
    |--------------------------------------------------------------------------
    |
    | Indicate whether to generate factory generators.
    |
    */

    'include_factory_builders' => true,

    /*
    |--------------------------------------------------------------------------
    | Model Magic Methods
    |--------------------------------------------------------------------------
    |
    | Indicate whether to generate magic methods for models.
    |
    */

    'write_model_magic_where' => true,

    /*
    |--------------------------------------------------------------------------
    | Eloquent Model Mixins
    |--------------------------------------------------------------------------
    |
    | Indicate whether to add the necessary DocBlock mixins to the model class
    | contained in the Laravel Framework which helps IDE in auto-completion.
    |
    | Warning: This setting changes a file within the vendor directory.
    |
    */

    'write_eloquent_model_mixins' => true,

    /*
    |--------------------------------------------------------------------------
    | Helper Files
    |--------------------------------------------------------------------------
    |
    | Indicate whether to include the helper files. By default they are not
    | included, however this can be toggled with the '--helpers' (-H) option.
    | More helper files can be included too.
    |
    */

    'include_helpers' => false,

    'helper_files' => [
        base_path(implode(DIRECTORY_SEPARATOR, [
            'vendor',
            'laravel',
            'framework',
            'src',
            'Illuminate',
            'Support',
            'helpers.php',
        ])),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Paths
    |--------------------------------------------------------------------------
    |
    | Define in which directories the 'ide-helper:models' command should look
    | for the models.
    |
    */

    'model_locations' => [
        'app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra Classes
    |--------------------------------------------------------------------------
    |
    | These implementations are not really extended, but are called with magic
    | functions.
    |
    */

    'extra' => [
        'Eloquent' => [
            \Illuminate\Database\Eloquent\Builder::class,
            \Illuminate\Database\Query\Builder::class,
        ],

        'Session' => [
            \Illuminate\Session\Store::class,
        ],
    ],

    'magic' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Interface Implementations
    |--------------------------------------------------------------------------
    |
    | These interfaces will be replaced with the implementing class. Some of
    | them are detected by the helpers, others can be listed below.
    |
    */

    'interfaces' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Database Types
    |--------------------------------------------------------------------------
    |
    | This array allows you to map any custom database type.
    |
    | Each key in this array is a name of the Doctrine Database Abstraction
    | Layer Platform. For example, 'postgresql', 'mysql' and 'sqlite'.
    |
    | The value of the array is an array of type mappings. Key is the name of
    | the custom type, (for example, 'jsonb' from Postgres 9.4) and the value
    | is the name of the corresponding Doctrine2 type (in our case it is
    | 'json_array').
    |
    | So, to support jsonb in your models when working with Postgres, just add
    | the following entry to the array below:
    |
    | 'postgresql' => [
    |       'jsonb' => 'json_array',
    |  ],
    |
    | Doctrine types are listed here:
    | http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html
    |
    */

    'custom_db_types' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Camel Cased Models
    |--------------------------------------------------------------------------
    |
    | There are some packages for Laravel (For example Eloquence) allow to
    | access Eloquent model properties via camel case.
    |
    | This option will indicate whether to support these packages by saving all
    | properties of model as camel case, instead of snake case.
    |
    | Note: It is currently an all-or-nothing option.
    |
    */

    'model_camel_case_properties' => false,

    /*
    |--------------------------------------------------------------------------
    | Property Casts
    |--------------------------------------------------------------------------
    |
    | Cast the given "real type" to the given "type".
    |
    */

    'type_overrides' => [
        'integer' => 'int',
        'boolean' => 'bool',
    ],

    /*
    |--------------------------------------------------------------------------
    | DocBlocks From Classes
    |--------------------------------------------------------------------------
    |
    | Indicate whether to include DocBlocks from classes to allow additional
    | code inspection for magic methods and properties.
    |
    */

    'include_class_docblocks' => true,

];

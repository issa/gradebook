<?php

namespace Studip\Grading;

use Instance;

class Definition extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'grading_definitions';

        $config['belongs_to']['course'] = [
            'class_name' => 'Course',
            'foreign_key' => 'course_id',
        ];

        $config['has_many']['instances'] = [
            'class_name' => Instance::class,
            'assoc_foreign_key' => 'definition_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];

        parent::configure($config);
    }
}

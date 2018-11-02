<?php

namespace Studip\Grading;

class Instance extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'grading_instances';

        $config['belongs_to']['test'] = [
            'class_name' => $config['relationTypes']['Test'],
            'foreign_key' => 'test_id'
        ];

        $config['has_many']['attempts'] = [
            'class_name' => $config['relationTypes']['Attempt'],
            'assoc_foreign_key' => 'assignment_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        ];

        parent::configure($config);
    }
}

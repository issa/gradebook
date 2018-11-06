<?php

namespace Studip\Grading;

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
            'class_name' => '\\Studip\\Grading\\Instance',
            'assoc_foreign_key' => 'definition_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];

        parent::configure($config);
    }

    public static function getCategoriesByCourse(\Course $course)
    {
        $query = 'SELECT category FROM grading_definitions
                  WHERE course_id = ?
                  GROUP BY category
                  ORDER BY category ASC';

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute([$course->id]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function findByCourse(\Course $course)
    {
        return \SimpleORMapCollection::createFromArray(Definition::findBySQL('course_id = ? ORDER BY position', [$course->id]));
    }
}

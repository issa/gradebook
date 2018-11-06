<?php

require_once 'abstract_gradebook_controller.php';

use Studip\Grading\Definition;
use Studip\Grading\Instance;

/**
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Gradebook_LecturersController extends AbstractGradebookController
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$this->viewerIsLecturer()) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function index_action()
    {
        if (Navigation::hasItem('/course/gradebook/index')) {
            Navigation::activateItem('/course/gradebook/index');
        }

        $course = \Context::get();
        $this->categories = Definition::getCategoriesByCourse($course);
        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');
        $gradingDefinitions = Definition::findByCourse($course);
        $gradingInstances = Instance::findByCourse($course);

        $this->groupedDefinitions = [];
        foreach ($gradingDefinitions as $def) {
            if (!isset($this->groupedDefinitions[$def->category])) {
                $this->groupedDefinitions[$def->category] = [];
            }
            $this->groupedDefinitions[$def->category][] = $def;
        }

        $groupedInstances = [];
        foreach ($gradingInstances as $instance) {
            if (!isset($groupedInstances[$instance->user_id])) {
                $groupedInstances[$instance->user_id] = [];
            }
            $groupedInstances[$instance->user_id][$instance->definition_id] = $instance;
        }

        $this->findInstance = function (Definition $definition, \User $user) use ($groupedInstances) {
            if (!isset($groupedInstances[$user->id])) {
                return null;
            }
            if (!isset($groupedInstances[$user->id][$definition->id])) {
                return null;
            }

            return $groupedInstances[$user->id][$definition->id];
        };

        $sumOfWeights = 0;
        foreach ($gradingDefinitions as $def) {
            $sumOfWeights += $def->weight;
        }

        $this->findWeight = function (Definition $definition) use ($sumOfWeights) {
            return $definition->weight / $sumOfWeights;
        };

        $this->totalSums = [];
        foreach ($this->students as $student) {
            if (!isset($this->totalSums[$student->id])) {
                $this->totalSums[$student->id] = 0;
            }

            foreach ($groupedInstances[$student->id] as $definitionId => $instance) {
                $definition = $gradingDefinitions->findOneBy('id', $definitionId);
                $this->totalSums[$student->id] += $instance->rawgrade * ($definition->weight / $sumOfWeights);
            }
        }

        $this->formatAsPercent = function ($value) {
            return (round($value * 1000) / 10).'%';
        };
    }
}

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
        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');
        $this->gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->groupedDefinitions();
        $this->groupedInstances = $this->groupedInstances($course);
        $this->sumOfWeights = $this->getSumOfWeights();
        $this->totalSums = $this->sumOfWeights ? $this->getTotalSums() : 0;
    }

    public function formatAsPercent($value)
    {
        return (round($value * 1000) / 10).'%';
    }

    public function getInstanceForUser(Definition $definition, \User $user)
    {
        if (!isset($this->groupedInstances[$user->id])) {
            return null;
        }
        if (!isset($this->groupedInstances[$user->id][$definition->id])) {
            return null;
        }

        return $this->groupedInstances[$user->id][$definition->id];
    }

    public function getNormalizedWeight(Definition $definition)
    {
        return $this->sumOfWeights ? $definition->weight / $this->sumOfWeights : 0;
    }

    private function groupedDefinitions()
    {
        $groupedDefinitions = [];
        foreach ($this->gradingDefinitions as $def) {
            if (!isset($groupedDefinitions[$def->category])) {
                $groupedDefinitions[$def->category] = [];
            }
            $groupedDefinitions[$def->category][] = $def;
        }

        return $groupedDefinitions;
    }

    private function groupedInstances($course)
    {
        $gradingInstances = Instance::findByCourse($course);
        $groupedInstances = [];
        foreach ($gradingInstances as $instance) {
            if (!isset($groupedInstances[$instance->user_id])) {
                $groupedInstances[$instance->user_id] = [];
            }
            $groupedInstances[$instance->user_id][$instance->definition_id] = $instance;
        }

        return $groupedInstances;
    }

    private function getSumOfWeights()
    {
        $sumOfWeights = 0;
        foreach ($this->gradingDefinitions as $def) {
            $sumOfWeights += $def->weight;
        }

        return $sumOfWeights;
    }

    private function getTotalSums()
    {
        $totalSums = [];
        foreach ($this->students as $student) {
            if (!isset($totalSums[$student->id])) {
                $totalSums[$student->id] = 0;
            }

            foreach ($this->groupedInstances[$student->id] as $definitionId => $instance) {
                if ($definition = $this->gradingDefinitions->findOneBy('id', $definitionId)) {
                    $totalSums[$student->id] += $instance->rawgrade * ($definition->weight / $this->sumOfWeights);
                }
            }
        }

        return $totalSums;
    }
}

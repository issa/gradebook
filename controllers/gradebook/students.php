<?php

require_once 'abstract_gradebook_controller.php';

use Studip\Grading\Definition;
use Studip\Grading\Instance;

/**
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Gradebook_StudentsController extends AbstractGradebookController
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!$this->viewerIsStudent()) {
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
        $user = $this->getCurrentUser();

        $this->gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->groupedDefinitions();

        $this->categories = array_keys($this->groupedDefinitions);
        sort($this->categories);

        $this->groupedInstances = $this->groupedInstances($course, $user);


        $this->sumOfWeights = $this->getSumOfWeights();
    }

    public function formatAsPercent($value)
    {
        return (double) (round($value * 1000) / 10);
    }

    public function getNormalizedWeight(Definition $definition)
    {
        return $this->sumOfWeights ? $definition->weight / $this->sumOfWeights : 0;
    }

    private function getSumOfWeights()
    {
        $sumOfWeights = 0;
        foreach ($this->gradingDefinitions as $def) {
            $sumOfWeights += $def->weight;
        }

        return $sumOfWeights;
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

    private function groupedInstances(\Course $course, \User $user)
    {
        $gradingInstances = Instance::findByCourseAndUser($course, $user);
        $groupedInstances = [];
        foreach ($gradingInstances as $instance) {
            $groupedInstances[$instance->definition_id] = $instance;
        }

        return $groupedInstances;
    }

}

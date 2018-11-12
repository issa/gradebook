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
        $this->categories = array_keys($this->groupedDefinitions);
        sort($this->categories);
        $this->groupedInstances = $this->groupedInstances($course);
        $this->sumOfWeights = $this->getSumOfWeights();
        $this->totalSums = $this->sumOfWeights ? $this->getTotalSums() : 0;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function export_action()
    {
        $this->response->add_header(
            'Cache-Control',
            $_SERVER['HTTPS'] === 'on' ? 'private' : 'no-cache, no-store, must-revalidate'
        );

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_.]+/', '-',
            sprintf(
                'gradebook-%s.json',
                \Context::getHeaderLine()
            )
        );

        $course = \Context::get();
        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');

        $this->gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->groupedDefinitions();
        $this->categories = array_keys($this->groupedDefinitions);
        sort($this->categories);
        $this->groupedInstances = $this->groupedInstances($course);

        $headerLine = [];
        foreach ($this->categories as $category) {
            foreach ($this->groupedDefinitions[$category] as $definition) {
                $headerLine[] = $category.': '.$definition->name;
            }
        }
        $studentLines = [];
        foreach ($this->students as $user) {
            $studentLine = [];
            foreach ($this->categories as $category) {
                foreach ($this->groupedDefinitions[$category] as $definition) {
                    $studentLine[] = isset($this->groupedInstances[$user->id][$definition->id])
                                   ? $this->groupedInstances[$user->id][$definition->id]->rawgrade
                                   : 0;
                }
            }
            $studentLines[] = $studentLine;
        }

        $data = array_merge([$headerLine], $studentLines);
        $exportString = array_to_csv($data);

        $this->response->add_header('Content-Disposition', 'attachment;filename="'.$filename.'"');
        $this->response->add_header('Content-Description', 'File Transfer');
        $this->response->add_header('Content-Transfer-Encoding', 'binary');
        $this->response->add_header('Content-Type', 'text/csv;charset=utf-8');
        $this->response->add_header('Content-Length', strlen($exportString));

        $this->render_text($exportString);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function weights_action()
    {
        if (Navigation::hasItem('/course/gradebook/weights')) {
            Navigation::activateItem('/course/gradebook/weights');
        }

        $course = \Context::get();
        $this->gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->groupedDefinitions();
        $this->categories = array_keys($this->groupedDefinitions);
        sort($this->categories);
        $this->sumOfWeights = $this->getSumOfWeights();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function store_weights_action()
    {
        $weights = \Request::intArray('definitions');
        $gradingDefinitions = Definition::findByCourse(\Context::get());

        foreach ($gradingDefinitions as $def) {
            if (!isset($weights[$def->id])) {
                continue;
            }
            $newWeight = (int) $weights[$def->id];
            if ($newWeight < 0) {
                continue;
            }
            $def->weight = $newWeight;
        }

        $changedDefinitions = array_filter($gradingDefinitions->store());
        if (count($changedDefinitions)) {
            $this->flash['success'] = _('Gewichtungen erfolgreich verändert.');
        }
        $this->redirect('gradebook/lecturers');
    }

    public function formatAsPercent($value)
    {
        return (float) (round($value * 1000) / 10).'%';
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

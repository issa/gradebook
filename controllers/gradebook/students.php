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
        $this->groupedDefinitions = $this->getGroupedDefinitions($this->gradingDefinitions);
        $this->categories = $this->getCategories($course);
        $this->groupedInstances = $this->groupedInstances($course, $user);
        $this->sumOfWeights = $this->getSumOfWeights($this->gradingDefinitions);
        $this->subtotals = $this->getSubtotalGrades();
        $this->total = $this->getTotalGrade();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function export_action()
    {
        $this->response->add_header(
            'Cache-Control',
            'on' === $_SERVER['HTTPS'] ? 'private' : 'no-cache, no-store, must-revalidate'
        );

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_.]+/', '-',
            sprintf(
                'gradebook-%s.json',
                \Context::getHeaderLine()
            )
        );

        $course = \Context::get();
        $user = $this->getCurrentUser();
        $this->gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->getGroupedDefinitions($this->gradingDefinitions);
        $this->categories = $this->getCategories($course);
        $this->groupedInstances = $this->groupedInstances($course, $user);

        $lines = [];
        foreach ($this->categories as $category) {
            foreach ($this->groupedDefinitions[$category] as $definition) {
                $instance = isset($this->groupedInstances[$definition->id]) ? $this->groupedInstances[$definition->id] : null;
                $lines[] = [
                    $category,
                    $definition->name,
                    $definition->tool,
                    $instance ? $instance->rawgrade : 0,
                    $instance ? $instance->feedback : null,
                ];
            }
        }

        $headerLine = [
            _('Kategorie'),
            _('Leistung'),
            _('Werkzeug'),
            _('Fortschritt'),
            _('Feedback'),
        ];

        $data = array_merge([$headerLine], $lines);
        $exportString = array_to_csv($data);

        $this->response->add_header('Content-Disposition', 'attachment;filename="'.$filename.'"');
        $this->response->add_header('Content-Description', 'File Transfer');
        $this->response->add_header('Content-Transfer-Encoding', 'binary');
        $this->response->add_header('Content-Type', 'text/csv;charset=utf-8');
        $this->response->add_header('Content-Length', strlen($exportString));

        $this->render_text($exportString);
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

    private function getSubtotalGrades()
    {
        $subtotals = [];

        foreach ($this->groupedDefinitions as $category => $definitions) {
            $sumOfWeightedGrades = 0;
            $sumOfWeights = 0;

            foreach ($definitions as $definition) {
                if (isset($this->groupedInstances[$definition->id])) {
                    $instance = $this->groupedInstances[$definition->id];
                    $sumOfWeightedGrades += $instance->rawgrade * $definition->weight;
                }
                $sumOfWeights += $definition->weight;
            }
            $subtotals[$category] = $sumOfWeights ? $sumOfWeightedGrades / $sumOfWeights : 0;
        }

        return $subtotals;
    }

    private function getTotalGrade()
    {
        $sumOfWeightedGrades = 0;
        $sumOfWeights = 0;

        foreach ($this->gradingDefinitions as $definition) {
            if (isset($this->groupedInstances[$definition->id])) {
                $instance = $this->groupedInstances[$definition->id];
                $sumOfWeightedGrades += $instance->rawgrade * $definition->weight;
            }
            $sumOfWeights += $definition->weight;
        }

        return $sumOfWeights ? $sumOfWeightedGrades / $sumOfWeights : 0;
    }
}

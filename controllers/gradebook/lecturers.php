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
        $this->categories = $this->getCategories($course);
        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');
        $gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->getGroupedDefinitions($gradingDefinitions);
        $this->groupedInstances = $this->groupedInstances($course);
        $this->sumOfWeights = $this->getSumOfWeights($gradingDefinitions);
        $this->totalSums = $this->sumOfWeights ? $this->getTotalSums($gradingDefinitions) : 0;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function export_action()
    {
        $this->response->add_header(
            'Cache-Control',
            $_SERVER['HTTPS'] === 'on' ? 'private' : 'no-cache, no-store, must-revalidate'
        );

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_.]+/',
            '-',
            sprintf(
                'gradebook-%s.json',
                \Context::getHeaderLine()
            )
        );

        $course = \Context::get();
        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');

        $gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->getGroupedDefinitions($gradingDefinitions);
        $this->categories = $this->getCategories($course);
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
        $gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->getGroupedDefinitions($gradingDefinitions);
        $this->categories = $this->getCategories($course);
        $this->sumOfWeights = $this->getSumOfWeights($gradingDefinitions);
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

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function custom_definitions_action()
    {
        if (Navigation::hasItem('/course/gradebook/custom_definitions')) {
            Navigation::activateItem('/course/gradebook/custom_definitions');
        }

        $course = \Context::get();
        $gradingDefinitions = Definition::findByCourse($course);
        $this->groupedDefinitions = $this->getGroupedDefinitions($gradingDefinitions);
        $this->customDefinitions = isset($this->groupedDefinitions[Definition::CUSTOM_DEFINITIONS_CATEGORY])
                                 ? $this->groupedDefinitions[Definition::CUSTOM_DEFINITIONS_CATEGORY]
                                 : [];

        $this->students = $course->getMembersWithStatus('autor', true)->pluck('user');
        $this->groupedInstances = $this->groupedInstances($course);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function new_custom_definition_action()
    {
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function create_custom_definition_action()
    {
        $name = trim(\Request::get('name', ''));
        if (!mb_strlen($name)) {
            $this->flash['error'] = _('Der Name einer Leistung darf nicht leer sein.');
        } else {
            $definition = Definition::create(
                [
                    'course_id' => \Context::getId(),
                    'item' => 'manual',
                    'name' => $name,
                    'tool' => 'manual',
                    'category' => Definition::CUSTOM_DEFINITIONS_CATEGORY,
                    'position' => 0,
                    'weight' => 1.0,
                ]
            );

            if (!$definition) {
                $this->flash['error'] = _('Die Leistung konnte nicht definiert werden.');
            } else {
                $this->flash['success'] = _('Die Leistung wurde erfolgreich definiert.');
            }
        }
        $this->redirect('gradebook/lecturers/custom_definitions');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function edit_custom_definition_action($definitionId)
    {
        if (!$this->definition = Definition::findOneBySQL('id = ? AND course_id = ?', [$definitionId, \Context::getId()])) {
            throw new \Trails_Exception(404);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function update_custom_definition_action($definitionId)
    {
        if (!$definition = Definition::findOneBySQL('id = ? AND course_id = ?', [$definitionId, \Context::getId()])) {
            throw new \Trails_Exception(404);
        }

        $name = trim(\Request::get('name', ''));
        if (!mb_strlen($name)) {
            $this->flash['error'] = _('Der Name einer Leistung darf nicht leer sein.');
        } else {
            $definition->name = $name;
            if (!$definition->store()) {
                $this->flash['error'] = _('Die Leistung konnte nicht geändert werden.');
            }
        }

        $this->redirect('gradebook/lecturers/custom_definitions');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function delete_custom_definition_action($definitionId)
    {
        if (!$definition = Definition::findOneBySQL(
                'id = ? AND course_id = ?',
                [$definitionId, \Context::getId()]
            )
        ) {
            $this->flash['error'] = _('Die Leistung konnte nicht gelöscht werden.');
        } else {
            if (Definition::deleteBySQL('id = ?', [$definition->id])) {
                $this->flash['success'] = _('Die Leistung wurde gelöscht.');
            } else {
                $this->flash['error'] = _('Die Leistung konnte nicht gelöscht werden.');
            }
        }

        $this->redirect('gradebook/lecturers/custom_definitions');
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

    private function getTotalSums($gradingDefinitions)
    {
        $totalSums = [];
        foreach ($this->students as $student) {
            if (!isset($totalSums[$student->id])) {
                $totalSums[$student->id] = 0;
            }

            foreach ($this->groupedInstances[$student->id] as $definitionId => $instance) {
                if ($definition = $gradingDefinitions->findOneBy('id', $definitionId)) {
                    $totalSums[$student->id] += $instance->rawgrade * ($definition->weight / $this->sumOfWeights);
                }
            }
        }

        return $totalSums;
    }
}

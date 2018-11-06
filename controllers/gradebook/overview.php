<?php

require_once 'abstract_gradebook_controller.php';

use Studip\Grading\Definition;
use Studip\Grading\Instance;

/**
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class Gradebook_OverviewController extends AbstractGradebookController
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function index_action()
    {
        if ($this->viewerIsStudent()) {
            $route = 'gradebook/students';
        } elseif ($this->viewerIsLecturer()) {
            $route = 'gradebook/lecturers';
        } else {
            throw new AccessDeniedException();
        }

        $this->redirect(\PluginEngine::getURL($this->plugin, [], $route));
    }

    // TODO: remove this action after testing
    public function reset_action()
    {
        if (!$cid = \Context::getId()) {
            throw new \BadMethodCallException();
        }

        Definition::deleteBySQL('1');
        Instance::deleteBySQL('1');

        $definitions = array_merge(
            $this->createToolDefinitions($cid, 'Courseware'),
            $this->createToolDefinitions($cid, 'Vips'),
            $this->createToolDefinitions($cid, 'DoIt')
        );

        $course = \Context::get();
        $students = $course->getMembersWithStatus('autor', true);
        $instancesCount = 0;
        foreach ($definitions as $definition) {
            foreach ($students as $student) {
                Instance::create(
                    [
                        'definition_id' => $definition->id,
                        'user_id' => $student->user_id,
                        'rawgrade' => rand(1, 100) / 100,
                        'feedback' => 'lorem ipsum',
                    ]
                );
                $instancesCount++;
            }
        }

        $this->render_text(count($definitions).' Definitionen und '.$instancesCount.' Instances angelegt.');
    }

    private function createToolDefinitions($cid, $tool)
    {
        return array_map(
            function ($num) use ($cid, $tool) {
                return Definition::create(
                    [
                        'course_id' => $cid,
                        'item' => strtolower($tool).':chapter:'.$num,
                        'name' => 'Kapitel '.$num,
                        'tool' => strtolower($tool),
                        'category' => $tool,
                        'position' => $num - 1,
                        'weight' => 1.0,
                    ]
                );
            },
            range(1, 10)
        );
    }
}

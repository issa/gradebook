<?php

use Studip\Grading\Definition;
use Studip\Grading\Instance;

abstract class AbstractGradebookController extends StudipController
{
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->flash = Trails_Flash::instance();
        $this->set_layout(
            $GLOBALS['template_factory']->open(\Request::isXhr() ? 'layouts/dialog' : 'layouts/base')
        );
        $this->setDefaultPageTitle();
        \PageLayout::addStylesheet($this->plugin->getPluginURL().'/gradebook.css');
    }

    public function formatAsPercent($value)
    {
        return (float) (round($value * 1000) / 10);
    }

    public function getNormalizedWeight(Definition $definition)
    {
        return $this->sumOfWeights ? $definition->weight / $this->sumOfWeights : 0;
    }

    protected function getSumOfWeights($gradingDefinitions)
    {
        $sumOfWeights = 0;
        foreach ($gradingDefinitions as $def) {
            $sumOfWeights += $def->weight;
        }

        return $sumOfWeights;
    }

    protected function getGroupedDefinitions($gradingDefinitions)
    {
        $groupedDefinitions = [];
        foreach ($gradingDefinitions as $def) {
            if (!isset($groupedDefinitions[$def->category])) {
                $groupedDefinitions[$def->category] = [];
            }
            $groupedDefinitions[$def->category][] = $def;
        }

        return $groupedDefinitions;
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getCurrentUser()
    {
        return \User::findCurrent();
    }

    protected function viewerIsStudent()
    {
        return $this->viewerHasPerm('autor') && !$this->viewerHasPerm('dozent');
    }

    protected function viewerIsLecturer()
    {
        return $this->viewerHasPerm('dozent');
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function viewerHasPerm($perm)
    {
        $currentUserId = $GLOBALS['user'] ? $GLOBALS['user']->id : 'nobody';
        $currentContextId = \Context::getId();

        return $GLOBALS['perm']->have_studip_perm($perm, $currentContextId, $currentUserId);
    }

    protected function setDefaultPageTitle()
    {
        \PageLayout::setTitle(Context::getHeaderLine().' - Gradebook');
    }

    protected function getCategories(\Course $course)
    {
        return Definition::getCategoriesByCourse($course);
    }
}

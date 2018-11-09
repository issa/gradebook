<?php

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
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        $this->setDefaultPageTitle();
        \PageLayout::addStylesheet($this->plugin->getPluginURL() . '/gradebook.css');
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
}

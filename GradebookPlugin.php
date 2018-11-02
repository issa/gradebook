<?php

/**
 * Gradebook Stud.IP plugin.
 */
class GradebookPlugin extends StudIPPlugin implements
    /* Plugin Interfaces */
    SystemPlugin
{
    public function __construct()
    {
        parent::__construct();

        require_once 'vendor/autoload.php';
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     *
     * @return void
     */
    public function perform($unconsumedPath) {
        $args = explode('/', $unconsumedPath);

        $trailsRoot = $this->getPluginPath();
        $trailsUri  = rtrim(PluginEngine::getLink($this, [], null, true), '/');

        $dispatcher = new Trails_Dispatcher($trailsRoot, $trailsUri, 'index');
        $dispatcher->current_plugin = $this;
        try {
            $dispatcher->dispatch($unconsumedPath);
        } catch (Trails_UnknownAction $exception) {
            if (count($args) > 0) {
                throw $exception;
            } else {
                throw new Exception(_('unbekannte Plugin-Aktion: ') . $unconsumedPath);
            }
        }
    }

    /* Interface Methods */
}

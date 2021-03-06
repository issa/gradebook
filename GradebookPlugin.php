<?php

/**
 * Gradebook Stud.IP plugin.
 */
class GradebookPlugin extends StudIPPlugin implements SystemPlugin, StandardPlugin
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
     */
    public function perform($unconsumedPath)
    {
        $args = explode('/', $unconsumedPath);

        $trailsRoot = $this->getPluginPath();
        $trailsUri = rtrim(PluginEngine::getLink($this, [], null, true), '/');

        $dispatcher = new Trails_Dispatcher($trailsRoot, $trailsUri, 'gradebook/overview');
        $dispatcher->plugin = $this;
        try {
            $dispatcher->dispatch($unconsumedPath);
        } catch (Trails_UnknownAction $exception) {
            if (count($args) > 0) {
                throw $exception;
            } else {
                throw new Exception(_('unbekannte Plugin-Aktion: ').$unconsumedPath);
            }
        }
    }

    /**
     * Return a template (an instance of the Flexi_Template class)
     * to be rendered on the course summary page. Return NULL to
     * render nothing for this plugin.
     *
     * The template will automatically get a standard layout, which
     * can be configured via attributes set on the template:
     *
     *  title        title to display, defaults to plugin name
     *  icon_url     icon for this plugin (if any)
     *  admin_url    admin link for this plugin (if any)
     *  admin_title  title for admin link (default: Administration)
     *
     * @return object template object to render or NULL
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getInfoTemplate($courseId)
    {
        return null;
    }

    /**
     * Return a navigation object representing this plugin in the
     * course overview table or return NULL if you want to display
     * no icon for this plugin (or course). The navigation object's
     * title will not be shown, only the image (and its associated
     * attributes like 'title') and the URL are actually used.
     *
     * By convention, new or changed plugin content is indicated
     * by a different icon and a corresponding tooltip.
     *
     * @param string $course_id  course or institute range id
     * @param int    $last_visit time of user's last visit
     * @param string $user_id    the user to get the navigation for
     *
     * @return object navigation item to render or NULL
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getIconNavigation($courseId, $lastVisit, $userId)
    {
        return null;
    }

    /**
     * Return a navigation object representing this plugin in the
     * course overview table or return NULL if you want to display
     * no icon for this plugin (or course). The navigation object's
     * title will not be shown, only the image (and its associated
     * attributes like 'title') and the URL are actually used.
     *
     * By convention, new or changed plugin content is indicated
     * by a different icon and a corresponding tooltip.
     *
     * @param string $cid course or institute range id
     *
     * @return array navigation item to render or NULL
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getTabNavigation($cid)
    {
        $overviewURL = \PluginEngine::getURL($this, compact('cid'), '', true);
        $gradebook = new Navigation('Gradebook', $overviewURL);

        $gradebook->addSubNavigation('index', new Navigation(_('Übersicht'), $overviewURL));

        if ($GLOBALS['perm']->have_studip_perm('dozent', $cid)) {
            $this->addTabNavigationOfLecturers($gradebook, $cid);
        } elseif ($GLOBALS['perm']->have_studip_perm('student', $cid)) {
            $this->addTabNavigationOfStudents($gradebook, $cid);
        }

        return compact('gradebook');
    }

    private function addTabNavigationOfLecturers(\Navigation $navigation, $cid)
    {
        $exportURL = \PluginEngine::getURL($this, compact('cid'), 'gradebook/lecturers/export', true);
        $navigation->addSubNavigation('export', new Navigation(_('Export'), $exportURL));

        $weightsURL = \PluginEngine::getURL($this, compact('cid'), 'gradebook/lecturers/weights', true);
        $navigation->addSubNavigation('weights', new Navigation(_('Gewichtungen'), $weightsURL));

        $customURL = \PluginEngine::getURL($this, compact('cid'), 'gradebook/lecturers/custom_definitions', true);
        $navigation->addSubNavigation('custom_definitions', new Navigation(_('Noten manuell erfassen'), $customURL));
    }

    private function addTabNavigationOfStudents(\Navigation $navigation, $cid)
    {
        $exportURL = \PluginEngine::getURL($this, compact('cid'), 'gradebook/students/export', true);
        $navigation->addSubNavigation('export', new Navigation(_('Export'), $exportURL));
    }

    /**
     * Provides metadata like a descriptional text for this module that
     * is shown on the course "+" page to inform users about what the
     * module acutally does. Additionally, a URL can be specified.
     *
     * @return array metadata containg description and/or url
     */
    public function getMetadata()
    {
        return [];
    }
}

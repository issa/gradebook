<?php

require_once 'abstract_gradebook_controller.php';

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
        /*
        if (Navigation::hasItem('/course/mooc_courseware/index')) {
            Navigation::activateItem('/course/mooc_courseware/index');
        }
        */

        $this->render_text(__METHOD__);
    }
}

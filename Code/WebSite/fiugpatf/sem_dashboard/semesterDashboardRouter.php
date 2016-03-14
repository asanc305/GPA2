<?php
/**
 * Created by PhpStorm.
 * User: sproject
 * Date: 3/9/16
 * Time: 11:55 PM
 */

include_once 'semesterForecastController.php';
include_once 'semesterDashboardController.php';

class SemesterDashboardRouter {
    protected $action;

    public function __construct($action)
    {
        $this->action = $action;
        $this->route();
    }

    public function route()
    {
        $action = $this->action;

        switch ($action)
        {
            case "courseLegend":
                $controller = new SemesterDashboardController();
                $controller->$action();
                break;
            case "currentAssessments":
                $controller = new SemesterDashboardController();
                $controller->$action();
                break;
            case "GPAGoal":
                $controller = new SemesterForecastController();
                $controller->$action();
                break;
            case "takenAndRemaining":
                $controller = new SemesterForecastController();
                $controller->$action();
                break;
            case "getGraphData":
                $controller = new SemesterDashboardController();
                $controller->$action();
                break;
            case "gradesAndCredits":
                $controller = new SemesterForecastController();
                $controller->$action();
                break;
            case "modifyWeightAndRelevance":
                $controller = new SemesterForecastController();
                $controller->$action();
                break;
            case "currentCourses":
                $controller = new SemesterForecastController();
                $controller->$action();
                break;
        }
    }
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action = "";
}

$pageRouter = new SemesterDashboardRouter($action);


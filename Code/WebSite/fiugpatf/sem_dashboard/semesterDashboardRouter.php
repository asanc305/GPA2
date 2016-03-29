<?php
/**
 * Created by PhpStorm.
 * User: Lizette Mendoza
 * Date: 3/9/16
 * Time: 11:55 PM
 */

include_once 'semesterForecastController.php';
include_once 'semesterDashboardController.php';

class SemesterDashboardRouter {

    protected $session_name = 'sec_session_id';   // Set a custom session name
    protected $secure = false; // This stops JavaScript being able to access the session id.
    protected $httponly = true; // Forces sessions to only use cookies.
    protected $cookieParams;
    protected $action;

    public function __construct($action)
    {
        if (ini_set('session.use_only_cookies', 1) === FALSE) {
            header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
            exit();
        }

        $this->cookieParams = session_get_cookie_params();

        session_set_cookie_params($this->cookieParams["lifetime"],
            $this->cookieParams["path"],
            $this->cookieParams["domain"],
            $this->secure,
            $this->httponly);

        session_name($this->session_name);
        session_start();

        $this->action = $action;
        $this->route();
    }

    public function route()
    {
        $action = $this->action;

        switch ($action)
        {
            case "courseLegend":
                $controller = new SemesterDashboardController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "currentAssessments":
                $controller = new SemesterDashboardController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "GPAGoal":
                $controller = new SemesterForecastController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "takenAndRemaining":
                $controller = new SemesterForecastController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "getGraphData":
                $controller = new SemesterDashboardController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "gradesAndCredits":
                $controller = new SemesterForecastController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "modifyWeightAndRelevance":
                $controller = new SemesterForecastController($_SESSION['userID'], $_SESSION['username']);
                $controller->$action();
                break;
            case "currentCourses":
                $controller = new SemesterForecastController($_SESSION['userID'], $_SESSION['username']);
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


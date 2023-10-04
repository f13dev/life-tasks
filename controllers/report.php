<?php namespace F13\Life\Tasks\Controllers;

class Report
{
    public $request_method;
    
    public function __construct()
    {
        $this->request_method = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') ? INPUT_POST : INPUT_GET;
        add_shortcode('life-tasks-report', array($this, 'report'));
    }

    public function report()
    {
        $m = new \F13\Life\Tasks\Models\Tasks();
        $tasks = $m->select_all_tasks();
        $completion = $m->select_all_completion();

        $start_time = filter_input($this->request_method, 'start_time');
        $user_id = filter_input($this->request_method, 'user_id');

        $data = array(
            'daily' => array(),
            'weekly' => array(),
            'monthly' => array(),
            'frequency' => array(),
        );

        foreach ($tasks as $task) {
            if (empty($task->frequency)) {
                continue;
            }
            $data['frequency'][$task->id] = $task->frequency;
            $data[$data['frequency'][$task->id]][$task->id] = array();
            $data[$data['frequency'][$task->id]][$task->id]['completion'] = array();
            $data[$data['frequency'][$task->id]][$task->id]['task'] = $task;
        }

        foreach ($completion as $complete) {
            if (!array_key_exists($complete->task_id, $data['frequency'])) {
                continue;
            }
            $data[$data['frequency'][$complete->task_id]][$complete->task_id]['completion'][$complete->period_start] = $complete;
        }

        $v = new \F13\Life\Tasks\Views\Report(array(
            'contain' => (defined('DOING_AJAX') && DOING_AJAX) ? 0 : 1,
            'data' => $data,
            'start_time' => $start_time,
            'user_id' => $user_id,
        ));
        
        return (defined('DOING_AJAX') && 'DOING_AJAX') ? $v->report() : '<div id="life-tasks-master-container">'.$v->report().'</div>';
    }

    public function select_all_tasks()
    {
        $sql = "SELECT db.id, db.task, db.user_id, db.frequency, u.user_login
                FROM ".F13_LIFE_DB_TASKS." db
                LEFT JOIN ".$this->wpdb->base_prefix."users AS u ON (u.ID = db.user_id);";
        
        return $this->wpdb->get_results($sql);
    }

    public function select_all_completion()
    {
        $sql = "SELECT db.id, db.task_id, db.user_id, db.timestamp, db.period_start, db.complete, u.user_login
                FROM ".F13_LIFE_DB_TASK_COMPLETION." db
                LEFT JOIN ".$this->wpdb->base_prefix."users AS u ON (u.id = db.user_id);";
        
        return $this->wpdb->get_results($sql);
    }
}
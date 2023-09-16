<?php namespace F13\Life\Tasks\Controllers;

class Control
{
    public $request_method;

    public function __construct()
    {
        $this->request_method = ($_SERVER['REQUEST_METHOD'] === 'POST') ? INPUT_POST : INPUT_GET;
        add_filter('f13-life-header-cards', array($this, 'task_stats'), 10, 1);
        add_shortcode('life-tasks', array($this, 'tasks'));
        add_filter('f13-life-header-pages', array($this, 'menu'), 50, 1);
    }  

    public function menu($items) 
    {
        if (get_current_user_id()) {
            $items['tasks'] = array(
                'title' => __('Tasks', 'life-tasks'),
                'url' => site_url().'/tasks/',
            );
        }

        return $items;
    }

    public function task_stats($arr)
    {
        $m = new \F13\Life\Tasks\Models\Tasks();
        $count = $m->get_count_tasks_today();
        $v = '<div class="header-filter-row">';
            $v .= '<div id="header-tasks-card"><a href="/tasks-2">'.$count.' tasks due today</a></div>';
        $v .= '</div>';

        $arr[] = $v;

        return $arr;


        $v .= '<div class="header-filter-row">';
            $v .= '<span id="header-life-tasks">4 tasks due today</span>';
        $v .= '</div>';

        return $v;
    }

    public function tasks()
    {
        if (!get_current_user_id()) {
            wp_die('<div class="f13-notice f13-notice-error " style="">Your user account does not have permissions for this function.</div>');
        }
        
        $user_id = filter_input($this->request_method, 'user_id');
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }
        $m = new \F13\Life\Tasks\Models\Tasks();
        $data = $m->get_list_my_tasks($user_id);

        $v = new \F13\Life\Tasks\Views\Tasks(array(
            'data' => $data,
            'user_id' => $user_id,
        ));

        return (defined('DOING_AJAX' && 'DOING_AJAX')) ? $v->tasks() : '<div id="life-tasks-master-container">'.$v->tasks().'</div>';
    }

    public function delete_task()
    {
        $id = filter_input($this->request_method, 'id');
        \F13_verify_token('delete-task'.$id);

        $m = new \F13\Life\Tasks\Models\Tasks();
        if ($m->delete_task($id)) {
            $frequency = filter_input($this->request_method, 'frequency');
            $msg = \F13_notice(array(
                'text' => __('Task deleted', 'life-tasks'),
            ));
            $msg .= '<div class="f13-data-refresh-form" data-form="f13-life-tasks-'.$frequency.'"></div>';
        } else {
            $msg = \F13_notice(array(
                'text' => __('Error: The task could not be deleted', 'life-tasks'),
                'mode' => 'error',
            ));
        }

        return $msg;
    }

    public function new_task()
    {
        \F13_verify_token('new-task');

        $task = filter_input($this->request_method, 'task');
        $frequency = filter_input($this->request_method, 'frequency');
        $user = (int) filter_input($this->request_method, 'user');
        $submit = (int) filter_input($this->request_method, 'submit');

        $m = new \F13\Life\Tasks\Models\Tasks();

        $contain = true;
        $msg = '';

        if ($submit) {
            $contain = false;

            if (empty(trim($task))) {
                $msg = \F13_notice(array(
                    'text' => __('Please enter a task name', 'life-tasks'),
                    'type' => 'error',
                ));
            } else 
            if (empty($frequency)) {
                $msg = \F13_notice(array(
                    'text' => __('Please select a valid frequency', 'life-tasks'),
                    'type' => 'error',
                ));
            } else {
                $settings = array(
                    'task' => $task,
                    'user_id' => $user,
                    'frequency' => $frequency,
                );

                if ($m->insert_task($settings)) {
                    $msg = \F13_notice(array(
                        'text' => __('Task created', 'life-tasks'),
                        'close' => false,
                    ));
                    $msg .= '<div class="f13-data-refresh-form" data-form="f13-life-tasks-'.$frequency.'"></div>';
                    return $msg;
                }
            }
        }

        $v = new \F13\Life\Tasks\Views\Tasks(array(
            'task' => $task,
            'frequency' => $frequency,
            'user' => $user,
            'contain' => $contain,
            'msg' => $msg,
        ));

        return $v->new_task();
    }

    public function edit_task()
    {
        $id = filter_input($this->request_method, 'id');
        \F13_verify_token('edit-task'.$id);

        $m = new \F13\Life\Tasks\Models\Tasks();
        $data = $m->select_task($id);

        $submit = (int) filter_input($this->request_method, 'submit');
        $contain = true;
        $msg = '';

        if ($submit) {
            $contain = false;
            $task = filter_input($this->request_method, 'task');
            $frequency = filter_input($this->request_method, 'frequency');
            $user = (int) filter_input($this->request_method, 'user');

            if (empty(trim($task))) {
                $msg = \F13_notice(array(
                    'text' => __('Please enter a task name', 'life-tasks'),
                    'type' => 'error',
                ));
            } else 
            if (empty($frequency)) {
                $msg = \F13_notice(array(
                    'text' => __('Please select a valid frequency', 'life-tasks'),
                    'type' => 'error',
                ));
            } else {
                $settings = array(
                    'task' => $task,
                    'frequency' => $frequency,
                    'user_id' => $user,
                );

                $m->update_task($id, $settings);

                $msg = \F13_notice(array(
                    'text' => 'Task updated',
                ));
                $msg .= '<div class="f13-data-refresh-form" data-form="f13-life-tasks-'.$data->frequency.'"></div>';
                if ($data->frequency != $frequency) {
                    $msg .= '<div class="f13-data-refresh-form" data-form="f13-life-tasks-'.$frequency.'"></div>';
                }

                return $msg;
            }
        }

        $v = new \F13\Life\Tasks\Views\Tasks(array(
            'contain' => $contain,
            'data' => $data,
            'msg' => $msg,
        ));

        return $v->edit_task();
    }

    public function complete_task()
    {
        $id = filter_input($this->request_method, 'id');
        $frequency = filter_input($this->request_method, 'frequency');
        
        \F13_verify_token('complete-task'.$id);

        $complete = (int) filter_input($this->request_method, 'complete');

        $m = new \F13\Life\Tasks\Models\Tasks();

        $msg = ''; 
        
        if ($complete && $m->complete_task($id, $frequency)) {
            //$msg = \F13_notice(array(
            //    'text' => __('Task completed', 'life-tasks'),
            //));
        } else 
        if (!$complete && $m->restore_task($id, $frequency)) {
            //$msg = \F13_notice(array(
            //    'text' => __('Completion removed', 'life-tasks'),
            //));
        } else {
            $msg = \F13_notice(array(
                'text' => __('Error: Could not save data', 'life-tasks'),
                'type' => 'error',
            ));

            return $msg;
        }

        // Check completion percentage
        if ($m->get_count_tasks_period($frequency) == 0) {
            $msg .= '<div id="f13-life-tasks-success"></div>';
        }

        return $msg.'<div class="f13-data-refresh-form" data-form="f13-life-tasks-'.$frequency.'"></div>';
    }
}
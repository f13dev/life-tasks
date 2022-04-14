<?php namespace F13\Life\Tasks\Views;

class Tasks
{
    public $label_complete;
    public $label_complete_title;
    public $label_daily;
    public $label_delete;
    public $label_delete_confirm;
    public $label_delete_title;
    public $label_edit;
    public $label_edit_title;
    public $label_frequency;
    public $label_group;
    public $label_group_task;
    public $label_incomplete;
    public $label_monthly;
    public $label_my_task;
    public $label_new_task;
    public $label_owner;
    public $label_reset;
    public $label_reset_confirm;
    public $label_reset_title;
    public $label_save;
    public $label_signed;
    public $label_task;
    public $label_user;
    public $label_weekly;

    public function __construct($params = array())
    {
        $this->label_complete       = __('Complete', 'life-tasks');
        $this->label_complete_title = __('Mark task as complete', 'life-tasks');
        $this->label_daily          = __('Daily', 'life-tasks');
        $this->label_delete         = __('Delete', 'life-tasks');
        $this->label_delete_confirm = __('Do you really want to delete this task?', 'life-tasks');
        $this->label_delete_title   = __('Delete task', 'life-tasks');
        $this->label_edit           = __('Edit', 'life-tasks');
        $this->label_edit_title     = __('Edit task', 'life-tasks');
        $this->label_frequency      = __('Frequency', 'life-tasks');
        $this->label_group          = __('Group', 'life-tasks');
        $this->label_group_task     = __('Group task', 'life-tasks');
        $this->label_incomplete     = __('Incomplete', 'life-tasks');
        $this->label_monthly        = __('Monthly', 'life-tasks');
        $this->label_my_task        = __('My task', 'life-tasks');
        $this->label_new_task       = __('New task', 'life-tasks');
        $this->label_owner          = __('Owner', 'f13-tasks');
        $this->label_reset          = __('Reset', 'life-tasks');
        $this->label_reset_confirm  = __('Do you really want to reset this tasks completion?', 'life-tasks');
        $this->label_reset_title    = __('Reset completion', 'life-tasks');
        $this->label_save           = __('Save', 'life-tasks');
        $this->label_signed         = __('Signed', 'life-tasks');
        $this->label_task           = __('Task', 'life-tasks');
        $this->label_user           = __('User', 'life-tasks');
        $this->label_weekly         = __('Weekly', 'life-tasks');

        foreach ($params as $k=>$v) {
            $this->{$k} = $v;
        }
    }

    public function _container($content, $class) 
    {
        $v = '<div id="'.$class.'">';
            $v .= $content;
        $v .= '</div>';

        return $v;
    }

    public function _tasks_for_period($period)
    {
        $class = 'f13-data-table-'.$period;
        return \F13_table(array(
            'class_name' => $class,
            'data' => $this->data->{$period},
            'head' => \F13_popup(array(
                'label' => '<span class="dashicons dashicons-plus-alt life-tasks-btn-new"></span>',
                'title' => $this->label_new_task,
                'class_name' => 'f13-life-tasks-edit-task',
                'attr' => array(
                    'action' => 'f13-life-tasks-new-task',
                    'frequency' => $period,
                    'user' => get_current_user_id(),
                    'token' => wp_create_nonce('new-task'),
                ),
            )),
            'progress' => 'signed_user_login',
            'columns' => array(
                array(
                    'col' => 'task',
                    'db' => 'db.task',
                    'title' => $this->label_task,
                ),
                array(
                    'col' => 'user_id',
                    'db' => 'db.user_id',
                    'title' => $this->label_owner,
                    'callback' => array($this, 'callback_group_tasks'),
                    'width' => '15%',
                    'dropdown' => array(
                        '0' => $this->label_group_task,
                        '>0' => $this->label_my_task,
                    ),
                ),
                array(
                    'col' => 'signed_user_login',
                    'db' => 'u2.user_login',
                    'title' => $this->label_signed,
                    'callback' => array($this, 'callback_signed'),
                    'width' => '15%',
                    'dropdown' => array(
                        'NULL' => $this->label_incomplete,
                        '>0' => $this->label_complete,
                    ),
                    'no_sort' => true,
                ),
                array(
                    'col' => 'update',
                    'db' => '',
                    'title' => '',
                    'callback' => array($this, 'callback_update'),
                    'width' => '100px',
                ),
                array(
                    'col' => 'actions',
                    'db' => '',
                    'title' => '',
                    'callback' => array($this, 'callback_actions'),
                    'width' => '20px;',
                )
            ),
            'attributes' => array(
                'show_search' => true,
            ),                                                  
            'title' => __(ucfirst(esc_attr($period)).' tasks', 'f13-life-dms'),
            'table_id' => 'f13-life-tasks-'.esc_attr($period),
        ));
    }

    public function callback_actions($row)
    {
        $actions = array();

        $actions[] = \F13_popup(array(
            'label' => $this->label_edit,
            'title' => $this->label_edit_title,
            'class_name' => 'f13-life-tasks-edit-task',
            'icon' => 'edit',
            'attr' => array(
                'action' => 'f13-life-tasks-edit-task',
                'id' => $row->id,
                'token' => wp_create_nonce('edit-task'.$row->id),
            ),
        ));

        // Delete action
        $actions[] = \F13_ajax_link(array(
            'label' => $this->label_delete,
            'title' => $this->label_delete_title,
            'confirm' =>$this->label_delete_confirm,
            'target' => 'f13-life-tasks-'.$row->frequency.'-notice',
            'method' => 'get',
            'icon' => 'delete',
            'attr' => array(
                'action' => 'f13-life-tasks-delete-task',
                'id' => $row->id,
                'frequency' => $row->frequency,
                'token' => wp_create_nonce('delete-task'.$row->id),
            ),
        ));

        return F13_actions(array(
            'links' => $actions,
        ));
        return 'X';
    }

    public function callback_signed($row)
    {
        if ($row->signed_user_login) {
            return '<span class="f13-tasks-complete">'.ucfirst($row->signed_user_login).'</span>';
        }
        return '<span class="f13-tasks-incomplete">-</span>';
        return ucfirst($row->signed_user_login);
    }

    public function callback_frequency($row)
    {
        return ucfirst($row->frequency);
    }

    public function callback_group_tasks($row)
    {
        if ($row->user_id == 0) {
            return $this->label_group;
        } else 
        if ($row->user_id == get_current_user_id()) {
            return $this->label_my_task;
        }
        return 'Unknown';
    }

    public function callback_update($row)
    {
        if (!$row->signed_user_id) {
            // Complete action
            return \F13_ajax_link(array(
                'label' => $this->label_complete,
                'title' => $this->label_complete_title,
                'target' => 'f13-life-tasks-'.$row->frequency.'-notice',
                'method' => 'get',
                'attr' => array(
                    'action' => 'f13-life-tasks-complete-task',
                    'id' => $row->id,
                    'frequency' => $row->frequency,
                    'complete' => '1',
                    'token' => wp_create_nonce('complete-task'.$row->id),
                ),
            ));
        } else {
            // Uncomplete action
            return \F13_ajax_link(array(
                'label' => $this->label_reset,
                'title' => $this->label_reset_title,
                'confirm' => $this->label_reset_confirm,
                'target' => 'f13-life-tasks-'.$row->frequency.'-notice',
                'method' => 'get',
                'attr' => array(
                    'action' => 'f13-life-tasks-complete-task',
                    'id' => $row->id,
                    'frequency' => $row->frequency,
                    'complete' => '0',
                    'token' => wp_create_nonce('complete-task'.$row->id),
                ),
            ));
        }
    }

    public function edit_task()
    {
        $class="f13-life-tasks-edit-task";

        $v = '<form class="f13-ajax-form" data-target="'.$class.'">';
            $v .= '<input type="hidden" name="action" value="f13-life-tasks-edit-task">';  
            $v .= '<input type="hidden" name="id" value="'.$this->data->id.'">';
            $v .= '<input type="hidden" name="submit" value="1">';
            $v .= '<input type="hidden" name="token" value="'.wp_create_nonce('edit-task'.$this->data->id).'">';
            
            $v .= $this->msg;

            $v .= '<div class="f13-form-field">';
                $v .= '<label for="task">'.$this->label_task.'</label>';
                $v .= '<input type="text" name="task" id="task" value="'.esc_attr($this->data->task).'">';
            $v .= '</div>';

            $v .= '<div class="f13-form-field">';
                $v .= '<label for="frequency">'.$this->label_frequency.'</label>';
                $v .= '<select name="frequency" id="frequency">';
                    $v .= '<option value="daily" '.($this->data->frequency == 'daily' ? 'selected="selected"' : '').'>'.$this->label_daily.'</option>';
                    $v .= '<option value="weekly" '.($this->data->frequency == 'weekly' ? 'selected="selected"' : '').'>'.$this->label_weekly.'</option>';
                    $v .= '<option value="monthly" '.($this->data->frequency == 'monthly' ? 'selected="selected"' : '').'>'.$this->label_monthly.'</option>';
                $v .= '</select>';
            $v .= '</div>';

            $v .= '<div class="f13-form-field">';
                $v .= '<label for="user">'.$this->label_user.'</label>';
                $v .= '<select name="user" id="user">';
                    $v .= '<option value="0">'.$this->label_group_task.'</option>';
                    $users = get_users('orderby=nicename');
                    foreach ($users as $user) {
                        $v .= '<option value="'.$user->ID.'" '.($this->data->user_id == $user->ID ? 'selected="selected"' : '').'>'.ucfirst($user->user_login).'</option>';
                    }
                $v .= '</select>';
            $v .= '</div>';

            $v .= '<div class="f13-form-submit">';
                $v .= '<input type="submit" value="'.$this->label_save.'">';
            $v .= '</div>';

        $v .= '</form>'; 

        return ($this->contain) ? $this->_container($v, $class) : $v;
    }

    public function new_task()
    {
        $class = 'f13-life-tasks-new-task';

        $v = '<form class="f13-ajax-form" data-target="'.$class.'">';
            $v .= '<input type="hidden" name="action" value="f13-life-tasks-new-task">';
            $v .= '<input type="hidden" name="submit" value="1">';
            $v .= '<input type="hidden" name="token" value="'.wp_create_nonce('new-task').'">';

            $v .= $this->msg;

            $v .= '<div class="f13-form-field">';
                $v .= '<label for="task">'.$this->label_task.'</label>';
                $v .= '<input type="text" name="task" id="task" value="'.esc_attr($this->task).'">';
            $v .= '</div>';

            $v .= '<div class="f13-form-field">';
                $v .= '<label class="frequency">'.$this->label_frequency.'</label>';
                $v .= '<select name="frequency" id="frequency">';
                    $v .= '<option value="daily" '.($this->frequency == 'daily' ? 'selected="selected"' : '').'>'.$this->label_daily.'</option>';
                    $v .= '<option value="weekly" '.($this->frequency == 'weekly' ? 'selected="selected"' : '').'>'.$this->label_weekly.'</option>';
                    $v .= '<option value="monthly" '.($this->frequency == 'monthly' ? 'selected="selected"' : '').'>'.$this->label_monthly.'</option>';
                $v .= '</select>';
            $v .= '</div>';

            $v .= '<div class="f13-form-field">';
                $v .= '<label for="user">'.$this->label_user.'</label>';
                $v .= '<select name="user" id="user">';
                    $v .= '<option value="0">'.$this->label_group_task.'</option>';
                    $users = get_users('orderby=nicename');
                    foreach ($users as $user) {
                        $v .= '<option value="'.$user->ID.'" '.($this->user == $user->ID ? 'selected="selected"' : '').'>'.ucfirst($user->user_login).'</option>';
                    }
                $v .= '</select>';
            $v .= '</div>';
            
            $v .= '<div class="f13-form-submit">';
                $v .= '<input type="submit" value="'.$this->label_save.'">';
            $v .= '</div>';

        $v .= '</form>';

        return ($this->contain) ? $this->_container($v, $class) : $v;
    }

    public function tasks()
    {
        $v = '<form class="f13-ajax-form" data-target="life-tasks-master-container">';
            $v .= '<input type="hidden" name="action" value="f13-life-tasks-load">';
            $v .= '<select name="user_id" id="life-tasks-user-select">';
                $users = get_users('orderby=nicename');
                foreach ($users as $user) {
                    $v .= '<option value="'.$user->ID.'" '.($this->user_id == $user->ID ? 'selected="selected"' : '').'>'.ucfirst($user->user_login).'</option>';
                }
            $v .= '</select>';
        $v .= '</form>';
        // Create jquery on change submit form
        $v .= '<div class="f13-life-tasks-daily-table">'.$this->_tasks_for_period('daily').'</div>';
        $v .= '<div class="f13-life-tasks-weekly-table">'.$this->_tasks_for_period('weekly').'</div>';
        $v .= '<div class="f13-life-tasks-monthly-table">'.$this->_tasks_for_period('monthly').'</div>';

        return $v;
    }

    public function success()
    {
        $v = '<div id="f13-life-tasks-success"></div>';

        return $v;
    }
}
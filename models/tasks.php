<?php namespace F13\Life\Tasks\Models;

class Tasks
{
    public $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function get_list_my_tasks($user_id)
    {
        $return = new \stdClass();
        $return->daily = $this->_get_list_my_tasks_for_period($user_id, 'daily', F13_LIFE_TASKS_DAY_START);
        $return->weekly = $this->_get_list_my_tasks_for_period($user_id, 'weekly', F13_LIFE_TASKS_WEEK_START);
        $return->monthly = $this->_get_list_my_tasks_for_period($user_id, 'monthly', F13_LIFE_TASKS_MONTH_START);

        return $return;
    }

    public function select_task($id) 
    {
        $sql = "SELECT db.id, db.task, db.user_id, db.frequency
                FROM ".F13_LIFE_DB_TASKS." db
                WHERE db.id = %d;";
                
        return $this->wpdb->get_row($this->wpdb->prepare($sql, $id)); 
    }

    public function insert_task($settings)
    {
        if (array_key_exists('task', $settings) && array_key_exists('frequency', $settings) && array_key_exists('user_id', $settings)) {
            $sql = "INSERT INTO ".F13_LIFE_DB_TASKS."
                        (task, frequency, user_id)
                    VALUES
                        (%s, %s, %d);";

            return $this->wpdb->query($this->wpdb->prepare($sql, $settings['task'], $settings['frequency'], $settings['user_id']));
        }

        // Frequency not working for some reason!
        
        $fields = array(
            'task' => '%s',
            'frequency' => '%s',
            'user_id' => '%d',
        );

        $insert = array_intersect_key($settings, $fields);

        return $this->wpdb->insert(F13_LIFE_DB_TASKS, $insert, $fields);
    }

    public function update_task($id, $settings) 
    {
        $db_fields = array(
            'task' => '%s',
            'frequency' => '%s',
            'user_id' => '%d',
        );
        $data = stripslashes_deep(array_intersect_key($settings, $db_fields));

        $format = array();
        foreach ($data as $field => $value) {
            $format[$field] = $db_fields[$field];
        }

        $where = array( 'id' => $id);

        $where_format = array( '%d' );

        return $this->wpdb->update(F13_LIFE_DB_TASKS, $data, $where, $format, $where_format);
    }

    public function delete_task($id)
    {
        $sql = "DELETE FROM ".F13_LIFE_DB_TASKS." 
                WHERE id = %d;";

        return $this->wpdb->query($this->wpdb->prepare($sql, $id));
    }

    public function complete_task($id, $frequency)
    {
        switch ($frequency) {
            case 'daily': 
                $start = F13_LIFE_TASKS_DAY_START;
                break;
            case 'weekly':
                $start = F13_LIFE_TASKS_WEEK_START;
                break;
            case 'monthly':
                $start = F13_LIFE_TASKS_MONTH_START;
                break;
            default: $start = '';
        }

        $sql = "INSERT INTO ".F13_LIFE_DB_TASK_COMPLETION."
                    (task_id, user_id, timestamp, period_start, complete)
                VALUES
                    (%d, %d, %d, %d, %d);";

        return $this->wpdb->query($this->wpdb->prepare($sql, $id, get_current_user_id(), time(), $start, 1));
    }

    public function restore_task($id, $frequency)
    {
        switch ($frequency) {
            case 'daily': 
                $start = F13_LIFE_TASKS_DAY_START;
                break;
            case 'weekly':
                $start = F13_LIFE_TASKS_WEEK_START;
                break;
            case 'monthly':
                $start = F13_LIFE_TASKS_MONTH_START;
                break;
            default: $start = '';
        }

        $sql = "DELETE FROM ".F13_LIFE_DB_TASK_COMPLETION."
                WHERE task_id = %d AND period_start = %d;";

        return $this->wpdb->query($this->wpdb->prepare($sql, $id, $start));
    }

    public function get_count_tasks_today()
    {
        $sql = "SELECT count(db.id)
                FROM ".F13_LIFE_DB_TASKS." db
                LEFT JOIN ".F13_LIFE_DB_TASK_COMPLETION." AS tc ON (tc.task_id = db.id AND tc.period_start = '".F13_LIFE_TASKS_DAY_START."')
                WHERE (db.user_id = '".get_current_user_id()."' || db.user_id = '0')
                AND db.frequency = 'daily'
                AND tc.timestamp IS NULL;";
        $daily = $this->wpdb->get_var($sql);

        $sql = "SELECT count(db.id)
                FROM ".F13_LIFE_DB_TASKS." db
                LEFT JOIN ".F13_LIFE_DB_TASK_COMPLETION." AS tc ON (tc.task_id = db.id AND tc.period_start = '".F13_LIFE_TASKS_WEEK_START."')
                WHERE (db.user_id = '".get_current_user_id()."' || db.user_id = '0')
                AND db.frequency = 'weekly'
                AND tc.timestamp IS NULL;";
        $weekly = $this->wpdb->get_var($sql);

        $sql = "SELECT count(db.id)
                FROM ".F13_LIFE_DB_TASKS." db
                LEFT JOIN ".F13_LIFE_DB_TASK_COMPLETION." AS tc ON (tc.task_id = db.id AND tc.period_start = '".F13_LIFE_TASKS_MONTH_START."')
                WHERE (db.user_id = '".get_current_user_id()."' || db.user_id = '0')
                AND db.frequency = 'monthly'
                AND tc.timestamp IS NULL;";
        $month = $this->wpdb->get_var($sql);

        return $daily + $weekly + $month;
    }

    public function _get_list_my_tasks_for_period($user_id, $frequency, $period_start) 
    {
        return \F13_list(array(
            'columns' => array(
                'db.id',
                'db.task',
                'db.user_id',
                'db.frequency',
                'tc.user_id AS signed_user_id',
                'tc.timestamp',
                'u1.user_login',
                'u2.user_login AS signed_user_login',
            ),
            'table' => F13_LIFE_DB_TASKS.' db',
            'left_join' => array(
                F13_LIFE_DB_TASK_COMPLETION.' AS tc ON (tc.task_id = db.id AND  tc.period_start = '.esc_attr($period_start).')',
                $this->wpdb->base_prefix.'users AS u1 ON (u1.ID = db.user_id)',
                $this->wpdb->base_prefix.'users AS u2 ON (u2.ID = tc.user_id)',
            ),
            'where' => array(
                '(db.user_id = "'.$user_id.'" || db.user_id = "0")',
                'db.frequency = "'.esc_attr($frequency).'"',
            ),
            'order_by' => array(
                'db.task ASC',
            ),
            'limit' => 0,
        ));
    }
}
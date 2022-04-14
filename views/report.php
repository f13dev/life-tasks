<?php namespace F13\Life\Tasks\Views;

class Report
{
    public function __construct($params = array()) 
    {
        foreach ($params as $k=>$v) {
            $this->{$k} = $v;
        }
    }

    public function report()
    {
        $v = $this->_load_chart();
        $v .= $this->_user_select();
        $v .= $this->_graph('daily');
        $v .= $this->_graph('weekly');
        $v .= $this->_graph('monthly');
        $v .= $this->_chart('daily');
        $v .= $this->_chart('weekly');
        $v .= $this->_chart('monthly');

        return $v;
    }

    public function _user_select()
    {
        $users = get_users('orderby=nicename');
        $v = '<form class="f13-ajax-form" data-target="life-tasks-master-container">';
            $v .= '<input type="hidden" name="action" value="f13-life-tasks-report-load">';
                $v .= '<div class="f13-row">';
                    $v .= '<div class="f13-col-6">';
                        $v .= '<select name="user_id" id="life-tasks-user-select">';
                            $v .= '<option value="0">'.__('All users', 'life-tasks').'</option>';
                            foreach ($users as $user) {
                                $v .= '<option value="'.$user->ID.'" '.($this->user_id == $user->ID ? 'selected="selected"' : '').'>'.ucfirst($user->user_login).'</option>';
                            }   
                        $v .= '</select>';
                    $v .= '</div>';
                    $v .= '<div class="f13-col-6">';
                        $v .= '<select name="start_time" id="life-tasks-start-time-select">';
                            $v .= '<option value="recent" '.($this->start_time == 'recent' ? 'selected="selected"' : '').'>'.__('Recent data', 'life-tasks').'</option>';
                            $v .= '<option value="all" '.($this->start_time == 'all' ? 'selected="selected"' : '').'>'.__('All data', 'life-tasks').'</option>';
                        $v .= '</select>';
                    $v .= '</div>';
                $v .= '</div>';
        $v .= '</form>';

        return $v;
    }

    public function _load_chart()
    {
        $v = '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';

        return $v;
    }

    public function _graph($frequency = 'daily')
    {
        switch($frequency) {
            case 'daily':
                $start = strtotime("-30 days 00:00:00");
                $units = 31;
                break;
            case 'weekly':
                $start = strtotime('last sunday 00:00:00');
                $start = strtotime('-11 weeks', $start);
                $units = 12;
                break;
            case 'monthly':
                $start = strtotime('first day of -11 months 00:00:00');
                $units = 12;
                break;        
        }

        if (!isset($start)) {
            return;
        }

        $v = '<div class="task-row">';
            foreach ($this->data[$frequency] as $task_id => $task) {
                $frequency_start = $start;
                while ($frequency_start < time()) {
                    $completion = false;
                    if (array_key_exists($frequency_start, $this->data[$frequency][$task_id]['completion']) && $this->data[$frequency][$task_id]['completion'][$frequency_start]->complete) {
                        $completion = true;
                    }

                    $v .= '<div class="task-completion '.($completion ? 'task-completion-complete' : '').'" style="width: '.(100 / $units).'%;">';
                        $v .= '<div class="hover">';
                            $v .= '<strong>'.__('Task', 'life-tasks').': </strong>'.$task['task']->task.'<br>';
                            $v .= '<strong>'.__('Date', 'life-tasks').': </strong>'.date('j F Y', $frequency_start).'<br>';
                            $v .= '<strong>'.__('Complete', 'life-tasks').': </strong>'.($completion ? ucfirst($task['task']->user_login) : '-');

                        $v .= '</div>';
                    $v .= '</div>';
                    switch ($frequency) {
                        case 'daily': 
                            $frequency_start = strtotime("+1 day", $frequency_start);
                            break;
                        case 'weekly':
                            $frequency_start = strtotime("+1 week", $frequency_start);
                            break;
                        case 'monthly':
                            $frequency_start = strtotime("+1 month", $frequency_start);
                            break;
                    }
                }
            }
        $v .= '</div>';
        $v .= '<div class="task-row task-row-dates">';
            $frequency_start = $start;
            while ($frequency_start < time()) {
                $v .= '<div class="task-completion task-completion-date" style="width: '.(100 / $units).'%; '.($units == 12 ? '' : '').'">';
                    $v .= '<span>'.date('j M Y', $frequency_start).'</span>';
                $v .= '</div>';
                switch ($frequency) {
                    case 'daily': 
                        $frequency_start = strtotime("+1 day", $frequency_start);
                        break;
                    case 'weekly':
                        $frequency_start = strtotime("+1 week", $frequency_start);
                        break;
                    case 'monthly':
                        $frequency_start = strtotime("+1 month", $frequency_start);
                        break;
                }
            }
        $v .= '</div>';

        return $v;
    }

    public function _chart($frequency = 'daily') 
    {
        switch ($frequency) {
            case 'daily':
                $start = strtotime("-1 month 00:00:00");
                break;
            case 'weekly':
                $start = strtotime('last sunday 00:00:00');
                $start = strtotime('-11 weeks', $start);
                break;
            case 'monthly':
                $start = strtotime('first day of -12 months 00:00:00');
                break;
        }

        $v = '<script type="text/javascript">';
            $v .= 'google.charts.load(\'current\', {\'packages\':[\'corechart\']});';
            $v .= 'google.charts.setOnLoadCallback(drawChart);';

            $v .= 'function drawChart() {';

                $v .= 'var data = google.visualization.arrayToDataTable([';
                    $v .= '[\'Date\',';
                        foreach ($this->data[$frequency] as $task) {
                            $v .= '\''.$task['task']->task.'\','; 
                        }
                        $v = trim($v, ',');
                    $v .= '],';
                    while ($start < time()) {
                        $v .= '[\''.date('Y-m-d', $start).'\',';
                            foreach ($this->data[$frequency] as $task_id => $task) {
                                if (array_key_exists($start, $this->data[$frequency][$task_id]['completion'])) {
                                    $v .= '1,';
                                } else {
                                    $v .= '0,';
                                }
                            }
                            $v = trim($v, ',');
                        $v .= '],';
                        switch ($frequency) {
                            case 'daily': 
                                $start = strtotime("+1 day", $start);
                                break;
                            case 'weekly':
                                $start = strtotime("+1 week", $start);
                                break;
                            case 'monthly':
                                $start = strtotime("+1 month", $start);
                            }
                    }
                    $v = trim($v, ',');
                $v .= ']);';

                $v .= 'var options = {';
                    $v .= 'title: \''.ucfirst($frequency).' report\',';
                    $v .= 'vAxis: {title: \'Tasks\'},';
                    $v .= 'isStacked: true';
                $v .= '};';
          
                $v .= 'var chart = new google.visualization.SteppedAreaChart(document.getElementById(\'chart_div_'.$frequency.'\'));';
          
                $v .= 'chart.draw(data, options);';
            $v .= '}';
        $v .= '</script>';

        $v .= '<div id="chart_div_'.$frequency.'" style="width: 100%; height: 250px;"></div>';

        return $v;
    }
}
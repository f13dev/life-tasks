<?php
/*
Plugin Name: Life Tasks
Plugin URI: http://www.f13dev.com
Description: Life Task Management System
Version: 0.1
Author: Jim Valentine
Author URI: http://www.f13dev.com
Text Domain: F13Dev
*/

namespace F13\Life\Tasks;

// General plugin defines
if (!isset($wpdb)) global $wpdb;
if (!function_exists('get_plugins')) require_once(ABSPATH.'wp-admin/includes/plugin.php');
if (!defined('F13_LIFE_TASKS')) define('F13_LIFE_TASKS', get_plugin_data(__FILE__, false, false));
if (!defined('F13_LIFE_TASKS_PATH')) define('F13_LIFE_TASKS_PATH', plugin_dir_path( __FILE__ ));
if (!defined('F13_LIFE_TASKS_URL')) define('F13_LIFE_TASKS_URL', plugin_dir_url(__FILE__));

// Define range timestamps
if (!defined('F13_LIFE_TASKS_DAY_START')) define('F13_LIFE_TASKS_DAY_START', strtotime('today 00:00:00'));
if (!defined('F13_LIFE_TASKS_DAY_END')) define('F13_LIFE_TASKS_DAY_END', strtotime('today 23:59:59'));
if (!defined('F13_LIFE_TASKS_WEEK_START')) define('F13_LIFE_TASKS_WEEK_START', strtotime('last sunday 00:00:00'));
if (!defined('F13_LIFE_TASKS_WEEK_END')) define('F13_LIFE_TASKS_WEEK_END', strtotime('next sunday 23:59:59'));
if (!defined('F13_LIFE_TASKS_MONTH_START')) define('F13_LIFE_TASKS_MONTH_START', strtotime('first day of this month 00:00:00'));
if (!defined('F13_LIFE_TASKS_MONTH_END')) define('F13_LIFE_TASKS_MONTH_END', strtotime('last day of this month 00:00:00'));

// Define database tables
if (!defined('F13_LIFE_DB_TASKS')) define('F13_LIFE_DB_TASKS', $wpdb->base_prefix.'f13_life_tasks');
if (!defined('F13_LIFE_DB_TASK_COMPLETION')) define('F13_LIFE_DB_TASK_COMPLETION', $wpdb->base_prefix.'f13_life_task_completion');

register_activation_hook(__FILE__, array('\F13\Life\Tasks\Plugin', 'install'));

class Plugin
{
    public function init()
    {
        spl_autoload_register(__NAMESPACE__.'\Plugin::loader');
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));

        new Controllers\Control();

        if (defined('DOING_AJAX') && DOING_AJAX) {
            new Controllers\Ajax();
        }
    }

    public static function loader($name)
    {
        $name = trim(ltrim($name, '\\'));
        if (strpos($name, __NAMESPACE__) !== 0) {
            return;
        }
        $file = str_replace(__NAMESPACE__, '', $name);
        $file = ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
        $file = plugin_dir_path(__FILE__).strtolower($file).'.php';

        if ($file !== realpath($file) || !file_exists($file)) {
            wp_die('Class not found: '.htmlentities($name));
        } else {
            require_once $file;
        }
    }

    public static function install()
    {
        $c = new Controllers\Install();
        return $c->database();
    }

    public function enqueue()
    {
        wp_enqueue_style('f13-tasks', F13_LIFE_TASKS_URL.'css/life-tasks.css', array(), F13_LIFE_TASKS['Version']);
        wp_enqueue_script('f13-tasks', F13_LIFE_TASKS_URL.'js/life-tasks.js', array('jquery'), F13_LIFE_TASKS['Version']);

    }
}

$p = new Plugin();
$p->init();
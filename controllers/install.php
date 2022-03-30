<?php namespace F13\Life\Tasks\Controllers;

class Install
{
    public function database()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE ".F13_LIFE_DB_TASKS." (
            id INT(8) NOT NULL AUTO_INCREMENT,
            task TEXT NOT NULL,
            user_id INT(8) NOT NULL DEFAULT '0',
            frequency ENUM('daily','weekly','monthly') NOT NULL,
            PRIMARY KEY (id)
        ) ".$charset_collate.";";

        dbDelta($sql);

        $sql = "CREATE TABLE ".F13_LIFE_DB_TASK_COMPLETION." (
            id int NOT NULL AUTO_INCREMENT,
            task_id INT(8) NOT NULL, 
            user_id INT(8) NOT NULL,
            timestamp INT(16) NOT NULL,
            period_start INT(16) NOT NULL,
            complete TINYINT(1) NOT NULL DEFAULT '1',
            PRIMARY KEY (id)  
        ) ".$charset_collate.";";

        dbDelta($sql);
    }
}
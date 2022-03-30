<?php namespace F13\Life\Tasks\Controllers;

class Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_f13-life-tasks-load', array($this, 'load'));
        add_action('wp_ajax_f13-life-tasks-edit-task', array($this, 'edit_task'));
        add_action('wp_ajax_f13-life-tasks-complete-task', array($this, 'complete_task'));
        add_action('wp_ajax_f13-life-tasks-new-task', array($this, 'new_task'));
        add_action('wp_ajax_f13-life-tasks-delete-task', array($this, 'delete_task'));
    }   

    public function load() { $c = new Control(); echo $c->tasks(); die; }
    public function edit_task() { $c = new Control(); echo $c->edit_task(); die; }
    public function complete_task() { $c = new Control(); echo $c->complete_task(); die; }
    public function new_task() { $c = new Control(); echo $c->new_task(); die; }
    public function delete_task() { $c = new Control(); echo $c->delete_task(); die; }
}
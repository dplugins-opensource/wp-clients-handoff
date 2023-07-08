<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WPCH_main {
    public function __construct(){
        add_action('admin_menu', array($this, 'WPCH_DB_tables'));
        add_action('wp_dashboard_setup', array($this, 'WPCH_register_admin_widget'));
        add_action('wp_ajax_WPCH_saveTask', array($this, 'WPCH_saveTask'));
        add_action('wp_ajax_WPCH_getTasks', array($this, 'WPCH_getTasks'));
    }

    public function WPCH_DB_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $wpch_tasks = $wpdb->prefix . "wpch_tasks";
        $sql = "CREATE TABLE " . $wpch_tasks . " (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          name TEXT DEFAULT '' NOT NULL,
          completed bigint(20) DEFAULT '0' NOT NULL,
          task_order bigint(20) DEFAULT '0' NOT NULL,
          PRIMARY KEY (id)
        ) " . $charset_collate . ";";
        dbDelta($sql);
    }

    public function WPCH_register_admin_widget()
    {
        wp_add_dashboard_widget(
            'wpch_clients_handoff_widget',  // Widget ID
            'Website Todo list',  // Widget title
            array($this, 'WPCH_custom_admin_widget_content')  // Callback function to display widget content
        );

        // Enqueue the custom CSS for the widget
        wp_enqueue_style('WPCH-clients-handoff-widget-style', WPCH_URL . 'css/todo-list.css', WPCH_PLUGINVERSION);
        wp_enqueue_script('WPCH-alpine', 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js', array(), WPCH_PLUGINVERSION, true);
        wp_register_script('WPCH_ajax_scripts', WPCH_URL . 'js/admin.js', array('jquery'), WPCH_PLUGINVERSION);
        wp_localize_script('WPCH_ajax_scripts', 'WPCH_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'WPCH_nonce' => wp_create_nonce('ajax-nonce')
        ));
        wp_enqueue_script('WPCH_ajax_scripts');

        // Retrieve tasks and pass them to JavaScript
        $tasks = $this->getTasks();
        wp_localize_script('WPCH_ajax_scripts', 'WPCH_tasks', $tasks);
    }

    public function WPCH_custom_admin_widget_content()
    {
        // Widget content goes here
        ?>

<div x-data="{
    tasks: WPCH_tasks,
    newTask: '',
    addTask() {
        if (this.newTask.trim() !== '') {
            this.tasks.push({
                name: this.newTask,
                completed: false,
                isEditing: false,
            });
            this.newTask = '';
            this.saveTasks();
        }
    },
    deleteTask(index) {
        this.tasks.splice(index, 1);
        this.saveTasks();
    },
    renameTask(task) {
        task.isEditing = true;
    },
    saveTask(task) {
        task.isEditing = false;
        this.saveTasks();
    },
    saveTasks() {
        const tasksData = this.tasks.map((task, index) => ({
            name: task.name,
            completed: task.completed,
            task_order: index + 1
        }));

        const formData = new FormData();
        formData.append('action', 'WPCH_saveTask');
        formData.append('tasks', JSON.stringify(tasksData));
        formData.append('verify_nonce', WPCH_ajax.WPCH_nonce);

        fetch(WPCH_ajax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                console.log('Tasks saved successfully.');
            } else {
                console.error('Failed to save tasks.');
            }
        })
        .catch(error => {
            console.error('An error occurred:', error);
        });
    },
}">
    <div id="add-task">
        <input type="text" x-model="newTask" placeholder="Enter a new task" x-on:keydown.enter="addTask">
    </div>

    <ul class="tasks-list">
        <template x-for="(task, index) in tasks" :key="index">
            <li>
                <input type="checkbox" x-model="task.completed">

                <span class="task" x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
                <input class="task-edit" type="text" x-show="task.isEditing" x-model="task.name" x-on:keydown.enter="saveTask(task)">

                <button x-show="!task.isEditing" x-on:click="renameTask(task)">Rename</button>
                <button x-show="task.isEditing" x-on:click="saveTask(task)">Save</button>
                <button x-on:click="deleteTask(index)">Delete</button>
            </li>
        </template>
    </ul>
</div>


    <?php
    }

    public function WPCH_saveTask()
    {
        check_ajax_referer('ajax-nonce', 'verify_nonce');
        $tasksData = json_decode(stripslashes($_POST['tasks']), true);

        global $wpdb;
        $wpch_tasks = $wpdb->prefix . "wpch_tasks";
        $wpdb->query("TRUNCATE TABLE $wpch_tasks");

        foreach ($tasksData as $taskData) {
            $name = sanitize_text_field($taskData['name']);
            $completed = absint($taskData['completed']);
            $task_order = absint($taskData['task_order']);

            $wpdb->insert($wpch_tasks, array(
                'name' => $name,
                'completed' => $completed,
                'task_order' => $task_order
            ));
        }

        wp_die();
    }

    public function WPCH_getTasks()
    {
        check_ajax_referer('ajax-nonce', 'verify_nonce');
        $tasks = $this->getTasks();
        wp_send_json($tasks);
    }

    public function getTasks()
    {
        global $wpdb;
        $wpch_tasks = $wpdb->prefix . "wpch_tasks";
        $tasks = $wpdb->get_results("SELECT * FROM $wpch_tasks ORDER BY task_order ASC", ARRAY_A);
        return $tasks ? $tasks : array();
    }
}

new WPCH_main();

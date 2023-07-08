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
    add_action('wp_print_scripts', function() use ($tasks) {
        echo "<script>var WPCH_tasks = " . wp_json_encode($tasks) . ";</script>";
    });
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
                    completed: task.completed ? '1' : '0',
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
            init() {
                this.tasks.forEach(task => {
    task.completed = task.completed === '1'; // Convert the completed value to boolean
    task.isEditing = false;
    
    // Add the 'status completed' class if the task is completed
    task.statusClass = task.completed ? 'status completed' : 'status';
});


}

        }" x-init="init">
            <div id="add-task">
                <input type="text" x-model="newTask" placeholder="Enter a new task" x-on:keydown.enter="addTask">
            </div>
    
            <ul class="tasks-list">
                <template x-for="(task, index) in tasks" :key="index">
                    <li>
                    <input type="checkbox" x-model="task.completed" x-bind:class="task.statusClass">


                        <span class="task" x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
                        <input class="task-edit" type="text" x-show="task.isEditing" x-model="task.name" x-on:keydown.enter="saveTask(task)">
    
                        <button x-show="!task.isEditing" x-on:click="renameTask(task)">Rename</button>
                        <button x-show="task.isEditing" x-on:click="saveTask(task)">Save</button>
                        <button x-on:click="deleteTask(index)">Delete</button>
                    </li>
                </template>
            </ul>
        </div>
    
        <div id="export-import">
            <span class="downloading" style="display:none;"></span>
            <button id="export-todo">Export</button>
            <button id="import-todo">import</button>
    
            <div id="import-todo-popup--bg">
                <div id="import-todo-popup">
                    <button id="close--import-todo-popup--bg">Close</button>
                    <div id="import-todo-drop">
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" class="dropzone" id="json-dropzone">
                            <input style="display:none;" type="hidden" name="action" value="upload_tasks_json">
                            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('upload_tasks_json'); ?>">
                            <input style="display:none;" type="file" name="json-file">
                        </form>
    
                        <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
                        <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
                        <script>
                            jQuery.noConflict();
                            (function($) {
                                Dropzone.autoDiscover = false;
                                jQuery(document).ready(function($) {
                                    // Initialize Dropzone.js
                                    var myDropzone = new Dropzone("#json-dropzone", {
                                        url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                                        paramName: "json-file",
                                        acceptedFiles: ".json",
                                        uploadMultiple: true,
                                        maxFiles: null,
                                        parallelUploads: 20,
                                        clickable: true,
                                        init: function() {
                                            /* this.on("sending", function(file, xhr, formData) {
                                                formData.append("action", "upload_partials");
                                            }); */
                                            this.on("success", function(file, response) {
                                                window.location.href = response;
                                            });
                                        }
                                    });
                                });
                            })(jQuery);
                        </script>
                    </div>
                    <button>Import</button>
                </div>
            </div>
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

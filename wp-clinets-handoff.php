<?php
/*
Plugin Name: WP Clients Handoff
Plugin URI: https://example.com/
Description: Admin widget to manage client handoff tasks.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com/
License: GPL2
*/

// Include the website-todo file
// include_once( plugin_dir_path( __FILE__ ) . 'website-todo.php' );

// Register the admin widget
function wch_register_admin_widget()
{
    wp_add_dashboard_widget(
        'wp_clients_handoff_widget',  // Widget ID
        'Website Todo list',  // Widget title
        'wtd_custom_admin_widget_content'  // Callback function to display widget content
    );

    // Enqueue the custom CSS for the widget
    wp_enqueue_style('wp-clients-handoff-widget-style', plugin_dir_url(__FILE__) . 'css/todo-list.css');
}
add_action('wp_dashboard_setup', 'wch_register_admin_widget');

function wtd_custom_admin_widget_content()
{
    // Widget content goes here
?>

    <div x-data="{
    unfinishedTasks: [],
    finishedTasks: [],
    newTask: '',
    addTask() {
        if (this.newTask.trim() !== '') {
            this.unfinishedTasks.push({
                name: this.newTask,
                completed: false,
                isEditing: false,
            });
            this.newTask = '';
        }
    },
    toggleTask(task, group) {
        if (group === 'unfinished') {
            task.completed = true;
            this.finishedTasks.push(task);
            this.unfinishedTasks.splice(this.unfinishedTasks.indexOf(task), 1);
        } else if (group === 'finished') {
            task.completed = false;
            this.unfinishedTasks.push(task);
            this.finishedTasks.splice(this.finishedTasks.indexOf(task), 1);
        }
    },
    deleteTask(task, group) {
        if (group === 'unfinished') {
            this.unfinishedTasks.splice(this.unfinishedTasks.indexOf(task), 1);
        } else if (group === 'finished') {
            this.finishedTasks.splice(this.finishedTasks.indexOf(task), 1);
        }
    }
}">

        <div id="add-task">
            <input type="text" x-model="newTask" placeholder="Enter a new task">
            <button x-on:click="addTask" class="button button-primary">Add Task</button>
        </div>

        <!-- <h4>Unfinished</h4> -->
        <ul class="tasks-list unfinished-tasks">
            <template x-for="(task, index) in unfinishedTasks" :key="index">
                <li>
                    <input type="checkbox" x-model="task.completed" x-on:change="toggleTask(task, 'unfinished')">

                    <span class="task" x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
                    <input class="task-rename-field" type="text" x-show="task.isEditing" x-model="task.name" x-on:keydown.enter="task.isEditing = false">

                    <button x-text="task.isEditing ? 'Save' : 'Rename'" x-on:click="task.isEditing = !task.isEditing"></button>
                    <button x-on:click="deleteTask(task, 'unfinished')">Delete</button>
                </li>
            </template>
        </ul>

        <!-- <h4>Finished</h4> -->
        <ul class="tasks-list finished-tasks">
            <template x-for="(task, index) in finishedTasks" :key="index">
                <li>
                    <input type="checkbox" x-model="task.completed" x-on:change="toggleTask(task, 'finished')">

                    <span class="task" x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
                    <input type="text" x-show="task.isEditing" x-model="task.name" x-on:keydown.enter="task.isEditing = false">

                    <button x-text="task.isEditing ? 'Save' : 'Rename'" x-on:click="task.isEditing = !task.isEditing"></button>
                    <button x-on:click="deleteTask(task, 'finished')">Delete</button>
                </li>
            </template>
        </ul>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.3/cdn.js"></script>


<?php
}

<?php

function wtd_custom_admin_widget_content() {
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
  <h1>To-Do List</h1>

  <input type="text" x-model="newTask" placeholder="Enter a new task">
  <button x-on:click="addTask">Add Task</button>

  <h2>Unfinished</h2>
  <ul>
    <template x-for="(task, index) in unfinishedTasks" :key="index">
      <li>
        <input type="checkbox" x-model="task.completed" x-on:change="toggleTask(task, 'unfinished')">

        <span x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
        <input type="text" x-show="task.isEditing" x-model="task.name" x-on:keydown.enter="task.isEditing = false">

        <button x-text="task.isEditing ? 'Save' : 'Rename'" x-on:click="task.isEditing = !task.isEditing"></button>
        <button x-on:click="deleteTask(task, 'unfinished')">Delete</button>
      </li>
    </template>
  </ul>

  <h2>Finished</h2>
  <ul>
    <template x-for="(task, index) in finishedTasks" :key="index">
      <li>
        <input type="checkbox" x-model="task.completed" x-on:change="toggleTask(task, 'finished')">

        <span x-show="!task.isEditing" x-text="task.name" :class="{ 'line-through': task.completed }"></span>
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


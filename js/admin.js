var unfinished_list, completed;
jQuery(document).ready(function($) {
    // Function to handle import button click
    function handleImportButtonClick() {
        $('#import-todo-popup--bg').css('display', 'flex');
    }

    // Function to handle close button click
    function handleCloseButtonClick() {
        $('#import-todo-popup--bg').css('display', 'none');
    }

    function initiate_sortable(){
        if (jQuery( ".tasks-list" ).hasClass('ui-sortable')){
            // Remove the sortable feature to prevent bad state caused by unbinding all
            jQuery( ".tasks-list" ).sortable('destroy');
            // Unbind all event handlers!
            jQuery( ".tasks-list" ).unbind();
        }
    
        // Initialization of the sortable feature
        jQuery( ".tasks-list" ).sortable({
            change: function(event, ui) {
                ui.placeholder.css({visibility: 'visible', border : '1px solid yellow'});
            },
            placeholder: "highlight",
            start: function (event, ui) {
                ui.item.toggleClass("highlight");
            },
            stop: function (event, ui) {
                ui.item.toggleClass("highlight");
                prepare_tasks_list();
            }
        });
    }

    function updateTasksList(tasks){
        $.ajax({
            url: WPCH_ajax.ajaxurl,
            type: "post",
            data: { 
                action: "WPCH_saveTask" ,
                tasks: tasks,
                verify_nonce: WPCH_ajax.WPCH_nonce
            },
            success: function (data) {
                
            },
        });
    }

    function prepare_tasks_list(){
        var tasks = [];
        if($(".tasks-list li").length > 0){
            var i = 1;
            $(".tasks-list li").each(function(){
                if($(".completed", this).is(":checked")){
                    tasks.push({
                        name: $(".task-edit", this).val(),
                        completed: 1,
                        task_order: i
                    });
                } else {
                    tasks.push({
                        name: $(".task-edit", this).val(),
                        completed: 0,
                        task_order: i
                    });
                }
                i++;
            }).promise().done(function(){
                updateTasksList(tasks);
            });
        } else {
            updateTasksList(tasks);
        }
    }

    if(jQuery(".tasks-list li").length > 0){
        initiate_sortable();
    }

    // Add event listeners to the import and close buttons
    $('#import-todo').on('click', handleImportButtonClick);
    $('#close--import-todo-popup--bg').on('click', handleCloseButtonClick);

    // add new task
    $(document).on("click", "#addTask", function(){
        var newTask = $("#newTask").val();
        if(newTask != ""){
            $(".tasks-list.unfinished").append('<li><input type="checkbox" class="status"><span class="task">'+newTask+'</span><input style="display:none;" class="task-edit" type="text" value="'+newTask+'"><button class="rename">Rename</button><button class="delete">Delete</button></li>');
            $("#newTask").val("");
            initiate_sortable();
            setTimeout(() => {
                prepare_tasks_list();
            }, 100);
        }
    });

    // press add task button
    $('#newTask').keypress(function(event){
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode == '13'){
            $("#addTask").trigger("click");
        }
    });
    
    // set task as completed
    $(document).on("change", ".status", function(){
        var parentObj = $(this).parents("li");
        if($(this).is(":checked")){
            $(this).addClass("completed");
            $('.finished').append(parentObj);
        } else {
            $(this).removeClass("completed");
            $('.unfinished').append(parentObj);
        }
        prepare_tasks_list();
    });

    // Add this event listener to close the dialog when clicking outside
    $('#import-todo-popup--bg').on('click', function(event) {
        if (event.target === this) {
            handleCloseButtonClick();
        }
    });

    // rename task
    $(document).on("click", ".rename", function(e){
        var currObj = $(this);
        var currObjParent = $(this).parents("li");
        if($(currObj).hasClass("renaming")){
            $(".task", currObjParent).show();
            $(".task-edit", currObjParent).hide();
            $(currObj).html("Rename").removeClass("renaming");
            setTimeout(() => {
                prepare_tasks_list();
            }, 100);
        } else {
            $(".task", currObjParent).hide();
            $(".task-edit", currObjParent).show();
            $(currObj).html("Save").addClass("renaming");
        }
    });
    
    // delete task
    $(document).on("click", ".delete", function(e){
        var currObj = $(this);
        var currObjParent = $(this).parents("li");
        $(currObjParent).remove();
        initiate_sortable();
        prepare_tasks_list();
    });

    // export tasks list
    $(document).on("click", "#export-todo", function(){
        $(".downloading").show();
        $(this).prop("disabled", true);
        $.ajax({
            url: WPCH_ajax.ajaxurl,
            type: "post",
            data: { 
                action: "WPCH_getTasks" ,
                verify_nonce: WPCH_ajax.WPCH_nonce
            },
            dataType: 'json',
            success: function (data) {
                $(this).prop("disabled", false);
                $("<a />", {
                    "download": "WP-Clients-Handoff-export.json",
                    "href" : "data:application/json," + encodeURIComponent(JSON.stringify(data))
                }).appendTo("body")
                .click(function() {
                    $(this).remove()
                })[0].click()
            },
        });
    });
});
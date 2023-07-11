<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPCH_main {
	public function __construct(){
        add_action( 'admin_menu', array($this, 'WPCH_DB_tables' ) );
		add_action( 'wp_dashboard_setup', array($this, 'WPCH_register_admin_widget') );
        add_action( 'wp_ajax_WPCH_saveTask', array($this, 'WPCH_saveTask') );
        add_action( 'wp_ajax_WPCH_getTasks', array($this, 'WPCH_getTasks') );
        add_action( 'wp_ajax_upload_tasks_json', array($this, 'WPCH_upload_tasks_json') );
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

    public function WPCH_register_admin_widget() {
        wp_add_dashboard_widget(
            'wpch_clients_handoff_widget',  // Widget ID
            'Website Todo list',  // Widget title
            array($this, 'WPCH_custom_admin_widget_content')  // Callback function to display widget content
        );
    
        // Enqueue the custom CSS for the widget
        wp_enqueue_style( 'WPCH-clients-handoff-widget-style', WPCH_URL . 'css/todo-list.css', WPCH_PLUGINVERSION );
        wp_enqueue_script( 'WPCH-alipine', WPCH_URL.'js/alpinejs_3.12.3.js', array('jquery'), WPCH_PLUGINVERSION );
        wp_enqueue_script( 'WPCH-sortable', WPCH_URL.'js/Sortable.min.js', array('jquery'), WPCH_PLUGINVERSION );
        wp_register_script( 'WPCH_ajax_scripts', WPCH_URL.'js/admin.js', array('jquery'), WPCH_PLUGINVERSION );
        wp_localize_script( 'WPCH_ajax_scripts', 'WPCH_ajax', array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ), 
            'WPCH_nonce' => wp_create_nonce('ajax-nonce')
        ));
        wp_enqueue_script( 'WPCH_ajax_scripts' );
    }

    public function WPCH_custom_admin_widget_content(){
        // Widget content goes here
    ?>

        <div>
            <div id="add-task">
                <input type="text" id="newTask" placeholder="Type and hit Enter">
            </div>
        
            <?php
                $unfishished_list = '';
                $fishished_list = '';
                global $wpdb;
                $wpch_tasks = $wpdb->prefix . "wpch_tasks";
                $tasks = $wpdb->get_results("SELECT * FROM $wpch_tasks ORDER BY task_order ASC", OBJECT);
                if(!empty($tasks)){
                    foreach($tasks as $task){
                        if($task->completed == 1){
                            $fishished_list .= '<li><input type="checkbox" class="'.(($task->completed == 1) ? 'completed' : '').' status" '.(($task->completed == 1) ? 'checked' : '').'><span class="task '.(($task->completed == 1) ? 'line-through' : '').'">'.esc_attr($task->name).'</span><input style="display:none;" class="task-edit" type="text" value="'.esc_attr($task->name).'"><div class="actions"><button class="rename button button-small">Rename</button><button class="delete button button-small">Delete</button></div></li>';
                        } else {
                            $unfishished_list .= '<li><input type="checkbox" class="'.(($task->completed == 1) ? 'completed' : '').' status" '.(($task->completed == 1) ? 'checked' : '').'><span class="task '.(($task->completed == 1) ? 'line-through' : '').'">'.esc_attr($task->name).'</span><input style="display:none;" class="task-edit" type="text" value="'.esc_attr($task->name).'"><div class="actions"><button class="rename button button-small">Rename</button><button class="delete button button-small">Delete</button></div></li>';
                        }
                    }
                }
            ?>
            <ul class="tasks-list unfinished"><?php echo $unfishished_list; ?></ul>
            <ul class="tasks-list finished"><?php echo $fishished_list; ?></ul>
        </div>
    
        <div id="export-import">
            <span class="downloading" style="display:none;"></span>
            <button class="button button-small" id="export-todo">Export</button>
            <button class="button button-small" id="import-todo">import</button>
        
            <div id="import-todo-popup--bg">
                <div id="import-todo-popup">
                    <button class="button" id="close--import-todo-popup--bg">Close</button>
                    <div id="import-todo-drop">
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="dropzone" id="json-dropzone">
                        <input style="display:none;" type="hidden" name="action" value="upload_tasks_json">
                        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('upload_tasks_json'); ?>">
                        <input style="display:none;" type="file" name="json-file">
                    </form>
                    
                    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
                    <link
                        rel="stylesheet"
                        href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css"
                        type="text/css"
                    />
                    <script>
                        jQuery.noConflict();
                        (function($) {

                            Dropzone.autoDiscover = false;
                            jQuery(document).ready(function($) {
                                
                                // Initialize Dropzone.js
                                var myDropzone = new Dropzone("#json-dropzone", {
                                    url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
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
                    <button class="button button-primary">Import</button>
                </div>
            </div>
        </div>
    <?php
    }
    
    public function WPCH_saveTask(){
        check_ajax_referer('ajax-nonce', 'verify_nonce');
		$tasks = rest_sanitize_array($_POST['tasks']);

        global $wpdb;
        $wpch_tasks = $wpdb->prefix . "wpch_tasks";
        $wpdb->query( "DELETE FROM $wpch_tasks" );
        if(!empty($tasks)){
            $insert_array = array();
            foreach($tasks as $task){
                $insert_array[] = "('".$task['name']."', '".$task['completed']."', '".$task['task_order']."')";
            }
            $query = "INSERT INTO ".$wpch_tasks." (name, completed, task_order) VALUES ";
            $query .= implode(', ', $insert_array);
            $wpdb->query( $query);
        }

		wp_die();
    }
    
    public function WPCH_getTasks(){
        check_ajax_referer('ajax-nonce', 'verify_nonce');
        global $wpdb;
        $wpch_tasks = $wpdb->prefix . "wpch_tasks";
        $tasks = $wpdb->get_results( "SELECT * FROM $wpch_tasks ORDER BY task_order ASC", ARRAY_A );
        if(!empty($tasks)){
            echo json_encode(array('data' => $tasks));
        } else {
            echo json_encode(array('data' => array()));
        }

		wp_die();
    }

    public function WPCH_upload_tasks_json(){
        if (isset($_FILES['json-file']) && wp_verify_nonce( $_REQUEST["_wpnonce"], "upload_tasks_json" )) {
            $files = $_FILES['json-file'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $filename = $files['name'][$i];
                $filetype = $files['type'][$i];
                $filesize = $files['size'][$i];
                $filetmp = $files['tmp_name'][$i];
                
                // Use a file upload library to scan the uploaded CSS file and extract its content
                $json_data = json_decode(file_get_contents($filetmp));
                if(!empty($json_data->data)){
                    global $wpdb;
                    $wpch_tasks = $wpdb->prefix . "wpch_tasks";
                    $insert_array = array();
                    foreach($json_data->data as $task){
                        $insert_array[] = "('".$task->name."', '".$task->completed."', '".$task->task_order."')";
                    }
                    $query = "INSERT INTO ".$wpch_tasks." (name, completed, task_order) VALUES ";
                    $query .= implode(', ', $insert_array);
                    $wpdb->query( $query);
                }
            }            
        }
        echo admin_url('index.php');
        wp_die();
    }
}

new WPCH_main();
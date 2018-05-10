<?php
/*
Plugin Name: WP-zipfolder
Plugin URI: https://github.com/Subair-tc/
Description: Downlaod an upload folder as zip
Version: 0.1.1
Author: Subair TC
Author URI: https://github.com/Subair-tc/
*/

/* Set constant path to the plugin directory. */
define( 'WP_ZIP_FOLDER_MULTI_PATH', plugin_dir_path( __FILE__ ) );

/* Set the constant path to the plugin's includes directory. */
define( 'WP_ZIP_FOLDER_INC', WP_ZIP_FOLDER_MULTI_PATH . trailingslashit( 'inc' ), true );

class WPZipArchive extends ZipArchive {
    /**
    * Construct the plugin
    *
    * @since 0.1.0   
    */
    public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ) );
        ob_start();
	}
    /**
    * Initialise plugin ( all hooks used are inside)
    *
    * @since 0.1.0   
    */
    public function initialize_plugin(){
        add_action( 'admin_enqueue_scripts',   array( $this,'add_scripts') );
        add_action( 'wp_ajax_download_as_zip', array( $this, 'download_as_zip' ) );
        add_action( 'admin_menu', array( $this,'wp_zip_folder_menu' ) );
        add_action( 'wp_ajax_download_file', array( $this,'force_download_a_file' ) );


    }
    
     /**
    * Added required JavaScripts and CSS files
    *
    * @since 0.1.1  
    */

    public function add_scripts() {
    
        wp_register_style( 'zip-folder-default', plugins_url( '/css/default.css', __FILE__ ) );
	    wp_enqueue_style( 'zip-folder-default' );
        wp_register_style( 'zip-folder-css', plugins_url( '/css/zip-folder.css', __FILE__ ) );
	    wp_enqueue_style( 'zip-folder-css' );

        wp_register_script( 'php_file_tree_jquery', plugins_url( '/js/php_file_tree_jquery.js', __FILE__ ), true );
        wp_enqueue_script( 'php_file_tree_jquery' );
        wp_localize_script('php_file_tree_jquery', 'Ajax', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	));
    }

    /**
    * Add settings menu
    *
    * @since 0.1.0   
    */
    public function wp_zip_folder_menu() {
        if ( function_exists('add_options_page') ) {
            add_options_page('ZIP Folder', 'Zip Folder', 'manage_options', basename(__FILE__), array( $this,'wp_zip_folder_page' ));
        }
    }

    /**
    * settings menu callback
    *
    * @since 0.1.0   
    */
    public function wp_zip_folder_page() {

        if (isset($_POST['sub_directories'])) {

			//wp_nonce check
			check_admin_referer('wp_zip_folder_options');
            
            if( isset( $_POST['sub_directories'] ) && '' !=  $_POST['sub_directories'] ) {
                $the_folder = $_POST['sub_directories'];
                $this->download_as_zip( $the_folder );
            }
            
        }

        ?>
        <div class="zip-container">

            <div class="row">


        <?php

        $upload = wp_upload_dir();

        $folders = new PHPFileTree();
        echo $folders->php_file_tree( $upload['basedir'], "javascript:alert('You clicked on [link]');" );


        $dirs = glob( $upload['basedir'] . '/*' , GLOB_ONLYDIR);


        ?>
        
        <form namme="download-folder" id="download-folder"  method="post" action="<?php echo esc_attr($_SERVER["REQUEST_URI"]); ?>">
            <?php
                if ( function_exists('wp_nonce_field') ) {
                    wp_nonce_field('wp_zip_folder_options');
                }	
            ?>
            <input type="hidden" id="sub_directories" name="sub_directories" value=""  />

            <?php /*
            <select name="sub_directories">
            <?php
            foreach( $dirs as $dir ) {
               echo '<option value='.$dir.'>'.str_replace( $upload,'', $dir).'</option>'; 
            }

            ?>
            </select>
            */ ?>

            <input type="hidden" name="download_folder" id="download_folder" />

        </form>
        </div>
        </div>

        <?php

    }

    /**
    * function for creating new directory
    *
    * @since 0.1.0   
    */
    public function addDir($location, $name) {
        $this->addEmptyDir($name);
         $this->addDirDo($location, $name);
    }

    /**
    * get all sub directories
    *
    * @since 0.1.0   
    */
    private function addDirDo($location, $name) {
        $name .= '/';         $location .= '/';
      // Read all Files in Dir
        $dir = opendir ($location);
        while ($file = readdir($dir))    {
            if ($file == '.' || $file == '..') continue;
            $do = (filetype( $location . $file) == 'dir') ? 'addDir' : 'addFile';
            $this->$do($location . $file, $name . $file);
        }
    }

    /**
    * Make the zip and download
    *
    * @since 0.1.0   
    */
    public function download_as_zip( $the_folder ){
        
        //var_dump($the_folder);
        if( !$the_folder ) {
            echo 'Error: Invalid Path';
        }
        $upload = wp_upload_dir();
        if( ! is_dir( $the_folder ) ) {
            if ( ! headers_sent()) {
                header("Content-Disposition: attachment; filename=\"" . basename($the_folder) . "\"");
                header("Content-Type: application/force-download");
                header("Content-Length: " . filesize($the_folder));
                header("Connection: close");
            } else {
                echo 'headers already send';
            }
                while (ob_get_level()) {
                ob_end_clean();
                @readfile($the_folder);
            }
            
        } else{ 
            $zip_file_name = $upload['basedir'].'/archived_name-'.current_time('timestamp').'.zip';

            $res = $this->open($zip_file_name, ZipArchive::CREATE);
            if($res === TRUE)    {
                $this->addDir($the_folder, basename($the_folder)); $this->close();
                
                if ( ! headers_sent()) {
                    header("Content-Disposition: attachment; filename=\"" . basename($zip_file_name) . "\"");
                    header("Content-Type: application/force-download");
                    header("Content-Length: " . filesize($zip_file_name));
                    header("Connection: close");
                } else {
                    echo 'headers already send';
                }

                while (ob_get_level()) {
                    ob_end_clean();
                    @readfile($zip_file_name);
                }

                unlink($zip_file_name);

            }
            else  { echo 'Could not create a zip archive';}

        }
        

    }
}

// Create an object for nitialisation.
$WPZipArchive = new WPZipArchive();

/**
* Include php filte tee classs
*
* @since 0.1.1  
*/
require_once( WP_ZIP_FOLDER_INC . 'php_file_tree.php' );


function custom_menu_order(){
    global $submenu;
    var_dump($submenu); exit;

   
}
//add_action('_admin_menu', 'custom_menu_order');
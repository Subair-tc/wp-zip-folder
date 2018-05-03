<?php
/*
Plugin Name: WP-zipfolder
Plugin URI: https://github.com/Subair-tc/
Description: Downlaod an upload folder as zip
Version: 0.1.0
Author: Subair TC
Author URI: https://github.com/Subair-tc/
*/

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
        add_action( 'wp_ajax_download_as_zip', array( $this, 'download_as_zip' ) );
        add_action( 'admin_menu', array( $this,'wp_zip_folder_menu' ) );


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

        if (isset($_POST['download_folder'])) {

			//wp_nonce check
			check_admin_referer('wp_zip_folder_options');
            
            if( isset( $_POST['sub_directories'] ) && '' !=  $_POST['sub_directories'] ) {
                $the_folder = $_POST['sub_directories'];
                $this->download_as_zip( $the_folder );
            }
            
        }

        $upload = wp_upload_dir();
        $dirs = glob( $upload['basedir'] . '/*' , GLOB_ONLYDIR);
        ?>
        <form method="post" action="<?php echo esc_attr($_SERVER["REQUEST_URI"]); ?>">
            <?php
                if ( function_exists('wp_nonce_field') ) {
                    wp_nonce_field('wp_zip_folder_options');
                }	
            ?>
            <select name="sub_directories">
            <?php
            foreach( $dirs as $dir ) {
               echo '<option value='.$dir.'>'.str_replace( $upload,'', $dir).'</option>'; 
            }

            ?>
            </select>

            <input type="submit" name="download_folder" id="download_folder" />

        </form>

        <?php

    }

    /**
    * function for creatingnew directory
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
          // Rekursiv, If dir: FlxZipArchive::addDir(), else ::File();
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
        $zip_file_name = $upload['basedir'].'/archived_name-'.current_time('timestamp').'.zip';

        $za = new FlxZipArchive;
        $res = $za->open($zip_file_name, ZipArchive::CREATE);
        if($res === TRUE)    {
            $za->addDir($the_folder, basename($the_folder)); $za->close();
            
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

// Create an object for nitialisation.
$WPZipArchive = new WPZipArchive();
<?php
/*
Plugin Name: WP-zipfolder
Plugin URI: https://github.com/Subair-tc/
Description: Downlaod a folder as zip
Version: 0.1.0
Author: Subair TC
Author URI: https://github.com/Subair-tc/
*/

class FlxZipArchive extends ZipArchive {

    public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'initialize_plugin' ) );
        ob_start();
	}



    public function initialize_plugin(){
        add_action( 'wp_ajax_download_as_zip', array( $this, 'download_as_zip' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'add_script' ) );
        add_action( 'admin_menu', array( $this,'wp_zip_folder_menu' ) );


    }

    function add_script() {

        wp_register_script( 'download-as-zip', plugins_url( '/js/download-as-zip.js', __FILE__ ), true );
        wp_enqueue_script( 'download-as-zip' );
        wp_localize_script('download-as-zip', 'Ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ));
    }

    public function wp_zip_folder_menu() {
        if ( function_exists('add_options_page') ) {
            add_options_page('ZIP Folder', 'Zip Folder', 'manage_options', basename(__FILE__), array( $this,'wp_zip_folder_page' ));
        }
    }


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
        echo'<pre>'; var_dump( $dirs); echo'</pre>';
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

    public function addDir($location, $name) {
        $this->addEmptyDir($name);
         $this->addDirDo($location, $name);
     }

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

    public function download_as_zip( $the_folder ){
        
        var_dump($the_folder);
        if( !$the_folder ) {
            echo 'Error: Could not create a zip archive';
        }
        $the_folder = "D:/Subair/study/wordpress-4.9.5/wordpress/wp-content/uploads/cfdb7_uploads";
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
            
            //http headers for Downloads
           

            //do something else after download like delete file
            unlink($zip_file_name);
            echo '<br/>'.$zip_file_name.'<br/>';
            echo 'zipped successfully';

        }
        else  { echo 'Could not create a zip archive';}

    }
}

$FlxZipArchive = new FlxZipArchive();




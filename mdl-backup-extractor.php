<?php

/**
 * Script for extracting all backups from particular Moodle instance into desired folder
 */

/**
 * GLOBAL CONFIGURATION
 */

const MOODLEDATA = "/var/www/site/moodledata/";   // absulute path to moodledata with trailing slash
const BACKUPDIR = "/var/www/site/backup/";        // absulute path to backup directory with trailing slash
const DBHOST = "localhost";                       // DB host. Usually - "localhost"
const DBNAME = "mdl";                             // DB name
const DBUSER = "mdl";                             // DB user name
const DBPASS = "mdlpass";                         // DB user password


// Start logging
$logfile = "log_".date("Y-m-d").".log";           // name of the log file (relative to work dir by default). Default - "log_".date("Y-m-d").".log"
$log = fopen($logfile, "a+") or die("Unable to open file!");
$logtxt = "Start logging at " . date("Y-m-d H:i:s:u") . "\n";
fwrite($log, $logtxt);
echo $logtxt;


//Create backup dir if it was not created
if (!is_dir(BACKUPDIR)) {
    if (!mkdir(BACKUPDIR)) {
        $logtxt = "Backup directory could not be created!!! Check permissions or make it by yourself! \n";
        fwrite($log, $logtxt);
        die ($logtxt);
     } else {
        $logtxt = "Backup directory was created. \n";
        fwrite($log, $logtxt);
        echo $logtxt;
     }
}

//Create connection to MySQL
$dbconn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

//Check connection
if ($dbconn->connect_error) {
    $logtxt = "Connection failed: " . $dbconn->connect_error . "\n";
    fwrite($log, $logtxt);
    die($logtxt);
} 
$logtxt = "Connected to DB successfully. Reading data... \n";
fwrite($log, $logtxt);
echo $logtxt;

$sql = "SET character_set_results='utf8'";
$dbconn->query($sql);

// Find all backups in DB
$sql = "SELECT DISTINCT contenthash, filename, filesize FROM mdl_files WHERE filename LIKE '%mbz%'";
$result = $dbconn->query($sql);

// processing found backup files
if ($result->num_rows > 0) {
    
    $logtxt = "$result->num_rows files will be copied now: \n";
    fwrite($log, $logtxt);
    echo $logtxt;

    // fetch files one by one
    while($row = $result->fetch_assoc()) { 
        
        $logtxt = "\tFile {$row['filename']} is processing now... \n";
        fwrite($log, $logtxt);
        echo $logtxt;
        
        $subdir1 = substr($row['contenthash'], 0, 2) . "/";
        $subdir2 = substr($row['contenthash'], 2, 2) . "/";

        // check either file does not exists OR either (is already present in backup dir AND is not fully copied)
        if (!(file_exists(BACKUPDIR . $row['contenthash']) || file_exists(BACKUPDIR . $row['filename'])) || (file_exists(BACKUPDIR . $row['contenthash']) && filesize(BACKUPDIR . $row['contenthash']) !== intval($row['filesize']))) {
            $logtxt = "\t\t Start copying... \n";
            fwrite($log, $logtxt);
            echo $logtxt;

            // copying file
            if (!copy(MOODLEDATA . "filedir/" . $subdir1 . $subdir2 . $row['contenthash'], BACKUPDIR . $row['contenthash'])) {
                $logtxt = "\t\t Some error occured while copying the file... Try to look into warnings in console!\n";
                fwrite($log, $logtxt);
                die ($logtxt);
            } else {
                $logtxt = "\t\t Copied successfully! \n";
                fwrite($log, $logtxt);
                echo $logtxt;
            }

            // Rename file from hash name to human readable name
            if (file_exists(BACKUPDIR . $row['contenthash'])) {
                if (!rename(BACKUPDIR . $row['contenthash'], BACKUPDIR . $row['filename'])){
                    $logtxt = "\t\t File was not renamed ... \n";
                    fwrite($log, $logtxt);
                    die ($logtxt);
                } else {
                    $logtxt = "\t\t File was renamed correctly! \n";
                    fwrite($log, $logtxt);
                    echo $logtxt;
                }
            }

        } else {
            $logtxt = "\t\t Looks like file was already copied to backup folder!\n";
            fwrite($log, $logtxt);
            echo $logtxt;
        } 
    }
} else {
    $logtxt = "Zero rows were fetched from DB! \n";
    fwrite($log, $logtxt);
    echo $logtxt;
}

// close DB connection
$dbconn->close();

// Stop logging
$logtxt = "Stop logging at " . date("Y-m-d H:i:s:u") . "\n";
fwrite($log, $logtxt);
echo $logtxt;

fclose($log);
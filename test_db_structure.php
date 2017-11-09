<?php

include ("../inc/includes.php");

$comparaisonSQLFile = "../install/mysql/glpi-empty.sql";

$file_content = file_get_contents($comparaisonSQLFile);
$a_lines = explode("\n", $file_content);

//============= Add plugins SQL structure =============//
if (file_exists("../plugins/fusioninventory")) {
   $file_content = file_get_contents("../plugins/fusioninventory/install/mysql/plugin_fusioninventory-empty.sql");
   $a_lines = array_merge($a_lines, explode("\n", $file_content));
}


//======================== end ========================//

$a_tables_ref = array();
$current_table = '';
foreach ($a_lines as $line) {
   if (strstr($line, "CREATE TABLE ")
           OR strstr($line, "CREATE VIEW")) {
      $matches = array();
      preg_match("/`(.*)`/", $line, $matches);
      $current_table = $matches[1];
   } else {
      if (preg_match("/^`/", trim($line))) {
         $s_line = explode("`", $line);
         $s_type = explode("COMMENT", $s_line[2]);
         $s_type[0] = trim($s_type[0]);
         $s_type[0] = str_replace(" COLLATE utf8_unicode_ci", "", $s_type[0]);
         $s_type[0] = str_replace(" CHARACTER SET utf8", "", $s_type[0]);
         $s_type[0] = str_replace("TINYINT(", "tinyint(", $s_type[0]);
         $s_type[0] = str_replace("VARCHAR(", "varchar(", $s_type[0]);
         $s_type[0] = str_replace("INT(", "int(", $s_type[0]);
         $a_tables_ref[$current_table][$s_line[1]] = str_replace(",", "", $s_type[0]);
      }
   }
}

// * Get tables from MySQL
$a_tables_db = array();
$a_tables = array();
// SHOW TABLES;
$query = "SHOW TABLES";
$result = $DB->query($query);
while ($data=$DB->fetch_array($result)) {
   $data[0] = str_replace(" COLLATE utf8_unicode_ci", "", $data[0]);
   $data[0] = str_replace("( ", "(", $data[0]);
   $data[0] = str_replace(" )", ")", $data[0]);
   $a_tables[] = $data[0];
}

foreach ($a_tables as $table) {
   $query = "SHOW CREATE TABLE ".$table;
   $result = $DB->query($query);
   while ($data=$DB->fetch_array($result)) {
      $a_lines = explode("\n", $data['Create Table']);

      foreach ($a_lines as $line) {
         if (strstr($line, "CREATE TABLE ")
                 OR strstr($line, "CREATE VIEW")) {
            $matches = array();
            preg_match("/`(.*)`/", $line, $matches);
            $current_table = $matches[1];
         } else {
            if (preg_match("/^`/", trim($line))) {
               $s_line = explode("`", $line);
               $s_type = explode("COMMENT", $s_line[2]);
               $s_type[0] = trim($s_type[0]);
               $s_type[0] = str_replace(" COLLATE utf8_unicode_ci", "", $s_type[0]);
               $s_type[0] = str_replace(" CHARACTER SET utf8", "", $s_type[0]);
               $s_type[0] = str_replace(",", "", $s_type[0]);
               if ((trim($s_type[0]) == 'text'
                     || trim($s_type[0]) == 'longtext')
                  && strstr($table, "fusioninventory")) {
                  $s_type[0] .= ' DEFAULT NULL';
               }
               $a_tables_db[$current_table][$s_line[1]] = $s_type[0];
            }
         }
      }
   }
}

$a_tables_ref_tableonly = array();
foreach ($a_tables_ref as $table=>$data) {
   $a_tables_ref_tableonly[] = $table;
}
$a_tables_db_tableonly = array();
foreach ($a_tables_db as $table=>$data) {
   $a_tables_db_tableonly[] = $table;
}

 // Compare
$tables_toremove = array_diff($a_tables_db_tableonly, $a_tables_ref_tableonly);
$tables_toadd = array_diff($a_tables_ref_tableonly, $a_tables_db_tableonly);

// See tables missing or to delete
if (count($tables_toadd) > 0) {
   echo "##########################\n";
   echo "##### Tables missing #####\n";
   echo "##########################\n";
   print_r($tables_toadd);
   echo "\n\n";
}
if (count($tables_toremove) > 0) {
   echo "############################\n";
   echo "##### Tables to delete #####\n";
   echo "############################\n";
   print_r($tables_toremove);
   echo "\n\n";
}

// See if fields are same
foreach ($a_tables_db as $table=>$data) {
   if (isset($a_tables_ref[$table])) {
      $fields_toremove = array_diff_assoc($data, $a_tables_ref[$table]);
      $fields_toadd = array_diff_assoc($a_tables_ref[$table], $data);
      $diff = "======= DB ============== Ref =======> ".$table."\n";
      $diff .= print_r($data, TRUE);
      $diff .= print_r($a_tables_ref[$table], TRUE);

      // See tables missing or to delete
      if (count($fields_toadd) > 0) {
         echo 'Fields missing/not good in '.$when.' '.$table.' '.print_r($fields_toadd, TRUE)." into ".$diff."\n";
         echo "\n\n";
      }
      if (count($fields_toremove) > 0) {
         echo 'Fields to delete in '.$when.' '.$table.' '.print_r($fields_toremove, TRUE)." into ".$diff."\n";
         echo "\n\n";
      }

   }
}

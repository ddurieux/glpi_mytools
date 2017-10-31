<?php

include ("../inc/includes.php");

$query = "SHOW TABLES";
$result = $DB->query($query);

if ($DB->numrows($result)) {
   while ($tables = $DB->fetch_array($result)) {
      $table = $tables[0];

      if (!strstr($table, 'backup')
              && !strstr($table, 'glpi_plugin_')
              && $table != 'glpi_knowbaseitems_comments') {
         $query2 = "SHOW INDEX FROM ".$table;
         $result2 = $DB->query($query2);
         $index_column = [];
         while ($indexes = $DB->fetch_array($result2)) {
            if ($indexes['Key_name'] != 'PRIMARY') {
               if (strstr($indexes['Column_name'], '_id')) {
                  $index_column[] = "`".$indexes['Column_name']."`=1";
               } else {
                  $index_column[] = "`".$indexes['Column_name']."`='2'";
               }
            }
         }
         if (count($index_column) == 0) {
            echo "ERROR: NO INDEX ON THE TABLE ".$table."\n";
         }
         $query3 = "EXPLAIN SELECT * FROM ".$table." WHERE ".  implode(' AND ', $index_column);
         $result3 = $DB->query($query3);
         while ($data = $DB->fetch_array($result3)) {
            if ($data['type'] == 'ALL') {
               echo "ERROR: INDEX ERROR ON THE TABLE ".$table."\n";
            }
         }
      }
   }
}
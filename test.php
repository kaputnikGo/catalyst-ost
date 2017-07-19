<?php
   // test the php7 install
   // and figure how to cmdline output etc.
   // env: linuxmint 18 - Minty Fresh

   // ASSUMPTION anglo-saxon naming convention of a single capitalised first name & surname
   // columns spearated by a comma ($delimiter = ",")

   // ASSUMPTION email check will not check that domain name resolves to an actual mail server 
   //domain, errors to be outputted to terminal

   // ASSUMPTION small csv file size means will use an array for pre-db

   print "\n------------------------\n";
   print "env info:\n";
   $phpv = phpversion();
   print "\nphp version: $phpv";
   $mysqlv = getMySQLVersion();
   print "\nmysql version: $mysqlv";
   print "\n------------------------\n\n";

// UTILS
   
   function getMySQLVersion() { 
      $output = shell_exec('mysql -V'); 
      preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
      return $version[0]; 
   }

   function interceptExit() {
      print "\nexiting...\n";
      exit();
   }

   function cleanStringInput($str) {
      $str = @strip_tags($str);
      $str = @stripslashes($str);
      $str = preg_replace('/\s+/', '', $str);
      return $str;
   }

   function cleanSQLstring($str) {
      //TODO
      //$str = mysql_real_escape_string($str);
   }

   function nameFormatAnglo($str) {
      // Capitalised String Function, remove non alpha chars
      $str = preg_replace("/[^a-zA-Z]/", "", $str);
      $str = strtolower($str);
      $str = ucfirst($str);
      return $str;
   }

   function simpleEmailCheck($str) {
      // only checks format of email string, not TLD validity etc
      // spec requests emails to lowercase
      $str = strtolower($str);
      if(filter_var($str, FILTER_VALIDATE_EMAIL)) {
         // valid address
         return $str;
      }
      else {
         // invalid address
         print "WARN: invalid email: ".$str."\n";
         return NULL;
      }
   }

// FUNCTIONS

   function createTable() {
      // this is set via spec to be "users"
      print "\n request to create table\n";
   }

   function dryRun() {
      // needs a filename to run, no DB actions
      print "\n request to run non-DB functions on file.\n";
   }

   function outputHelp() {
      // make a bit verbose for proper help...
      print "\n-----------------------------\n";
      print "USER DATABASE ENTRY PROGRAM\n";
      print "instructions for use:\n\n";
      print "user_upload.php [--file][--create_table][--dry_run][-u][-p][-h][--help]\n";
      print "--file [csv filename]\n".
            "--create_table (creates db table no other action taken)\n".
            "--dry_run (used with --file, parses file for content but NO db actions\n".
            "-u (sql username)\n".
            "-p (sql password)\n".
            "-h (sql host)\n".
            "--help (print this menu)\n";
      print "\n-----------------------------\n\n";
   }

   function parseFilename($str) {
      // check it first
      print "\nfilename: ".$str."\n";
   }
   function parseSQLusername($str) {
      // check it first
      print "\nSQL username: ".$str."\n";
   }
   function parseSQLpassword($str) {
      // check it first and print teh passw0rdz!!
      print "\nSQL password: ".$str."\n";
   }
   function parseSQLhost($str) {
      // check it first
      print "\nSQL host: ".$str."\n";
   }

   function parseOptions($optionsArray) {
      // yup
      print "\nparsing options...\n";
      $optionNum = 0;
 
      // check that an option with corresponding entry actually has one,ie:
      // "--filename filenametxt" not "--filename --help --iamidiot"

      foreach($optionsArray as $option) {
         if (substr($option[0], 0, 1) == "-") {
            switch ($option) {
               case "--file":
                  parseFilename($optionsArray[$optionNum + 1]);
                  break;
               case "--create_table":
                  print "\ncreate table option: ".$optionNum."\n";
                  break;
               case "--dry_run":
                  print "\ndry run option: ".$optionNum."\n";
                  break;
               case "-u":
                  parseSQLusername($optionsArray[$optionNum + 1]);
                  break;
               case "-p":
                  parseSQLpassword($optionsArray[$optionNum + 1]);
                  break;
               case "-h":
                  parseSQLhost($optionsArray[$optionNum + 1]);
                  break;
               case "--help":
                  outputHelp();
                  break;
               default:
                  print "\nunknown option found: ".$optionsArray[$optionNum]."\n";
            }                 
         }
         // end of substr check
         $optionNum++;
      }
   }


// CMDLINE ARGS

   // $argc = number of arguments passed 
   // $argv = arguments passed
   // first $argc is for php exe filename
   // need to look for switches (options) 

   if ($argc <= 1) {
      // no options found at cmdline
      // autorun the help option
      print "You need to add options for this program to run.\n\n";
      outputHelp();
      interceptExit();
   }
   else {
      // just what options are used?
      $argsCount = 0;
      $optionsArray = array("");
      foreach($argv as $argStr) {
         $optionsArray[$argsCount] = $argStr;
         $argsCount++;
      }
      parseOptions($optionsArray);
      interceptExit();
   }


// CHECK FILE HAS CONTENTS
   print "\nFile check: ";
   $rawFile = fopen($fileHandle, "r") or die("unable to open file: $fileHandle\n");
   $lineNums = 0;

   // set up empty 2D array for entries of 3 fields
   $entryArray = array(array("","",""));

   if ($rawFile) {
      while (($line = fgets($rawFile)) !== false) {
         
         $entryArray[$lineNums] = explode(",", cleanStringInput($line));
         $lineNums++;
      }
      fclose($rawFile);
      print "OK. file closed after $lineNums lines read.\n\n";
   } 
   else {
      // error opening the file.
      print ("error reading file\n");
   }

// CONTENTS CHECK
   // according to spec first line of file is headings "name,surname,email"
   $contentNum = sizeof($entryArray);
   print "Array check size: $contentNum\n";
   //ignore $fileContents[0] assume its the headings

   print "\nArray check cleaned and formatted entries:\n";

   $lineNums = 0;
   foreach($entryArray as $entry) {
      
      if ($lineNums == 0) { 
         $lineNums++;
         // skip first entry as it 'should' be the headings
         continue; 
      }
      // first name
      $entry[0] = nameFormatAnglo($entry[0]);
      // surname
      $entry[1] = nameFormatAnglo($entry[1]);
      // email check, can return NULL
      $entry[2] = simpleEmailCheck($entry[2]);
      if ($entry[2] === NULL) {
         // have error in email
         // option to manually fix email?
         print $entry[0].",".$entry[1].", INVALID EMAIL.\n";

      }
      else {
         print $entry[0].",".$entry[1].",".$entry[2]."\n";
      }
   }

   print "\nend of array print.\n\n";
   

// SQL DB


// MAIN 

?>

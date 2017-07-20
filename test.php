<?php
   // test the php7 install
   // and figure how to cmdline output etc.
   // env: linuxmint 18 - Minty Fresh

   // ASSUMPTION anglo-saxon naming convention of a single capitalised first name & surname
   // columns spearated by a comma ($delimiter = ",")

   // ASSUMPTION email check will not check that domain name resolves to an actual mail server 
   // domain, errors to be outputted to terminal

   // ASSUMPTION lack of proper email means user is not entered into db, names must be present too

   // ASSUMPTION small csv file size means will use an array for pre-db

   // ASSUMPTION spec is for a program that enters data into db, no mention of db edits etc.
   // this program will therefore only add entries that do not already exist
 

   //TODO
      // global vars... pass by ref
      // at DB entry strips ' from O'Really names
      // sane and proper into classes
      // sql checks
      // cmdline options proper
      // help display better info
      // order of execution in php

// VARS
   // set up empty 2D array for entries of 3 fields
   $entryArray = array(array("","",""));
   $dbhost = "localhost"; // default, to be set by user
   $dbuser = "catOST"; // default, to be set by user
   $dbpass = "password"; // default, to be set by user
   $dbname = "catalyst_ost"; // default
   $dbtable = "users"; // set as per spec

// SQL FUNCTIONS
   function getMySQLVersion() { 
      $output = shell_exec('mysql -V'); 
      preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
      return $version[0]; 
   }

   function createDB() {
      global $dbhost;
      global $dbuser;
      global $dbpass;
      global $dbname;
      global $dbtable;

      // TODO $db vars check

      $sqlConn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
      if ($sqlConn->connect_error) {
         die("\nSQL Connection failed: ".$sqlConn->connect_error)."\n";
      }

      $sqlCreate = "CREATE TABLE IF NOT EXISTS ".$dbtable." (
         name VARCHAR(30) NOT NULL,
         surname VARCHAR(30) NOT NULL,
         email VARCHAR(50) NOT NULL,
         CONSTRAINT UNIQUE (email)
      )";

      if ($sqlConn->query($sqlCreate) === TRUE) {
         print "\ndb created.\n";
         $sqlConn->close();
         return TRUE;
      }
      else {
         print "\nERROR - db not created: ".$sqlConn->error."\n";
         $sqlConn->close();
         return FALSE;
      }

   }
 
   function createTable() {
      // this is set via spec to be "users"
      //TODO
      // requires -h -u -p options from commandline,
      // test version use default names for local

      print "\n request to create table\n";
      if (createDB()) {
         // wow
         print "\ncreated\n";
         interceptExit();
      }
      else {
         // sigh
         print "\nfailed\n";
         interceptExit();
      }
      
   }

   function prepEntriesDB() {
      // remove headings entry
      // check for null email, delete from array
      // can option here for check and edit instead of delete
      global $entryArray;
      $counter = 0;
   
      foreach ($entryArray as $entry) {
         if (($entry[0] === "name") 
               && ($entry[1] === "surname") 
               && ($entry[2] === "email")) {
            print "\nheadings entry: ".$entryArray[$counter][0]."\n";
            unset($entryArray[$counter]);
         }
         else if ($entry[2] === NULL) {
            unset($entryArray[$counter]);
         }
         $counter++;
      }
      return TRUE;  
   } 

   function addEntries() {
      // TODO assume has db, have set dbhost,dbuser,dbpass, has array
      global $dbhost;
      global $dbuser;
      global $dbpass;
      global $dbname;
      global $dbtable;
      global $entryArray;
      
      if (prepEntriesDB()) {
         $checkNum = sizeof($entryArray); 
         $counter = 0;

         $sqlConn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
         if ($sqlConn->connect_error) {
            die("\nSQL Connection failed: ".$sqlConn->connect_error)."\n";
         }

         $sqlStatement = $sqlConn->prepare("INSERT INTO ".$dbtable." (name, surname, email)".
                    "VALUES (?, ?, ?)");
         $sqlStatement->bind_param("sss", $name, $surname, $email);

         foreach ($entryArray as $entry) {
            $name = $entry[0];
            $surname = $entry[1];
            $email = $entry[2];
            $sqlStatement->execute();
            $counter++;
         }
         $sqlStatement->close();
         $sqlConn->close();

         //checker
         if ($counter === $checkNum) {
            print "DB entry number matches array size.\n";
         }
         else {
            print "DB entry number mismatch (count, size): ".$counter.", ".$checkNum."\n";
         }
      }
      else {
         //
      }
   }
   

// UTILS 

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

   function filenameCheck($str) {
      // check file is a .csv filename
      if(strpos($str, ".csv") !== FALSE) {
         return TRUE;
      }
      else 
         return FALSE;
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

   function outputHelp() {
      // make a bit verbose for proper help...
      print "\n-----------------------------\n";
      print "USER DATABASE ENTRY PROGRAM\n";
      print "instructions for use:\n\n";
      print "user_upload.php [--file][--dry_run][h][-u][-p][--create_table][--help]\n";
      print "--file [.csv filename]\n".          
            "--dry_run (used with --file, parses file for content but NO db actions\n".
            "-h (sql host)\n".            
            "-u (sql username)\n".
            "-p (sql password)\n".
            "--create_table (creates db table no other action taken)\n".
            "--help (print this menu)\n";
      print "\n-----------------------------\n\n";
   }

   function parseFilename($fileHandle) {
      // this is the full option with file and db entry, 
      // reqs: dbhost, dbuser, dbpass
      // check it first

      global $entryArray;

      print "\nfilename: ".$fileHandle."\n";

      // CHECK FILE
      print "\nFile check: ";
      
      if (filenameCheck($fileHandle)) {
         $rawFile = fopen($fileHandle, "r") or die("unable to open file: $fileHandle\n");
         $lineNums = 0;

         if ($rawFile) {
            while (($line = fgets($rawFile)) !== false) {
            
               $entryArray[$lineNums] = explode(",", cleanStringInput($line));
               $lineNums++;
            }
            fclose($rawFile);
            print "OK. file closed after $lineNums lines read.\n\n";
            processFileContents();
         } 
         else {
            // error opening the file.
            print ("error reading file\n");
            interceptExit();
         }
      }
      else {
         print "unknown file type: ".$str;
         interceptExit();
      }
   }

   function processFileContents() {
      // according to spec first line of file is headings "name,surname,email"
      global $entryArray;

      $contentNum = sizeof($entryArray);
      //ignore $fileContents[0] assume headings

      print "\nProcess file contents, format entries:\n";

      $lineNums = 0;
      foreach($entryArray as $entry) {
         
         if ($lineNums == 0) { 
            $lineNums++;
            // skip first entry as it 'should' be the headings
            continue; 
         }
         // first name
         $entryArray[$lineNums][0] = nameFormatAnglo($entry[0]);
         // surname
         $entryArray[$lineNums][1] = nameFormatAnglo($entry[1]);
         // email check, can return NULL
         $entryArray[$lineNums][2] = simpleEmailCheck($entry[2]);
         if ($entry[2] === NULL) {
            // have error in email
            // option to manually fix email?
            print $entryArray[$lineNums][0].
                  ",".$entryArray[$lineNums][1].
                  ", INVALID EMAIL.\n";
         }
         else {
            print $entryArray[$lineNums][0].
                  ",".$entryArray[$lineNums][1].
                  ",".$entryArray[$lineNums][2]."\n";
         }
         $lineNums++;
      }
      print "\nend of array print.\n\n";
      addEntries();
   }

   function dryRun() {
      global $entryArray;

      // needs a file of entries to run, no DB actions
      print "\nrequest to run non-DB functions on file.\n";
      // check for valid entryArray
      if (sizeof($entryArray) >= 2) {
         // assume have at least one entry as well as headings
         print "\nDry_run has an array to work with.\n";
         processFileContents();
      }
      else {
         print "\nDry_run did not find any entries to work with.\n";
         outputHelp();
         interceptExit();
      }
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
               case "--dry_run":
                  // check for filename
                  dryRun();
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
               case "--create_table":
                  createTable();
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




      $optionsArray = array("");

      print "\n------------------------\n";
      print "env info:\n";
      $phpv = phpversion();
      print "\nphp version: $phpv";
      $mysqlv = getMySQLVersion();
      print "\nmysql version: $mysqlv";
      print "\n------------------------\n\n";
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
         //protected $optionsArray = array("");
         foreach($argv as $argStr) {
            $optionsArray[$argsCount] = $argStr;
            $argsCount++;
         }
         parseOptions($optionsArray);
   //TODO
   // rem interceptExit() to allow program to continue 
         //interceptExit();
      }





?>

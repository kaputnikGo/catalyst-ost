<?php
   // test the php7 install
   // and figure how to cmdline output etc.
   // env: linuxmint 18 - Minty Fresh

   // ASSUMPTION anglo-saxon naming convention of a single capitalised first name & surname
   // columns spearated by a comma ($delimiter = ","), and Gaelic "descendant" abbr.
   // NOT looking for MacGregor, will result in Macgregor

   // ASSUMPTION email check will not check that domain name resolves to an actual mail server 
   // domain, errors to be outputted to terminal

   // ASSUMPTION lack of proper email means user is not entered into db, names must be present too

   // ASSUMPTION small csv file size means will use an array for pre-db

   // ASSUMPTION spec is for a program that enters data into db, no mention of db edits etc.
   // this program will therefore only add entries that do not already exist

   // ASSUMPTION not informing the user that dupes of emails exist and not going into DB

   // ASSUMPTION this program is not used in a public or production environment,
   // rather is for demo purposes in a localhost dev type environment
 

   //TODO
      // global vars... pass by ref
      // sane and proper into classes
      // sql checks
      // cmdline options proper
      // help display better info
      // order of execution in php

// GLOBAL VARS
   // set up empty 2D array for entries of 3 fields
   $entryArray = array(array("","",""));
   $dbhost = "localhost"; // default, to be set by user
   $dbuser = "catOST"; // default, to be set by user
   $dbpass = "password"; // default, to be set by user
   $dbtable = "users"; // set as per spec
//TODO this for local dev test only, rem at submission
   $dbname = "catalyst_ost"; // dev machine

// SQL FUNCTIONS
   function getMySQLVersion() { 
      $output = shell_exec('mysql -V'); 
      preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
      return $version[0]; 
   }

   function checkDBready() {
      global $dbhost;
      global $dbuser;
      global $dbpass;

      $missing = FALSE;

      if (empty($dbhost)) {
         print "\nWARN: Database host name missing.\n";
         $missing = TRUE;
      }
      if (empty($dbuser)) {
         print "\nWARN: Database user name missing.\n";
         $missing = TRUE;
      }
      if (empty($dbpass)) {
         print "\nWARN: Database pass word missing.\n";
         $missing = TRUE;
      }
      if ($missing) {
         outputHelp();
         interceptExit();
      }
   }

   function createDB() {
      global $dbhost;
      global $dbuser;
      global $dbpass;
      global $dbtable;
      global $dbname; // TODO rem

      checkDBready();

// TODO rem $dbname at submission
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

   function prepEntriesDB($entryArray) {
      // remove headings entry

      // check for null email, delete from array
      // NOTE: can option here for user check and edit instead of delete
      // function remains as placeholder
      
      $counter = 0;
   
      foreach ($entryArray as $entry) {
         if (($entry[0] === "name") 
               && ($entry[1] === "surname") 
               && ($entry[2] === "email")) {
            unset($entryArray[$counter]);
         }
         else if ($entry[2] === NULL) {
            unset($entryArray[$counter]);
         }
         $counter++;
      }
      return TRUE;  
   } 

   function addEntries($entryArray) {
      // TODO assume has db, have set dbhost,dbuser,dbpass, has array
      global $dbhost;
      global $dbuser;
      global $dbpass;
      global $dbtable;
      global $dbname; // TODO rem
      
      checkDBready();

      print "\nAdding entries from file to database...\n";
     
      if (prepEntriesDB($entryArray)) {
         $checkNum = sizeof($entryArray); 
         $counter = 0;

// TODO rem $dbname at submission
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
            print "Database entry complete.\n";
         }
         else {
            print "\nWARN: Database entry number mismatch (count, size): ".$counter.", ".$checkNum."\n";
         }
      }
      else {
         print "\nWARN: Failed to prepare entries for database.\n";
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
      // allow ' (apostrophe) and - (hyphen) as valid char in name
      $str = preg_replace("/[^a-zA-Z'-]/", "", $str);
      $str = strtolower($str);
      $str = ucfirst($str);
      // specific: need check for "O'Conner" etc, capitalised after the apostrophe.
      if (strpos($str, "'") == 1) {
         $str = join("'", array_map("ucfirst", array_map("strtolower", explode("'", $str))));
      }
      // or have a hyphenated name
      if (strpos($str, "-") !== FALSE) {
         $str = join("-", array_map("ucfirst", array_map("strtolower", explode("-", $str))));
      }
      return $str;
   }

   function simpleEmailCheck($str) {
      // only checks format of email string, not TLD validity etc
      // spec requests emails to lowercase
      // NOTE common allows for emails names (RFC 3696):
      /*
         a–z, A–Z, 0-9, !#$%&'*+-/=?^_`{|}~ 
      */

      if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
         // valid address
         $str = strtolower($str);
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
      print "\n-------------------------------------------------------------------------------\n\n";
      print ":: USER DATABASE ENTRY DEMO PROGRAM :: \n\n";
      print "A program that accepts a .csv file for inputting into an existing database.\n";
      print "Coarse checks and cleans are made over the names and email addresses inputted.\n";
      print "Unless requested from management, there is no option for editing entries.\n";
      print "This program expects benevolent and dispassionate user interaction only.\n";
      print "\nCommand line use:\n\n";
      print "user_upload.php [--file][--dry_run][h][-u][-p][--create_table][--help]\n";
      print "--file (csv file of name, surname, email)\n".          
            "--dry_run (used with --file, parses file for content but NO db actions)\n".
            "-h (sql host)\n".            
            "-u (sql username)\n".
            "-p (sql password)\n".
            "--create_table (creates db table no other action taken)\n".
            "--help (print this menu)\n";
      print "\n-------------------------------------------------------------------------------\n\n";
   }

   function processFileContents($entryArray) {
      // according to spec first line of file is headings "name,surname,email"
      $contentNum = sizeof($entryArray);
      //ignore $fileContents[0] assume headings

      print "\nProcess file contents, format entries:\n";

      $lineNums = 0;
      foreach($entryArray as $entry) {
         
         if ($lineNums == 0) { 
            // del first entry as it 'should' be the headings
            unset($entryArray[$lineNums]);            
            $lineNums++;
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
         $lineNums++;
      }
      print "\nEnd of file processing.\n\n";
      addEntries($entryArray);
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
            processFileContents($entryArray);
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

   function dryRun() {
      global $entryArray;

      // needs a file of entries to run, no DB actions
      print "\nrequest to run non-DB functions on file.\n";
      // check for valid entryArray
      if (sizeof($entryArray) >= 2) {
         // assume have at least one entry as well as headings
         print "\nDry_run has an array to work with.\n";
         processFileContents($entryArray);
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

   function runtime($argc, $argv) {

      $optionsArray = array("");
      print "\n--------------------------------------------\n";
      print "USER DATABASE ENTRY DEMO PROGRAM\n";
      print "CSV file (name, surname, email) to database.\n";
      print "\n";
      print "\nenv info:";
      $phpv = phpversion();
      print "\nphp version: $phpv";
      $mysqlv = getMySQLVersion();
      print "\nmysql version: $mysqlv";
      print "\n--------------------------------------------\n\n";
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
      }
   }

runtime($argc, $argv);

?>

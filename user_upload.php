<?php
/********************************************

User database entry program.
-  For localhost commandline use only.
-  Check assumption list below.
-  Test version, not production.

-  Written on xed via an A1297, Linux Mint 18
   tested using PHP 7.0.18, MYSQL 5.7.19
   git via GitEye

*********************************************/

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


/*********************

GLOBALLED VARS

*********************/
   // set up empty 2D array for entries of 3 fields
   $entryArray = array(array("","",""));
   $dbhost = ""; // dev: localhost, to be set by user
   $dbuser = ""; // dev: catOST, to be set by user
   $dbpass = ""; // dev: password, to be set by user
   $dbtable = "users"; // set as per spec
//TODO this for local dev test only, rem at submission
   $dbname = "catalyst_ost"; // dev machine


/*********************

MYSQL FUNCTIONS

*********************/
   function getMySQLVersion() { 
      $output = shell_exec('mysql -V'); 
      preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
      return $version[0]; 
   }

   function testDBconnect() {
      // undocumented dev debug, just in case
      print "\nTEST: database connection:";
      global $dbhost;
      global $dbuser;
      global $dbpass;
      global $dbname; // TODO rem

      if (empty($dbhost) || empty($dbuser) || empty($dbpass)) {
         print "\nWARN: either db host,user or pass info missing.\n";
      }
      else {
         // TODO rem $dbname at submission

         $sqlConn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
         if ($sqlConn->connect_error) {
            print "\nSQL Connection failed: ".$sqlConn->connect_error."\n";
         } 
         else {
            print "\nSQL connection successful, closing...\n";
            $sqlConn->close();
         }
      }
      interceptExit();
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
         print "Halting program with no database actions performed.\n";
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
         print "\nSQL Connection failed: ".$sqlConn->connect_error."\n";
         interceptExit();
      }

      $sqlCreate = "CREATE TABLE IF NOT EXISTS ".$dbtable." (
         name VARCHAR(30) NOT NULL,
         surname VARCHAR(30) NOT NULL,
         email VARCHAR(50) NOT NULL,
         CONSTRAINT UNIQUE (email)
      )";

      if ($sqlConn->query($sqlCreate) === TRUE) {
         print "\nTable created: ";
         $sqlConn->close();
         return TRUE;
      }
      else {
         print "\nWARN: Table not created: ".$sqlConn->error."\n";
         $sqlConn->close();
         return FALSE;
      }

   }
 
   function createTable() {
      // requires -h -u -p options from commandline,
      print "\nCreate table: ".$dbtable."\n";
      if (createDB()) {
         print "OK\n";
      }
      interceptExit();
   }

   function prepEntriesDB($entryArray) {
      // remove headings entry
      // check for null email, delete from array
      // NOTE: can option here for user check and edit instead of delete
      
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

   function addEntries(&$entryArray) {
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
            print "\nSQL Connection failed: ".$sqlConn->connect_error."\n";
            inteceptExit();
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
            print "\nWARN: Database entry number mismatch (count, size): ".
                     $counter.", ".$checkNum."\n";
         }
      }
      else {
         print "\nWARN: Failed to prepare entries for database.\n";
      }
   }
   

/*********************

UTILITY FUNCTIONS

*********************/ 

   function interceptExit() {
      print "\nexiting...\n";
      exit();
   }

   function cleanStringInput($str) {
      $str = @strip_tags($str, ".");
      $str = @stripslashes($str);
      $str = preg_replace('/\s+/', '', $str);
      return $str;
   }

   function filenameCheck($str) {
      // check file is a .csv filename
      return (strpos($str, ".csv") !== FALSE);
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

/*********************

FILE FUNCTIONS

*********************/
      
   function checkFileHeading($entryArray) {
      // look for "name","surname","email"
      return ((strtolower($entryArray[0]) === "name") &&
            (strtolower($entryArray[1]) === "surname") &&
            (strtolower($entryArray[2]) === "email"));
   }

   function processFileContents(&$entryArray, $dbentry) {
      // according to spec first line of file is headings "name,surname,email"
      //ignore $fileContents[0] assume headings - check

      print "\nProcess file contents, format entries:\n";

      $contentNum = sizeof($entryArray);
      $lineNums = 0;

      foreach($entryArray as $entry) {
         
         if ($lineNums == 0) { 
            // del first entry as it 'should' be the headings- check
            if (checkFileHeading($entryArray[$lineNums])) {
               unset($entryArray[$lineNums]);
            }          
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
   
      if ($dbentry === TRUE) {
         print "\nAdd entries.\n";
         addEntries($entryArray);
      }
      else {
         print "\nEntries not added.\n";
      }
   }

   function parseFilename($fileHandle, $dbentry) {
      // this could be full option with file and db entry, or dry_run 
      // reqs: dbhost, dbuser, dbpass
      // check it first, default to no db actions
      if ($dbentry === NULL) $dbentry = FALSE;

      global $entryArray;

      print "\nfilename: ".$fileHandle."\n";
      print "\nFile check: ";
      
      if (filenameCheck($fileHandle)) {
         try {
            if(!file_exists($fileHandle)) {
               throw new Exception("\nWARN: File not found\n");
            }
            $rawFile = fopen($fileHandle, "r");
            $lineNums = 0;
            if (!$rawFile) {
               throw new Exception("\nWARN: error opening file.\n");
            }
            else  {
               while (($line = fgets($rawFile)) !== false) {
               
                  $entryArray[$lineNums] = explode(",", cleanStringInput($line));
                  $lineNums++;
               }
               fclose($rawFile);
               print "OK. file closed after $lineNums lines read.\n\n";
               processFileContents($entryArray, $dbentry);
            }
         } 
         catch (Throwable $th) {
            print ("Exiting program due to: ". $th->getMessage()."\n");
            interceptExit();
         }
         catch (Exception $ex) {
            // legacy for PHP 5.x, not reached in PHP 7
            print ("Exiting program due to: ". $ex->getMessage()."\n");
            interceptExit();
         }
      }
      else {
         print "unknown file type: ".$fileHandle;
         outputHelp();
         interceptExit();
      }
   }

/*********************

COMMAND LINE FUNCTIONS

*********************/

   function outputHelp() {
      print "\n-------------------------------------------------------------------------------\n\n";
      print ":: USER DATABASE ENTRY DEMO PROGRAM :: \n\n";
      print "A program that accepts a .csv file for inputting into an existing database.\n";
      print "Database host, username, password need to be set prior to attempting to \n";
      print "populate database.\n\n";
      print "Coarse checks and cleans are made over the names and email addresses inputted.\n";
      print "Unless requested from management, there is no option for editing entries.\n";
      print "This program expects benevolent and dispassionate user interaction only.\n";
      print "\nCommand line use:\n\n";
      print "user_upload.php [--file][--dry_run][-h][-u][-p][--create_table][--help]\n";
      print "\t--file (csv file of name, surname, email)\n".          
            "\t--dry_run (include after --file, parses file for content but NO db actions)\n".
            "\t-h (sql host)\n".            
            "\t-u (sql username)\n".
            "\t-p (sql password)\n".
            "\t--create_table (creates db table no other action taken)\n".
            "\t--help (print this menu)\n";

      print "\nExample usage:\n \t> user_upload.php --file users.csv --dry_run\n";
      print "\t> user_upload.php -h localhost -u myuser -p password --create_table\n";
      print "\t> user_upload.php -h localhost -u myuser -p password --file users.csv\n";
      print "\n-------------------------------------------------------------------------------\n\n";
   }

   function parseSQLusername($str) {
      global $dbuser;
      // check it first
      $dbuser = cleanStringInput($str);
      print "\nSQL username: ".$dbuser."\n";
   }

   function parseSQLpassword($str) {
      global $dbpass;
      // check it first and print teh passw0rdz!!
      $dbpass = cleanStringInput($str);
      print "\nSQL password: ".$dbpass."\n";
   }

   function parseSQLhost($str) {
      global $dbhost;      
      // TODO need a sensible check function, could be ip
      $dbhost = $str;
      print "\nSQL host: ".$str."\n";
      
   }

   function parseSQLoptions($optionsArray) {
      $optionNum = 0;
      foreach($optionsArray as $option) {
         if (substr($option[0], 0, 1) == "-") {
            switch ($option) {
               case "-h":
                  parseSQLhost($optionsArray[$optionNum + 1]);
                  break;
               case "-u":
                  parseSQLusername($optionsArray[$optionNum + 1]);
                  break;
               case "-p":
                  parseSQLpassword($optionsArray[$optionNum + 1]);
                  break;
               case "--create_table":
               case "--help":
               case "--test":
               case "--file":
               case "--dry_run":
                  // skip above
                  break;
               default:
                  print "\nunknown option found: ".$optionsArray[$optionNum]."\n";
                  outputHelp();
                  interceptExit();
            }
         }
         $optionNum++;
      }      
   }

   function parseOptions($optionsArray) {
      // usage: "--filename filenametxt" not "--filename --help --iamidiot"

      print "\nParsing options...\n";

      // need to check $optionsArray for dependencies
      $fileOpt = array_search("--file", $optionsArray);
      $dryOpt = array_search("--dry_run", $optionsArray);
      $dbOpt = array_search("--create_table", $optionsArray);
      $testOpt = array_search("--test", $optionsArray);
     
      if (($dryOpt >= 1) && ($fileOpt === FALSE)) {
         // d'oh
         print "\nWARN: a filename option was not found.\n";
         outputHelp();
         interceptExit();
      }      
      elseif (($dryOpt >= 1) && ($fileOpt >= 1)) {
         if ($fileOpt + 1 > (sizeof($optionsArray) - 1)) {
            print "\nWARN: no filename option.\n";
            outputHelp();
            interceptExit();
         }
         else {
            parseFilename($optionsArray[$fileOpt + 1], FALSE);
         }
      }
      elseif ($fileOpt >= 1) {
         // check have a filename too
         if ($fileOpt + 1 > (sizeof($optionsArray) - 1)) {
            print "\nWARN: no filename option.\n";
            outputHelp();
            interceptExit();
         }
         else {
            // check have sql connection info
            parseSQLoptions($optionsArray);
            parseFilename($optionsArray[$fileOpt + 1], TRUE);
         }
      }
      elseif ($dbOpt >= 1) {
         parseSQLoptions();
         createTable();    
      }
      elseif ($testOpt >=1) {
         parseSQLoptions($optionsArray);
         testDBconnect();
      }
      else {
         print "\nWARN: error parsing commandline options.\n";
         outputHelp();
         interceptExit();
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

      // $argc = number of arguments passed 
      // $argv = arguments passed
      // first $argc is for php exe filename

      if ($argc <= 1) {
         print "You need to add options for this program to run.\n\n";
         outputHelp();
         interceptExit();
      }
      elseif ($argc >= 12) {
         print "You seem to have used too many options...\n\n";
         outputHelp();
         interceptExit();
      }
      else {
         $argsCount = 0;
         foreach($argv as $argStr) {
            $optionsArray[$argsCount] = $argStr;
            $argsCount++;
         }
         parseOptions($optionsArray);
      }
   }

runtime($argc, $argv);

?>

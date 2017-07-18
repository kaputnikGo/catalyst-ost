<?php
   // test the php7 install
   // and figure how to cmdline output etc.
   // env: linuxmint 18
   print "HelloWorld!\n";


   $phpv = phpversion();
   print "\nphp version: $phpv";
   $mysqlv = getMySQLVersion();
   print "\nmysql version: $mysqlv";

   print "\n\n------------------------\n";

   // FUNCTIONS
   
   function getMySQLVersion() { 
      $output = shell_exec('mysql -V'); 
      preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version); 
      return $version[0]; 
   }

   function cleanStringInput($str) {
      $str = @strip_tags($str);
      $str = @stripslashes($str);
      //$str = mysql_real_escape_string($str);
      return $str;
   }

   // CMDLINE ARGS

   //var_dump($argc); //number of arguments passed 
   //var_dump($argv); //the arguments passed
   // first $argc is for php exe filename
   if ($argc == 2) {
      print "\nfound cmdline argument:\n";
      $cleanArg = cleanStringInput($argv[1]);
      print "$cleanArg \n";
   }
   elseif ($argc >= 3) {
      // too many args
      print "Too many arguments sent, exiting...\n";
      // TODO make meaningful reply
      exit();
   }
   else {
      // no input filename found at cmdline
      print "You need to add a filename for processing ie: test.php filename.txt\n";
      print "exiting...\n";
      exit();
   }

   // CHECK FILE HAS CONTENTS
   print "\nFile check:\n\n";
   $rawFile = fopen($cleanArg, "r") or die("unable to open file: $cleanArg\n");
   $lineNums = 0;
   $fileContents[] = "";
   if ($rawFile) {
      while (($line = fgets($rawFile)) !== false) {
         print "$line";
         $fileContents[$lineNums] = $line;
         $lineNums++;
      }
      fclose($rawFile);
      print "\nfile closed after $lineNums lines read.\n\n";
   } 
   else {
      // error opening the file.
      print ("error reading file\n");
   }

   // CONTENTS CHECK
   // according to spec first line of file is headings "name,surname,email"
   $contentNum = sizeof($fileContents);
   print "Contents Check:\n\nfile contents size: $contentNum\n";
   //ignore $fileContents[0] assume its the headings


   // SQL DB
   // will create/use specific "users" db 
   // ASSUMPTION anglo-saxon naming convention of a single capitalised first name & surname
   
   // force all names to lowercase then uppercase first letter, 
   // strip spaces, strip chars other than apostrophes (ie. O'Really)
   
   // emails are to be forced lowercase, check for proper format name@domain.com 
   // ASSUMPTION will not check that domain name resolves to an actual mail server domain
   // errors to be outputted for human checking (perhaps a simple common error etc)
   
   // allow user to manually edit an email ?
   // after checks make safe for db entry

   // RUNTIME 

?>

<?php
/************************

CMDLINE COUNTER FUNCTION
prints 1-100 and word
   substitutes

************************/

   function outputCount($number) {
      // single case
      if ($number == 1) {
         print $number; 
         $number++;
         outputCount($number); 
      }
      if ($number >= 101) {
         print "\n\nend of function\n";
         exit();
      }
      
      // multiple cases
      if (($number % 3 == 0) && ($number % 5 == 0)) {
         print ", foobar"; 
      }
      elseif ($number % 3 == 0) {
         print ", foo";   
      }
      elseif ($number % 5 == 0) {
         print ", bar";  
      }
      else {
         print ", ".$number;
      }
      $number++;
      outputCount($number);
   }

print "\nCount 1-100 with some word substitutes:\n\n";
outputCount(1);     

?>

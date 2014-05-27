<?php
  header('Content-Type: text/plain; charset="us-ascii"');
  include_once("com/hartwick/mysql.class.inc");

  $dbh = new \com\hartwick\MyDB();
  echo $dbh->version();
?>

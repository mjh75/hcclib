<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>MyDB Demo</title>
  </head>
  <body>
    <?php
    error_reporting(E_ALL);
    echo date('M d, Y H:i:s');

    include_once("com/hartwick/mysql.class.inc");

    /*
     * Instantiate the class
     */
    $dbh = new \com\hartwick\MyDB('localhost', 'mydbdemo', 'mydbdemo', 'mydbdemo');

    /*
     * Check for connection errors and bail if we cannot connect.
     */
    if($dbh->ConnectErrno() != 0) {
      die("Could not connect: [".$dbh->ConnectErrno(). "] ".$dbh->ConnectError()."\r\n");
    }

    /*
     * Display the current version of the class
     */
    echo "<p>Using MyDB class version ".__MYDB_VERSION__."</p>";
    /*
     * Check to see if this is the latest version
     */
    $dbh->IsLatestVersion();
    /*
     * Store the message into a variable so it can be displayed and/or emailed
     */
    $message = "<p>This is part of a demo.</p>";

    /*
     * Get a Query Set key
     */
    $key = $dbh->getQS();
    /*
     * Set and execute the query using the Query Set key we got
     */
    $dbh->Query($key, "SHOW TABLES");

    /*
     * Check to see if there is an error set for the query, if so display
     * both the query and the error it returned.
     */
    $ret = $dbh->QSError($key);
    if(!empty($ret)) {
      echo "<p>Query: ".$dbh->Query($key)." failed with $ret</p>\r\n";
      $dbh->freeQS($key);
      exit();
    }
    /*
     * If we have some rows of results we can continue
     */
    if($dbh->getQSRowCount($key) > 0) {
      /*
       * Walk through the Query Set's rows and get the back as a numeric array
       */
      while($row = $dbh->getQSRow($key, "row")) {
        $message .= "<h1>Table: ".$row[0]."</h1>";
        /*
         * Get a second Query Set key so we can run a nested query
         */
        $key2 = $dbh->getQS();
        /*
         * Build the query to select all columns from the tables we found in
         * the previous query
         */
        $query = "SELECT * FROM ".$row[0];
        /*
         * Run the query with the new Query Set key
         */
        $dbh->Query($key2, $query);
        /*
         * Check to see if we received rows in the Query Set
         */
        if($dbh->getQSRowCount($key2) > 0) {
          /*
           * Walk through the Query Set's rows and get the results back as
           * an object
           */
          while($row2 = $dbh->getQSRow($key2, "object")) {
            $message .= "<p>".$row2->id."</p>";
          }
        }
        /*
         * We can free the second Query Set
         */
        $dbh->freeQS($key2);
      }
    }
    /*
     * We can free the first Query Set
     */
    $dbh->freeQS($key);

    /*
     * This is used to demonstrate the html2text class
     */
    include_once("com/hartwick/html2text.class.inc");
    $h2t = new \com\hartwick\html2text($message);
    $text = $h2t->getText(70);

    /*
     * This is for demonstrating how to send mail with the class
     */
    include_once("com/hartwick/smtpmail.class.inc");
    $m = new \com\hartwick\smtpmail();
    $m->setSender("hartwick@hartwick.com");
    $m->addRecipient("hartwick@hartwick.com");
    $m->addHeader("Subject: HCC Class Demo");
    $m->send($text, $message);

    echo "<div style='border: 1px solid black;'>";
    echo $message;
    echo "</div>";
    echo date('M d, Y H:i:s');
    ?>
  </body>
</html>

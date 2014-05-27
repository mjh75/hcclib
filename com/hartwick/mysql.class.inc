<?php namespace com\hartwick;
define("__MYDB_VERSION__", "2.1.0");
define("__MYDB_URL__", "http://www.hartwick.com/software/com_hartwick/mydbversion.php");
include_once("myqs.class.inc");
/*! \file mysql.class.inc
 * \brief Contains the MyDB class
 * \author Michael J. Hartwick <hartwick@hartwick.com>
 * \version 2.1.0
 */

/*!  \brief MySQL Database class.
 *
 * This MySQL Database class performs some sanity checking on return values and
 * sets various members to contain the insert ID, number of rows, error values etc.
 */
class MyDB {
  private $dbg;         /*!< Debug flag. Not currently used */
  private $readerhost;  /*!< The reader host name */
  private $writerhost;  /*!< The writer host name */
  private $username;    /*!< The username to use when connecting */
  private $password;    /*!< The password to use when connecting */
  private $readerdb;    /*!< The reader database handle */
  private $writerdb;    /*!< The writer database handle */
  private $database;    /*!< The name of the database to connect to */
  private $profile;     /*!< The call profile */
  private $QS;          /*!< The "master" variable that contains all of the Query Sets */
  private $errno;       /*!< Connection wide error number, not for specific queries */
  private $error;       /*!< Connection wide error, not for specific queries */
  public $showProfile;  /*!< Do we want the profile displayed */

  /*! \brief Method to return this classes version
   *
   * This method simply returns the version of the class.
   */
  public function version() {
    return __MYDB_VERSION__;
  }

  /*! \brief Check to see if the version is current
   *
   * Check back with the server to make sure the latest version of the library is being used.
   */
  public function IsLatestVersion() {
    $contents = '';
    $handle = @fopen(__MYDB_URL__, "r");
    if($handle == FALSE) {
      return -1;
    }
    while(!feof($handle)) {
      $contents .= fread($handle, 8192);
    }
    fclose($handle);
    $latestversion = $contents;
    if($latestversion != $this->version()) {
      echo "MyDB_class is not up to date. The latest version is $latestversion.";
    }
  }

  /*! \brief Open a "reader" connection
   *
   */
  private function safe_reader_connect() {
    $this->profile['readconnections']++;
    if(is_resource($this->readerdb)) {
      if(\mysqli_ping($this->readerdb)) {
       return TRUE;
      } else {
        $this->readerdb = 0;
      }
    }
    $this->readerdb = @mysqli_connect($this->readerhost, $this->username, $this->password, $this->database) or $this->readerdb = 0;
    if(\mysqli_connect_errno($this->readerdb)) {
      $this->error = \mysqli_connect_error();
      $this->errno = \mysqli_connect_errno();
      return FALSE;
    }
    return TRUE;
  }

  /*! \brief Open a "writer" connection
   * \todo Copies the reader connection to the writer and returns. This method might be a candidate for removal or it might need to be fixed to open a second connection only if a different server exists.
   * \return true On success returns true
   * \return false On failure returns false
   */
  private function safe_writer_connect() {
    $this->writerdb = $this->readerdb;
    return TRUE;
    $this->profile['writeconnections']++;
    if(is_resource($this->writerdb)) {
      if(mysqli_ping($this->writerdb)) {
        return TRUE;
      }
      $this->writerdb = 0;
    }
    $this->writerdb = @mysqli_connect($this->writerhost, $this->username, $this->password, $this->database) or $this->writerdb = 0;
    if(\mysql_connect_errno($this->writerdb)) {
      $this->error = \mysqli_connect_error();
      $this->errno = \mysqli_connect_errno();
      return FALSE;
    }
    return TRUE;
  }

  /*! \brief Execute a query
   * \param $key The index of the Query Set to be used
   * \return true On success return true
   * \return false On failure return false
   */
  public function real_query($key = NULL) {
    if($key === NULL) {
      return;
    }
    if($this->QS[$key]->isRead()) {
      $this->profile['readquery']++;
      $dbhandle = $this->readerdb;
    } else {
      $this->profile['writequery']++;
      $dbhandle = $this->writerdb;
    }
    if(!is_resource($dbhandle)) {
      if($this->safe_reader_connect() === FALSE) {
        return FALSE;
      }
    }
    $result = \mysqli_query($dbhandle, $this->QS[$key]->getQuery());
    if($result === FALSE) {
      error_log("Query: ".$this->QS[$key]->getQuery());
      $back = $this->backtrace();
      error_log($back);
      $this->QS[$key]->setError(\mysqli_errno($dbhandle), \mysqli_error($dbhandle));
      return FALSE;
    } else {
      if($this->QS[$key]->isRead()) {
        $this->QS[$key]->setResult($result);
        $this->QS[$key]->setRows(\mysqli_num_rows($this->result($key)));
      } else {
        $this->QS[$key]->setRows(\mysqli_affected_rows($dbhandle));
        if(\mysqli_insert_id($dbhandle)) {
          $this->QS[$key]->setInsert(\mysqli_insert_id($dbhandle));
        }
      }
    }

    $this->profile['rows'] += $this->QS[$key]->getRows();
    return TRUE;
  }

  /*! \brief Execute a write query
   * \param $key The Query Set index to work with
   * \deprecated This function will be removed in a future version.
   */
  public function safe_writer_query($key = NULL) {
    error_log("safe_writer_query has been deprecated. Please update to use MyDB::real_query()");
    return $this->real_query($key);
  }

  /*! \brief Execute a read query
   * \param $key The Query Set index to work with
   * \deprecated This function will be removed in a future version.
   */
  public function safe_reader_query($key = NULL) {
    error_log("safe_reader_query has been deprecated. Please update to use MyDB::real_query()");
    return $this->real_query($key);
  }

  /*! On instantiation of the class we can configure the connection parameters
   *  and establish a connection to the reader and writer databases. Also register
   *  a call to the shutdown method to ensure that the memory is released and the
   *  database connections closed.
   * @see configure()
   * @param $server The IP or hostname of the server
   * @param $database The name of the database to connect to
   * @param $username The username to use for the connection
   * @param $password The password to use for the connection
   * @return TRUE on successful connection
   * @return FALSE on connection failure (see errno and error properties)
   */
  public function __construct($server = "", $database = "", $username = "", $password = "") {
    $this->profile = array('writequery'=>0,
                           'readquery'=>0,
                           'writeconnections'=>0,
                           'readconnections'=>0,
                           'rows'=>0
                          );
    $this->configure($server, $database, $username, $password);
    $this->safe_reader_connect();
    $this->safe_writer_connect();
    $this->QS = array();
    $this->addQS();
    $this->showProfile = 0;
    register_shutdown_function(array(&$this, "shutdown"));
  }

  /*! \brief Return the connect error number
   * @return ErrNo The connect error number
   */
  public function ConnectErrno() {
    return $this->errno;
  }

  /*! \brief Return the connect error message
   * @return Error The connect error message
   */
  public function ConnectError() {
    return $this->error;
  }

  /*! Make sure we free the results and if the connections
   *  are open to the reader and writer databases close them and free the memory
   *  used by this class.
   */
  public function shutdown() {
    foreach($this->QS as $qs) {
      $qs->free();
      $qs->unlock();
    }
    if($this->readerdb == $this->writerdb) {
      $this->writerdb = NULL;
    }
    if($this->readerdb !== NULL && \is_resource($this->readerdb)) {
      if(\mysqli_close($this->readerdb) == FALSE) {
        $back = $this->backtrace();
        error_log($back);
      }
      $this->readerdb = NULL;
    }
    if($this->writerdb !== NULL && \is_resource($this->writerdb)) {
      if(\mysqli_close($this->writerdb) == FALSE) {
        $back = $this->backtrace();
        error_log($back);
      }
      $this->writerdb = NULL;
    }
    if($this->showProfile) {
      error_log("-----------------------");
      error_log($this->username."@".$this->readerhost.":/".$this->database);
      error_log(" Read connections: ".$this->profile['readconnections']);
      error_log("Write connections: ".$this->profile['writeconnections']);
      error_log("     Read queries: ".$this->profile['readquery']);
      error_log("    Write queries: ".$this->profile['writequery']);
      error_log("    Rows Returned: ".$this->profile['rows']);
      error_log("-----------------------");
    }
    unset($this->profile);
  }

  /*! Take the connection information and store that in the appropriate
   *  attributes.
   * @param $server The IP or hostname of the server
   * @param $database The name of the database to connect to
   * @param $username The username to use for the connection
   * @param $password The password to use for the connection
   */
  public function configure($server, $database, $username, $password) {
    $this->readerhost = $server;
    $this->writerhost = $server;
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
  }

  /*! Set the debug attribute to the value passed. Basically an interface
   *  to the private debug attribute.
   * @param $state The debug level we want to enable
   */
  public function debug($state) {
    $this->dbg = $state;
  }

  /*! Just return the type of class for print_r() etc.
   */
  public function __toString() {
    return "DB Class";
  }

  private function Backtrace($NL = "<br>") {
    $dbgTrace = \debug_backtrace();
    $dbgMsg = $NL."Debug backtrace begin:$NL";
    foreach($dbgTrace as $dbgIndex => $dbgInfo) {
        $dbgMsg .= "\t at $dbgIndex  ".$dbgInfo['file']." (line ${dbgInfo['line']}) -> ${dbgInfo['function']}(".\join(',', $dbgInfo['args']).")$NL";
    }
    $dbgMsg .= "Debug backtrace end$NL";
    return $dbgMsg;
  }

  /*! \brief Create a new Query Set
   *
   * This method creates a new Query Set, registers it with the index of Query
   * Sets and returns it for use.
   */
  private function addQS() {
    $size = array_push($this->QS, new MyQS());
    $key = $size - 1;
    if($this->QS[$key]->isLocked()) {
      $this->QS[$key]->unlock();
    } else {
      $key = -1;
    }
    return $key;
  }

  /*! \brief Get an ID for a Query Set (QS)
   *
   * This method checks the currently registered Query Set's for an unlocked
   * set so we can reuse an existing Query Set instead of having to create a
   * new one. If there are no registered but unused Query Set's request a new
   * Query Set be created, lock it and return the index of that Query Set.
   */
  public function getQS() {
    $as = count($this->QS);
    if($as == 0) {
      return $this->addQS();
    }
    foreach($this->QS as $key=>$qs) {
      if($qs->isLocked() == 0) {
        $qs->lock();
        return $key;
      }
    }
    $key = $this->addQS();
    $this->QS[$key]->lock();
    return $key;
  }

  /*! \brief Release a Query Set so it can be reused
   *
   * Makes a call to the free method of MyQS to free the Query Set, then set
   * the Query Set to unlocked so it can be reused.
   */
  public function freeQS($key) {
    if(!isset($key)) {
      return;
    }
    $qs = $this->QS[$key];
    $qs->free();
    $qs->unlock();
  }

  /*! \brief Run the query with some housekeeping
   *
   * Run the query against the database. Optionally, specifying a Query Set
   * index (highly encouraged), and the SQL query to run.
   * \param $key The Query Set index for this query
   * \param $q The query to execute
   */
  public function query($key = NULL, $q = NULL) {
    $qs = $this->QS[$key];
    if($q === NULL) {
      return $qs->getQuery();
    }
    $qs->setQuery($q);
    $this->real_query($key);
    return $qs->getResult();
  }

  /*! \brief Get the result from the QS
   * \param $key The Query Set index we are working with
   * \return The result resource
   */
  private function result($key = NULL) {
    if($key === NULL) {
      return NULL;
    }
    return $this->QS[$key]->getResult();
  }

  /*! \brief Get the next row from the Query Set
   * \param $key The Query Set that we want the results for
   * \param $format The output format of the row. object is an object, row is a numerical array, assoc is an associative array
   * \return The next row available to be retreived
   * \return FALSE on no more rows
   */
  public function getQSRow($key, $format = "object") {
    if($format === "object") {
      $row = mysqli_fetch_object($this->result($key));
    } else if($format === "row") {
      $row = mysqli_fetch_row($this->result($key));
    } else if($format === "assoc") {
      $row = mysqli_fetch_assoc($this->result($key));
    }
    return $row;
  }

  /*! \brief Get the error that a Query Set has
   * \param $key The Query Set index
   * \return null if no index provided
   * \return The value returned by QS->getError
   * \sa MyQS::getError
   */
  public function QSError($key = NULL) {
    if($key === NULL) {
      return NULL;
    }
    return $this->QS[$key]->getError();
  }

  /*! \brief Get the number of rows returned by the Query Set
   * \param $key The Query Set index we are working with
   * \return The number of rows in the Query Set
   * \return FALSE on missing key
   */
  public function getQSRowCount($key = NULL) {
    if($key === NULL) {
      return FALSE;
    }
    return $this->QS[$key]->getRows();
  }

  /*! \brief Get the Insert ID from the Query Set
   * \param $key The Query Set index we are working with
   * \return The insert id
   * \return FALSE on missing key
   */
  public function getQSInsertID($key = NULL) {
    if($key === NULL) {
      return FALSE;
    }
    return $this->QS[$key]->getInsert();
  }
}

if(!extension_loaded("mysqli")) {
  error_log($_SERVER['SCRIPT_FILENAME']." - PHP does not have MySQLi support.\n");
  exit();
}
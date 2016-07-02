<?php namespace com\hartwick;
/* 
 * Copyright (C) 2016 Michael J. Hartwick <hartwick at hartwick.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*!  \brief MyQS Database Query Set class.
 *
 * Allow the MyDB class to handle multiple queries over one database connection.
 * This is done by implementing query specific values in this class which is
 * used in an array in MyDB. Not the prettiest way of doing it, but it does
 * work.
 *
 * \author Michael J. Hartwick <hartwick@hartwick.com>
 * \version 2.1.0
 */
class MyQS {
  protected $locked;    /*!< The locked flag */
  protected $query;     /*!< The query */
  private $result;    /*!< The results */
  protected $insertid;  /*!< The insert ID */
  protected $rows;      /*!< The number of rows in this Query Set */
  protected $errno;     /*!< The error number */
  protected $error;     /*!< The error message */
  protected $read;      /*!< Read only flag */

  /*! \brief Constructor that unset's all of the variables
   * used in the class
   */
  public function __construct() {
    unset($this->query);
    unset($this->result);
    unset($this->insertid);
    unset($this->rows);
    unset($this->errno);
    unset($this->error);
    unset($this->read);
  }

  /*! \brief Lock this Query Set
   *
   */
  public function lock() {
    $this->locked = 1;
  }

  /*! \brief Unlock this Query Set
   *
   */
  public function unlock() {
    $this->locked = 0;
  }

  /*! \brief If this Query Set is locked.
   * \return 1 for locked
   * \return -1 for unlocked
   */
  public function isLocked() {
    if(!isset($this->locked)) {
      return -1;
    } else {
      return $this->locked;
    }
  }

  /*! \brief If this Query Set is a read only.
   * \return TRUE for read query
   * \return FALSE for write query
   * \return -1 for unknown
   */
  public function isRead() {
    if(!isset($this->read)) {
      return -1;
    } else {
      return $this->read;
    }
  }

  /*! \brief Set the query for this Query Set
   * \param $query The query that will be executed
   */
  public function setQuery($query) {
    if(strncasecmp($query, "SELECT", 6) == 0) {
      $this->read = TRUE;
    } else if(strncasecmp($query, "SHOW", 4) == 0) {
      $this->read = TRUE;
    } else {
      $this->read = FALSE;
    }
    $this->query = $query;
  }

  /*! \brief Get the query for this Query Set
   * \return The current query for this Query Set
   */
  public function getQuery() {
    return $this->query;
  }

  /*! \brief Set the last insert ID for this Query Set
   * \param $insertid The insert ID to store
   */
  public function setInsert($insertid) {
    $this->insertid = $insertid;
  }

  /*! \brief Get the insert ID for this Query Set
   * \return The insert ID, returns null if not set
   */
  public function getInsert() {
    if(!empty($this->insertid)) {
      return $this->insertid;
    } else {
      return NULL;
    }
  }

  /*! \brief Set the result for this Result Set
   * \param $result MySQL resource that contains the results
   */
  public function setResult($result) {
    $this->result = $result;
  }

  /*! \brief Get the result for this Result Set
   * \return The result set
   */
  public function getResult() {
    if(isset($this->result)) {
      return $this->result;
    } else {
      return false;
    }
  }

  /*! \brief Set the number of rows in this Query Set
   * \param $rows The number of rows in this Query Set
   */
  public function setRows($rows) {
    $this->rows = $rows;
  }

  /*! \brief Get the number of rows in this Query Set
   * \return The number of rows in this Query Set
   */
  public function getRows() {
    if(!isset($this->rows)) {
      return 0;
    }
    return $this->rows;
  }

  /*! \brief Sets the error that will be reported
   * \param $errno The numerical error code
   * \param $error The textual description of the error
   */
  public function setError($errno, $error) {
    $this->errno = $errno;
    $this->error = $error;
  }

  /*! \brief Get the error that is set for this Query Set
   * \param $numonly Only return the error number
   * \return A string value that has the error and error number
   */
  public function getError($numonly = \FALSE) {
    if(isset($this->error) && isset($this->errno)) {
      if($numonly === \TRUE) {
        return $this->errno;
      } else {
        return $this->error." [".$this->errno."]";
      }
    } else {
      return "";
    }
  }
    
  /*! \brief Free up any resources in use by this Query Set
   *
   * This method free's the MySQL result set if there is one, unsets the query,
   * insert id, number of rows, and error number and text values.
   *
   */
  public function free() {
    if(isset($this->result)) {
      \mysqli_free_result($this->result);
    }
    unset($this->query);
    unset($this->result);
    unset($this->insertid);
    unset($this->rows);
    unset($this->errno);
    unset($this->error);
  }

}
?>

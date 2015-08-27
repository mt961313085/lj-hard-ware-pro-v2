<?php

/**
 * @author Steve Smith
 * @copyright 2014
 */
$con = mysql_connect("localhost","yunkauser","KKBBtS7ynWJnPnWm");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }
$success = mysql_select_db("yunkadb");
mysql_query("SET NAMES 'utf8'");
error_reporting(E_ALL &~E_NOTICE);
?>

#!/usr/bin/env php
<?php

/**
 * This script removes the 'ignored' fields from the Conflict Resolver table.
 * Should be run after any fields are added to the _doubleDataEntryDiffIgnoreColumns
 * after the instrument has been completed.
 *
 * It has two modes:
 *     regular mode -> Prints the conflicts to be removed, but does not remove them.
 *
 *     confirm mode -> Actually removes the conflicts.
 *
 * Usage: php assign_missing_instrument.php [Test_name] [confirm]
 *
 * Example: php delete_ignored_conflicts.php tsi
 * (Will use regular mode and print the obsolete conflicts)
 *
 * Example: php delete_ignored_conflicts.php tsi confirm
 * (Will use confirm mode and remove obsolete tsi conflicts)
 *
 * Example: php delete_ignored_conflicts.php confirm
 * (Will use confirm mode and remove all obsolete conflicts)
 *
 * PHP Version 7
 *
 * @category Main
 * @package  Loris
 * @author   Loris Team <loris-dev@bic.mni.mcgill.ca>
 * @license  Loris license
 * @link     https://www.github.com/aces/Loris/
 */

set_include_path(
    get_include_path().":".
    __DIR__."../../../project/libraries:" .
    __DIR__."../../../php/libraries:"
);

require_once __DIR__ . "/../../../vendor/autoload.php";
require_once "NDB_Client.class.inc";
$client = new NDB_Client();
$client->makeCommandLine();
$client->initialize();

$config =& NDB_Config::singleton();

// Meta fields that should be removed
$defaultFields = [
    'CommentID',
    'UserID',
    'Testdate',
    'Window_Difference',
    'Candidate_Age',
];

$instruments         = [];
$instrumentSpecified = false;

$confirm = false;
if ((isset($argv[1]) && $argv[1] === "confirm")
    || (isset($argv[2]) && $argv[2] === "confirm")
) {
    $confirm = true;
}

if (!empty($argv[1]) && $argv[1]!="confirm") {
    $instruments[0]      = $argv[1];
    $instrumentSpecified = true;
} else {
    $instruments = $config->getSetting('DoubleDataEntryInstruments');
}

if (isset($instruments)) {
    detectIgnoreColumns($instruments);
    echo "Done.";
} else {
    echo "No instruments found";
}

if ($confirm === false) {
    echo "\n\nRun this tool again with the argument 'confirm' to ".
        "perform the changes\n\n";
}

/**
 * Populates the DDE ignore fields for each instrument and runs
 * the ignoreColumn function on the instrument for the given fields
 *
 * @param array $instruments The instruments to check for ignore fields
 *
 * @return void
 *
 * @throws Exception
 */
function detectIgnoreColumns($instruments)
{
    $instrumentFields = [];

    foreach ($instruments as $instrument) {
        echo "Checking DDE ignore fields for " . $instrument . "\n";

        $file = "../project/instruments/NDB_BVL_Instrument_$instrument.class.inc";
        if (file_exists($file)) {
            include $file;
            $instance =& NDB_BVL_Instrument::factory($instrument, null, null);

            $DDEIgnoreFields = $instance->_doubleDataEntryDiffIgnoreColumns;

            if ($DDEIgnoreFields != null) {
                foreach ($DDEIgnoreFields as $key => $DDEField) {
                    if (!in_array($DDEField, $this->defaultFields)) {
                        $instrumentFields = array_merge(
                            $instrumentFields,
                            [$DDEField => $instrument]
                        );
                    }
                }
            } else {
                echo "No DDE ignore fields found for " . $instrument . "\n";
            }
            if (!$this->instrumentSpecified) {
                defaultIgnoreColumns();
            }
            ignoreColumn($instrument, $instrumentFields);
        }
    }
}

/**
 * Prints the default ignore columns to be removed
 * Removes the fields if confirmation is set
 *
 * @return void
 */
function defaultIgnoreColumns()
{
    $db = \NDB_Factory::singleton()->database();

    if ($this->confirm) {
        foreach ($this->defaultFields as $field) {
            $defaultQuery = "DELETE FROM conflicts_unresolved
                WHERE FieldName = '$field'";
            $changes      = $db->run($defaultQuery);
            echo $changes . "\n";
        }
    } else {
        foreach ($this->defaultFields as $field) {
            $defaultQuery  = "SELECT TableName, FieldName, Value1, Value2
          FROM conflicts_unresolved WHERE FieldName = '$field'";
            $defaultColumn = $db->pselectOne($defaultQuery, []);
            echo "TableName, FieldName, Value1, Value2: ";
            print_r($defaultColumn);
            echo "\n";
        }
    }
}

/**
 * Prints the instrument-specific ignore columns to be removed
 * Removes the fields if confirmation is set
 *
 * @param string $instrument       The name of the instrument
 * @param array  $instrumentFields The fields for the instrument
 *
 * @return void
 */
function ignoreColumn($instrument, $instrumentFields)
{
    $db =& Database::singleton();

    if ($this->confirm) {
        foreach ($instrumentFields as $field => $instr) {
            $query   = "DELETE FROM conflicts_unresolved 
                WHERE TableName = '$instrument' AND FieldName = '$field'";
            $changes = $db->run($query);
            echo $changes . "\n";
        }
    } else {
        foreach ($instrumentFields as $field => $instr) {
            $query        = "SELECT TableName, FieldName, Value1, Value2 
                FROM conflicts_unresolved 
                WHERE TableName = '$instrument' AND FieldName = '$field'";
            $ignoreColumn = $db->pselectOne($query, []);
            echo "TableName, FieldName, Value1, Value2: ";
            print_r($ignoreColumn);
            echo  "\n";
        }
    }
}

?>

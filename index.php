<?php

// display arrays and objects in a human readable format
function prnt($val)
{
    echo '<pre>' . print_r($val, true) . '</pre>';
}

// if a project directory is set in the query string
$project_directory = null;
if (isset($_GET))
{
    if (isset($_GET['dir']))
    {
        $dir = $_GET['dir'];
        
        if (substr($dir, -1) != '/')
        {
            $dir .= '/';
        }
        
        $project_directory = $dir;
    }
}

// if a version is set in the query string
$version = null;
if (isset($_GET))
{
    if (isset($_GET['v']))
    {
        if ($_GET['v'] == '2.2')
        {
            $version = '2.2';
        }
    }
}

// if loading an older project
if ($version == '2.2')
{
    // include older GMS2 Parser and create a new instance
    include 'class.GMS2_Parser.php';
    $gms2_parser = new GMS2_Parser($project_directory);
    
    // if there were errors
    if ( ! $gms2_parser->initialize())
    {
        prnt($gms2_parser->report);
        exit;
    }
    
    include 'gms2-parser.php';
    exit;
}


// include the newer GMS2 Parser and create a new instance
include 'class.GMS2_Parser_2.php';
$gms2_parser = new GMS2_Parser_2($project_directory);

// if there were errors
if ( ! $gms2_parser->initialize())
{
    prnt($gms2_parser->report);
    exit;
}

include 'gms2-parser-2.php';
exit;

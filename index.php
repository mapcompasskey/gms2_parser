<?php

// *I NEED TO FIND AND LOAD THE CREATION CODE ON THE ROOMS

// display arrays and objects in a human readable format
function prnt($val)
{
    echo '<pre>' . print_r($val, true) . '</pre>';
}

// if a project directory is set in the query string
$project_directory = null;
if (isset($_GET))
{
    if ($_GET['dir'])
    {
        $dir = $_GET['dir'];
        
        if (substr($dir, -1) != '/')
        {
            $dir .= '/';
        }
        
        $project_directory = $dir;
    }
}

// include GMS2 Parser and create a new instance
include 'class.GMS2_Parser.php';
$gms2_parser = new GMS2_Parser($project_directory);

// if there were errors
if ( ! $gms2_parser->initialize())
{
    prnt($gms2_parser->report['errors']);
    exit;
}

?><!doctype html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <title>GMS2 Parser</title>
    <style>
        * {
            margin: 0;
        }
        
        body {
            margin: 0;
            color: #000000;
            font-family: monospace;
        }
        
        a {
            color: #000000;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        
        a.anchor {
            display: block;
            position: relative;
            top: -10px;
            left: 0;
        }
        
        ul {
            list-style-type: none;
            margin-bottom: 1em;
            padding-left: 0;
        }
        ul ul {
            padding-left: 20px;
        }
        
        button {
            cursor: pointer;
            padding: 8px 28px;
            color: #ffffff;
            border: 1px solid #ffffff;
            background-color: #000000;
        }
        
        button.return-to-top {
            position: fixed;
            bottom: 0;;
            right: 30px;
            z-index: 30;
            padding: 5px 10px;
            color: #ffffff;
            font-size: 30px;
            font-weight: 700;
            border-bottom: 0px none;
        }
        
        .page-navigation {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
            width: 25%;
            height: 100%;
            overflow: scroll;
        }
        .page-navigation-inner {
            padding: 10px;
        }
        
        .page-scripts {;
            margin-left: 25%;
        }
        .page-scripts-inner {
            padding: 10px;
            padding-bottom: 100%;
        }
        
        .report-wrapper {
            padding: 10px;
            margin-bottom: 2em;
            border: 1px solid #999999;
            background-color: #f0f0f0;
        }
        .report-container {
            display: none;
            margin-top: 1em;
        }
        .report-wrapper-active .report-container {
            display: block;
        }
        
        .resource-tree {
            padding: 10px;
            margin-bottom: 4em;
            border: 1px solid #dedede;
            background-color: #f9f9f9;
        }
        
        .code-block {
            margin-bottom: 4em;
        }
        .code-block header {
            color: #ffffff;
            padding: 10px;
            background-color: #666666;
        }
        .code-block pre {
            padding: 10px;
            border: 1px solid #dedede;
            background-color: #f9f9f9;
        }
        .code-block .footer {
            padding: 10px;
            text-align: right;
            background-color: #dedede;
        }
        .code-block .footer a {
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="page-navigation">
        <div class="page-navigation-inner">
        
            <?php // scripts resource tree ?>
            <?php if ($scripts_resource_tree = $gms2_parser->scripts_resource_tree) : ?>
                <div class="resource-tree">
                    <ul>
                        <?php $gms2_parser->output_resource_tree($scripts_resource_tree); ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php // rooms resource tree ?>
            <?php if ($rooms_resource_tree = $gms2_parser->rooms_resource_tree) : ?>
                <div class="resource-tree">
                    <ul>
                        <?php $gms2_parser->output_resource_tree($rooms_resource_tree); ?>
                    </ul>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <div class="page-scripts">
        <div class="page-scripts-inner">
        
            <button class="return-to-top" onclick="document.documentElement.scrollTop = 0;">&uarr;</button>
            
            <?php // GMS2 Parser report ?>
            <div class="report-wrapper">
                <button>View Report</button>
                <div class="report-container">
                    <?php prnt($gms2_parser->report); ?>
                </div>
            </div>
            
            <?php // Scripts ?>
            <?php $files = $gms2_parser->scripts_resource_files; ?>
            <?php foreach ($files as $file) : ?>
                <?php $gms2_parser->output_text_file($file); ?>
            <?php endforeach; ?>
            
            <?php // Rooms ?>
            <?php $files = $gms2_parser->rooms_resource_files; ?>
            <?php foreach ($files as $file) : ?>
                <?php $gms2_parser->output_text_file($file); ?>
            <?php endforeach; ?>
            
        </div>
    </div>
    
    <script>
    
        // toggle the report on button click
        var reportWrapper = document.getElementsByClassName('report-wrapper');
        if (reportWrapper.length) {
            var reportButton = reportWrapper[0].getElementsByTagName('button');
            if (reportButton.length) {
                reportButton[0].onclick = function() {
                    reportWrapper[0].classList.toggle('report-wrapper-active');
                };
            }
        }
        
        // get all the code blocks
        var codeBlocks = document.getElementsByClassName('code-block');
        for (var i = 0; i < codeBlocks.length; i++) {
        
            // create a button
            var copyButton = document.createElement('button');
            copyButton.innerHTML = 'COPY';
            
            // create an anonymous function and add it to the button's click event
            var onCopyButtonClick = (function (i){
                return function() {
                
                    // find the code within the code block
                    var pre = codeBlocks[i].getElementsByTagName('pre');
                    if (pre.length) {
                        if (window.getSelection) {
                        
                            // select the code
                            selection = window.getSelection();        
                            range = document.createRange();
                            range.selectNodeContents(pre[0]);
                            selection.removeAllRanges();
                            selection.addRange(range);
                            
                            // copy it to the clipboard
                            document.execCommand('Copy');
                            
                            // deselect the code
                            selection.removeAllRanges();
                        }
                        
                    }
                    
                };
            }(i));
            copyButton.onclick = onCopyButtonClick;
            
            // create a div
            var footerDiv = document.createElement('div');
            footerDiv.className = 'footer';
            
            // add the button to footerDiv
            footerDiv.appendChild(copyButton);
            
            // add the footer div to the code block
            codeBlocks[i].appendChild(footerDiv);
            
        }
        
    </script>

</body>
</html>
<?php
$template->load_view('base');

$template->section_start('header_title');
    echo 'A FANCY HEADER TITLE';
$template->section_end('header_title');

$template->section_start('body');
    ?> This is the body section you are looking for. <?php
$template->section_end('body');
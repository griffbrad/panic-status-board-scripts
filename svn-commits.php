<?php

require_once __DIR__ . '/config.php';

$xml = file_get_contents(SVN_LOG_LOCATION);
$log = simplexml_load_string($xml);

$unix = time();
$week = date('W');

$revsByWeek = array(
    'me'       => array(),
    'everyone' => array()    
);

while (4 >= count($revsByWeek['me'])) {
    $year  = date('Y', $unix);
    $parts = strptime("1 {$week} {$year}", '%w %U %Y');
    $time  = mktime(0, 0, 0, $parts['tm_mon'] + 1, $parts['tm_mday'], $parts['tm_year'] + 1900);
    $title = date('M j', $time);

    $revsByWeek['me'][$week]       = array('title' => $title, 'value' => 0);
    $revsByWeek['everyone'][$week] = array('title' => $title, 'value' => 0);

    $unix = $unix - (86400 * 7); 
    $week = date('W', $unix);
}

foreach ($log->logentry as $entry) {
    $unix = strtotime($entry->date);
    $week = date('W', $unix);

    if (!isset($revsByWeek['me'][$week])) {
        break;
    }

    if (SVN_PRIMARY_USER === (string) $entry->author) {
        $revsByWeek['me'][$week]['value'] += 1;
    }

    $revsByWeek['everyone'][$week]['value'] += 1;
}

ksort($revsByWeek['me']);
ksort($revsByWeek['everyone']);

$me       = array_values($revsByWeek['me']);
$everyone = array_values($revsByWeek['everyone']);

echo json_encode(
    array(
        'graph' => array(
            'title'         => 'SVN Commits by Week',
            'datasequences' => array(
                array(
                    'title'      => 'Everyone',
                    'datapoints' => $everyone
                ), 
                array(
                    'title'      => SVN_PRIMARY_USER,
                    'datapoints' => $me
                ) 
            )
        )
    )
);

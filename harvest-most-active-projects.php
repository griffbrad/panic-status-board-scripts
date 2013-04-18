<?php

require_once __DIR__ . '/config.php';

function request_time($day, $year)
{
    $cmd = sprintf(
        "curl -H 'Content-Type: application/xml' -H 'Accept: application/xml' -u %s:%s https://%s/daily/%d/%d?of_user=%d",
        HARVEST_EMAIL,
        HARVEST_PASSWORD,
        HARVEST_URL,
        $day, 
        $year,
        HARVEST_USER_ID
    );

    $key = 'hvt_stat_brd_' . $day . ':' . $year;
    $xml = apc_fetch($key, $success);

    if (!$success || (date('z', time()) + 1) === $day) {
        $xml = shell_exec($cmd);
        apc_store($key, $xml, HARVEST_CACHE_TTL);
    }
    
    return simplexml_load_string($xml);
}

$unix = time();
$week = date('W');

$weeks = array();

while (4 >= count($weeks)) {
    $year  = date('Y', $unix);
    $parts = strptime("1 {$week} {$year}", '%w %U %Y');
    $time  = mktime(0, 0, 0, $parts['tm_mon'] + 1, $parts['tm_mday'], $parts['tm_year'] + 1900);
    $title = date('M j', $time - (86400 * 7));

    $weeks[$week] = $title;

    $unix = $unix - (86400 * 7); 
    $week = date('W', $unix);
}

$time          = time();
$timeByWeek    = array();
$timeByProject = array();
$projects      = array();
$mostActive    = array();

foreach ($weeks as $week => $title) {
    $timeByWeek[$week] = array();
}

while (1) {
    $week = date('W', $time);

    if (!array_key_exists($week, $weeks)) {
        break;
    }

    $entries = request_time(date('z', $time) + 1, date('Y', $time));

    foreach ($entries->day_entries->day_entry as $entry) {
        $projectId = (string) $entry->project_id;
        $hours     = (string) $entry->hours;

        if (!isset($projects[$projectId])) {
            if (!HARVEST_ABBREVIATE_PROJECT) {
                $projects[$projectId] = $entry->client . ': ' . $entry->project;
            } else {
                $clientWords  = explode(' ', $entry->client);
                $projectWords = explode(' ', $entry->project);

                $projects[$projectId] = $clientWords[0] . ': ' . $projectWords[0];
            }
        }

        if (!isset($timeByWeek[$week][$projectId])) {
            $timeByWeek[$week][$projectId] = 0;
        }

        if (!isset($timeByProject[$projectId])) {
            $timeByProject[$projectId] = 0;
        }

        $timeByWeek[$week][$projectId] += $hours;
        $timeByProject[$projectId]     += $hours;
    }

    $time -= 86400;
}

arsort($timeByProject);

foreach ($timeByProject as $id => $hours) {
    $mostActive[$id] = $projects[$id];

    if (HARVEST_NUM_PROJECTS === count($mostActive)) {
        break;
    }
}

$data = array();

ksort($weeks);

foreach ($mostActive as $id => $project) {
    $datapoints = array();

    foreach ($weeks as $week => $title) {
        if (isset($timeByWeek[$week][$id])) {
            $time = $timeByWeek[$week][$id];
        } else {
            $time = 0;
        }

        $datapoints[] = array(
            'title' => $title,
            'value' => $time
        );
    }

    $data[] = array(
        'title'      => $project,
        'datapoints' => $datapoints
    );
}

echo json_encode(
    array(
        'graph' => array(
            'title'         => 'Most Active Projects',
            'total'         => true,
            'datasequences' => $data
        )
    )
);

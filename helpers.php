<?php

function formatBytes($bytes, $precision = 2) {
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  $bytes /= pow(1024, $pow);

  return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * @throws Exception
 */
function loadRates() {
    echo "Loading rates from remote source.\n";

    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, 'https://developers.paysera.com/tasks/api/currency-exchange-rates');
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);

    $jsonData = json_decode(curl_exec($curlSession));
    curl_close($curlSession);

    if (!$jsonData || !isset($jsonData->rates)) {
        throw new Exception('Remote source is not available.');
    }

    return (array)$jsonData->rates;
}

<?php

/*
 * Cron Job : 
 *            Minute   Hour   Day   Month   Weekday
 *               0      *      *      *        *
 */

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://example.com/CuteYourDay/sender.php");  // Replace your site address.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_TIMEOUT_MS, 99999000);
curl_setopt($ch, CURLOPT_TIMEOUT, 99999);
curl_exec($ch);
curl_close($ch);

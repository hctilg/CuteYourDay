<?php

/*
 * Cron Job : 
 *            Minute   Hour   Day   Month   Weekday
 *               0      *      *      *        *
 */

$ch = curl_init();
// Replace your site address.
curl_setopt($ch, CURLOPT_URL, "https://example.com/CuteYourDay/sender.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

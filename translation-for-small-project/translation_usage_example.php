<?php
use App\Translations\DateDictionary;
use App\Translations\ReminderDictionary;

DateDictionary::load(); //loading default language (english)
echo DateDictionary::$dates_range_is_required . "\n";
//--> 'Dates range is required'

DateDictionary::load('en');
echo DateDictionary::$dates_range_is_required . "\n";
//--> 'Dates range is required'

ReminderDictionary::load('en');
echo ReminderDictionary::x_has_birth_day_in_n_days('Tom', 5);
//--> 'Tom has birthday in 5 days'

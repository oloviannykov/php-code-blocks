<?php
use SurenHome\Models\PagesModel;
use SurenHome\Models\PageData;
use SurenHome\Models\entities\ReportPageMaker;

use SurenHome\Translations\EmployeeDictionary;
use SurenHome\Translations\WorkDictionary;

EmployeeDictionary::load();
WorkDictionary::load();

$records = []; //todo: pass records from arguments
$occupation_domain = []; //todo: pass occupations from arguments
$language_domain = []; //todo: pass languages from arguments
function format_date($date)
{
    return $date; //todo: format the date
}

$report = new ReportPageMaker();
$report->report_start(EmployeeDictionary::$employees);
$report->printing_date();

$weight_bold = 'font-weight: bold;';
$report->paragraph(EmployeeDictionary::$working_employees, 1, true, 8);
foreach ($records as $record) {
    if ($record->get_end_working_date()) {
        continue;
    }
    $name = implode(' ', [$record->get_first_name(), $record->get_sur_name()]);
    $occupation_id = $record->get_occupation_id();
    $occupation = empty($occupation_domain[$occupation_id]) ? '' : $occupation_domain[$occupation_id];
    $report->paragraph($name, 1, true, 6);
    $language = $record->get_main_language();
    $report->paragraph(implode(', ', [
        ($occupation ? $occupation : ''),
        (isset($language_domain[$language]) ? $language_domain[$language] : '')
    ]));

    if ($record->get_start_working_date()) {
        $report->paragraph(WorkDictionary::$works_from . ' ' . PagesModel::date($record->get_start_working_date()));
    } else {
        $report->paragraph(WorkDictionary::$has_not_started_working_yet);
    }
    $report->paragraph(implode(', ', [
        $record->get_phone_number(),
        $record->get_home_city(),
        $record->get_home_address()
    ]));
}

$report->paragraph('---------------');
$report->paragraph(EmployeeDictionary::$fired_employees, 1, true, 8);
foreach (PageData::$records as $record) {
    if (!$record->get_end_working_date()) {
        continue;
    }
    $name = implode(' ', [$record->get_first_name(), $record->get_sur_name()]);
    $occupation_id = $record->get_occupation_id();
    $occupation = empty($occupation_domain[$occupation_id]) ? '' : $occupation_domain[$occupation_id];
    $report->paragraph($name, 1, true, 6);
    $language = $record->get_main_language();
    $report->paragraph(implode(', ', [
        ($occupation ? $occupation : ''),
        (isset($language_domain[$language]) ? $language_domain[$language] : '')
    ]));
    $report->paragraph(
        WorkDictionary::$does_not_work_from . ' ' . format_date($record->get_end_working_date())
    );
    $report->paragraph(implode(', ', [
        $record->get_phone_number(),
        $record->get_home_city(),
        $record->get_home_address()
    ]));
}

$report->report_end();

<?php
use SurenHome\Models\entities\ReportPageMaker;
use SurenHome\Models\PageData;

use SurenHome\Translations\AdminPageDictionary;
use SurenHome\Translations\EmployeeDictionary;
use SurenHome\Translations\DateDictionary;
use SurenHome\Translations\FieldDictionary;

AdminPageDictionary::load();
EmployeeDictionary::load();
DateDictionary::load();
FieldDictionary::load();

$employee = new \StdObject(); //todo: pass employee object from arguments
$bank_domain = ['xxx' => 'Bank of XXX']; //todo: pass banks domain from arguments
$occupation_domain = ['waiter' => 'Waiter']; //todo: pass professions domain from arguments
$language_domain = ['en' => 'English']; //todo: pass language domain from arguments
function format_date($date)
{
    return $date; //todo: format date output
}

$report = new ReportPageMaker();
$report->report_start(EmployeeDictionary::$employee . ' ' . $employee->get_full_name());
$report->printing_date();

$weight_bold = 'font-weight: bold;';
$general_info = [];
if ($employee->get_birth_date()) {
    $general_info[] = EmployeeDictionary::$birth_date . ' - ' . format_date($employee->get_birth_date());
}
if ($employee->get_sex()) {
    $general_info[] = EmployeeDictionary::$sex . " - " . $employee->sex_domain()[$employee->get_sex()];
}
if ($employee->get_civil_state()) {
    $general_info[] = $employee->civil_state_domain()[$employee->get_civil_state()];
}
if ($employee->get_children_quantity()) {
    $general_info[] = EmployeeDictionary::$children_quantity . ' - ' . $employee->get_children_quantity();
}
$report->paragraph(implode(', ', $general_info));

$report->paragraph(EmployeeDictionary::$main_language . ' - ' . (
    $employee->get_main_language()
    ? $language_domain[$employee->get_main_language()]
    : FieldDictionary::$unknown
));
$report->paragraph($employee->get_home_city() . ', ' . $employee->get_home_address());
$report->paragraph($employee->get_phone_number() . ', ' . $employee->get_email());
if ($employee->get_notes()) {
    $report->paragraph(EmployeeDictionary::$notes . ': ' . $employee->get_notes(), 2);
}

if ($employee->get_passport_type() && $employee->get_passport_country() && $employee->get_passport_number()) {
    $report->heading(EmployeeDictionary::$passport);
    $report->paragraph(
        $employee->passport_type_domain()[$employee->get_passport_type()]
        . ' ' . EmployeeDictionary::$of . ' ' . $employee->passport_country_domain()[$employee->get_passport_country()]
    );
    $report->paragraph(
        EmployeeDictionary::$number . ' ' . $employee->get_passport_number() . ($employee->get_passport_expiration_date() ?
            ', ' . EmployeeDictionary::$expires . ' ' . format_date($employee->get_passport_expiration_date()) : ''
        )
    );
}

if ($employee->get_insurance_company_name()) {
    $report->heading(EmployeeDictionary::$health_insurance);
    $text = $employee->insurance_company_name_domain()[$employee->get_insurance_company_name()];
    if ($employee->get_insurance_contract_number()) {
        $text .= ', ' . EmployeeDictionary::$contract_number . ' ' . $employee->get_insurance_contract_number()
            . ' ' . format_date($employee->get_insurance_contract_start_date());
    }
    $report->paragraph($text);
    if ($employee->get_insurance_payment_period_in_days()) {
        $every_x_days = DateDictionary::every_n_days($employee->get_insurance_payment_period_in_days());
        $report->paragraph(EmployeeDictionary::$payment . ' ' . $every_x_days);
    }
}
$bank_id = $employee->get_bank_id();
if ($bank_id) {
    $report->heading(EmployeeDictionary::$bank_account);
    $bank_name = empty($bank_domain[$bank_id])
        ? 'unknown bank' : $bank_domain[$bank_id];
    $report->paragraph(
        $bank_name . ($employee->get_bank_account_number()
            ? ', ' . EmployeeDictionary::$account . ' ' . $employee->get_bank_account_number()
            : '')
    );
}

if ($employee->get_start_working_date()) {
    $report->heading(EmployeeDictionary::$work);
    $report->paragraph(
        EmployeeDictionary::$started_working . ' ' . format_date($employee->get_start_working_date())
    );
    if ($employee->get_occupation_id()) {
        $report->paragraph(
            EmployeeDictionary::$profession . ': '
            . $occupation_domain[$employee->get_occupation_id()]
        );
    }
    $currency = $employee->get_payment_currency() ? $employee->get_payment_currency() : 'DOP';
    if ($employee->get_salary_per_month()) {
        $report->paragraph(EmployeeDictionary::$salary_per_month . ' ' . $employee->get_salary_per_month() . $currency);
    }
    if ($employee->get_premium_per_month()) {
        $report->paragraph(EmployeeDictionary::$premium_per_month . ' ' . $employee->get_premium_per_month() . $currency);
    }
    if ($employee->get_end_working_date()) {
        $report->paragraph(
            EmployeeDictionary::$ended_working . ' ' . format_date($employee->get_end_working_date())
        );
    }
} else {
    $report->interval(4);
    $report->paragraph(EmployeeDictionary::$not_started_to_work);
}

if ($employee->get_admin_login()) {
    $report->heading(EmployeeDictionary::$admin_panel_access);
    $report->paragraph(EmployeeDictionary::$login . ': ' . $employee->get_admin_login());
}

$report->report_end(); //closes the body of report

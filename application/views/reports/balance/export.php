<?php
/**
 * This view builds a Spreadsheet file of the native report 'balance of leave requests'.
 * @copyright  Copyright (c) 2014-2016 Benjamin BALET
 * @license      http://opensource.org/licenses/AGPL-3.0 AGPL-3.0
 * @link            https://github.com/bbalet/jorani
 * @since         0.2.0
 */

$sheet = $this->excel->setActiveSheetIndex(0);
$sheet->setTitle(mb_strimwidth(lang('reports_export_balance_title'), 0, 28, "..."));  //Maximum 31 characters allowed in sheet title.
$result = array();
$summary = array();
$types = $this->types_model->getTypes();        
$users = $this->organization_model->allEmployees($_GET['entity'], $include_children);
foreach ($users as $user) {
    $result[$user->id]['identifier'] = $user->identifier;
    $result[$user->id]['firstname'] = $user->firstname;
    $result[$user->id]['lastname'] = $user->lastname;
    $result[$user->id]['datehired'] = $user->datehired;
    $result[$user->id]['department'] = $user->department;
    $result[$user->id]['position'] = $user->position;
    $result[$user->id]['contract'] = $user->contract;
    //Init type columns
    foreach ($types as $type) {
        $result[$user->id][$type['name']] = '';
    }

    $summary = $this->leaves_model->getLeaveBalanceForEmployee($user->id, TRUE, $refDate);
    if (count($summary) > 0 ) {
        foreach ($summary as $key => $value) {
            $result[$user->id][$key] = round($value[1] - $value[0], 3, PHP_ROUND_HALF_DOWN);
        }
    }
}

$max = 0;
$line = 2;
$i18n = array("identifier", "firstname", "lastname", "datehired", "department", "position", "contract");
foreach ($result as $row) {
    $index = 1;
    foreach ($row as $key => $value) {
        if ($line == 2) {
            $colidx = $this->excel->column_name($index) . '1';
            if (in_array($key, $i18n)) {
                $sheet->setCellValue($colidx, lang($key));
            } else {
                $sheet->setCellValue($colidx, $key);
            }
            $max++;
        }
        $colidx = $this->excel->column_name($index) . $line;
        $sheet->setCellValue($colidx, $value);
        $index++;
    }
    $line++;
}

$colidx = $this->excel->column_name($max) . '1';
$sheet->getStyle('A1:' . $colidx)->getFont()->setBold(true);
$sheet->getStyle('A1:' . $colidx)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

//Autofit
for ($ii=1; $ii <$max; $ii++) {
    $col = $this->excel->column_name($ii);
    $sheet->getColumnDimension($col)->setAutoSize(TRUE);
}

exportSpreadsheet($this, 'leave_balance');

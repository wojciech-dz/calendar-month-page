<?php

namespace Calendar;

use DateTime;
use IntlDateFormatter;
use IntlCalendar;

class Calendar
{
    public const DAY_LABELS = ['Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So', 'N'];
    public const SUNDAY = self::DAY_LABELS[6];

    private $month;
    private $year;
    private $loc;
    private $tz;

    public function __construct(int $month, int $year, string $loc = 'pl_PL', string $tz = 'Europe/Warsaw')
    {
        $this->month = $month;
        $this->year = $year;
        $this->loc = $loc;
        $this->tz = $tz;
        setlocale(LC_ALL, $this->loc . '/' . $this->tz);
    }

    public function makeTable()
    {
        $table = '<table>';
        $table .= PHP_EOL;
        $table .= $this->showTitle();
        $table .= PHP_EOL;
        $table .= $this->showLabels();
        $table .= PHP_EOL;
        $table .= $this->showDays();
        $table .= PHP_EOL;
        $table .= '</table>';

        return $table;
    }

    private function showTitle(): string
    {
        $name = $this->localMonthName();
        $title = "<tr class='title'>";
        $title .= "<td colspan='6' class='month'> $name </td>";
        $title .= "<td class='year'> $this->year </td></tr>";

        return $title;
    }

    private function showLabels()
    {
        $labels = "<tr class='day-labels'>";
        foreach (self::DAY_LABELS as $day) {
            $labels .= $day !== self::SUNDAY ? "<td class='day-label'>$day</td>" : "<td class='day-label-red'>$day</td>";
        }
        $labels .= "</tr>";

        return $labels;
    }

    private function showDays(): string
    {
        $days = "<tr class='month-table'>\n";
        $rowStart = "\t<tr>\n\t\t";
        $rowEnd = "\n\t</tr>\n";

        [$rowsNumber, $firstDay, $lastDay] = $this->calculateRowsNumber();

        $days .= $rowStart;
        foreach (self::DAY_LABELS as $k => $v) {
            $days .= $v !== self::SUNDAY ? "<td class='day-box'>" : "<td class='day-box-red'>";
            $days .= ($k < $firstDay ? '' : $k - $firstDay + 1) . "</td>";
        }
        $days .= $rowEnd;

        for ($rowNumber = 1; $rowNumber < $rowsNumber - 1; $rowNumber++) {
            $days .= $rowStart;
            foreach (self::DAY_LABELS as $k => $v) {
                $days .= $v !== self::SUNDAY ? "<td class='day-box'>" : "<td class='day-box-red'>";
                $days .= (string)(($rowNumber * 7) + $k - $firstDay + 1) . "</td>";
            }
            $days .= $rowEnd;
        }

        $days .= $rowStart;
        foreach (self::DAY_LABELS as $k => $v) {
            $days .= $v !== self::SUNDAY ? "<td class='day-box'>" : "<td class='day-box-red'>";
            $days .= ($k > $lastDay ? '' : $k - $firstDay + 1 + ($rowNumber * 7)) . "</td>";
        }
        $days .= $rowEnd;

        return $days;
    }

    private function calculateDaysInMonth(): int
    {
        return date('t', strtotime($this->year . '-' . $this->month . '-01'));
    }

    private function calculateRowsNumber(): array
    {
        $daysNumber = $this->calculateDaysInMonth();
        $weeksNumber = ($daysNumber % 7 === 0 ? 0 : 1) + intdiv($daysNumber, 7);
        $firstDay = date('N', strtotime($this->year . '-' . $this->month . '-01'));
        $lastDay= date('N', strtotime($this->year . '-' . $this->month . '-' . $daysNumber));
        $firstDay = $this->makeSundayLast($firstDay);
        $lastDay = $this->makeSundayLast($lastDay);
        if ($lastDay < $firstDay) {
            $weeksNumber++;
        }

        return [$weeksNumber, $firstDay, $lastDay];
    }

    private function makeSundayLast(int $day): int
    {
        return ($day + 6) % 7;
    }

    public function localMonthName()
    {
        $this->dateFormatter = new IntlDateFormatter(
            $this->loc,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $this->tz,
            IntlDateFormatter::GREGORIAN,
            'LLLL'
        );

        return datefmt_format($this->dateFormatter, strtotime($this->year . '-' . $this->month . '-01'));
    }

    public function localDaysArray()
    {
        $this->dateFormatter = new IntlDateFormatter(
            $this->loc,
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            $this->tz,
            IntlDateFormatter::GREGORIAN,
            'eeeeee'
        );
        $week = [];
        for ($i = 1; $i < 8; $i++) {
            $week[] = datefmt_format($this->dateFormatter, strtotime($this->year . '-' . $this->month . '-0' . $i));
        }

        return $week;
    }

}


$path = $_SERVER['HOME'] . '/Projekty/';
$htmlStart = "<html>\n<head><link href=" . '"calendar.css" type="text/css" rel="stylesheet"' . "/></head>\n<body>\n";
$htmlEnd = "\n</body>\n</html>";
$f = new \SplFileObject($path . 'output.html', 'w');
$cal = new Calendar(10, 2022);

$f->fwrite($htmlStart . $cal->makeTable() . $htmlEnd);


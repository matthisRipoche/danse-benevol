<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class SpreadsheetReader
{
    /**
     * Read the non-empty rows of the first sheet of an Excel (.xlsx) or CSV file, keyed by line number.
     * CSV files may use « ; » (Excel in French) or « , » as separator.
     *
     * @return array<int, list<string>>
     */
    public function rows(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            $reader = new XlsxReader;
        } else {
            $firstLine = (string) strtok((string) file_get_contents($path, length: 4096), "\n");
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $reader = new CsvReader(new CsvOptions(SHOULD_PRESERVE_EMPTY_ROWS: true, FIELD_DELIMITER: $delimiter));
        }

        $reader->open($path);
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $line => $row) {
                $cells = array_map(fn ($value) => trim((string) $value), $row->toArray());

                if (implode('', $cells) !== '') {
                    $rows[$line] = $cells;
                }
            }

            break;
        }

        $reader->close();

        return $rows;
    }
}

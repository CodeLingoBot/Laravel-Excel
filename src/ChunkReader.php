<?php

namespace Maatwebsite\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Jobs\ReadChunk;
use Maatwebsite\Excel\Jobs\QueueImport;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Files\TemporaryFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Imports\HeadingRowExtractor;

class ChunkReader
{
    /**
     * @param WithChunkReading $import
     * @param IReader          $reader
     * @param TemporaryFile    $temporaryFile
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch|null
     */
    public function read(WithChunkReading $import, IReader $reader, TemporaryFile $temporaryFile)
    {
        $chunkSize  = $import->chunkSize();
        $file       = $temporaryFile->getLocalPath();
        $totalRows  = $this->getTotalRows($reader, $file);
        $worksheets = $this->getWorksheets($import, $reader, $file);

        if ($import instanceof WithProgressBar) {
            $import->getConsoleOutput()->progressStart(array_sum($totalRows));
        }

        $jobs = new Collection();
        foreach ($worksheets as $name => $sheetImport) {
            $startRow         = HeadingRowExtractor::determineStartRow($sheetImport);
            $totalRows[$name] = $sheetImport instanceof WithLimit ? $sheetImport->limit() : $totalRows[$name];

            for ($currentRow = $startRow; $currentRow <= $totalRows[$name]; $currentRow += $chunkSize) {
                $jobs->push(new ReadChunk(
                    $reader,
                    $temporaryFile,
                    $name,
                    $sheetImport,
                    $currentRow,
                    $chunkSize
                ));
            }
        }

        if ($import instanceof ShouldQueue) {
            return QueueImport::withChain($jobs->toArray())->dispatch();
        }

        $jobs->each(function (ReadChunk $job) {
            dispatch_now($job);
        });

        if ($import instanceof WithProgressBar) {
            $import->getConsoleOutput()->progressFinish();
        }

        unset($jobs);

        return null;
    }

    /**
     * @param WithChunkReading $import
     * @param IReader          $reader
     * @param string           $file
     *
     * @return array
     */
    

    /**
     * @param IReader $reader
     * @param string  $file
     *
     * @return array
     */
    
}

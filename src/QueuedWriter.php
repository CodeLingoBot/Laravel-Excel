<?php

namespace Maatwebsite\Excel;

use Traversable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Jobs\CloseSheet;
use Maatwebsite\Excel\Jobs\QueueExport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Files\TemporaryFile;
use Maatwebsite\Excel\Jobs\SerializedQuery;
use Maatwebsite\Excel\Jobs\AppendDataToSheet;
use Maatwebsite\Excel\Jobs\StoreQueuedExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Jobs\AppendQueryToSheet;
use Maatwebsite\Excel\Files\TemporaryFileFactory;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;

class QueuedWriter
{
    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var int
     */
    protected $chunkSize;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @param Writer               $writer
     * @param TemporaryFileFactory $temporaryFileFactory
     */
    public function __construct(Writer $writer, TemporaryFileFactory $temporaryFileFactory)
    {
        $this->writer               = $writer;
        $this->chunkSize            = config('excel.exports.chunk_size', 1000);
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param object       $export
     * @param string       $filePath
     * @param string       $disk
     * @param string|null  $writerType
     * @param array|string $diskOptions
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function store($export, string $filePath, string $disk = null, string $writerType = null, $diskOptions = [])
    {
        $temporaryFile = $this->temporaryFileFactory->make();

        $jobs = $this->buildExportJobs($export, $temporaryFile, $writerType);

        $jobs->push(new StoreQueuedExport(
            $temporaryFile,
            $filePath,
            $disk,
            $diskOptions
        ));

        return QueueExport::withChain($jobs->toArray())->dispatch($export, $temporaryFile, $writerType);
    }

    /**
     * @param object        $export
     * @param TemporaryFile $temporaryFile
     * @param string        $writerType
     *
     * @return Collection
     */
    

    /**
     * @param FromCollection $export
     * @param TemporaryFile  $temporaryFile
     * @param string         $writerType
     * @param int            $sheetIndex
     *
     * @return Collection
     */
    

    /**
     * @param FromQuery     $export
     * @param TemporaryFile $temporaryFile
     * @param string        $writerType
     * @param int           $sheetIndex
     *
     * @return Collection
     */
    

    /**
     * @param object|WithCustomChunkSize $export
     *
     * @return int
     */
    
}

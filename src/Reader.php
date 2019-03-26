<?php

namespace Maatwebsite\Excel;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Maatwebsite\Excel\Events\AfterImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Files\TemporaryFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Maatwebsite\Excel\Factories\ReaderFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Files\TemporaryFileFactory;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Transactions\TransactionHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Maatwebsite\Excel\Exceptions\SheetNotFoundException;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;

class Reader
{
    use DelegatedMacroable, HasEventBus;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /**
     * @var object[]
     */
    protected $sheetImports = [];

    /**
     * @var TemporaryFile
     */
    protected $currentFile;

    /**
     * @var TemporaryFileFactory
     */
    protected $temporaryFileFactory;

    /**
     * @var TransactionHandler
     */
    protected $transaction;

    /**
     * @param TemporaryFileFactory $temporaryFileFactory
     * @param TransactionHandler   $transaction
     */
    public function __construct(TemporaryFileFactory $temporaryFileFactory, TransactionHandler $transaction)
    {
        $this->setDefaultValueBinder();

        $this->transaction          = $transaction;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string|null         $disk
     *
     * @throws Exception
     * @throws NoTypeDetectedException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @return \Illuminate\Foundation\Bus\PendingDispatch|$this
     */
    public function read($import, $filePath, string $readerType = null, string $disk = null)
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);

        if ($import instanceof WithChunkReading) {
            return (new ChunkReader)->read($import, $reader, $this->currentFile);
        }

        $this->beforeReading($import, $reader);

        ($this->transaction)(function () use ($import) {
            foreach ($this->sheetImports as $index => $sheetImport) {
                if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                    $sheet->import($sheetImport, $sheet->getStartRow($sheetImport));
                    $sheet->disconnect();
                }
            }
        });

        $this->afterReading($import);

        return $this;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return array
     */
    public function toArray($import, $filePath, string $readerType, string $disk = null): array
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = [];
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets[$index] = $sheet->toArray($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas);
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string              $readerType
     * @param string|null         $disk
     *
     * @throws Exceptions\SheetNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws NoTypeDetectedException
     * @return Collection
     */
    public function toCollection($import, $filePath, string $readerType, string $disk = null): Collection
    {
        $reader = $this->getReader($import, $filePath, $readerType, $disk);
        $this->beforeReading($import, $reader);

        $sheets = new Collection();
        foreach ($this->sheetImports as $index => $sheetImport) {
            $calculatesFormulas = $sheetImport instanceof WithCalculatedFormulas;
            if ($sheet = $this->getSheet($import, $sheetImport, $index)) {
                $sheets->put($index, $sheet->toCollection($sheetImport, $sheet->getStartRow($sheetImport), null, $calculatesFormulas));
                $sheet->disconnect();
            }
        }

        $this->afterReading($import);

        return $sheets;
    }

    /**
     * @return object
     */
    public function getDelegate()
    {
        return $this->spreadsheet;
    }

    /**
     * @return $this
     */
    public function setDefaultValueBinder(): self
    {
        Cell::setValueBinder(
            app(config('excel.value_binder.default', DefaultValueBinder::class))
        );

        return $this;
    }

    /**
     * @param $import
     * @param $sheetImport
     * @param $index
     *
     * @throws SheetNotFoundException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return Sheet|null
     */
    protected function getSheet($import, $sheetImport, $index)
    {
        try {
            return Sheet::make($this->spreadsheet, $index);
        } catch (SheetNotFoundException $e) {
            if ($import instanceof SkipsUnknownSheets) {
                $import->onUnknownSheet($index);

                return null;
            }

            if ($sheetImport instanceof SkipsUnknownSheets) {
                $sheetImport->onUnknownSheet($index);

                return null;
            }

            throw $e;
        }
    }

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @return array
     */
    

    /**
     * @param object              $import
     * @param string|UploadedFile $filePath
     * @param string|null         $readerType
     * @param string              $disk
     *
     * @throws InvalidArgumentException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws NoTypeDetectedException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @return IReader
     */
    

    /**
     * @param object  $import
     * @param IReader $reader
     *
     * @throws Exception
     */
    

    /**
     * @param object $import
     */
    

    /**
     * Garbage collect.
     */
    
}

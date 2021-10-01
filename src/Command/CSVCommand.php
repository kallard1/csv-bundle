<?php

namespace Dnd\Bundle\CSVBundle\Command;

use Cocur\Slugify\Slugify;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CSVCommand
 *
 * @package   Dnd\Bundle\CSVBundle\Command
 * @author    Area42 <contact@area42.fr>
 * @copyright 2020-present Area42
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.area42.fr/
 */
class CSVCommand extends Command
{
    /**
     * Description $defaultName field
     *
     * @var string $defaultName
     */
    protected static $defaultName = 'csv:grid';
    /**
     * Description $defaultDescription field
     *
     * @var string $defaultDescription
     */
    protected static $defaultDescription = 'Show CSV file content into CLI Grid';
    /**
     * Description $kernel field
     *
     * @var KernelInterface $kernel
     */
    protected $kernel;
    /**
     * Description $filesystem field
     *
     * @var Filesystem $filesystem
     */
    protected $filesystem;
    /**
     * Description $header field
     *
     * @var string[] $header
     */
    protected $header;
    /**
     * Description $csvData field
     *
     * @var mixed $csvData
     */
    protected $csvData;
    /**
     * Description $logger field
     *
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * EnableMaintenanceCommand constructor
     *
     * @param KernelInterface $kernel
     * @param Filesystem      $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        KernelInterface $kernel,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->kernel     = $kernel;
        $this->filesystem = $filesystem;
        $this->logger     = $logger;
    }

    /**
     * Description configure function
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription(self::$defaultDescription)->addArgument('file', InputArgument::REQUIRED, 'CSV Filepath');
    }

    /**
     * Description execute function
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SymfonyStyle $io */
        $io = new SymfonyStyle($input, $output);
        /** @var string $file */
        $file = $input->getArgument('file');

        if (!$this->filesystem->exists($file)) {
            $io->error('The file ' . $file . ' not exist');

            return 0;
        }

        $this->getCSVData($io, $file);

        /** @var Table $table */
        $table = new Table($output);

        $table->setHeaders($this->header)->setRows($this->csvData)->render();

        return 0;
    }

    /**
     * Description getCSVData function
     *
     * @param SymfonyStyle $io
     * @param string       $file
     *
     * @return void
     */
    protected function getCSVData(SymfonyStyle $io, string $file): void
    {
        /** @var string[] $header */
        $header = null;
        /** @var string[] $data */
        $data = [];

        /** @var resource|false $handle */
        if (($handle = fopen($file, 'r')) !== false) {
            /** @var mixed[]|false */
            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (!$header) {
                    $header       = $row;
                    $this->header = $header;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }

            fclose($handle);
        }

        $this->setHeader();

        $data = $this->formatData($data);

        $this->csvData = $data;
    }

    /**
     * Description setHeader function
     *
     * @return void
     */
    protected function setHeader(): void
    {
        $this->removeInArray('currency');
        $this->addColumn('slug');
        /** @var string[] $header */
        $header = $this->header;
        $header = str_replace('is_enabled', 'status', $header);
        /** @var string $item */
        foreach ($header as $key => $value) {
            $value = ucfirst($value);
            $value = str_replace('_', ' ', $value);

            $header[$key] = $value;
        }

        $this->header = $header;
    }

    /**
     * Description addColumn function
     *
     * @param string $columnName
     *
     * @return void
     */
    protected function addColumn(string $columnName): void
    {
        $this->header[] = $columnName;
    }

    /**
     * Description removeInArray function
     *
     * @param string $element
     *
     * @return void
     */
    protected function removeInArray(string $element): void
    {
        $pos = array_search($element, $this->header);

        unset($this->header[$pos]);
    }

    protected function formatData(array $data): array
    {
        /** @var mixed[] $product */
        foreach ($data as $productKey => $product) {
            /**
             * @var string $key
             * @var string $value
             */
            foreach ($product as $key => $value) {
                switch ($key) {
                    case 'title':
                        /** @var Slugify $slugger */
                        $slugger = new Slugify();
                        $product['slug'] = $slugger->slugify($value);
                        break;
                    case 'is_enabled':
                        $product[$key] = ($value === '1') ? "Enabled" : "Disabled";
                        break;
                    case 'price':
                        /** @var string $value */ $value = $value . ' ' . $product['currency'];
                        $value                           = str_replace('.', ',', $value);
                        $product[$key]                   = $value;

                        unset($product['currency']);
                        break;
                    case 'created_at':
                        try {
                            /** @var DateTime $date */
                            $date          = new DateTime($value);
                            $product[$key] = $date->format('l, d-M-Y H:i:s e');
                        } catch (Exception $e) {
                            $this->logger->error($e->getMessage());
                        }
                        break;
                }

                $data[$productKey] = $product;
            }
        }

        return $data;
    }
}

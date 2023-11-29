<?php

declare(strict_types=1);

namespace Jajuma\PotQuickReindex\Magewire;

use DateTimeImmutable;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magewirephp\Magewire\Component;
use Throwable;
use Symfony\Component\Process\Exception\ProcessFailedException as SymfonyProcessFailedException;
use Symfony\Component\Process\Process as SymfonyProcess;
use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class Reindex
 * @package Jajuma\PotQuickReindex\Magewire
 */
class Reindex extends Component
{
    /**
     * @var string
     */
    public $message = '';

    /**
     * @var bool
     */
    protected $loader = false;

    /**
     * @var string[]
     */
    protected $listeners = ['reindexById', 'checkIndexIsValid', 'reindexAllIndexers', 'resetAllIndexers'];

    /**
     * @var IndexerInterfaceFactory
     */
    protected $indexerFactory;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var CollectionFactory
     */
    protected $indexCollectionFactory;

    /**
     * @var array
     */
    private $indexers = [];

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * Reindex constructor.
     * @param IndexerInterfaceFactory $indexerFactory
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigInterface $config
     * @param CollectionFactory $indexCollectionFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(
        IndexerInterfaceFactory $indexerFactory,
        IndexerRegistry $indexerRegistry,
        ConfigInterface $config,
        CollectionFactory $indexCollectionFactory,
        DirectoryList $directoryList
    ) {
        $this->indexerFactory = $indexerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->config = $config;
        $this->indexCollectionFactory = $indexCollectionFactory;
        $this->directoryList = $directoryList;
    }

     /**
     * Get Directory Root
     *
     * @return string
     */
    private function getDirectoryRoot(): string
    {
        return $this->directoryList->getRoot();
    }

    public function getAllRegistryIndexers()
    {
        $indexers = [];
        $indexerList = $this->indexCollectionFactory->create()->getItems();
        $indexerList = array_combine(
            array_map(
                function ($item) {
                    /** @var IndexerInterface $item */
                    return $item->getId();
                },
                $indexerList
            ),
            $indexerList
        );
        foreach ($indexerList as $indexer) {
            $data = [
                'indexer_id' => $indexer->getId(),
                'title' => $indexer->getTitle(),
                'description' => $indexer->getDescription(),
                'is_scheduled' => $indexer->isScheduled(),
                'status' => $indexer->getStatus(),
                'updated' => $indexer->getLatestUpdated(),
            ];
            $indexers[] = new DataObject($data);
        }

        return $indexers;
    }

    /**
     *
     */
    public function resetAllIndexers()
    {
        $phpPath = $this->getPhpPath();
        $this->message = '';
        $goToProject = 'cd ' . $this->getDirectoryRoot();
        try {
            $command = "$phpPath bin/magento indexer:reset";
            $process = SymfonyProcess::fromShellCommandline($goToProject . ';' . $command);
            $process->run();
            $output = $process->getOutput();
            $output = explode("\n", $output);
            $outputCount = count($output) - 1;
            if ($outputCount) {
                for($i=0; $i < $outputCount; $i++) {
                    if (strpos($output[$i], 'invalidated') !== false) {
                        $this->message .= '<p>' . '<span style="color: green">&#x2714;</span>' . $output[$i] . '</p>';
                    } else {
                        $this->message .= '<p>' . '<span style="color: red">&#x2718;</span>' . $output[$i] . '</p>';
                    }
                }
            }
        } catch (SymfonyProcessFailedException $e) {
            throw new LocalizedException(__($e->getMessage()));
            $this->message = $e->getMessage();
        }
        $this->dispatchBrowserEvent('reload-indexers-list');
    }

    /**
     * @return array
     */
    private function getAllIndexers()
    {
        if (!count($this->indexers)) {
            $indexers = $this->indexCollectionFactory->create()->getItems();
            return array_combine(
                array_map(
                    function ($item) {
                        /** @var IndexerInterface $item */
                        return $item->getId();
                    },
                    $indexers
                ),
                $indexers
            );
        }
        return $this->indexers;
    }

    /**
     *
     */
    public function reindexAllIndexers()
    {
        $phpPath = $this->getPhpPath();
        $this->message = '';
        $goToProject = 'cd ' . $this->getDirectoryRoot();
        try {
            $command = "$phpPath bin/magento indexer:reindex &";
            $process = SymfonyProcess::fromShellCommandline($goToProject . ';' . $command);
            $process->run();
            $output = $process->getOutput();
            $output = explode("\n", $output);
            $outputCount = count($output)-1;
            if ($outputCount) {
                for($i=0; $i < $outputCount; $i++) {
                    if (strpos($output[$i], 'successfully') !== false) {
                        $this->message .= '<p>' . '<span style="color: green">&#x2714;</span>' . $output[$i] . '</p>';
                    } else {
                        $this->message .= '<p>' . '<span style="color: red">&#x2718;</span>' . $output[$i] . '</p>';
                    }
                }
            }
        } catch (SymfonyProcessFailedException $e) {
            throw new LocalizedException(__($e->getMessage()));
            $this->message = $e->getMessage();
        }
        $this->dispatchBrowserEvent('reload-indexers-list');
    }

    /**
     * @param $indexer_ID
     * @throws Throwable
     */
    public function reindexById($indexer_ID)
    {
        if (is_array($indexer_ID)) {
            if (empty($indexer_ID['indexer_ID'])) {
                return;
            }
            $indexer_ID = $indexer_ID['indexer_ID'];
        }

        if (empty($indexer_ID)) {
            return;
        }

        try {
            /** @var Indexer $indexer */
            $indexer = $this->indexerFactory->create();
            $indexer->load($indexer_ID);
            if ($indexer->isInvalid()) {
                $indexer->reindexAll();
            }
            $this->dispatchBrowserEvent('reload-indexers-list');
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->dispatchErrorMessage($message);
            return;
        }
    }

    /**
     *
     */
    public function checkIndexIsValid()
    {
        foreach (array_keys($this->config->getIndexers()) as $indexerId) {
            $indexer = $this->indexerRegistry->get($indexerId);
            if ($indexer->getStatus() == "invalid") {
                $this->dispatchBrowserEvent('index-is-invalid');
                return;
            }
        }

        $this->dispatchBrowserEvent('index-is-valid');
    }

    private function getPhpPath(){
        $phpFinder = new PhpExecutableFinder;
        if (!$phpPath = $phpFinder->find()) {
            return 'php';
        }

        return $phpPath;
    }
}

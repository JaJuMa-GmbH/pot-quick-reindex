<?php

namespace Jajuma\PotQuickReindex\Block\PowerToys\QuickAction;

use Jajuma\PotQuickReindex\ViewModel\Reindex as ReindexViewModel;
use Jajuma\PowerToys\Block\PowerToys\QuickAction;
use Jajuma\PowerToys\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Indexer\Model\Indexer\CollectionFactory;
/**
 * Class Reindex
 * @package Jajuma\PotQuickReindex\Block\PowerToys\QuickAction
 */
class Reindex extends QuickAction
{
    const XML_PATH_ENABLE = 'power_toys/pot_reindex/is_enabled';

    /**
     * @var Data
     */
    protected $powerToysHelper;

    /**
     * @var ReindexViewModel
     */
    private $viewModel;

    /**
     * @var CollectionFactory
     */
    protected $indexCollectionFactory;

    /**
     * Reindex constructor.
     * @param Template\Context $context
     * @param Data $powerToysHelper
     * @param ReindexViewModel $viewModel
     * @param CollectionFactory $indexCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $powerToysHelper,
        ReindexViewModel $viewModel,
        CollectionFactory $indexCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->powerToysHelper = $powerToysHelper;
        $this->viewModel = $viewModel;
        $this->indexCollectionFactory = $indexCollectionFactory;
    }

    /**
     * @return array
     */
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
     * Is enable
     *
     * @return true
     */
    public function isEnable(): bool
    {
        return $this->_scopeConfig->isSetFlag(self::XML_PATH_ENABLE);
    }

    /**
     * @return ReindexViewModel
     */
    public function getViewModel()
    {
        return $this->viewModel;
    }

}

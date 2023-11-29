<?php

namespace Jajuma\PotQuickReindex\ViewModel;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\ColumnFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\Indexer\Block\Backend\Grid\Column\Renderer\Scheduled;
use Magento\Indexer\Block\Backend\Grid\Column\Renderer\ScheduleStatus;
use Magento\Indexer\Block\Backend\Grid\Column\Renderer\Status;
use Magento\Indexer\Block\Backend\Grid\Column\Renderer\Updated;

/**
 * Class Reindex
 * @package Jajuma\PotQuickReindex\ViewModel
 */
class Reindex
{

    /**
     * @var Scheduled
     */
    private $reindexScheduledRenderer;

    /**
     * @var Status
     */
    private $reindexStatusRenderer;

    /**
     * @var ScheduleStatus
     */
    private $reindexScheduleStatusRenderer;

    /**
     * @var Updated
     */
    private $reindexUpdatedRenderer;

    /**
     * @var ColumnFactory
     */
    private $gridColumnFactory;

    /**
     * Reindex constructor.
     * @param Scheduled $reindexScheduledRenderer
     * @param Status $reindexStatusRenderer
     * @param ScheduleStatus $reindexScheduleStatusRenderer
     * @param Updated $reindexUpdatedRenderer
     * @param ColumnFactory $gridColumnFactory
     */
    public function __construct(
        Scheduled $reindexScheduledRenderer,
        Status $reindexStatusRenderer,
        ScheduleStatus $reindexScheduleStatusRenderer,
        Updated $reindexUpdatedRenderer,
        ColumnFactory $gridColumnFactory
    ) {
        $this->reindexScheduledRenderer = $reindexScheduledRenderer;
        $this->reindexStatusRenderer = $reindexStatusRenderer;
        $this->reindexScheduleStatusRenderer = $reindexScheduleStatusRenderer;
        $this->reindexUpdatedRenderer = $reindexUpdatedRenderer;
        $this->gridColumnFactory = $gridColumnFactory;
    }

    /**
     * @param DataObject $dataObject
     * @param $column
     * @return Phrase|string
     */
    public function renderHtml(DataObject $dataObject, $column)
    {
        /** @var Column $columnObject * */
        $columnObject = $this->gridColumnFactory->create();
        $columnObject->setData([
            'index' => $column
        ]);
        switch ($column) {
            case 'is_scheduled':
                $this->reindexScheduledRenderer->setColumn($columnObject);
                return $this->reindexScheduledRenderer->render($dataObject);
                break;
            case 'status':
                $this->reindexStatusRenderer->setColumn($columnObject);
                return $this->reindexStatusRenderer->render($dataObject);
                break;
            case 'schedule_status':
                $this->reindexScheduleStatusRenderer->setColumn($columnObject);
                return $this->reindexScheduleStatusRenderer->render($dataObject);
                break;
            case 'updated':
                $this->reindexUpdatedRenderer->setColumn($columnObject);
                return $this->reindexUpdatedRenderer->render($dataObject);
                break;
            default:
                return '';
                break;
        }
    }

}
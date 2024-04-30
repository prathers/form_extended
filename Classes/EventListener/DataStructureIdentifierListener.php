<?php

declare(strict_types=1);


namespace WapplerSystems\FormExtended\EventListener;

use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 * Scope: backend
 * @internal
 */
class DataStructureIdentifierListener
{


    /**
     */
    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {

        $identifier = $event->getIdentifier();
        if (!isset($identifier['ext-form-persistenceIdentifier'])) {
            return;
        }

        $featureSiteEmail = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('form_extended', 'featureSiteEmail');
        if ($featureSiteEmail) {
            $dataStructure = $event->getDataStructure();

            $dataStructure['sheets']['sDEF']['ROOT']['el']['settings.sender'] = [
                'label' => 'LLL:EXT:form_extended/Resources/Private/Language/locallang_db.xlf:site_sender',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [],
                    'itemsProcFunc' => \WapplerSystems\FormExtended\Hooks\ItemsProcFunc::class . '->getSiteSenders',
                ],
            ];
            $event->setDataStructure($dataStructure);
        }

    }

}

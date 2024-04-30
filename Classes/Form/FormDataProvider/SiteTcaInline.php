<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace WapplerSystems\FormExtended\Form\FormDataProvider;

/**
 * Special data provider for the sites configuration module.
 *
 * Handle inline children of 'site'
 */
class SiteTcaInline extends \TYPO3\CMS\Backend\Form\FormDataProvider\SiteTcaInline
{
    /**
     * Resolve inline fields
     */
    public function addData(array $result): array
    {
        $result = $this->addInlineFirstPid($result);
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!$this->isInlineField($fieldConfig)) {
                continue;
            }
            $childTableName = $fieldConfig['config']['foreign_table'] ?? '';
            if (!in_array($childTableName, ['site_errorhandling', 'site_route', 'site_base_variant', 'site_sender'], true)) {
                throw new \RuntimeException('Inline relation to other tables not implemented', 1522494737);
            }
            $result['processedTca']['columns'][$fieldName]['children'] = [];
            $result = $this->resolveSiteRelatedChildren($result, $fieldName);
            if (!empty($result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'])) {
                throw new \RuntimeException('selectorOrUniqueConfiguration not implemented in sites module', 1624313533);
            }
        }

        return $result;
    }

}

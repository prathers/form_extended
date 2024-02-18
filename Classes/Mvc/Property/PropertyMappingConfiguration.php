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

namespace WapplerSystems\FormExtended\Mvc\Property;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;
use WapplerSystems\FormExtended\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;

/**
 * Scope: frontend
 * @internal
 */
class PropertyMappingConfiguration extends \TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration
{
    /**
     * This hook is called for each form element after the class
     * TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory has built the entire form.
     *
     * It is invoked after the static form definition is ready, but without knowing
     * about the individual state organized in `FormRuntime` and `FormState`.
     *
     * @param RenderableInterface $renderable
     * @internal
     */
    public function afterBuildingFinished(RenderableInterface $renderable)
    {
        if ($renderable instanceof FileUpload) {
            // Set the property mapping configuration for the file upload element.
            // * Add the UploadedFileReferenceConverter to convert an uploaded file to a
            //   FileReference.
            // * Add the MimeTypeValidator to the UploadedFileReferenceConverter to
            //   delete non-valid file types directly.
            // * Setup the storage:
            //   If the property "saveToFileMount" exist for this element it will be used.
            //   If this file mount or the property "saveToFileMount" does not exist
            //   the default storage "1:/user_uploads/" will be used. Uploads are placed
            //   in a dedicated sub-folder (e.g. ".../form_<40-chars-hash>/actual.file").

            $typeConverter = GeneralUtility::makeInstance(UploadedFileReferenceConverter::class);
            /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
            $propertyMappingConfiguration = $renderable->getRootForm()
                ->getProcessingRule($renderable->getIdentifier())
                ->getPropertyMappingConfiguration()
                ->setTypeConverter($typeConverter);

            $allowedMimeTypes = [];
            $validators = [];
            if (isset($renderable->getProperties()['allowedMimeTypes']) && \is_array($renderable->getProperties()['allowedMimeTypes'])) {
                $allowedMimeTypes = array_filter($renderable->getProperties()['allowedMimeTypes']);
            }
            if (!empty($allowedMimeTypes)) {
                $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class, ['allowedMimeTypes' => $allowedMimeTypes]);
                $validators = [$mimeTypeValidator];
            }

            $renderable->getRootForm()
                ->getProcessingRule($renderable->getIdentifier())
                ->filterValidators(
                    static function ($validator) use (&$validators) {
                        if ($validator instanceof NotEmptyValidator) {
                            return true;
                        }
                        $validators[] = $validator;
                        return false;
                    }
                );

            $uploadConfiguration = [
                UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS => $validators,
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
            ];

            $saveToFileMountIdentifier = $renderable->getProperties()['saveToFileMount'] ?? '';
            if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
                $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
            } else {
                // @todo Why should uploaded files be stored to the same directory as the *.form.yaml definitions?
                $persistenceIdentifier = $renderable->getRootForm()->getPersistenceIdentifier();
                if (!empty($persistenceIdentifier)) {
                    $pathinfo = PathUtility::pathinfo($persistenceIdentifier);
                    $saveToFileMountIdentifier = $pathinfo['dirname'];
                    if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
                        $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
                    }
                }
            }
            $propertyMappingConfiguration->setTypeConverterOptions(UploadedFileReferenceConverter::class, $uploadConfiguration);
            return;
        }

        if ($renderable->getType() === 'Date') {
            // Set the property mapping configuration for the `Date` element.

            /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
            $propertyMappingConfiguration = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier())->getPropertyMappingConfiguration();
            // @see https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html#input.date.attrs.value
            // 'Y-m-d' = https://tools.ietf.org/html/rfc3339#section-5.6 -> full-date
            $propertyMappingConfiguration->setTypeConverterOption(DateTimeConverter::class, DateTimeConverter::CONFIGURATION_DATE_FORMAT, 'Y-m-d');
        }
    }


}

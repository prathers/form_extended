<?php

namespace WapplerSystems\FormExtended\Domain\Finishers;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Service\TranslationService;
use WapplerSystems\FormExtended\Domain\Model\OptIn;
use WapplerSystems\FormExtended\Domain\Repository\OptInRepository;
use WapplerSystems\FormExtended\Event\AfterOptInCreationEvent;
use WapplerSystems\FormExtended\Event\AfterOptInValidationEvent;

class DoubleOptInFinisher extends \TYPO3\CMS\Form\Domain\Finishers\EmailFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'recipientName' => '',
        'senderName' => '',
        'attachUploads' => true,
        'payloadElements' => [],
        'validationPid' => null,
        'useFluidEmail' => true,
        'addHtmlPart' => true,
    ];


    public function __construct(readonly OptInRepository          $optInRepository,
                                readonly EventDispatcherInterface $eventDispatcher)
    {
    }


    /**
     * Executes this finisher
     * @throws FinisherException
     * @see AbstractFinisher::execute()
     *
     */
    protected function executeInternal()
    {

        /* passing options from default options to options for using in EmailFinisher */
        if (empty($this->options['subject'])) {
            $this->options['subject'] = LocalizationUtility::translate('subject.pleaseConfirmEmailAddress', 'form_extended');
        }

        $formRuntime = $this->finisherContext->getFormRuntime();
        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();
        $payloadElementsConfiguration = $this->parseOption('payloadElements');

        $senderAddress = $this->parseOption('senderAddress');
        $senderAddress = is_string($senderAddress) ? $senderAddress : '';


        $featureSiteEmail = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('form_extended', 'featureSiteEmail');
        if ($featureSiteEmail) {

            $flexformService = GeneralUtility::makeInstance(FlexFormService::class);
            $settings = $flexformService->convertFlexFormContentToArray($this->finisherContext->getRequest()->getAttribute('currentContentObject')?->data['pi_flexform'] ?? '');
            $settings = $settings['settings'] ?? [];

            /** @var Site $site */
            $site = $this->finisherContext->getRequest()->getAttribute('site');
            $senders = $site->getAttribute('senders');

            if (isset($settings['sender'])) {
                $senderName = '';
                foreach ($senders as $sender) {
                    if ($sender['email'] === ($settings['sender'] ?? '')) {
                        $senderName = $sender['name'];
                    }
                }
                $senderAddress = $settings['sender'] ?? '';
            }
        }


        $recipients = $this->getRecipients('recipients');
        $validationPid = $this->parseOption('validationPid');

        if (empty($senderAddress)) {
            throw new FinisherException('A valid sender field and a sender address is required.', 1527145483);
        }
        if (empty($recipients)) {
            throw new FinisherException('The option "recipients" must be set for the EmailFinisher.', 1327060200);
        }
        if (empty($validationPid)) {
            throw new FinisherException('The option "validationPid" must be set.', 1527148282);
        }

        /* Opt in data set  */
        $optIn = new OptIn();
        $optIn->setEmail($recipients[0]->getAddress());

        if (is_array($payloadElementsConfiguration)) {
            $payload = $this->prepareData($payloadElementsConfiguration);
            $optIn->setDecodedValues($payload);
        }


        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configuration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $storagePid = $configuration['plugin.']['tx_formextended_doubleoptin.']['persistence.']['storagePid'] ?? -1;

        $optIn->setPid($storagePid);

        $this->optInRepository->add($optIn);

        $this->eventDispatcher->dispatch(
            new AfterOptInCreationEvent($optIn)
        );

        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();
        /* Opt in data set  */


        $languageBackup = null;
        // Flexform overrides write strings instead of integers so
        // we need to cast the string '0' to false.
        if (
            isset($this->options['addHtmlPart'])
            && $this->options['addHtmlPart'] === '0'
        ) {
            $this->options['addHtmlPart'] = false;
        }

        $subject = $this->parseOption('subject');

        $senderName = $this->parseOption('senderName');
        $senderName = is_string($senderName) ? $senderName : '';
        $replyToRecipients = $this->getRecipients('replyToRecipients');
        $carbonCopyRecipients = $this->getRecipients('carbonCopyRecipients');
        $blindCarbonCopyRecipients = $this->getRecipients('blindCarbonCopyRecipients');
        $addHtmlPart = $this->parseOption('addHtmlPart') ? true : false;
        $attachUploads = $this->parseOption('attachUploads');
        $title = (string)$this->parseOption('title') ?: $subject;

        if (empty($subject)) {
            throw new FinisherException('The option "subject" must be set for the EmailFinisher.', 1327060320);
        }
        if (empty($senderAddress)) {
            throw new FinisherException('The option "senderAddress" must be set for the EmailFinisher.', 1327060210);
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        $translationService = GeneralUtility::makeInstance(TranslationService::class);
        if (is_string($this->options['translation']['language'] ?? null) && $this->options['translation']['language'] !== '') {
            $languageBackup = $translationService->getLanguage();
            $translationService->setLanguage($this->options['translation']['language']);
        }

        $mail = $this
                ->initializeFluidEmail($formRuntime)
                ->from(new Address($senderAddress, $senderName))
                ->to(...$recipients)
                ->subject($subject)
                ->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
                ->assign('title', $title)
                ->assign('optIn', $optIn)
                ->assign('validationPid', $validationPid);

        if (!empty($replyToRecipients)) {
            $mail->replyTo(...$replyToRecipients);
        }

        if (!empty($carbonCopyRecipients)) {
            $mail->cc(...$carbonCopyRecipients);
        }

        if (!empty($blindCarbonCopyRecipients)) {
            $mail->bcc(...$blindCarbonCopyRecipients);
        }

        if (!empty($languageBackup)) {
            $translationService->setLanguage($languageBackup);
        }

        if ($attachUploads) {
            foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $element) {
                if (!$element instanceof FileUpload) {
                    continue;
                }
                $file = $formRuntime[$element->getIdentifier()];
                if ($file) {
                    if (is_array($file)) {
                        foreach ($file as $singleFile) {
                            if ($singleFile instanceof FileReference) {
                                $singleFile = $singleFile->getOriginalResource();
                            }
                            $mail->attach($singleFile->getContents(), $singleFile->getName(), $singleFile->getMimeType());
                        }
                        continue;
                    }
                    if ($file instanceof FileReference) {
                        $file = $file->getOriginalResource();
                    }
                    $mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
                }
            }
        }

        GeneralUtility::makeInstance(MailerInterface::class)->send($mail);
    }


    /**
     * Prepare data for saving to database
     *
     * @param array $elementsConfiguration
     * @return mixed
     */
    protected function prepareData(array $elementsConfiguration)
    {
        $data = [];
        foreach ($this->getFormValues() as $elementIdentifier => $elementValue) {

            if (!in_array($elementIdentifier, $elementsConfiguration, true)) {
                continue;
            }

            $data[$elementIdentifier] = $elementValue;
        }
        return $data;
    }

    /**
     * Returns the values of the submitted form
     *
     * @return array
     */
    protected function getFormValues(): array
    {
        return $this->finisherContext->getFormValues();
    }


    /**
     * Returns a form element object for a given identifier.
     *
     * @param string $elementIdentifier
     * @return FormElementInterface|null
     */
    protected function getElementByIdentifier(string $elementIdentifier)
    {
        return $this
            ->finisherContext
            ->getFormRuntime()
            ->getFormDefinition()
            ->getElementByIdentifier($elementIdentifier);
    }

}

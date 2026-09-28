<?php
namespace Fnn\FnnPowermailAltcha\ViewHelpers;

use Fnn\FnnPowermailAltcha\Service\AltchaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AltchaSpamProtectionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    protected string $hmacKey = '';

    protected int $minimumComplexity = 0;

    protected int $maximumComplexity = 0;

    protected int $expires = 0;

    protected int $pid = 0;

    /**
     * Initializes the arguments for the function.
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('settings', 'array', 'Powermail settings');
    }

    /**
     * Generates and returns a configuration array used for rendering
     * a spam shield challenge along with corresponding language labels.
     *
     * The method extracts and validates certain configurations,
     * such as hmacKey, complexity, expiration time, and pid from the given
     * settings, initializing default values if necessary. Language labels
     * configured in TypoScript are returned as overrides for the widget translations.
     *
     * @return array An array containing the generated challenge (`altchaChallenge`)
     *               and optional translated language labels (`langLabels`).
     */
    public function render(): array
    {

        $this->hmacKey = $this->getHmacKey($this->arguments['settings']);

        $this->minimumComplexity = (int) ($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['minimumComplexity'] ?? 0) ?: 5000;
        $this->maximumComplexity = (int) ($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['maximumComplexity'] ?? 0) ?: 15000;

        if (isset($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['expires']) &&
            (int)$this->arguments['settings']['spamshield']['methods'][2025]['configuration']['expires'] > 0) {
            $this->expires = (int)$this->arguments['settings']['spamshield']['methods'][2025]['configuration']['expires'];
        } else {
            $this->expires = 86400;
        }

        if (isset($this->arguments['settings']['main']['pid']) &&
            (int)$this->arguments['settings']['main']['pid'] > 0) {
            $this->pid = (int)$this->arguments['settings']['main']['pid'];
        }

        // Labels are optional overrides of the translations shipped with the widget.
        // Only labels configured in TypoScript are passed on.
        $langLabels = [];
        foreach ($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['labels'] ?? [] as $key => $labelKey) {
            if (is_string($labelKey) && trim($labelKey) !== '') {
                $langLabels[$key] = LocalizationUtility::translate($labelKey) ?? $labelKey;
            }
        }

        $altchaService = GeneralUtility::makeInstance(AltchaService::class,$this->hmacKey, $this->minimumComplexity, $this->maximumComplexity, $this->expires, $this->pid);
        return [
            'altchaChallenge' => $altchaService->createChallenge(),
            'langLabels' => $langLabels,
            'lifetime' => $this->expires,
            'configuration' => $this->arguments['settings']['spamshield']['methods'][2025]['configuration'],
            'widgetConfiguration' => [
                'hideLogo' => (bool) ($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['hideLogo'] ?? false),
                'hideFooter' => (bool) ($this->arguments['settings']['spamshield']['methods'][2025]['configuration']['hideFooter'] ?? false),
            ],
            'hmacKey' => $this->hmacKey,
        ];
    }

    /**
     * @param $settings
     * @return string
     * TODO: Duplicate code fragment. See fnn_powermail_altcha/Classes/Domain/Validator/SpamShield/AltchaMethod.php. Refactoring required.
     */
    private function getHmacKey($settings): string
    {
        if($settings['spamshield']['methods'][2025]['configuration']['hmacKey'] !== '[YOUR_UNIQUE_KEY_HERE]' && $settings['spamshield']['methods'][2025]['configuration']['hmacKey'] !== '') {
            $this->hmacKey = $settings['spamshield']['methods'][2025]['configuration']['hmacKey'];
            return $settings['spamshield']['methods'][2025]['configuration']['hmacKey'];
        } else if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] !== '') {
            return $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        } else {
            return 'error';
        }
    }
}

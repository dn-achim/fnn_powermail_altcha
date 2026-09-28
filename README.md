# fnn_powermail_altcha

**Title:** 599media Powermail Altcha

**Description:**
Altcha spam protection for Powermail forms. This extension integrates the Altcha library to add challenge/response spam protection to Powermail forms.

After installation, a new element called “Altcha Spam Protection” is available in Powermail among the available field types.

To activate spam protection, simply add this field to the desired form. As soon as a Powermail form contains an active “Altcha Spam Protection” field, the Altcha widget is automatically rendered at exactly this point in the frontend.

The visitor then sees the Altcha widget. This additional validation effectively prevents automated bots, while the user experience remains as smooth as possible for real users.



---

## Overview

- **Extension-Key:** `fnn_powermail_altcha`
- **Version:** 13.0.0
- **Author:** 599media Dev-Team (599media GmbH)
- **License:** MIT
- **Category:** Service
- **Dependencies:**
    - TYPO3 ≥ 12.4.0 – 13.4.99
    - PHP ≥ 8.2.0 – 8.4.99
    - Powermail ≥ 12.5.3 – 13.99.99

---

## Installation

### Install extension via ZIP file ###
Installation can now only be performed using Composer.

### Install extension via composer command ###
1. Go to your folder where the root composer.json file is located
2. Type: composer require fnn/fnn-powermail-altcha to get the latest version that runs on your TYPO3 version.
3. Run 'Analyze database' and create the 'table tx_fnnpowermailaltcha_domain_model_challenge'
4. Go to your rootpage, open the TypoScript record and add 'Powermail Altcha' to 'include TypoScript sets'
5. Clear backend cache

---

## Configuration

### TypoScript ###

#### Constants ####
`Configuration/TypoScript/constants.typoscript`
```
plugin.tx_fnn_powermail_altcha {
    # HMAC key (random character string)
    **IMPORTANT: For security, you must generate a unique key for your installation and replace the placeholder below.**
    hmacKey = [YOUR_UNIQUE_KEY_HERE]

    # Complexity of the challenge (PBKDF2 iterations per attempt).
    # A random value between minimum and maximum is used for each challenge.
    minimumComplexity = 5000
    maximumComplexity = 15000

    # Expiry time in seconds (0 = 24 hours)
    expires = 0

    # Hide Altcha logo (0 = show, 1 = hide)
    hideLogo = 0

    # Hide altcha footer (0 = show, 1 = hide)
    hideFooter = 0

    # Validation type: off, onfocus, onload, onsubmit
    validation = onload

    # By default the widget uses its own translations (language taken from <html lang>).
    # Each label can optionally be overridden with an LLL reference or plain text, e.g.:
    # label = LLL:EXT:fnn_powermail_altcha/Resources/Private/Language/locallang.xlf:altchaWidget.label
    labels {
        error     =
        expired   =
        label     =
        verified  =
        verifying =
        waitAlert =
    }
}
```

#### Setup ####
`Configuration/TypoScript/setup.typoscript`
```
plugin.tx_powermail {
    view {
        # The Altcha widget is rendered via a fluid partial.
        # A low number was selected to enable overwriting without major adjustments.
        partialRootPaths {
            5 = EXT:fnn_powermail_altcha/Resources/Private/Templates/Powermail/Partials/
        }
    }
    settings {
        setup {
            spamshield {
                methods {
                    2025 {
                        # The spam check can be activated or deactivated here.
                        _enable = 1
                    }
                }
            }
        }
    }
}
```

### TSConfig ###
The required TSConfig is loaded automatically after installation of the extension.

---

## Changelog ##
All changes can be found in the ChangeLog file. Version 10.0.0 (initial release) and future updates are documented there.

---

## Support & Contact ##
- Author: Dev-Team @ 599media GmbH
- E-Mail: info@599media.de
- Homepage: https://599media.de

---

## License (MIT) ##
The complete license information can be found here.

Additional license information:

This extension contains the ***altcha-org/altcha*** library, which is licensed under a separate MIT license.
The complete license text of the Altcha library can be found in the file ***Vendor/altcha-lib-php/LICENSE.txt*** .

---

## Functionality (simplified) ##
```
                         ┌─────────────────────────┐
                         │     TYPO3 Frontend      │
                         │                         │
                         │  Powermail form page    │
                         └───────────┬─────────────┘
                                     │
                                     ▼
             ┌────────────────────────────────────────────────┐
             │  Render partial „AltchaSpamProtection.html“    │
             │                                                │
             │  • execute AltchaSpamProtectionViewHelper      │
             │    AltchaService::createChallenge()            │
             └───────────────────────┬────────────────────────┘
                                     │
                                     ▼
             ┌────────────────────────────────────────────────┐
             │  AltchaService                                 │
             │  • picks random cost (min/maxComplexity)       │
             │  • calculates HMAC signature (hmacKey)         │
             │  • Saves record in DB (Challenge, expires)     │
             │                                                │
             │  challenge + signature back to Widget          │
             └───────────────────────┬────────────────────────┘
                                     │
                                     ▼
             ┌────────────────────────────────────────────────┐
             │  Browser (Client)                              │
             │  ┌──────────────────────────────────────────┐  │
             │  │ altcha.i18n.min.js (ALTCHA widget v3):   │  │
             │  │  • displays interactive captcha          │  │
             │  │  • User solves task                      │  │
             │  │  • Payload is packed (response)          │  │
             │  └──────────────────────────────────────────┘  │
             │   Form submit with Altcha payload              │
             └───────────────────────┬────────────────────────┘
                                     │
                                     ▼
             ┌────────────────────────────────────────────────┐
             │  Powermail Spamshield (AltchaMethod)           │
             │  • execute AltchaService->verifySolution()     │
             └───────────────────────┬────────────────────────┘
                                     │
                                     ▼
             ┌────────────────────────────────────────────────┐
             │  AltchaService                                 │
             │  • validate payload with                       │
             │    Altcha::verifySolution()                    │
             │                                                │
             │  result back to AltchaMethod                   │
             └────────────────────────────────────────────────┘
```

---

## Note ##
Ignore the folder Vendor/altcha-lib-php. Only the complete source code of the external Altcha library is located there in order to bypass a Composer installation on the TER platform. All further adjustments and configurations refer to the Classes/ directory and the TYPO3-specific configuration files.

With a Composer installation, the library is installed by Composer.

## Thank you for your support ##
- Andreas Kießling https://github.com/akiessling

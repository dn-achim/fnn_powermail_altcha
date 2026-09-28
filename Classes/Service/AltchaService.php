<?php
namespace Fnn\FnnPowermailAltcha\Service;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Fnn\FnnPowermailAltcha\Domain\Model\Challenge;
use Fnn\FnnPowermailAltcha\Domain\Repository\ChallengeRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * The AltchaService class provides methods to create and verify challenges using
 * the Altcha library. It integrates with a repository to persist and manage challenges.
 */
class AltchaService
{
    protected string $hmacKey = '';

    protected int $minimumComplexity = 0;

    protected int $maximumComplexity = 0;

    protected int $expires = 0;

    protected int $pid = 0;

    protected ?ChallengeRepository $challengeRepository = null;

    public function __construct(string $hmacKey, int $minimumComplexity, int $maximumComplexity, int $expires, int $pid = 0)
    {
        $this->hmacKey = $hmacKey;
        $this->minimumComplexity = $minimumComplexity;
        $this->maximumComplexity = $maximumComplexity;
        $this->expires = $expires;
        $this->pid = $pid;
        $this->challengeRepository = GeneralUtility::makeInstance(ChallengeRepository::class);
    }

    /**
     * Creates a new challenge based on the configured options and repository.
     *
     * @return array The created challenge as array, ready to be passed to the widget as JSON.
     */
    public function createChallenge(): array
    {
        $options = new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: $this->determineCost(),
            expiresAt: (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->expires . 'S')),
        );

        $altcha = new Altcha($this->hmacKey);
        $altchaChallenge = $altcha->createChallenge($options);
        $challenge = new Challenge(
            $altchaChallenge->parameters->nonce,
            $options->expiresAt
        );
        $challenge->setPid($this->pid);

        $this->challengeRepository->add($challenge);
        $this->challengeRepository->persistAll();

        return $altchaChallenge->toArray();
    }

    /**
     * Verifies the solution from the base64 encoded payload posted by the widget.
     *
     * @param string $payload The base64 encoded payload of the widget.
     * @return bool Returns true if the solution is valid, false otherwise.
     */
    public function verifySolution(string $payload): bool
    {
        try {
            $options = new VerifySolutionOptions($payload, new Pbkdf2());
        } catch (\Throwable) {
            // Malformed payload (invalid base64, JSON or missing fields)
            return false;
        }

        $challenge = $this->challengeRepository->findByChallenge($options->payload->challenge->parameters->nonce);

        if (is_null($challenge)) {
            return false;
        }

        $altcha = new Altcha($this->hmacKey);
        $checkResult = $altcha->verifySolution($options)->verified;
        if ($checkResult) {
            $challenge->setSolved(true);
            $this->challengeRepository->update($challenge);
            $this->challengeRepository->persistAll();
        }

        $this->challengeRepository->removeObsolete();

        return $checkResult;
    }

    /**
     * Picks a random PBKDF2 iteration count between minimum and maximum complexity,
     * so the workload of a challenge is not predictable.
     */
    protected function determineCost(): int
    {
        if ($this->maximumComplexity < $this->minimumComplexity) {
            return $this->minimumComplexity;
        }

        return random_int($this->minimumComplexity, $this->maximumComplexity);
    }
}

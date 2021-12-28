<?php

namespace App\Form;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Carbon\Exceptions\InvalidFormatException;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

class ImageFilterData
{
    /**
     * @var ?CarbonImmutable
     *
     * @Assert\Range(
     *  min = "Jan 1 1900",
     *  max = "now + 10 years"
     * )
     */
    private $startDate;

    /**
     * @var ?CarbonImmutable
     *
     * @Assert\Range(
     *  min = "Jan 1 1900",
     *  max = "now + 10 years"
     * )
     */
    private $endDate;

    /**
     * @var ?int
     *
     * @Assert\Range(
     *  min = 0,
     *  max = 5
     * )
     *
     */
    private $rating;

    /**
     * @var string
     *
     * @Assert\Regex(
     *  pattern = "/eq|lte|gte/"
     * )
     *
     */
    private $ratingComparison;

    /** @var ?string */
    private $location;

    public function __construct(
        DateTimeInterface $startDate = null,
        DateTimeInterface $endDate = null
    ) {
        $this->startDate = new CarbonImmutable($startDate);
        $this->endDate = new CarbonImmutable($endDate);
        $this->ratingComparison = 'eq';
    }

    public function setStartDate(?DateTimeInterface $startDate): void
    {
        if ($startDate === null) {
            $this->startDate = null;
        } else {
            $this->startDate = new CarbonImmutable($startDate);
        }
    }
    public function overrideStartDateFromUrlParam(?string $startDate): void
    {
        if (!empty($startDate)) {
            $this->startDate = $this->dateFromUrlParam($startDate);
        }
    }
    public function getStartDate(): ?DateTimeInterface
    {
        if ($this->startDate === null) {
            return null;
        }
        return $this->startDate->toDateTimeImmutable();
    }
    public function hasStartDate(): bool
    {
        return $this->startDate !== null;
    }
    public function setEndDate(?DateTimeInterface $endDate): void
    {
        if ($endDate === null) {
            $this->endDate = null;
        }
        $this->endDate = new CarbonImmutable($endDate);
    }
    public function overrideEndDateFromUrlParam(?string $endDate): void
    {
        if (!empty($endDate)) {
            $this->endDate = $this->dateFromUrlParam($endDate);
        }
    }
    public function getEndDate(): ?DateTimeImmutable
    {
        if ($this->endDate === null) {
            return null;
        }
        return $this->endDate->toDateTimeImmutable();
    }
    public function hasEndDate(): bool
    {
        return $this->endDate !== null;
    }
    public function setRating(?int $rating): void
    {
        $this->rating = $rating;
    }
    /**
     * @throws InvalidArgumentException
     */
    public function overrideRatingFromUrlParam(?int $rating): void
    {
        if ($rating !== null && $rating >= 0 && $rating <= 5) {
            $this->rating = $rating;
        } else {
            throw new InvalidArgumentException('Invalid rating override in URL parameter');
        }
    }
    public function getRating(): ?int
    {
        return $this->rating;
    }
    public function hasRating(): bool
    {
        return $this->rating !== null;
    }
    public function setRatingComparison(string $ratingComparison): void
    {
        $this->ratingComparison = $ratingComparison;
    }
    public function getRatingComparison(): string
    {
        return $this->ratingComparison;
    }
    public function setLocation(?string $location): void
    {
        $this->location = $location === "" ? null : $location;
    }
    public function overrideLocationFromUrlParam(?string $location): void
    {
        if (!empty($location)) {
            $this->location = $location;
        }
    }
    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function hasLocation(): bool
    {
        return $this->location !== null;
    }
    private function getDateForUrlParam(?CarbonImmutable $d): string
    {
        if ($d === null) {
            return '';
        }
        return $d->isoFormat('YYYY-MM-DD');
    }
    /**
     * Gets all filter parameters in a format suitable for passing to Symfony's
     * route generator.
     *
     * @return array<string>
     */
    public function getAsUrlParams(): array
    {
        return [
            'startDate' => $this->getDateForUrlParam($this->startDate),
            'endDate' => $this->getDateForUrlParam($this->endDate),
            'rating' => $this->rating === null ? '' : (string) $this->rating,
            'ratingComparison' => $this->ratingComparison === null ? '' : $this->ratingComparison,
            'location' => $this->location === null ? '' : $this->location
        ];
    }
    /**
     * @throws InvalidArgumentException
     */
    private function dateFromUrlParam(?string $param): ?CarbonImmutable
    {
        if ($param === null || $param === "") {
            return null;
        }
        // These inputs are as-yet unsanitised, so:
        $result = null;
        try {
            $result = CarbonImmutable::parse($param);
            if ($result->year < 1900 || $result->year > 9999) {
                // Improbable dates could cause problems if used in DB queries.
                $result = null;
            }
        } catch (Exception $e) {
            // I don't care what happened personally; we'll just not bother
            // overriding our existing dates. However, whatever called us
            // might want to log this, as someone may be probing the app for
            // insecure points.
            throw new InvalidArgument('Invalid date URL parameter', 0, $e);
        }
        return $result;
    }
}

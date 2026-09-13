<?php

namespace App\Rules;

use App\Domain\Beta\Contracts\BetaRequestRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects a beta request whose email already has one awaiting review or
 * approved, so a visitor sees "your request was already submitted" instead of
 * silently overwriting their earlier request. Rejected requests are allowed
 * through so a previously declined applicant can try again.
 */
class NotAlreadyRequestedBetaAccess implements ValidationRule
{
    public function __construct(private readonly BetaRequestRepository $betaRequests) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->betaRequests->hasActiveRequestForEmail($value)) {
            $fail(__('Your request was already submitted. We\'ll be in touch soon.'));
        }
    }
}

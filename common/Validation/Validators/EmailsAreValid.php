<?php

namespace Common\Validation\Validators;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Illuminate\Support\Facades\Log;

class EmailsAreValid implements Rule
{
    use ValidatesAttributes;

    private $maxEmails = 5;
    private $validateExistence;
    private $validationMessage;

    public function __construct($validateExistence = true)
    {
        $this->validateExistence = $validateExistence;
    }

    public function passes($attribute, $value)
    {
        // Ensure $value is an array
        if (!is_array($value)) {
            $value = [$value];
        }

        // Validate email format
        $invalidEmails = array_filter($value, function ($email) use ($attribute) {
            return !$this->validateEmail($attribute, $email, []);
        });

        if (!empty($invalidEmails)) {
            $this->validationMessage = $this->invalidEmailsMessage($invalidEmails);
            return false;
        }

        // Validate email existence in the database
        if ($this->validateExistence) {
            $dbEmails = app(User::class)->whereIn('email', $value)->pluck('email');
            Log::info('Checking emails in DB: ', ['emails' => $value, 'found' => $dbEmails]);

            $nonExistentEmails = array_filter($value, function ($email) use ($dbEmails) {
                return !$dbEmails->contains($email);
            });

            if (!empty($nonExistentEmails)) {
                $this->validationMessage = $this->emailsDontExistMessage($nonExistentEmails);
                return false;
            }
        }

        return true;
    }

    private function invalidEmailsMessage(array $emails): string
    {
        $emailString = implode(', ', array_slice($emails, 0, $this->maxEmails));
        if (count($emails) > $this->maxEmails) {
            $emailString .= '...';
        }
        return trans('Invalid emails: :emails', ['emails' => $emailString]);
    }

    private function emailsDontExistMessage(array $emails): string
    {
        $emailString = implode(', ', array_slice($emails, 0, $this->maxEmails));
        if (count($emails) > $this->maxEmails) {
            $emailString .= '...';
        }
        return trans('User with email: :emails does not exist', ['emails' => $emailString]);
    }

    public function message()
    {
        return $this->validationMessage;
    }
}

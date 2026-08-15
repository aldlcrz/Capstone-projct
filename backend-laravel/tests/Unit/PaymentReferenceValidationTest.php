<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class PaymentReferenceValidationTest extends TestCase
{
    private function validateReference(string $method, ?string $ref)
    {
        $isGcash = strcasecmp($method, 'GCash') === 0;
        $isMaya  = strcasecmp($method, 'Maya') === 0;

        return Validator::make([
            'paymentMethod' => $method,
            'paymentReference' => $ref,
        ], [
            'paymentMethod' => 'required|string',
            'paymentReference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($isGcash, $isMaya) {
                    $raw = trim((string)$value);

                    if (preg_match('/^(\d)\1+$/', $raw)) {
                        $fail('Invalid payment reference number. Repeated digit sequences are not allowed.');
                        return;
                    }

                    if ($isGcash) {
                        if (!preg_match('/^\d{13}$/', $raw)) {
                            $fail('Reference number must be exactly 13 digits.');
                            return;
                        }
                    } elseif ($isMaya) {
                        if (!preg_match('/^\d{12}$/', $raw)) {
                            $fail('Reference number must be exactly 12 digits.');
                            return;
                        }
                    } else {
                        if (!preg_match('/^\d{10,16}$/', $raw)) {
                            $fail('The payment reference number must contain between 10 and 16 digits.');
                            return;
                        }
                    }
                },
            ],
        ]);
    }

    public function test_gcash_rejects_empty()
    {
        $v = $this->validateReference('GCash', '');
        $this->assertTrue($v->fails());
    }

    public function test_gcash_rejects_10_digits()
    {
        $v = $this->validateReference('GCash', '2222222222');
        $this->assertTrue($v->fails());
    }

    public function test_gcash_rejects_11_digits()
    {
        $v = $this->validateReference('GCash', '11111111111');
        $this->assertTrue($v->fails());
    }

    public function test_gcash_rejects_12_digits()
    {
        $v = $this->validateReference('GCash', '111111111111');
        $this->assertTrue($v->fails());
    }

    public function test_gcash_rejects_repeated_digits()
    {
        foreach (['1111111111111', '2222222222222', '3333333333333', '4444444444444', '9999999999999', '0000000000000'] as $fakeRef) {
            $v = $this->validateReference('GCash', $fakeRef);
            $this->assertTrue($v->fails(), "Failed to reject repeated sequence: $fakeRef");
            $this->assertContains('Invalid payment reference number. Repeated digit sequences are not allowed.', $v->errors()->get('paymentReference'));
        }
    }

    public function test_gcash_accepts_valid_realistic_13_digits()
    {
        $v = $this->validateReference('GCash', '1002345678901');
        $this->assertTrue($v->passes());

        $v2 = $this->validateReference('GCash', '2026194827163');
        $this->assertTrue($v2->passes());
    }

    public function test_gcash_rejects_14_digits()
    {
        $v = $this->validateReference('GCash', '22222222222222');
        $this->assertTrue($v->fails());
    }

    public function test_gcash_rejects_letters_and_symbols()
    {
        $v1 = $this->validateReference('GCash', '123456789012A');
        $this->assertTrue($v1->fails());

        $v2 = $this->validateReference('GCash', '1234 5678 9012');
        $this->assertTrue($v2->fails());

        $v3 = $this->validateReference('GCash', '1234-5678-90123');
        $this->assertTrue($v3->fails());
    }

    public function test_maya_validation()
    {
        // 11 digits fails
        $v1 = $this->validateReference('Maya', '11111111111');
        $this->assertTrue($v1->fails());

        // Repeated digits fails
        $v2 = $this->validateReference('Maya', '111111111111');
        $this->assertTrue($v2->fails());

        // Realistic 12 digits passes
        $v3 = $this->validateReference('Maya', '123456789012');
        $this->assertTrue($v3->passes());

        // 13 digits fails for Maya
        $v4 = $this->validateReference('Maya', '1002345678901');
        $this->assertTrue($v4->fails());
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomersBankValidationTest extends TestCase
{
    public function test_bank_customer_fields_are_correctly_configured(): void
    {
        $createContent = file_get_contents(base_path('resources/views/contact/create.blade.php'));
        $editContent = file_get_contents(base_path('resources/views/contact/edit.blade.php'));

        // 1. Assert country field is converted to Form::select dropdown
        $this->assertStringContainsString("Form::select('country'", $createContent);
        $this->assertStringContainsString("Form::select('country'", $editContent);

        // 2. Assert field title "Photo" is renamed to "Customer Photo"
        $this->assertStringContainsString("Customer Photo", $createContent);
        $this->assertStringContainsString("Customer Photo", $editContent);
        $this->assertStringNotContainsString("__('Photo')", $createContent);

        // 3. Assert Mobile title includes country code instruction
        $this->assertStringContainsString("(Enter with the country code)", $createContent);
        $this->assertStringContainsString("(Enter with the country code)", $editContent);

        // 4. Assert JS validation has dynamic required callback for nic_number and passport_number
        $this->assertStringContainsString("required: function", $createContent);
        $this->assertStringContainsString("required: function", $editContent);

        // 5. Assert image fields are no longer HTML required
        $this->assertStringNotContainsString("Form::file('nic_image', ['id' => 'nic_image', 'accept' => 'image/*', 'required'])", $createContent);
        $this->assertStringNotContainsString("Form::file('passport_image', ['id' => 'passport_image', 'accept' => 'image/*', 'required'])", $createContent);
        $this->assertStringNotContainsString("Form::file('image', ['id' => 'image', 'accept' => 'image/*', 'required'])", $createContent);
        $this->assertStringNotContainsString("Form::file('signature', ['id' => 'signature', 'accept' => 'image/*', 'required'])", $createContent);
    }
}

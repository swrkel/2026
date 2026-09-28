<?php

namespace Tests\Feature\Petro;

use App\Business;
use App\Account;
use App\AccountTransaction;
use App\Journal;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalEntryDateAndFilterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_displays_creation_date_as_date_and_selected_date_as_transaction_date_for_journal_entries()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create Account
        $account = Account::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cash'],
            [
                'account_number' => 'CASH-9988',
                'account_type_id' => 1,
                'created_by' => $user->id,
            ]
        );

        // 2. Create Journal Entry
        // selected date: 2026-05-01
        // creation/system date: 2026-05-27
        $journal = Journal::create([
            'business_id' => $business->id,
            'journal_id' => 999,
            'location_id' => 1,
            'date' => '2026-05-01',
            'debit_amount' => 5000,
            'account_id' => $account->id,
            'added_by' => $user->id,
            'show_in_ledger' => 'no',
        ]);

        $acc_tran = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 5000,
            'operation_date' => '2026-05-01 00:00:00',
            'created_at' => '2026-05-27 10:00:00',
            'created_by' => $user->id,
            'journal_entry' => $journal->id,
        ]);

        // 3. Query Account Book with AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $account->id . '?start_date=2026-05-01&end_date=2026-05-30&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        fwrite(STDERR, "\nReturned Data: " . json_encode($data) . "\n");

        // Find our journal transaction row
        $journalRow = null;
        foreach ($data as $row) {
            if (($row['journal_entry'] ?? null) == $journal->id) {
                $journalRow = $row;
                break;
            }
        }

        $this->assertNotNull($journalRow, 'Journal transaction row should be returned in DataTables data.');

        // Date column (operation_date formatted) should show the system creation date: 2026-05-27
        $this->assertEquals('2026-05-27', $journalRow['operation_date']);

        // Transaction Date column (realize_date formatted) should show the selected transaction date: 2026-05-01
        $this->assertEquals('2026-05-01', $journalRow['realize_date']);
    }

    /** @test */
    public function it_filters_journal_entries_by_transaction_date_correctly()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        $account = Account::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cash'],
            [
                'account_number' => 'CASH-9988',
                'account_type_id' => 1,
                'created_by' => $user->id,
            ]
        );

        $journal = Journal::create([
            'business_id' => $business->id,
            'journal_id' => 999,
            'location_id' => 1,
            'date' => '2026-05-01',
            'debit_amount' => 5000,
            'account_id' => $account->id,
            'added_by' => $user->id,
            'show_in_ledger' => 'no',
        ]);

        $acc_tran = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 5000,
            'operation_date' => '2026-05-01 00:00:00',
            'created_at' => '2026-05-27 10:00:00',
            'created_by' => $user->id,
            'journal_entry' => $journal->id,
        ]);

        // Filter for transaction date (operation_date) in a range containing 2026-05-01
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $account->id . '?start_date=2026-05-01&end_date=2026-05-15&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $found = false;
        foreach ($data as $row) {
            if (($row['journal_entry'] ?? null) == $journal->id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Journal entry should be found when filtering by selected transaction date.');

        // Filter for transaction date in a range not containing 2026-05-01
        $responseOut = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $account->id . '?start_date=2026-05-10&end_date=2026-05-20&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $responseOut->assertStatus(200);
        $dataOut = $responseOut->json('data');

        $foundOut = false;
        foreach ($dataOut as $row) {
            if (($row['journal_entry'] ?? null) == $journal->id) {
                $foundOut = true;
                break;
            }
        }
        $this->assertFalse($foundOut, 'Journal entry should NOT be found when filtering outside selected transaction date.');
    }

    /** @test */
    public function it_filters_journal_entries_by_system_date_correctly()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        $account = Account::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cash'],
            [
                'account_number' => 'CASH-9988',
                'account_type_id' => 1,
                'created_by' => $user->id,
            ]
        );

        $journal = Journal::create([
            'business_id' => $business->id,
            'journal_id' => 999,
            'location_id' => 1,
            'date' => '2026-05-01',
            'debit_amount' => 5000,
            'account_id' => $account->id,
            'added_by' => $user->id,
            'show_in_ledger' => 'no',
        ]);

        $acc_tran = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 5000,
            'operation_date' => '2026-05-01 00:00:00',
            'created_at' => '2026-05-27 10:00:00',
            'created_by' => $user->id,
            'journal_entry' => $journal->id,
        ]);

        // Filter for range not containing 2026-05-01 but containing created_at 2026-05-27.
        // It should NOT find the journal entry because it filters by transaction date.
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $account->id . '?start_date=2026-05-25&end_date=2026-05-30&date_based_on=cheque_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $found = false;
        foreach ($data as $row) {
            if (($row['journal_entry'] ?? null) == $journal->id) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, 'Journal entry should NOT be found when range is outside selected transaction date, even with cheque_date option.');

        // Filter for range containing selected transaction date 2026-05-01.
        // It SHOULD find the journal entry.
        $responseIn = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $account->id . '?start_date=2026-05-01&end_date=2026-05-15&date_based_on=cheque_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $responseIn->assertStatus(200);
        $dataIn = $responseIn->json('data');

        $foundIn = false;
        foreach ($dataIn as $row) {
            if (($row['journal_entry'] ?? null) == $journal->id) {
                $foundIn = true;
                break;
            }
        }
        $this->assertTrue($foundIn, 'Journal entry should be found when range contains selected transaction date.');
    }

    /** @test */
    public function it_displays_correct_journal_entry_amount_and_description_in_customer_ledger()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create a Customer Contact
        $contact = \App\Contact::firstOrCreate(
            ['business_id' => $business->id, 'type' => 'customer', 'name' => 'John Doe Ledger Test'],
            [
                'contact_id' => 'CUST-LEDGER-9988',
                'created_by' => $user->id,
            ]
        );

        // 2. Create Journal Transaction of type 'ledger'
        // Parent transaction total: 16826.06
        $transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => 1,
            'type' => 'ledger',
            'contact_id' => $contact->id,
            'invoice_no' => 'Journal: 9999',
            'total_before_tax' => 16826.06,
            'transaction_date' => '2026-05-01 12:00:00',
            'final_total' => 16826.06,
            'additional_notes' => 'Test notes',
            'created_by' => $user->id,
        ]);

        // 3. Create specific ContactLedger record for that customer
        // Specific amount: 5000.00
        $ledger = \App\ContactLedger::create([
            'created_by' => $user->id,
            'contact_id' => $contact->id,
            'type' => 'credit',
            'amount' => 5000.00,
            'transaction_id' => $transaction->id,
            'operation_date' => '2026-05-01 12:00:00',
            'note' => 'Bayar hutang',
            'cheque_number' => 'CHQ-123',
            'description' => 'Journal 9999',
        ]);

        // 4. Hit contact ledger endpoint
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
            'business' => ['date_format' => 'Y-m-d'],
        ])->getJson('/contacts/ledger?contact_id=' . $contact->id . '&start_date=2026-05-01&end_date=2026-05-30&type=customer');

        $response->assertStatus(200);
        $html = $response->json('html');

        fwrite(STDERR, "\nReturned Ledger HTML: " . $html . "\n");

        // Verify it displays the correct amount (5000.00), NOT the wrong amount (16,826.06)
        $this->assertStringContainsString('5,000', $html, 'The specific journal ledger amount of 5000 should be displayed.');
        $this->assertStringNotContainsString('16,826', $html, 'The parent ledger transaction total of 16826.06 should NOT be displayed.');

        // Verify description displays "Journal Entry with Note"
        $this->assertStringContainsString('Journal Entry with Note: Bayar hutang', $html, 'The description should show Journal Entry with Note: [note]');
    }

    /** @test */
    public function it_displays_correct_journal_entry_amount_and_description_in_supplier_ledger()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create a Supplier Contact
        $contact = \App\Contact::firstOrCreate(
            ['business_id' => $business->id, 'type' => 'supplier', 'name' => 'Jane Doe Supplier Ledger Test'],
            [
                'contact_id' => 'SUPP-LEDGER-9988',
                'created_by' => $user->id,
            ]
        );

        // 2. Create Journal Transaction of type 'ledger'
        // Parent transaction total: 40000.00
        $transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => 1,
            'type' => 'ledger',
            'contact_id' => $contact->id,
            'invoice_no' => 'Journal: 8888',
            'total_before_tax' => 40000.00,
            'transaction_date' => '2026-05-01 12:00:00',
            'final_total' => 40000.00,
            'additional_notes' => 'Test notes supplier',
            'created_by' => $user->id,
        ]);

        // 3. Create specific ContactLedger record for that supplier
        // Specific amount: 6300.00
        $ledger = \App\ContactLedger::create([
            'created_by' => $user->id,
            'contact_id' => $contact->id,
            'type' => 'debit',
            'amount' => 6300.00,
            'transaction_id' => $transaction->id,
            'operation_date' => '2026-05-01 12:00:00',
            'note' => 'Note Note',
            'cheque_number' => 'CHQ-456',
            'description' => 'Journal 8888',
        ]);

        // 4. Hit contact ledger endpoint for supplier
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
            'business' => ['date_format' => 'Y-m-d'],
        ])->getJson('/contacts/ledger?contact_id=' . $contact->id . '&start_date=2026-05-01&end_date=2026-05-30&type=supplier');

        $response->assertStatus(200);
        $html = $response->json('html');

        fwrite(STDERR, "\nReturned Supplier Ledger HTML: " . $html . "\n");

        // Verify it displays the correct amount (6300.00), NOT the wrong amount (40,000.00)
        $this->assertStringContainsString('6,300', $html, 'The specific journal ledger amount of 6300 should be displayed.');
        $this->assertStringNotContainsString('40,000', $html, 'The parent ledger transaction total of 40000 should NOT be displayed.');

        // Verify description displays "Journal Entry with Note: Note Note"
        $this->assertStringContainsString('Journal Entry with Note: Note Note', $html, 'The description should show Journal Entry with Note: [note]');
    }

    /** @test */
    public function it_generates_sequential_journal_no_regardless_of_request_input()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        \Modules\Superadmin\Entities\Subscription::updateOrCreate(
            ['business_id' => $business->id],
            [
                'package_id' => 1,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'package_price' => 0,
                'package_details' => [
                    'daily_review' => 0
                ],
                'created_id' => $user->id,
                'status' => 'approved',
                'module_activation_details' => json_encode([]),
                'customer_credit_notification_type' => json_encode([]),
            ]
        );

        // Delete any existing journals so we start clean from 1
        Journal::truncate();

        // 1. Send first store request with journal_id = 2 (simulate outdated UI state)
        $payload1 = [
            'location_id' => 1,
            'date' => '2026-05-27',
            'debit_total' => 1000,
            'credit_total' => 1000,
            'show_in_ledger' => 'no',
            'journal_id' => 2,
            'journal' => [
                'account_type_id' => [1, 2],
                'account_id' => [1, 2],
                'debit_amount' => [1000, 0],
                'credit_amount' => [0, 1000],
                'note' => ['note1', 'note2'],
            ]
        ];

        $response1 = $this->withSession([
            'business' => ['id' => $business->id, 'date_format' => 'Y-m-d'],
            'user' => ['id' => $user->id, 'business_id' => $business->id]
        ])->post('/accounting-module/journal', $payload1);
        $response1->assertRedirect();

        // The first journal entry should be assigned Journal No = 1 (or at least a sequential unique number starting clean)
        $firstJournal = Journal::orderBy('id', 'asc')->first();
        $this->assertNotNull($firstJournal);
        $firstJournalId = $firstJournal->journal_id;

        // 2. Send second store request also with journal_id = 2 (simulating concurrent submission)
        $payload2 = [
            'location_id' => 1,
            'date' => '2026-05-27',
            'debit_total' => 2000,
            'credit_total' => 2000,
            'show_in_ledger' => 'no',
            'journal_id' => 2,
            'journal' => [
                'account_type_id' => [1, 2],
                'account_id' => [1, 2],
                'debit_amount' => [2000, 0],
                'credit_amount' => [0, 2000],
                'note' => ['note3', 'note4'],
            ]
        ];

        $response2 = $this->withSession([
            'business' => ['id' => $business->id, 'date_format' => 'Y-m-d'],
            'user' => ['id' => $user->id, 'business_id' => $business->id]
        ])->post('/accounting-module/journal', $payload2);
        $response2->assertRedirect();

        // Fetch the second journal entry (created later)
        $secondJournal = Journal::orderBy('id', 'desc')->first();
        $this->assertNotNull($secondJournal);

        // Verify that the second entry has automatically been assigned the next sequential Journal No (firstJournalId + 1), NOT duplicate
        $this->assertEquals($firstJournalId + 1, $secondJournal->journal_id, 'The second journal entry must get the next sequential journal_id.');
    }
}



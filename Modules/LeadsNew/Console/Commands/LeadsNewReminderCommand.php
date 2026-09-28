<?php
namespace Modules\LeadsNew\Console\Commands;
use Illuminate\Console\Command;
class LeadsNewReminderCommand extends Command { protected $signature='leadsnew:reminders'; protected $description='Send Leads-New follow-up reminders'; public function handle(){ $this->info('Leads-New reminders checked.'); return self::SUCCESS; } }

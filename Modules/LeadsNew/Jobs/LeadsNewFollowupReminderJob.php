<?php
namespace Modules\LeadsNew\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels;
class LeadsNewFollowupReminderJob implements ShouldQueue { use InteractsWithQueue, Queueable, SerializesModels; public function handle(): void { /* standalone reminder hook */ } }

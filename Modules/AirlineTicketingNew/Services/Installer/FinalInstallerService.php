<?php
namespace Modules\AirlineTicketingNew\Services\Installer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
class FinalInstallerService {
 public function install(): array {Artisan::call('module:migrate',['module'=>'AirlineTicketingNew','--force'=>true]);Artisan::call('optimize:clear');$ok=Schema::hasTable('atn_settings');return ['status'=>$ok?'installed':'attention_required','installed_at'=>now()->toDateTimeString()];}
}

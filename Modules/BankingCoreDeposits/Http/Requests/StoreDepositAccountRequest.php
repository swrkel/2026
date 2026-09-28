<?php
namespace Modules\BankingCoreDeposits\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreDepositAccountRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['account_name'=>'required|string|max:191','account_type'=>'required|in:savings,current,fixed_deposit','product_id'=>'required|integer']; } }

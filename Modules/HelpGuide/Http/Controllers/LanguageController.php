<?php
namespace Modules\HelpGuide\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpGuide\Services\LanguageService;
class LanguageController extends Controller
{
    public function __construct(private LanguageService $languages){}
    public function setDefault(Request $request){$d=$request->validate(['language_code'=>'required|string|max:12']);$this->languages->saveUserDefault((int)auth()->id(),$d['language_code']);return back()->with('hg_success','Default Help Guide language updated.');}
}

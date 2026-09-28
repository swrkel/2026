<?php
use Illuminate\Support\Facades\Route;
use Modules\HelpGuide\Http\Controllers\GuideController;
use Modules\HelpGuide\Http\Controllers\SuperadminModuleController;
use Modules\HelpGuide\Http\Controllers\ArticleAdminController;
use Modules\HelpGuide\Http\Controllers\LanguageController;

Route::middleware(['web','auth'])->group(function(){
    Route::get('/help-guide',[GuideController::class,'index'])->name('helpguide.index');
    Route::get('/help-guide/module/{moduleKey}',[GuideController::class,'module'])->name('helpguide.module');
    Route::get('/help-guide/article/{id}',[GuideController::class,'article'])->whereNumber('id')->name('helpguide.article');
    Route::get('/help-guide/category/{id}',[GuideController::class,'category'])->whereNumber('id')->name('helpguide.category');
    Route::get('/help-guide/helpdesk-article/{id}',[GuideController::class,'helpdeskArticle'])->whereNumber('id')->name('helpguide.helpdesk.article');
    Route::get('/help-guide/search',[GuideController::class,'search'])->name('helpguide.search');
    Route::post('/help-guide/default-language',[LanguageController::class,'setDefault'])->name('helpguide.language.default');
    Route::redirect('/helpguide','/help-guide',301);
    Route::prefix('superadmin/help-guide-modules')->group(function(){
        Route::get('/',[SuperadminModuleController::class,'index'])->name('helpguide.superadmin.index');
        Route::get('/businesses',[SuperadminModuleController::class,'businesses'])->name('helpguide.superadmin.businesses');
        Route::get('/matrix',[SuperadminModuleController::class,'matrix'])->name('helpguide.superadmin.matrix');
        Route::post('/save',[SuperadminModuleController::class,'save'])->name('helpguide.superadmin.save');
        Route::get('/articles',[ArticleAdminController::class,'index'])->name('helpguide.superadmin.articles');
        /*
         * IS2164: the article list has its own page.
         *
         * /articles is the editor; /articles/list is the listing. Both use the
         * same controller data, so an article edited from the list still opens
         * in the editor with ?edit={id} exactly as before.
         */
        Route::get('/articles/list',[ArticleAdminController::class,'listArticles'])->name('helpguide.superadmin.articles.list');
        Route::post('/articles/save',[ArticleAdminController::class,'save'])->name('helpguide.superadmin.articles.save');
        Route::get('/articles/{id}/view',[ArticleAdminController::class,'view'])->whereNumber('id')->name('helpguide.superadmin.articles.view');
        Route::post('/articles/{id}/delete',[ArticleAdminController::class,'delete'])->name('helpguide.superadmin.articles.delete');
        Route::post('/articles/{id}/retry-translation',[ArticleAdminController::class,'retry'])->name('helpguide.superadmin.articles.retry');
        /*
         * Image and video upload for the article editor.
         *
         * The editor has always had the picture button, and articles.blade.php
         * already builds its url from this route name - but the route was never
         * registered, so Route::has() returned false, the url resolved to an
         * empty string, and every upload failed silently.
         */
        Route::post('/articles/upload',[ArticleAdminController::class,'upload'])->name('helpguide.superadmin.articles.upload');
    });
});

# Banking Work Route Notes

The module must not duplicate controller namespaces in Routes files because the module RouteServiceProvider already applies the namespace.

Use:

Route::get('example', 'ExampleController@index');

Do not add another namespace group inside the module routes file.

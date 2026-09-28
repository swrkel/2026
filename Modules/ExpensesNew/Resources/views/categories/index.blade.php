@extends('expensesnew::layouts.app', ['heading'=>'Categories'])
@section('module_content')
@include('expensesnew::components.toolbar', [
    'createRoute' => route('expensesnew.categories.create'),
    'createLabel' => '+ Add',
])
<div class="expnew-card expnew-centered-table-shell">
    <div class="table-responsive">
        <table id="expnew_categories_table" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Expense Account</th>
                    <th>Default Payee Name</th>
                    <th>Active</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->code ?: '—' }}</td>
                        <td>{{ optional($category->expenseAccount)->name ?: '—' }}</td>
                        <td>{{ optional($category->defaultPayee)->name ?: '—' }}</td>
                        <td>{{ $category->is_active ? 'Yes' : 'No' }}</td>
                        <td>@include('expensesnew::categories.partials.actions', ['c' => $category])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    function initialise(attempt) {
        if (!window.jQuery) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }

        jQuery(function ($) {
            var selector = '#expnew_categories_table';
            var $table = $(selector);
            var table = null;

            if (!$table.length) {
                return;
            }

            $(document)
                .off('click.expnewCategoryDelete', selector + ' .expnew-delete')
                .on('click.expnewCategoryDelete', selector + ' .expnew-delete', function (event) {
                    event.preventDefault();
                    var $button = $(this);

                    if ($button.hasClass('disabled') || !window.confirm('Delete this category?')) {
                        return;
                    }

                    $button.addClass('disabled').attr('aria-disabled', 'true');
                    $.ajax({
                        url: $button.data('url'),
                        type: 'DELETE',
                        data: {_token: $('meta[name="csrf-token"]').attr('content')}
                    }).done(function () {
                        var $row = $button.closest('tr');
                        if (table && $.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                            table.row($row).remove().draw(false);
                        } else {
                            $row.remove();
                        }
                    }).fail(function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Unable to delete the category.';
                        window.alert(message);
                        $button.removeClass('disabled').removeAttr('aria-disabled');
                    });
                });

            if (!$.fn.DataTable) {
                return;
            }

            if ($.fn.DataTable.isDataTable(selector)) {
                $table.DataTable().destroy();
            }

            table = $table.DataTable({
                autoWidth: false,
                pageLength: 25,
                scrollX: true,
                order: [[0, 'asc']],
                columnDefs: [
                    {targets: [5], orderable: false, searchable: false}
                ]
            });

            $('.expnew-search')
                .off('keyup.expnewCategories input.expnewCategories')
                .on('keyup.expnewCategories input.expnewCategories', function () {
                    table.search(this.value).draw();
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initialise(0); });
    } else {
        initialise(0);
    }
})();
</script>
@endsection

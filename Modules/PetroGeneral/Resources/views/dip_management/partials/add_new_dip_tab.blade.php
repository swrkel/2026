{{--
    S665: "Add New Dip" tab for Petro General > DIP Management.

    The module already contained everything needed except a way to reach it:

        route   GET  /add-new-dip                 -> DipManagementController@addNewDip
        route   POST /save-new-dip-reading        -> DipManagementController@saveNewDip
        view    dip_management/add_new_dip.blade.php  (the modal, 376 lines)

    Nothing anywhere referenced add-new-dip, so addNewDip() was unreachable from
    the interface. This tab is the missing piece.

    Deliberately NOT a copy of the Petro module's page, per the requirement. The
    existing Petro General modal is reused as-is, so the fields, validation and
    save behaviour are the module's own and there is only one place to maintain.

    The tab follows the same shape as dip_report / dip_resetting / dip_chart:
    a partial included from a .tab-pane in index.blade.php.
--}}

<section class="content">
    <div class="row">
        <div class="col-md-12">

            @component('components.widget', [
                'class' => 'box-primary',
                'title' => __('petrogeneral::lang.add_new_dip'),
            ])

                <p class="text-muted" style="margin-bottom:15px;">
                    @lang('petrogeneral::lang.add_new_dip_tab_help')
                </p>

                {{--
                    Opens the existing .dip_modal that index.blade.php already
                    renders for the other dip screens, so no new modal markup is
                    introduced.

                    data-href + the .load() handler is the pattern this page
                    already uses for .edit_dip - deliberately matched rather than
                    using .btn-modal, which is not wired up on this page.
                --}}
                <button type="button"
                        class="btn btn-primary open_add_new_dip_modal"
                        data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@addNewDip') }}">
                    <i class="fa fa-plus"></i> @lang('petrogeneral::lang.add_new_dip')
                </button>

            @endcomponent

        </div>
    </div>
</section>

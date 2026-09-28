@extends('layouts.app')
@section('title', 'Users')

@section('content')
{{--
    MA-002 (LA-1135): users list.
    Mirrors roles/index.blade.php - same layout, same umn- classes, same
    stylesheet - so the two halves of this module look like one thing.
--}}
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=1">
<section class="content-header umn-page-heading">
    <div>
        <span class="umn-eyebrow">USER MANAGEMENT</span>
        <h1>Users</h1>
        <p>Users belonging to the current business, and the role each one holds.</p>
    </div>
    @can('users.create')
        <a class="btn btn-primary umn-primary-btn" href="{{ route('user-management-new.users.create') }}">
            <i class="fa fa-plus"></i> Add User
        </a>
    @endcan
</section>

<section class="content">
    {{--
        MA-002 (IS-1906): show the stored username in a dialog that has to be
        dismissed.

        This system appends the business number to every username it creates,
        so what the admin TYPED is not what the new user logs in with. The
        green banner below states it, but a banner at the top of a long page is
        easy to walk past - and walking past it is what produced this ticket.

        Plain Bootstrap modal, opened on load and closed by the admin. No new
        library, and it degrades to the banner if JavaScript is off.
    --}}
    @if (session('created_user'))
        @php($createdUser = session('created_user'))
        <div class="modal fade" id="umn-user-created" tabindex="-1" role="dialog"
             data-backdrop="static" aria-labelledby="umn-user-created-title">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="umn-user-created-title">
                            <i class="fa fa-check-circle text-success"></i>
                            {{ !empty($createdUser['updated']) ? 'Username changed' : 'User created' }}
                        </h4>
                    </div>
                    <div class="modal-body text-center">
                        <p style="margin-bottom:6px;">
                            <strong>{{ $createdUser['name'] ?: 'The user' }}</strong>
                            {{ !empty($createdUser['updated']) ? 'now signs in with a different username.' : 'has been added.' }}
                        </p>
                        <p style="margin-bottom:4px;">Log in with this username:</p>
                        <p>
                            <span style="display:inline-block; padding:10px 18px; border:2px dashed #0f9d58;
                                         border-radius:6px; font-size:22px; font-weight:800; color:#0b8043;
                                         letter-spacing:0.5px;">
                                {{ $createdUser['username'] }}
                            </span>
                        </p>
                        @if (!empty($createdUser['suffixed']))
                            <p class="text-muted" style="margin-top:10px;">
                                You typed <strong>{{ $createdUser['typed'] }}</strong>. This system adds the
                                business number automatically, so the login name is
                                <strong>{{ $createdUser['username'] }}</strong> - not
                                <strong>{{ $createdUser['typed'] }}</strong>.
                            </p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" id="umn-copy-username">
                            <i class="fa fa-copy"></i> Copy username
                        </button>
                        <button type="button" class="btn btn-primary umn-primary-btn" data-dismiss="modal">
                            Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="alert {{ data_get(session('status'), 'success') ? 'alert-success' : 'alert-danger' }}">
            {{ data_get(session('status'), 'msg') }}
        </div>
    @endif

    <div class="umn-list-card">
        <div class="umn-list-card__head">
            <div>
                <h3>Business Users</h3>
                <p>Users listed here belong only to the current business.</p>
            </div>
            <div class="umn-search">
                <i class="fa fa-search"></i>
                {{-- LA-1152: autocomplete/autofill off. The browser was restoring a previously
                     typed term (e.g. "ishadi2") into this box on load. Because the filter
                     below only runs on the `input` event, the box showed a term that had
                     never been applied and the list disagreed with it. --}}
                {{--
                    IS2155: the box arrived pre-filled with a saved username.

                    autocomplete="off" was already here and Chrome ignored it -
                    it does that whenever a field looks like a login box, and this
                    one sits on a page full of usernames. The browser offered
                    "syzygy", the superadmin login, so the list opened filtered by
                    a term nobody typed.

                    Two things make Chrome respect it:

                      - autocomplete="new-password". Chrome honours this where it
                        overrides "off", and it suppresses the saved-username
                        list. It looks odd on a search box; it is the accepted way
                        to stop this.

                      - a name that is not username-like. Left unnamed the browser
                        falls back to the id for its matching, and "user-search"
                        is close enough to trigger it.

                    The field is also cleared on load, below, because Chrome fills
                    some fields AFTER the page settles - the attributes alone do
                    not always win.
                --}}
                <input type="search" id="umn-user-search" name="umn_filter_term"
                       placeholder="Search users" value=""
                       autocomplete="new-password" autocorrect="off"
                       autocapitalize="off" spellcheck="false" data-lpignore="true">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table umn-table" id="umn-user-table">
                <thead>
                    <tr>
                        <th>Name</th><th>Username</th><th>Email</th>
                        <th>Role</th><th>Status</th><th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr data-user-name="{{ strtolower(trim($user->first_name . ' ' . $user->last_name . ' ' . $user->username)) }}">
                            <td>
                                <strong>{{ trim($user->first_name . ' ' . $user->last_name) }}</strong>
                                <small>Business user</small>
                            </td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email ?: '-' }}</td>
                            <td>
                                @if ($user->umn_role)
                                    <span class="umn-badge is-managed">{{ $user->umn_role }}</span>
                                @else
                                    <span class="umn-badge is-legacy">No role</span>
                                @endif
                            </td>
                            <td>
                                <span class="umn-badge {{ $user->status === 'active' ? 'is-managed' : 'is-legacy' }}">
                                    {{ $user->status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a class="umn-icon-btn" title="View"
                                   href="{{ route('user-management-new.users.show', $user->id) }}"><i class="fa fa-eye"></i></a>
                                @can('users.update')
                                    <a class="umn-icon-btn umn-icon-btn-edit" title="Edit"
                                       href="{{ route('user-management-new.users.edit', $user->id) }}"><i class="fa fa-edit"></i></a>
                                    {{-- Deactivate, not delete. A deleted user leaves orphaned
                                         created_by references across transactions and ledgers. --}}
                                    <form class="umn-inline-delete" method="POST"
                                          action="{{ route('user-management-new.users.toggle-status', $user->id) }}">
                                        @csrf @method('PUT')
                                        <button class="umn-icon-btn {{ $user->status === 'active' ? 'is-danger' : '' }}"
                                                title="{{ $user->status === 'active' ? 'Deactivate' : 'Reactivate' }}"
                                                type="submit">
                                            <i class="fa {{ $user->status === 'active' ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
    @if (session('created_user'))
    (function () {
        // Bootstrap 3/4 modal, whichever this install ships.
        if (window.jQuery && $.fn.modal) {
            $('#umn-user-created').modal('show');
        }
        var btn = document.getElementById('umn-copy-username');
        if (btn) {
            btn.addEventListener('click', function () {
                var name = @json(session('created_user')['username']);
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(name).then(function () {
                        btn.innerHTML = '<i class="fa fa-check"></i> Copied';
                    });
                } else {
                    // Older browsers: select it so the admin can copy by hand.
                    window.prompt('Copy the username:', name);
                }
            });
        }
    }());
    @endif

    /*
     * LA-1152: the search box must start empty, and the list must always agree
     * with whatever is in it.
     *
     * Two separate problems were combined here. The browser restores a search
     * input's previous value on load and on back-navigation (bfcache), and the
     * filter only ran on the `input` event - so a restored term was displayed
     * but never applied, and the user saw a filter that was not doing anything.
     *
     * Now: the field is cleared on load and on page-show, and the filter is a
     * named function applied on load as well as on input, so the two can never
     * disagree.
     */
    (function () {
        var searchBox = document.getElementById('umn-user-search');
        if (!searchBox) {
            return;
        }

        function applyUserFilter() {
            var term = (searchBox.value || '').toLowerCase();
            document.querySelectorAll('#umn-user-table tbody tr[data-user-name]').forEach(function (row) {
                row.style.display = row.getAttribute('data-user-name').indexOf(term) === -1 ? 'none' : '';
            });
        }

        function resetUserFilter() {
            searchBox.value = '';
            applyUserFilter();
        }

        /*
         * IS2155: clear anything the browser filled in.
         *
         * Chrome can populate a field after DOMContentLoaded, so clearing once is
         * not always enough - the short timeout catches the late pass. Only runs
         * when the user has not typed, so a real search is never wiped.
         *
         * applyUserFilter() is called afterwards so the list matches the now-empty
         * box; previously the box showed a term the list had never been filtered
         * by.
         */
        (function () {
            var userHasTyped = false;

            searchBox.addEventListener('input', function () { userHasTyped = true; });

            var clearIfUntouched = function () {
                if (!userHasTyped && searchBox.value !== '') {
                    searchBox.value = '';
                    applyUserFilter();
                }
            };

            clearIfUntouched();
            window.setTimeout(clearIfUntouched, 250);
        }());

        searchBox.addEventListener('input', applyUserFilter);

        // Clear anything the browser restored, then show the full list.
        resetUserFilter();

        // Returning via the back button restores the old value from bfcache.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                resetUserFilter();
            }
        });
    }());
</script>
@endsection

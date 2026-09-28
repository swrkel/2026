{{-- Help Guide Module --}}

@if (!empty($helpguide) || auth()->user()->can('superadmin'))
    {{-- Spelling corrected from 'helpguild.access'. The permission is renamed
         to match by the accompanying SQL script - the view and the permission
         must change together, or the menu vanishes for everyone. --}}
        @if(auth()->user()->can('superadmin') || auth()->user()->can('helpguide.access') || auth()->user()->can('helpguild.access'))
        <li class="nav-item {{ in_array($request->segment(1), ['helpguide', 'my_account']) ? 'active active-sub' : '' }}">
            <a class="nav-link {{ in_array($request->segment(1), ['helpguide', 'my_account']) ? '' : 'collapsed' }}"
               href="#"
               data-toggle="collapse"
               data-target="#helpguide-menu"
               aria-expanded="{{ in_array($request->segment(1), ['helpguide', 'my_account']) ? 'true' : 'false' }}"
               aria-controls="helpguide-menu">
                <i class="ti-settings"></i>
                <span>Help Guide</span>
            </a>

            <div id="helpguide-menu"
                 class="collapse {{ in_array($request->segment(1), ['helpguide', 'my_account']) ? 'show' : '' }}"
                 aria-labelledby="headingTwo"
                 data-parent="#accordionSidebar">

                <div class="bg-white py-2 collapse-inner rounded">

                    <h6 class="collapse-header">Help Guide:</h6>

                    @cannot('superadmin')

                        <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == '' ? 'active active-sub' : '' }}"
                           href="/helpguide">
                            Home Page
                        </a>

                        <a class="collapse-item {{ $request->segment(1) == 'my_account' && $request->segment(2) == 'helpguide' ? 'active active-sub' : '' }}"
                           href="{{ \Illuminate\Support\Facades\Route::has('my_account') ? route('my_account') . '#/tickets' : url('/helpguide') }}">
                            Tickets
                        </a>

                    @endcannot

                    @can('superadmin')

                        <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == 'dashboard' ? 'active active-sub' : '' }}"
                           href="/helpguide/dashboard">
                            Dashboard
                        </a>

                        <a class="collapse-item {{ $request->segment(1) == 'helpguide' && $request->segment(2) == '' ? 'active active-sub' : '' }}"
                           href="{{ \Illuminate\Support\Facades\Route::has('frontend') ? route('frontend') : url('/helpguide') }}"
                           target="_blank">
                            Home Page
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard/tickets">
                            Tickets
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/articles">
                            Articles
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/categories">
                            Categories
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/saved_replies">
                            Saved Replies
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/customers">
                            Customers
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/employees">
                            Employees
                        </a>

                        <a class="collapse-item"
                           href="{{ route('languages.index') }}">
                            Translations
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard#/modules">
                            Modules
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard/settings">
                            Settings
                        </a>

                        <a class="collapse-item"
                           href="/helpguide/dashboard/customizer">
                            Customizer
                        </a>

                    @endcan

                </div>
            </div>
        </li>
    @endif
@endif
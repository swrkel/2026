@csrf
@php
    $selectedRegionId = (string) old('region_id', $member->region_id ?? '');
    $selectedRegion = $regions->firstWhere('id', (int) $selectedRegionId);
    $selectedRegionText = $selectedRegion ? trim($selectedRegion->region_no . ' - ' . $selectedRegion->region) : '';
    $selectedBusinessName = old('business_name', $member->business_name ?? ($businessNames->first() ?? ''));
@endphp
<div class="mn-member-form-grid">
    <label>Member Code
        <input name="member_code" value="{{ old('member_code', $member->member_code ?? '') }}" placeholder="Auto if blank">
    </label>

    <label>Title
        <select name="title">
            <option value="">Select Title</option>
            @foreach($titles as $titleOption)
                <option value="{{ $titleOption }}" {{ old('title', $member->title ?? '') === $titleOption ? 'selected' : '' }}>{{ $titleOption }}</option>
            @endforeach
        </select>
    </label>

    <label>First Name
        <input required name="first_name" value="{{ old('first_name', $member->first_name ?? '') }}">
    </label>

    <label>Last Name
        <input name="last_name" value="{{ old('last_name', $member->last_name ?? '') }}">
    </label>

    <label>Full Name
        <input name="full_name" value="{{ old('full_name', $member->full_name ?? '') }}">
    </label>

    <label>Full Name in Second Language
        <input name="full_name_second_language" value="{{ old('full_name_second_language', $member->full_name_second_language ?? '') }}">
    </label>

    <div class="mn-member-field">
        <label for="mn-region-trigger">Region</label>
        <div class="mn-region-select" data-mn-region-select>
            <input type="hidden" name="region_id" value="{{ $selectedRegionId }}" data-mn-region-value>
            <button type="button" class="mn-region-trigger" id="mn-region-trigger" data-mn-region-trigger aria-haspopup="listbox" aria-expanded="false">
                <span data-mn-region-label>{{ $selectedRegionText !== '' ? $selectedRegionText : 'Select Region' }}</span>
                <i class="fa fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="mn-region-menu" data-mn-region-menu hidden>
                <div class="mn-region-search-wrap">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input type="search" data-mn-region-search placeholder="Type to filter Region" autocomplete="off">
                </div>
                <div class="mn-region-options" role="listbox" data-mn-region-options tabindex="-1">
                    <button type="button" class="mn-region-option" data-value="" data-label="Select Region" role="option">Select Region</button>
                    @foreach($regions as $region)
                        @php
                            $regionLabel = trim($region->region_no . ' - ' . $region->region);
                        @endphp
                        <button type="button" class="mn-region-option {{ (string) $region->id === $selectedRegionId ? 'selected' : '' }}" data-value="{{ $region->id }}" data-label="{{ $regionLabel }}" role="option" aria-selected="{{ (string) $region->id === $selectedRegionId ? 'true' : 'false' }}">{{ $regionLabel }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <label>Mobile No <span class="mn-field-help">(Include country code, without starting 0)</span>
        <input name="mobile" inputmode="tel" value="{{ old('mobile', $member->mobile ?? '') }}" placeholder="Example: 94771234567">
    </label>

    <label>Other Mobile Nos <span class="mn-field-help">(Separate multiple numbers with commas)</span>
        <input name="other_mobile_nos" inputmode="tel" value="{{ old('other_mobile_nos', $member->other_mobile_nos ?? '') }}" placeholder="94771234567, 94781234567">
    </label>

    <label>Business Name
        <select name="business_name">
            <option value="">Select Business Name</option>
            @foreach($businessNames as $businessName)
                <option value="{{ $businessName }}" {{ (string) $selectedBusinessName === (string) $businessName ? 'selected' : '' }}>{{ $businessName }}</option>
            @endforeach
        </select>
    </label>

    <label>Membership Type
        <select name="membership_type_id">
            <option value="">Select Membership Type</option>
            @foreach($membershipTypes as $membershipType)
                <option value="{{ $membershipType->id }}" {{ (string) old('membership_type_id', $member->membership_type_id ?? '') === (string) $membershipType->id ? 'selected' : '' }}>{{ $membershipType->setting_value }}</option>
            @endforeach
        </select>
    </label>

    <label>No of Shares
        <input type="number" step="0.0001" min="0" name="no_of_shares" value="{{ old('no_of_shares', $member->no_of_shares ?? '') }}">
    </label>

    <label>Total Share Value ({{ $currencyLabel }})
        <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" min="0" name="total_share_value" value="{{ old('total_share_value', $member->total_share_value ?? '') }}">
    </label>

    <label>Gender
        <select name="gender">
            <option value="">Select Gender</option>
            @foreach($genders as $genderOption)
                <option value="{{ $genderOption }}" {{ old('gender', $member->gender ?? '') === $genderOption ? 'selected' : '' }}>{{ $genderOption }}</option>
            @endforeach
        </select>
    </label>

    <label>Email
        <input type="email" name="email" value="{{ old('email', $member->email ?? '') }}">
    </label>

    <label>NIC
        <input name="nic" value="{{ old('nic', $member->nic ?? '') }}">
    </label>

    <label>Date of Birth
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($member) && $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '') }}">
    </label>

    <label>Joined On
        <input type="date" name="joined_on" value="{{ old('joined_on', isset($member) && $member->joined_on ? $member->joined_on->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </label>

    <label>Address
        <textarea name="address">{{ old('address', $member->address ?? '') }}</textarea>
    </label>

    <label class="mn-active-field">
        <span>Status</span>
        <span class="mn-active-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ (string) old('is_active', isset($member) ? (int) $member->is_active : 1) === '1' ? 'checked' : '' }}>
            Active
        </span>
    </label>

    <label class="mn-member-note">Note
        <textarea name="note">{{ old('note', $member->note ?? '') }}</textarea>
    </label>
</div>
<button class="mn-btn mn-btn-success" type="submit"><i class="fa fa-save"></i> Save</button>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-mn-region-select]');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    var hidden = root.querySelector('[data-mn-region-value]');
    var trigger = root.querySelector('[data-mn-region-trigger]');
    var label = root.querySelector('[data-mn-region-label]');
    var menu = root.querySelector('[data-mn-region-menu]');
    var search = root.querySelector('[data-mn-region-search]');
    var optionsWrap = root.querySelector('[data-mn-region-options]');
    var options = Array.prototype.slice.call(root.querySelectorAll('.mn-region-option'));
    var activeIndex = -1;

    function visibleOptions() {
        return options.filter(function (option) { return option.style.display !== 'none'; });
    }

    function openMenu() {
        menu.hidden = false;
        root.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
        search.value = '';
        filterOptions('');
        window.setTimeout(function () { search.focus(); }, 0);
    }

    function closeMenu() {
        menu.hidden = true;
        root.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        options.forEach(function (option) { option.classList.remove('keyboard-active'); });
    }

    function filterOptions(term) {
        var needle = (term || '').trim().toLowerCase();
        options.forEach(function (option) {
            var matches = !needle || option.textContent.toLowerCase().indexOf(needle) !== -1;
            option.style.display = matches ? '' : 'none';
        });
        activeIndex = -1;
    }

    function selectOption(option) {
        hidden.value = option.dataset.value || '';
        label.textContent = option.dataset.label || option.textContent.trim() || 'Select Region';
        options.forEach(function (item) {
            var selected = item === option;
            item.classList.toggle('selected', selected);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
        closeMenu();
        trigger.focus();
    }

    function moveActive(direction) {
        var visible = visibleOptions();
        if (!visible.length) return;
        activeIndex += direction;
        if (activeIndex < 0) activeIndex = visible.length - 1;
        if (activeIndex >= visible.length) activeIndex = 0;
        options.forEach(function (option) { option.classList.remove('keyboard-active'); });
        visible[activeIndex].classList.add('keyboard-active');
        visible[activeIndex].scrollIntoView({block: 'nearest'});
    }

    trigger.addEventListener('click', function () {
        if (menu.hidden) openMenu(); else closeMenu();
    });

    search.addEventListener('input', function () { filterOptions(search.value); });
    search.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') { event.preventDefault(); moveActive(1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); moveActive(-1); }
        if (event.key === 'Enter') {
            var visible = visibleOptions();
            if (activeIndex >= 0 && visible[activeIndex]) {
                event.preventDefault();
                selectOption(visible[activeIndex]);
            }
        }
        if (event.key === 'Escape') { event.preventDefault(); closeMenu(); trigger.focus(); }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () { selectOption(option); });
    });

    document.addEventListener('click', function (event) {
        if (!root.contains(event.target)) closeMenu();
    });
})();
</script>
@endpush

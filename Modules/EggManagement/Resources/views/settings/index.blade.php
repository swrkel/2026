@extends('egg::layouts.app', ['title' => 'Egg Management Settings'])

@section('egg_subtitle')
Manage Egg Management masters and integrations from one place.
@endsection

@section('egg_content')
<div class="egg-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px;">
        <div>
            <h3 style="margin-bottom:4px;">Settings</h3>
            <div style="color:#667085;font-size:13px;">Choose a settings area below.</div>
        </div>
        <span style="width:42px;height:42px;border-radius:12px;background:#eef4ff;color:#2f6fed;display:inline-flex;align-items:center;justify-content:center;font-size:18px;">
            <i class="fa fa-cog"></i>
        </span>
    </div>

    <div class="egg-grid-2">
        @forelse($items as $item)
            @if(\Illuminate\Support\Facades\Route::has($item['route']))
                <a href="{{ route($item['route']) }}" style="display:flex;align-items:flex-start;gap:14px;padding:16px;border:1px solid #e4e7ec;border-radius:14px;background:#fff;text-decoration:none;color:#1d2939;min-height:92px;">
                    <span style="width:44px;height:44px;border-radius:12px;background:#f2f4f7;color:#344054;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto;">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <span>
                        <strong style="display:block;font-size:14px;margin-bottom:5px;">{{ $item['label'] }}</strong>
                        <span style="display:block;color:#667085;font-size:12px;line-height:1.5;">{{ $item['description'] }}</span>
                    </span>
                </a>
            @endif
        @empty
            <div class="egg-alert egg-alert-danger">No settings pages are available for your current permissions.</div>
        @endforelse
    </div>
</div>
@endsection

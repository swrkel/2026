<div class="erp-login-banner-panel">
    <div class="erp-login-banner-box">

        <div class="carousel-container"
            style="position:relative; width:100%; height:100%; min-height:100%; overflow:hidden;">

            @foreach ($banners as $index => $banner)
                <img src="{{ $banner->image_url }}"
                    class="carousel-slide"
                    style="
                        position:absolute;
                        inset:0;
                        width:100%;
                        height:100%;
                        object-fit:cover;
                        transition:opacity .7s ease;
                        opacity:{{ $index === 0 ? '1' : '0' }};
                        z-index:{{ $index === 0 ? '10' : '0' }};
                        cursor:{{ !empty($banner->link_url) ? 'pointer' : 'default' }};
                    "
                    data-duration="{{ $banner->display_duration ?? 5 }}"
                    data-link="{{ $banner->link_url ?? '' }}"
                    alt="{{ $banner->title ?? 'Banner' }}">
            @endforeach

        </div>

    </div>
</div>

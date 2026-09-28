<div class="row text-center"
     style="margin-top: 10px; margin-bottom: 25px;">

    @if(file_exists(public_path('uploads/logo.png')))

        <div class="col-xs-12">

            <div style="
                background: linear-gradient(
                    135deg,
                    rgba(255,255,255,0.08),
                    rgba(255,255,255,0.03)
                );
                border-radius: 18px;
                padding: 20px 10px;
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255,255,255,0.06);
                box-shadow: 0 6px 18px rgba(0,0,0,0.18);
            ">

                <img src="/uploads/logo.png"
                     class="img-rounded"
                     alt="Enterprise Logo"
                     style="
                        max-width: 150px;
                        max-height: 70px;
                        object-fit: contain;
                        margin-bottom: 12px;
                        filter: drop-shadow(
                            0 4px 10px rgba(0,0,0,0.18)
                        );
                     ">

                <div style="
                    color: rgba(255,255,255,0.85);
                    font-size: 11px;
                    font-weight: 600;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                ">

                    Enterprise Governance Platform

                </div>

            </div>

        </div>

    @else

        <div class="col-xs-12">

            <div style="
                background: linear-gradient(
                    135deg,
                    #2563eb,
                    #06b6d4
                );
                border-radius: 18px;
                padding: 24px 18px;
                box-shadow: 0 10px 25px rgba(37,99,235,0.25);
            ">

                <h1 style="
                    margin: 0;
                    color: #ffffff;
                    font-size: 28px;
                    font-weight: 800;
                    letter-spacing: 1px;
                ">

                    {{ config('app.name', 'Enterprise ERP') }}

                </h1>

                <div style="
                    margin-top: 10px;
                    color: rgba(255,255,255,0.82);
                    font-size: 12px;
                    font-weight: 600;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                ">

                    Institutional Fintech Governance Suite

                </div>

            </div>

        </div>

    @endif

</div>
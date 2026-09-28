<?php

namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Redirect;
use Modules\MPCS\Entities\FormF25DeliveryLocation;
use Modules\MPCS\Entities\FormF25Detail;
use Modules\MPCS\Entities\FormF25Header;
use Modules\MPCS\Entities\FormF25Setting;
use App\Services\Documents\GlobalPdfService;
use Yajra\DataTables\Facades\DataTables;

class F25FormController extends Controller
{
    private function authorizeF25Access(): void
    {
        $user = Auth::user();

        if (
            empty($user)
            || (! Gate::forUser($user)->check('f25_form') && ! Gate::forUser($user)->check('f22_stock_taking_form'))
        ) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        if (! auth()->check()) {
            return Redirect::route('login');
        }
        $this->authorizeF25Access();

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);

        $business_locations = BusinessLocation::forDropdown($business_id);
        if (empty($business_locations) || (is_object($business_locations) && $business_locations->isEmpty()) || (is_array($business_locations) && count($business_locations) === 0)) {
            $business_locations = BusinessLocation::where('business_id', $business_id)
                ->Active()
                ->pluck('name', 'id');
        }
        $default_location_id = count($business_locations) === 1 ? collect($business_locations)->keys()->first() : null;
        // dd($business_locations);

        $suppliers = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)
            ->pluck('name', 'id');

        // Do not crash the whole F25 page when a tenant has not yet run the F25 SQL.
        // The supplied SQL/migration creates the table; until then the page opens safely.
        $settings = collect();
        if (Schema::hasTable('mpcs_f25_settings')) {
            $settings = FormF25Setting::where('business_id', $business_id)
                ->orderByDesc('opening_date')
                ->orderByDesc('id')
                ->get();
        }

        $delivery_locations = FormF25DeliveryLocation::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('location_code')
            ->get();

        $all_delivery_locations = FormF25DeliveryLocation::where('business_id', $business_id)
            ->orderBy('location_code')
            ->get();

        $today = now()->format('Y-m-d');
        $current_time = now()->format('H:i');
        $form_no = $this->getGeneratedFormNo($business_id, $today);
        $currency_precision = (int) ($business->currency_precision ?? 2);
        $quantity_precision = (int) ($business->quantity_precision ?? 2);

        return view('mpcs::forms.F25.index', compact(
            'business',
            'business_locations',
            'default_location_id',
            'suppliers',
            'products',
            'settings',
            'delivery_locations',
            'all_delivery_locations',
            'today',
            'current_time',
            'form_no',
            'currency_precision',
            'quantity_precision'
        ));
    }

    public function getFormNo(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $date = $request->input('date', now()->format('Y-m-d'));
        $location_id = $request->input('location_id');

        return response()->json(['form_no' => $this->getGeneratedFormNo($business_id, $date, $location_id)]);
    }

    public function storeSetting(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $validated = $request->validate([
            'opening_date' => ['required', 'date'],
            'starting_number' => ['required', 'numeric', 'min:1'],
        ]);

        if (!Schema::hasTable('mpcs_f25_settings')) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => 'F25 settings table is missing. Please run the supplied MPCS F25 SQL once for this tenant database.',
            ]);
        }

        FormF25Setting::create([
            'business_id' => $business_id,
            'opening_date' => $validated['opening_date'],
            'starting_number' => (int) $validated['starting_number'],
            'created_by' => Auth::id(),
        ]);

        return Redirect::back()->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.success'),
        ]);
    }

    public function storeDeliveryLocation(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $validated = $request->validate([
            'names' => ['required', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $names = collect(preg_split('/[\n\r,]+/', $validated['names']))
            ->map(fn($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }

        DB::transaction(function () use ($business_id, $names, $validated) {
            $maxCode = FormF25DeliveryLocation::where('business_id', $business_id)
                ->select(DB::raw('MAX(CAST(location_code as UNSIGNED)) as max_code'))
                ->value('max_code');

            $next = (int) $maxCode;

            foreach ($names as $name) {
                $next++;
                $nextCode = str_pad((string) $next, 4, '0', STR_PAD_LEFT);

                FormF25DeliveryLocation::create([
                    'business_id' => $business_id,
                    'location_code' => $nextCode,
                    'location_name' => $name,
                    'status' => (isset($validated['is_active']) && (int) $validated['is_active'] === 1) ? 'active' : 'inactive',
                    'created_by' => Auth::id(),
                ]);
            }
        });

        return Redirect::back()->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.success'),
        ]);
    }

    public function updateDeliveryLocation(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $location = FormF25DeliveryLocation::where('business_id', $business_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (!empty($validated['name'])) {
            $location->location_name = $validated['name'];
        }
        $location->status = (int) $validated['is_active'] === 1 ? 'active' : 'inactive';
        $location->save();

        return Redirect::back()->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.success'),
        ]);
    }

    public function getProductPrice(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $product_id = (int) $request->input('product_id');
        if (! $product_id) {
            return response()->json(['success' => false, 'msg' => 'Invalid product'], 422);
        }

        $variation = Variation::leftJoin('products', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $business_id)
            ->where('variations.product_id', $product_id)
            ->select('variations.id', 'variations.dpp_inc_tax')
            ->first();

        $price = (float) ($variation->dpp_inc_tax ?? 0);

        // Fallback to latest purchase line unit cost if available.
        if ($price <= 0 && ! empty($variation)) {
            $latestPurchase = TransactionSellLinesPurchaseLines::leftJoin('purchase_lines as pl', 'transaction_sell_lines_purchase_lines.purchase_line_id', '=', 'pl.id')
                ->where('pl.variation_id', $variation->id)
                ->orderByDesc('transaction_sell_lines_purchase_lines.id')
                ->select('pl.purchase_price_inc_tax')
                ->first();
            $price = (float) ($latestPurchase->purchase_price_inc_tax ?? 0);
        }

        return response()->json([
            'success' => true,
            'unit_price' => $price,
        ]);
    }

    public function getProducts(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $query = Product::query()
            ->where('products.business_id', $business_id)
            ->select('products.id', 'products.name')
            ->orderBy('products.name');

        $products = $query->distinct()->get();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $validated = $request->validate([
            'location_id' => ['required', 'integer'],
            'form_no' => ['required', 'string', 'max:100'],
            'form_date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'integer'],
            'bill_no' => ['nullable', 'string', 'max:100'],
            'delivery_location_id' => ['nullable', 'integer'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.line_bill_no' => ['nullable', 'string', 'max:100'],
            'rows.*.product_id' => ['nullable', 'integer'],
            'rows.*.product_name' => ['nullable', 'string', 'max:255'],
            'rows.*.pcs' => ['nullable', 'string', 'max:191'],
            'rows.*.qty' => ['nullable', 'numeric'],
            'rows.*.unit_price' => ['nullable', 'numeric'],
            'rows.*.total_amount' => ['nullable', 'numeric'],
            'rows.*.received_qty' => ['nullable', 'numeric'],
            'rows.*.short_qty' => ['nullable', 'numeric'],
            'rows.*.excess_qty' => ['nullable', 'numeric'],
            'rows.*.short_amount' => ['nullable', 'numeric'],
            'rows.*.excess_amount' => ['nullable', 'numeric'],
            'rows.*.short_signature' => ['nullable', 'string', 'max:191'],
            'rows.*.line_date' => ['nullable', 'date'],
            'rows.*.line_time' => ['nullable', 'string'],
            'rows.*.field_21' => ['nullable', 'string'],
            'rows.*.field_22' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $business_id) {
            $header = FormF25Header::create([
                'business_id' => $business_id,
                'location_id' => $validated['location_id'],
                'form_no' => $validated['form_no'],
                'transaction_date' => $validated['form_date'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'bill_no' => $validated['bill_no'] ?? null,
                'delivery_location_id' => $validated['delivery_location_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['rows'] as $i => $row) {
                FormF25Detail::create([
                    'f25_form_id' => $header->id,
                    'bill_no' => $row['line_bill_no'] ?? null,
                    'product_id' => $row['product_id'] ?? null,
                    'description' => $row['product_name'] ?? null,
                    'pcs' => $row['pcs'] ?? null,
                    'qty' => (float) ($row['qty'] ?? 0),
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                    'total_amount' => (float) ($row['total_amount'] ?? 0),
                    'received_qty' => (float) ($row['received_qty'] ?? 0),
                    'short_qty' => (float) ($row['short_qty'] ?? 0),
                    'excess_qty' => (float) ($row['excess_qty'] ?? 0),
                    'short_amount' => (float) ($row['short_amount'] ?? 0),
                    'excess_amount' => (float) ($row['excess_amount'] ?? 0),
                    'short_signature' => $row['short_signature'] ?? null,
                    'line_date' => $row['line_date'] ?? null,
                    'line_time' => $row['line_time'] ?? null,
                    'field_21' => $row['field_21'] ?? null,
                    'field_22' => $row['field_22'] ?? null,
                ]);
            }
        });

        return Redirect::back()->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.success'),
        ]);
    }

    public function list(Request $request)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');

        $query = FormF25Header::leftJoin('business_locations as bl', 'mpcs_f25_forms.location_id', '=', 'bl.id')
            ->leftJoin('contacts as c', 'mpcs_f25_forms.supplier_id', '=', 'c.id')
            ->leftJoin('mpcs_f25_delivery_locations as dl', 'mpcs_f25_forms.delivery_location_id', '=', 'dl.id')
            ->where('mpcs_f25_forms.business_id', $business_id)
            ->select([
                'mpcs_f25_forms.id',
                'mpcs_f25_forms.form_no',
                'mpcs_f25_forms.transaction_date as form_date',
                'bl.name as location_name',
                'c.name as supplier_name',
                'dl.location_code as delivery_code',
                'dl.location_name as delivery_name',
            ]);

        return DataTables::of($query)
            /*
             * IS2162: the Date column was blank.
             *
             * @format_date() formats using the business date format from the
             * session, and returns nothing when that value is absent - the same
             * fault that blanked the Received Date on the F25 print under
             * IS2122. The stored transaction_date was never the problem.
             *
             * A fallback format is used so a saved date always renders.
             */
            ->editColumn('form_date', function ($row) {
                if (empty($row->form_date)) {
                    return '';
                }

                $format = session('business.date_format') ?: 'd/m/Y';

                try {
                    return \Carbon\Carbon::parse($row->form_date)->format($format);
                } catch (\Throwable $e) {
                    return (string) $row->form_date;
                }
            })
            /*
             * IS2162: the Delivery Location column was blank.
             *
             * It rendered dl.location_code - the delivery location's CODE, not
             * its name - and those codes are not set, so the column was empty
             * even though a delivery location had been chosen and saved.
             *
             * delivery_name is already selected in the query above and was
             * simply unused. The NAME is shown now, with the padded code kept as
             * a fallback for any location that has a code but no name.
             */
            ->editColumn('delivery_code', function ($row) {
                if (! empty($row->delivery_name)) {
                    return e($row->delivery_name);
                }

                if (empty($row->delivery_code)) {
                    return '';
                }

                $padded = str_pad((string) $row->delivery_code, 4, '0', STR_PAD_LEFT);
                return $row->delivery_name ? $padded . ' - ' . $row->delivery_name : $padded;
            })
            ->addColumn('action', function ($row) {
                $view_url = url('mpcs/F25/' . $row->id . '/view');
                $preview_url = url('mpcs/F25/' . $row->id . '/preview');
                $print_url = url('mpcs/F25/' . $row->id . '/print');
                $pdf_url = url('mpcs/F25/' . $row->id . '/pdf');
                $email_base_url = url('mpcs/F25/' . $row->id . '/email');

                /*
                 * IS2162: the five actions moved inside one Action menu.
                 *
                 * They were loose buttons side by side, which made the Action
                 * column wider than every other column on the list. A dropdown
                 * matches the other list screens and keeps the row readable.
                 *
                 * Every action, its URL and its behaviour is unchanged - only the
                 * markup around them. The email prompt keeps working the same
                 * way.
                 */
                $items  = '<li><a href="' . $view_url . '"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                $items .= '<li><a href="' . $preview_url . '" target="_blank"><i class="fa fa-search"></i> ' . __('lang_v1.preview') . '</a></li>';
                $items .= '<li><a href="' . $print_url . '" target="_blank"><i class="fa fa-print"></i> ' . __('mpcs::lang.print') . '</a></li>';
                $items .= '<li><a href="' . $pdf_url . '" target="_blank"><i class="fa fa-file-pdf-o"></i> PDF</a></li>';
                $items .= '<li><a href="#" onclick="var e=prompt(\'Enter email address\'); if(e){window.location=\'' . $email_base_url . '?email=\'+encodeURIComponent(e);} return false;"><i class="fa fa-envelope"></i> ' . __('lang_v1.email') . '</a></li>';

                $html = '<div class="btn-group">'
                    . '<button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">'
                    . __('messages.actions') . ' <span class="caret"></span></button>'
                    . '<ul class="dropdown-menu dropdown-menu-right" role="menu">' . $items . '</ul>'
                    . '</div>';

                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function show(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $data = $this->getF25DocumentData($business_id, $id);

        return view('mpcs::forms.F25.view', $data);
    }

    public function preview(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $data = $this->getF25DocumentData($business_id, $id);
        $data['is_preview'] = true;
        $data['is_pdf'] = false;

        return view('mpcs::forms.F25.print', $data);
    }

    public function print(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $data = $this->getF25DocumentData($business_id, $id);
        $data['is_preview'] = false;
        $data['is_pdf'] = false;

        return view('mpcs::forms.F25.print', $data);
    }

    public function pdf(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $data = $this->getF25DocumentData($business_id, $id);
        $data['is_preview'] = false;
        $data['is_pdf'] = true;

        $html = view('mpcs::forms.F25.print', $data)->render();
        $file_name = 'F25_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $data['header']->form_no) . '.pdf';

        return app(GlobalPdfService::class)->download(
            $html,
            $file_name,
            ['format' => 'A4-L'],
            [
                'page_title' => 'F25 Form ' . $data['header']->form_no,
                'location_id' => $data['header']->location_id ?? null,
                'date_range' => $data['header']->transaction_date ?? 'All Dates',
            ]
        );
    }

    public function email(Request $request, $id)
    {
        $this->authorizeF25Access();
        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $email = trim((string) $request->input('email', ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => 'Invalid email address.',
            ]);
        }

        $data = $this->getF25DocumentData($business_id, $id);
        $data['is_preview'] = false;
        $data['is_pdf'] = true;

        $html = view('mpcs::forms.F25.print', $data)->render();
        $pdf_content = app(GlobalPdfService::class)->binary(
            $html,
            ['format' => 'A4-L'],
            [
                'page_title' => 'F25 Form ' . $data['header']->form_no,
                'location_id' => $data['header']->location_id ?? null,
                'date_range' => $data['header']->transaction_date ?? 'All Dates',
            ]
        );
        $file_name = 'F25_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $data['header']->form_no) . '.pdf';

        Mail::send('mpcs::forms.F25.email', ['header' => $data['header']], function ($message) use ($email, $pdf_content, $file_name, $data) {
            $message->to($email)
                ->subject('F25 Form - ' . $data['header']->form_no)
                ->attachData($pdf_content, $file_name, ['mime' => 'application/pdf']);
        });

        return Redirect::back()->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.success'),
        ]);
    }

    private function getF25DocumentData($business_id, $id): array
    {
        $business = Business::findOrFail($business_id);

        $header = FormF25Header::leftJoin('business_locations as bl', 'mpcs_f25_forms.location_id', '=', 'bl.id')
            ->leftJoin('contacts as c', 'mpcs_f25_forms.supplier_id', '=', 'c.id')
            ->leftJoin('mpcs_f25_delivery_locations as dl', 'mpcs_f25_forms.delivery_location_id', '=', 'dl.id')
            ->where('mpcs_f25_forms.business_id', $business_id)
            ->where('mpcs_f25_forms.id', $id)
            ->select([
                'mpcs_f25_forms.*',
                'bl.name as location_name',
                'c.name as supplier_name',
                'dl.location_code as delivery_code',
                'dl.location_name as delivery_location_name',
            ])
            ->firstOrFail();

        $details = FormF25Detail::where('f25_form_id', $header->id)
            ->get();

        $currency_precision = (int) ($business->currency_precision ?? 2);
        $quantity_precision = 2;

        return compact('header', 'details', 'currency_precision', 'quantity_precision');
    }

    private function getGeneratedFormNo($business_id, $date, $location_id = null)
    {
        // 1. Fetch the latest setting where opening_date <= selected date
        if (!Schema::hasTable('mpcs_f25_settings')) {
            return 0;
        }

        $setting = FormF25Setting::where('business_id', $business_id)
            ->whereDate('opening_date', '<=', $date)
            ->orderByDesc('opening_date')
            ->orderByDesc('id')
            ->first();

        $formNoInt = 1;

        if ($setting) {
            if ($date === $setting->opening_date) {
                // Check if any form already exists for this exact date
                $formForDate = FormF25Header::where('business_id', $business_id)
                    ->where('transaction_date', $date)
                    ->orderByDesc('id')
                    ->first();

                $formNoInt = $formForDate ? ((int) $formForDate->form_no + 1) : $setting->starting_number;
            } else {
                // The selected date is AFTER the opening date.
                // We should increment from the latest form created since the opening date.
                $lastForm = FormF25Header::where('business_id', $business_id)
                    ->whereDate('transaction_date', '>=', $setting->opening_date)
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id')
                    ->first();

                $formNoInt = $lastForm ? ((int) $lastForm->form_no + 1) : ($setting->starting_number + 1);
            }
        } else {
            // 2. If no setting exists at all for or before the given date, check the absolute last form created
            $lastForm = FormF25Header::where('business_id', $business_id)
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->first();

            $formNoInt = $lastForm ? ((int) $lastForm->form_no + 1) : 1;
        }

        // DRY: Format string padding done only once at the end
        return str_pad((string) $formNoInt, 4, '0', STR_PAD_LEFT);
    }

    private function permittedLocations()
    {
        $user = Auth::user();
        if (is_object($user) && method_exists($user, 'permitted_locations')) {
            return $user->permitted_locations();
        }

        return 'all';
    }
}

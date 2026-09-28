<?php

namespace Modules\Development\Http\Controllers;



use Illuminate\Http\Request;

use Illuminate\Routing\Controller;

use Log;

use Modules\Development\Entities\DevelopmentModule;

use Yajra\DataTables\Facades\DataTables;



class ListDevelopmentController extends Controller

{

    public function index()

    {

        if (! DevelopmentModule::hasDevelopmentModuleAccess()) {

            abort(403, 'Unauthorized Access');

        }

        // Get data for filters

        $documentNumbers = \Modules\Development\Models\AddDevelopment::pluck('doc_no')->unique();


        $usernames       = \App\User::select('id', 'username')->get();

        $modules         = \Modules\Development\Entities\DevelopmentModule::select('id', 'name')->get();

        $taskHeadings    = \Modules\Development\Models\AddDevelopment::whereNotNull('task_heading')

            ->pluck('task_heading')

            ->unique();

        $relatedDocNos = \Modules\Development\Models\RelatedDocNo::get();



        Log::info('Related Document Numbers:', $relatedDocNos->toArray());



        if (request()->ajax()) {

            // Return filter data if it's a filter request

            if (request()->has('get_filters')) {

                return response()->json([

                    'documentNumbers' => $documentNumbers,

                    'usernames'       => $usernames,

                    'modules'         => $modules,

                    'taskHeadings'    => $taskHeadings,

                    'relatedDocNos'   => $relatedDocNos,

                ]);

            }



            // Handle DataTables request

            $query = \Modules\Development\Models\AddDevelopment::query()->with([
                    'user'   => function ($q) {
                        $q->select('id', 'username', 'email');
                    },
                    'module' => function ($q) {
                        $q->select('id', 'name');
                    },
                ])
                ->where('add_developments.status', '!=', 'Completed')
                ->select([
                    'add_developments.id',
                    'add_developments.doc_no',
                    'add_developments.datetime',
                    'add_developments.task_heading',
                    'add_developments.type',
                    'add_developments.priority',
                    'add_developments.status',
                    'add_developments.user_id',
                    'add_developments.development_module_id',
                    'add_developments.related_doc_no',
                    'add_developments.details',
                    'add_developments.group_comments',
                ]);




            // Apply filters

            if (request()->has('start_date') && ! empty(request('start_date'))) {

                $startDate = request('start_date') . ' 00:00:00';

                $query->where('datetime', '>=', $startDate);

            }



            if (request()->has('end_date') && ! empty(request('end_date'))) {

                $endDate = request('end_date') . ' 23:59:59';

                $query->where('datetime', '<=', $endDate);

            }



            if (request()->has('doc_no') && ! empty(request('doc_no'))) {

                $query->where('doc_no', request('doc_no'));

            }



            if (request()->has('user_id') && ! empty(request('user_id'))) {

                $query->where('user_id', request('user_id'));

            }



            if (request()->has('module_id') && ! empty(request('module_id'))) {

                $query->where('development_module_id', request('module_id'));

            }



            if (request()->has('type') && ! empty(request('type'))) {

                $query->where('type', request('type'));

            }



            if (request()->has('task_heading') && ! empty(request('task_heading'))) {

                $query->where('task_heading', 'like', '%' . request('task_heading') . '%');

            }



            if (request()->has('related_doc_no') && ! empty(request('related_doc_no'))) {

                $query->where('related_doc_no', request('related_doc_no'));

            }



            if (request()->has('status') && ! empty(request('status'))) {

                $query->where('status', request('status'));

            }



            return Datatables::of($query)

                ->addColumn('module_name', function ($row) {

                    return $row->module->name ?? null;

                })

                ->addColumn('username', function ($row) {

                    return $row->user->username ?? null;

                })

                ->addColumn('task_heading', function ($row) {

                    return $row->task_heading ?? '-';

                })

                ->orderColumn('module_name', function ($query, $order) {

                    $query->join('development_modules', 'add_developments.development_module_id', '=', 'development_modules.id')

                        ->orderBy('development_modules.name', $order)

                        ->select('add_developments.*');

                })

                ->orderColumn('username', function ($query, $order) {

                    $query->join('users', 'add_developments.user_id', '=', 'users.id')

                        ->orderBy('users.username', $order)

                        ->select('add_developments.*');

                })

                ->orderColumn('task_heading', function ($query, $order) {

                    $query->orderBy('add_developments.task_heading', $order);

                })

              ->addColumn('status', function ($row) {
                    // If user is Super Admin, show a clickable dropdown button
                    if (auth()->user()->can('superadmin')) {
                        $statuses = ['Pending', 'Not Completed', 'Completed'];
                        $colors = [
                            'Pending' => 'primary',
                            'Not Completed' => 'warning',
                            'Completed' => 'success'
                        ];

                        $html = '<div class="btn-group">';
                        $html .= '<button type="button" class="btn btn-sm btn-' . $colors[$row->status] . ' dropdown-toggle" 
                                    style="padding: .25rem .5rem; font-size: .85rem;" 
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    ' . $row->status . '
                                </button>';
                        $html .= '<div class="dropdown-menu">';

                        foreach ($statuses as $status) {
                            if ($status !== $row->status) {
                                $html .= '<a class="dropdown-item change-status" href="#" 
                                            data-id="' . $row->id . '" 
                                            data-status="' . $status . '">
                                            <i class="fa fa-circle mr-2" style="color:' . 
                                                ($status === 'Completed' ? 'green' : ($status === 'Not Completed' ? 'orange' : 'blue')) . 
                                                '"></i> ' . $status . '
                                        </a>';
                            }
                        }

                        $html .= '</div></div>';

                        return $html;


                    } else {
                        // Normal label for other users
                        return '<span class="label label-' . 
                                    ($row->status === 'Completed' ? 'success' : ($row->status === 'Not Completed' ? 'warning' : 'primary')) . 
                                    '">' . $row->status . '</span>';
                    }
                })

            ->addColumn('action', function ($row) {
                    $html = '<button type="button" class="btn btn-xs btn-primary edit-task-modal" 
                                data-id="' . $row->id . '" 
                                data-doc-no="' . e($row->doc_no ?? '') . '"
                                data-datetime="' . e($row->datetime ?? '') . '"
                                data-task-heading="' . e($row->task_heading ?? '') . '"
                                data-type="' . e($row->type ?? '') . '"
                                data-priority="' . e($row->priority ?? '') . '"
                                data-status="' . e($row->status ?? '') . '"
                                data-user="' . e($row->user->username ?? '') . '"
                                data-user-email="' . e($row->user->email ?? '') . '"
                                data-module="' . e($row->module->name ?? '') . '"
                                data-related-doc-no-id="' . e($row->related_doc_no ?? '') . '"
                                data-related-doc-no-text="' . e(optional($row->relatedDoc)->doc_no ?? '') . '"
                                data-details="' . e($row->details ?? '') . '"
                                title="Edit Task">
                                <i class="fa fa-edit"></i> Edit
                            </button>';
                    return $html;
                })




              ->addColumn('related_doc_no', function ($row) {
                    if ($row->related_doc_no) {
                        $related = \Modules\Development\Models\RelatedDocNo::where('doc_no', $row->related_doc_no)->first();
                        return $related ? $related->doc_no : null;
                    }
                    return null;
                })


              ->addColumn('commented_by', function ($row) {
    $comments = $row->group_comments; // Already an array

    if (!$comments || count($comments) === 0) {
        return '<span class="badge badge-secondary">No Comments</span>';
    }

    // Extract user names
    $userNames = array_map(function ($comment) {
        return $comment['user_name'] ?? '';
    }, $comments);

    // Count of commenters
    $count = count($userNames);

    // Button HTML with tooltip showing all names
    $tooltip = implode('<br>', $userNames);

    return '<button type="button" class="btn btn-sm btn-outline-primary" 
                data-toggle="tooltip" 
                data-html="true" 
                title="' . $tooltip . '">
                ' . $count . ' Commenter(s)
            </button>';
})

                



                ->rawColumns(['action','commented_by','status'])

                ->make(true);

        }



        return view('development::add-development.index', [

            'documentNumbers' => $documentNumbers,

            'usernames'       => $usernames,

            'modules'         => $modules,

            'taskHeadings'    => $taskHeadings,

            'relatedDocNos'   => $relatedDocNos,

        ]);

    }



    public function completed()

    {

        if (! DevelopmentModule::hasDevelopmentModuleAccess()) {

            abort(403, 'Unauthorized Access');

        }



        // Same filter data as index

        $documentNumbers = \Modules\Development\Models\AddDevelopment::pluck('doc_no')->unique();

        $usernames       = \App\User::select('id', 'username')->get();

        $modules         = \Modules\Development\Entities\DevelopmentModule::select('id', 'name')->get();

        $taskHeadings    = \Modules\Development\Models\AddDevelopment::whereNotNull('task_heading')

            ->pluck('task_heading')

            ->unique();

        $relatedDocNos = \Modules\Development\Models\RelatedDocNo::get();



        if (request()->ajax()) {

            $query = \Modules\Development\Models\AddDevelopment::query()

                ->where('status', 'Completed')

                ->with([

                    'user'   => function ($q) {$q->select('id', 'username', 'email');},

                    'module' => function ($q) {$q->select('id', 'name');},

                ])

                ->select([

                    'add_developments.id',

                    'add_developments.doc_no',

                    'add_developments.datetime',

                    'add_developments.task_heading',

                    'add_developments.type',

                    'add_developments.priority',

                    'add_developments.status',

                    'add_developments.user_id',

                    'add_developments.development_module_id',

                    'add_developments.related_doc_no',

                    'add_developments.details',

                ]);

            if (request()->has('start_date') && ! empty(request('start_date'))) {

                $startDate = request('start_date') . ' 00:00:00';

                $query->where('datetime', '>=', $startDate);

            }



            if (request()->has('end_date') && ! empty(request('end_date'))) {

                $endDate = request('end_date') . ' 23:59:59';

                $query->where('datetime', '<=', $endDate);

            }



            if (request()->has('doc_no') && ! empty(request('doc_no'))) {

                $query->where('doc_no', request('doc_no'));

            }



            if (request()->has('user_id') && ! empty(request('user_id'))) {

                $query->where('user_id', request('user_id'));

            }



            if (request()->has('module_id') && ! empty(request('module_id'))) {

                $query->where('development_module_id', request('module_id'));

            }



            if (request()->has('type') && ! empty(request('type'))) {

                $query->where('type', request('type'));

            }



            if (request()->has('task_heading') && ! empty(request('task_heading'))) {

                $query->where('task_heading', 'like', '%' . request('task_heading') . '%');

            }



            if (request()->has('related_doc_no') && ! empty(request('related_doc_no'))) {

                $query->where('related_doc_no', request('related_doc_no'));

            }



            if (request()->has('status') && ! empty(request('status'))) {

                $query->where('status', request('status'));

            }



            return Datatables::of($query)

                ->addColumn('module_name', function ($row) {

                    return $row->module->name ?? null;

                })

                ->addColumn('username', function ($row) {

                    return $row->user->username ?? null;

                })

                ->addColumn('task_heading', function ($row) {

                    return $row->task_heading ?? '-';

                })

                ->orderColumn('module_name', function ($query, $order) {

                    $query->join('development_modules', 'add_developments.development_module_id', '=', 'development_modules.id')

                        ->orderBy('development_modules.name', $order)

                        ->select('add_developments.*');

                })

                ->orderColumn('username', function ($query, $order) {

                    $query->join('users', 'add_developments.user_id', '=', 'users.id')

                        ->orderBy('users.username', $order)

                        ->select('add_developments.*');

                })

                ->orderColumn('task_heading', function ($query, $order) {

                    $query->orderBy('add_developments.task_heading', $order);

                })

                ->orderColumn('status', function ($query, $order) {

                    $query->orderBy('add_developments.status', $order);

                })

                ->addColumn('action', function ($row) {

                    $html = '<div class="btn-group">';

                    $html .= '<button type="button" class="btn btn-xs btn-primary dropdown-toggle"

                            data-toggle="dropdown" aria-expanded="false">

                            <i class="fa fa-ellipsis-v"></i>

                        </button>';

                    $html .= '<ul class="dropdown-menu dropdown-menu-right" role="menu">';



                    $html .= '<li>';

                    $html .= '<a href="' . route('development.show', $row->id) . '" class="btn btn-xs btn-info">

                            <i class="fa fa-eye"></i> ' . __('messages.view') . '

                        </a>';

                    $html .= '</li>';



                    $html .= '<li>';

                    $html .= '<a href="' . route('development.edit', $row->id) . '" class="btn btn-xs btn-primary">

                            <i class="fa fa-edit"></i> ' . __('messages.edit') . '

                        </a>';

                    $html .= '</li>';



                    $html .= '<li>';

                    $html .= '<button data-href="' . route('development.destroy', $row->id) . '"

                            class="btn btn-xs btn-danger delete_development">

                            <i class="fa fa-trash"></i> ' . __('messages.delete') . '

                        </button>';

                    $html .= '</li>';



                    $html .= '</ul></div>';

                    return $html;

                })

                ->rawColumns(['action'])

                ->make(true);

        }



        return view('development::add-development.completed', [

            'documentNumbers' => $documentNumbers,

            'usernames'       => $usernames,

            'modules'         => $modules,

            'taskHeadings'    => $taskHeadings,

            'relatedDocNos'   => $relatedDocNos,

        ]);

    }

}


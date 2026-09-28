<?php
namespace Modules\Development\Http\Controllers;

use App\User;
use App\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Development\Entities\DevelopmentModule;
use Modules\Development\Models\AddDevelopment;
use Modules\Development\Models\RelatedDocNo;

class AddDevelopmentController extends Controller
{
    protected function generateNextDocNo(): string
    {
        // Get the highest existing document number
        $lastDoc = \Modules\Development\Models\AddDevelopment::orderBy('id', 'desc')->first();

        if ($lastDoc) {
            // Extract the numeric part and increment by 1
            $lastNumber = (int) substr($lastDoc->doc_no, 4);
            $nextNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no documents exist
            $nextNumber = 1;
        }

        // Format as NSND followed by 5-digit number with leading zeros
        return 'NSND' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        if (! DevelopmentModule::hasDevelopmentModuleAccess()) {
            abort(403, 'Unauthorized Access');
        }
        $doc_no          = $this->generateNextDocNo();
        $modules         = DevelopmentModule::pluck('name', 'id');
        $user_groups     = UserGroup::pluck('name', 'id')->toArray();
        $related_doc_nos = RelatedDocNo::pluck('doc_no', 'id');
        $users           = User::get();
        $user_groups     = UserGroup::pluck('name', 'id')->toArray();

        // Get the selected doc_no from session if it exists
        $selectedDocNo = session('selected_doc_no');

        return view('development::add-development.create', compact(
            'doc_no',
            'modules',
            'user_groups',
            'related_doc_nos',
            'users',
            'selectedDocNo'
        ));
    }

    // save or create related doc number
    public function saveDocNo(Request $request)
    {
        $request->validate([
            'doc_no' => 'required|unique:related_doc_no,doc_no',
        ]);

        $doc = RelatedDocNo::create([
            'doc_no'     => $request->doc_no,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('development::lang.document_number_saved_successfully'),
            'doc_no'  => $doc->doc_no,
            'doc_id'  => $doc->id,
        ]);

        // return redirect()->route('development.create')
        //     ->with('success', 'Document number saved successfully')
        //     ->with('selected_doc_no', $doc->doc_no);
    }

    // Add a comment to the development record
    public function addComment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'group_comment.comment_type' => 'required|string|max:255',
                'group_comment.status'       => 'nullable|string|in:Pending,Not Completed,Completed',
            ]);

            $development = AddDevelopment::findOrFail($id);

            // Get existing group comments or initialize as empty array
            $groupComments = $development->group_comments ?? [];

            // Add new comment with all group comment data
            $newComment = [
                'user_id'      => auth()->id(),
                'user_name'    => auth()->user()->username,
                'comment_type' => $request->input('group_comment.comment_type'),
                'status'       => $request->input('group_comment.status', 'Pending'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            // Add to the beginning of the array to show newest first
            array_unshift($groupComments, $newComment);

            // Update the development record
            $updateData = [
                'group_comments' => $groupComments,
                'status'         => $request->input('group_comment.status', $development->status),
            ];

            // Save the updated development record
            $development->update($updateData);

            return response()->json([
                'success' => true,
                'message' => __('development::lang.comment_added_successfully'),
                'data'    => [
                    'comment'      => $newComment,
                    'status_notes' => $request->input('status_notes', [])
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adding comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the comment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'datetime'              => 'required|date',
            'doc_no'                => 'required|unique:add_developments',
            'task_heading'          => 'required|string|max:255',
            'type'                  => 'required|in:Task,Issue',
            'development_module_id' => 'required|exists:development_modules,id',
            'details'               => 'required|string',
            'priority'              => 'required|in:Urgent,Priority,Normal',
            'visible_to_groups'     => 'required|array',
            'related_doc_no'        => 'nullable|required_if:type,Task',
            'status_notes'          => 'nullable|array',
            'status_notes.*'        => 'nullable|string|max:1000',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        DB::beginTransaction();

        // $imagePath = null;

        // if ($request->hasFile('image')) {
        //     $image = $request->file('image');
        //     $imageName = time() . '_' . $image->getClientOriginalName();
        //     $image->move(public_path('development_images'), $imageName);
        //     $imagePath = 'development_images/' . $imageName;
        // } else {
        //     $imagePath = null;
        // }

        $development = \Modules\Development\Models\AddDevelopment::create([
            'datetime'              => $validatedData['datetime'],
            'doc_no'                => $validatedData['doc_no'],
            'user_id'               => auth()->id(),
            'type'                  => $validatedData['type'],
            'task_heading'          => $validatedData['task_heading'],
            'development_module_id' => $validatedData['development_module_id'],
            'details'               => $validatedData['details'],
            'related_doc_no'        => $validatedData['type'] === 'Task' ? $validatedData['related_doc_no'] : null,
            'priority'              => $validatedData['priority'],
            'visible_to_groups'     => $validatedData['visible_to_groups'],
            'status'                => 'Pending',
            'status_notes'          => $validatedData['status_notes'] ?? [],
            // 'image' => $imagePath
        ]);

        DB::commit();

        $output = [
            'success' => 1,
            'msg'     => __('development::lang.add_development_added_successfully'),
        ];

        return redirect()->back()
            ->with('status', $output);
    }

    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ]);

            $image    = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // $path = $request->file('image')->stopath: path: re('uploads/images', 'public');
            $path = $request->file('image')->store('uploads/images', [
                'disk' => 'public',
            ]);

            return response()->json([
                'location' => asset('storage/' . $path),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500, ['Content-Type' => 'application/json; charset=UTF-8']);
        }
    }

    public function edit($id)
    {
        $development = \Modules\Development\Models\AddDevelopment::with(['module'])->findOrFail($id);
        $modules     = DevelopmentModule::pluck('name', 'id');
        $user_groups = UserGroup::pluck('name', 'id')->toArray();
        // Get unique document numbers, using the latest ID if there are duplicates
        $related_doc_nos = RelatedDocNo::select('doc_no')
            ->groupBy('doc_no')
            ->orderBy('id', 'desc')
            ->pluck('doc_no', 'doc_no');
        $users = User::get();

        // Prepare status notes for view
        $status_notes = $development->status_notes ?? [];

        // Prepare group comments for view
        $group_comments = [];
        foreach ($user_groups as $groupId => $groupName) {
            $group_comments[$groupId] = [
                'comment_type' => isset($development->group_comments[$groupId]) ? $development->group_comments[$groupId]['comment_type'] : null,
                'user_id'      => isset($development->group_comments[$groupId]) ? $development->group_comments[$groupId]['user_id'] : null,
            ];
        }

        return view('development::add-development.edit', compact('development', 'modules', 'user_groups', 'related_doc_nos', 'users', 'group_comments', 'status_notes'));
    }
    public function update(Request $request, $id)
    {
        // Validate only the fields you want to update
        $validatedData = $request->validate([
            'task_heading'   => 'required|string|max:255',
            'related_doc_no' => 'nullable|string', // or 'exists:related_doc_nos,id' if using IDs
        ]);

        $development = \Modules\Development\Models\AddDevelopment::findOrFail($id);

        // Update only the specific fields
        $development->update([
            'task_heading'   => $validatedData['task_heading'],
            'related_doc_no' => $validatedData['related_doc_no'] ?? $development->related_doc_no,
        ]);

        return response()->json([
            'success' => true,
            'msg'     => __('development::lang.add_development_updated_successfully'),
        ]);
    }


    public function updateStatus(Request $request, $id)
{
    $development = \Modules\Development\Models\AddDevelopment::findOrFail($id);

    // Only Super Admin can change status
    if (!auth()->user()->can('superadmin')) {
        return response()->json([
            'success' => false,
            'msg' => 'Unauthorized action'
        ], 403);
    }

    $request->validate([
        'status' => 'required|in:Pending,Not Completed,Completed',
    ]);

    $development->status = $request->status;
    $development->save();

    return response()->json([
        'success' => true,
        'msg' => __('development::lang.status_updated_successfully'),
        'status' => $development->status
    ]);
}



    // public function update(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'datetime'                          => 'required|date',
    //         'doc_no'                            => 'required|unique:add_developments,doc_no,' . $id,
    //         'development_module_id'             => 'required|exists:development_modules,id',
    //         'type'                              => 'required|in:Task,Issue',
    //         'details'                           => 'required|string',
    //         'priority'                          => 'required|in:Urgent,Priority,Normal',
    //         'visible_to_groups'                 => 'required|array',
    //         'related_doc_no'                    => 'nullable|required_if:type,Task',
    //         'status'                            => 'required|in:Pending,Not Completed,Completed',

    //         // For group comment (single group section)
    //         'group_comment'                     => 'nullable|array',
    //         'group_comment.comment_type'        => 'nullable|in:Issue Existing,No Issue,New Task',
    //         'group_comment.status'              => 'nullable|in:Pending,Not Completed,Completed',
    //         'group_comment.status_note_heading' => 'nullable|string|max:255',
    //         'group_comment.status_note'         => 'nullable|string',

    //         // For status notes (multiple possible)
    //         'status_notes'                      => 'nullable|array',
    //         'status_notes.*'                    => 'nullable|string|max:1000',
    //     ]);

    //     $development = \Modules\Development\Models\AddDevelopment::findOrFail($id);
    //     $development->update([
    //         'datetime'              => $validatedData['datetime'],
    //         'doc_no'                => $validatedData['doc_no'],
    //         'development_module_id' => $validatedData['development_module_id'],
    //         'type'                  => $validatedData['type'],
    //         'details'               => $validatedData['details'],
    //         'related_doc_no'        => $validatedData['type'] === 'Task' ? $validatedData['related_doc_no'] : null,
    //         'priority'              => $validatedData['priority'],
    //         'visible_to_groups'     => $validatedData['visible_to_groups'],
    //         'status'                => $validatedData['status'],
    //         'status_notes'          => $validatedData['status_notes'] ?? [],
    //         'group_comment'         => $validatedData['group_comment'] ?? [],
    //     ]);

    //     DB::commit();

    //     $output = [
    //         'success' => 1,
    //         'msg'     => __('development::lang.add_development_updated_successfully'),
    //     ];

    //     return redirect()
    //         ->route('list-development.index')
    //         ->with('status', $output);

    // }

    public function show($id)
    {
        $development = \Modules\Development\Models\AddDevelopment::with([
            'module',
            'user',
            'user.userGroup',
        ])->findOrFail($id);
        // dd($development);
        // Get user groups for this development
        $user_groups = \App\UserGroup::whereIn('id', $development->visible_to_groups)->get()->pluck('name', 'id')->toArray();

        // Pass the raw group comments to the view
        $group_comments = $development->group_comments ?? [];

        return view('development::add-development.show', compact('development', 'group_comments', 'user_groups'));
    }

    public function getRelatedDocs()
    {
        $docs = RelatedDocNo::all();
        return response()->json($docs);
    }

}

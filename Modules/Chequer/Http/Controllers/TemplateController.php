<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;
        $q = trim((string) $request->get('q', ''));

        $rows = $this->tableReady('cheq_templates')
            ? DB::table('cheq_templates')
                ->where('business_id', $this->businessId())
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('template_name', 'like', "%{$q}%")
                            ->orWhere('bank_name', 'like', "%{$q}%")
                            ->orWhere('status', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate($perPage)
                ->appends($request->query())
            : collect();

        return view('chequer::templates.index', compact('rows'));
    }

    public function create()
    {
        return view('chequer::templates.form', [
            'row' => null,
            'templates' => $this->templatesForCopy(),
            'fieldMap' => $this->defaultFieldMap(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);
        $data['business_id'] = $this->businessId();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('cheq_templates')->insertGetId($data);
        $this->enforceSingleDefault($id, $this->decodeFieldMap($data['field_map']));

        return redirect('/chequer-module/templates/'.$id.'/edit')
            ->with('status', ['success' => 1, 'msg' => 'Template saved successfully']);
    }

    public function edit($id)
    {
        $row = $this->findTemplate($id);

        return view('chequer::templates.form', [
            'row' => $row,
            'templates' => $this->templatesForCopy($id),
            'fieldMap' => $this->decodeFieldMap($row->field_map ?? null),
        ]);
    }

    public function update(Request $request, $id)
    {
        $row = $this->findTemplate($id);
        $oldMap = $this->decodeFieldMap($row->field_map ?? null);
        $data = $this->validatedPayload($request, $oldMap);
        $data['updated_at'] = now();

        DB::table('cheq_templates')
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->update($data);

        $newMap = $this->decodeFieldMap($data['field_map']);
        $this->deleteReplacedAsset($oldMap['template_image'] ?? null, $newMap['template_image'] ?? null);
        $this->deleteReplacedAsset($oldMap['signature_image'] ?? null, $newMap['signature_image'] ?? null);
        $this->enforceSingleDefault((int) $id, $newMap);

        return redirect('/chequer-module/templates/'.$id.'/edit')
            ->with('status', ['success' => 1, 'msg' => 'Template updated successfully']);
    }

    public function destroy($id)
    {
        $row = $this->findTemplate($id);
        $map = $this->decodeFieldMap($row->field_map ?? null);

        DB::table('cheq_templates')
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->delete();

        $this->deleteStoredAsset($map['template_image'] ?? null);
        $this->deleteStoredAsset($map['signature_image'] ?? null);

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Template deleted successfully']);
    }

    public function preview($id)
    {
        $row = $this->findTemplate($id);

        return response()->json([
            'id' => $row->id,
            'template_name' => $row->template_name,
            'bank_name' => $row->bank_name,
            'paper_width' => $row->paper_width,
            'paper_height' => $row->paper_height,
            'field_map' => $this->decodeFieldMap($row->field_map ?? null),
        ]);
    }

    public function testPrint($id)
    {
        $row = $this->findTemplate($id);
        $fieldMap = $this->decodeFieldMap($row->field_map ?? null);

        return view('chequer::templates.test_print', compact('row', 'fieldMap'));
    }

    protected function findTemplate($id)
    {
        $row = DB::table('cheq_templates')
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->first();

        abort_if(!$row, 404);
        return $row;
    }

    protected function templatesForCopy($excludeId = null)
    {
        if (!$this->tableReady('cheq_templates')) {
            return collect();
        }

        return DB::table('cheq_templates')
            ->where('business_id', $this->businessId())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('template_name')
            ->get(['id', 'template_name', 'bank_name', 'paper_width', 'paper_height', 'field_map']);
    }

    protected function validatedPayload(Request $request, array $existingMap = []): array
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:191',
            'bank_name' => 'nullable|string|max:191',
            'paper_width' => 'required|numeric|min:1|max:30',
            'paper_height' => 'required|numeric|min:1|max:20',
            'status' => 'required|in:active,inactive',
            'template_image' => 'nullable|image|max:5120',
            'signature_image' => 'nullable|image|max:3072',
            'field_map' => 'required|string',
        ]);

        $fieldMap = $this->decodeFieldMap($request->input('field_map'));
        $fieldMap['date_format'] = $request->input('date_format', $fieldMap['date_format'] ?? 'dd-mm-yyyy');
        $fieldMap['separator'] = $request->input('separator', $fieldMap['separator'] ?? '-');
        $fieldMap['date_digits'] = $this->dateDigitLabels($fieldMap['date_format'], $fieldMap['separator']);
        $fieldMap['is_default'] = $request->boolean('is_default');
        $fieldMap['snap_to_grid'] = $request->boolean('snap_to_grid');
        $fieldMap['grid_size'] = max(1, min(50, (int) $request->input('grid_size', 5)));
        $fieldMap['options'] = [
            'amount_words_2' => $request->boolean('amount_words_2'),
            'amount_words_3' => $request->boolean('amount_words_3'),
            'strike_bearer' => $request->boolean('strike_bearer'),
            'stamp' => $request->boolean('stamp'),
            'signature' => $request->boolean('signature'),
            'double_cross' => $request->boolean('double_cross'),
            'account_payee_only' => $request->boolean('account_payee_only'),
            'not_negotiable' => $request->boolean('not_negotiable'),
        ];

        foreach ($fieldMap['options'] as $option => $enabled) {
            $fieldKey = $option === 'account_payee_only' ? 'account_payee' : $option;
            if (isset($fieldMap['fields'][$fieldKey])) {
                $fieldMap['fields'][$fieldKey]['enabled'] = $enabled;
            }
        }

        if ($request->boolean('remove_template_image')) {
            $fieldMap['template_image'] = null;
        } elseif ($request->hasFile('template_image')) {
            $fieldMap['template_image'] = $request->file('template_image')->store('chequer/templates', 'public');
        } else {
            $fieldMap['template_image'] = $existingMap['template_image'] ?? $request->input('existing_template_image');
        }

        if ($request->boolean('remove_signature_image')) {
            $fieldMap['signature_image'] = null;
        } elseif ($request->hasFile('signature_image')) {
            $fieldMap['signature_image'] = $request->file('signature_image')->store('chequer/signatures', 'public');
        } else {
            $fieldMap['signature_image'] = $existingMap['signature_image'] ?? $request->input('existing_signature_image');
        }

        return [
            'template_name' => $validated['template_name'],
            'bank_name' => $validated['bank_name'] ?? null,
            'paper_width' => $validated['paper_width'],
            'paper_height' => $validated['paper_height'],
            'status' => $validated['status'],
            'field_map' => json_encode($fieldMap, JSON_UNESCAPED_SLASHES),
        ];
    }

    protected function enforceSingleDefault(int $currentId, array $currentMap): void
    {
        if (empty($currentMap['is_default']) || !$this->tableReady('cheq_templates')) {
            return;
        }

        $others = DB::table('cheq_templates')
            ->where('business_id', $this->businessId())
            ->where('id', '!=', $currentId)
            ->get(['id', 'field_map']);

        foreach ($others as $other) {
            $map = $this->decodeFieldMap($other->field_map ?? null);
            if (!empty($map['is_default'])) {
                $map['is_default'] = false;
                DB::table('cheq_templates')->where('id', $other->id)->update([
                    'field_map' => json_encode($map, JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    protected function deleteReplacedAsset(?string $oldPath, ?string $newPath): void
    {
        if ($oldPath && $oldPath !== $newPath) {
            $this->deleteStoredAsset($oldPath);
        }
    }

    protected function deleteStoredAsset(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function dateDigitLabels(string $format, string $separator): array
    {
        $parts = explode('-', $format);
        $labels = [];
        foreach ($parts as $partIndex => $part) {
            foreach (str_split(strtoupper($part)) as $index => $letter) {
                $labels[] = $letter.($index + 1);
            }
            if ($partIndex < count($parts) - 1) {
                $labels[] = $separator;
            }
        }
        return $labels;
    }

    protected function decodeFieldMap($raw): array
    {
        if (is_array($raw)) {
            return array_replace_recursive($this->defaultFieldMap(), $raw);
        }
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_replace_recursive($this->defaultFieldMap(), $decoded);
            }
        }
        return $this->defaultFieldMap();
    }

    protected function defaultFieldMap(): array
    {
        return [
            'date_format' => 'dd-mm-yyyy',
            'separator' => '-',
            'date_digits' => ['D1', 'D2', '-', 'M1', 'M2', '-', 'Y1', 'Y2', 'Y3', 'Y4'],
            'template_image' => null,
            'signature_image' => null,
            'is_default' => false,
            'snap_to_grid' => true,
            'grid_size' => 5,
            'fields' => [
                'payee' => ['label' => 'Name of Payee', 'sample' => 'SAMPLE PAYEE NAME', 'top' => 78, 'left' => 50, 'width' => 416, 'height' => 30, 'font_size' => 14, 'enabled' => true],
                'date' => ['label' => 'Date', 'sample' => date('d-m-Y'), 'top' => 30, 'left' => 480, 'width' => 130, 'height' => 26, 'font_size' => 14, 'enabled' => true],
                'amount' => ['label' => 'Amount', 'sample' => '125,000.00', 'top' => 120, 'left' => 500, 'width' => 120, 'height' => 28, 'font_size' => 14, 'enabled' => true],
                'amount_words' => ['label' => 'Amount in Words', 'sample' => 'One Hundred Twenty Five Thousand Only', 'top' => 145, 'left' => 50, 'width' => 520, 'height' => 45, 'font_size' => 14, 'enabled' => true],
                'amount_words_2' => ['label' => 'Amount in Words 2', 'sample' => 'Continuation Line Two', 'top' => 190, 'left' => 50, 'width' => 520, 'height' => 35, 'font_size' => 14, 'enabled' => false],
                'amount_words_3' => ['label' => 'Amount in Words 3', 'sample' => 'Continuation Line Three', 'top' => 225, 'left' => 50, 'width' => 520, 'height' => 35, 'font_size' => 14, 'enabled' => false],
                'stamp' => ['label' => 'Stamp / Seal', 'sample' => 'STAMP', 'top' => 230, 'left' => 470, 'width' => 90, 'height' => 45, 'font_size' => 12, 'enabled' => false],
                'signature' => ['label' => 'Signature', 'sample' => 'SIGNATURE', 'top' => 220, 'left' => 500, 'width' => 120, 'height' => 55, 'font_size' => 12, 'enabled' => false],
                'account_payee' => ['label' => 'A/C PAYEE ONLY', 'sample' => 'A/C PAYEE ONLY', 'top' => 25, 'left' => 75, 'width' => 140, 'height' => 22, 'font_size' => 12, 'enabled' => false],
                'not_negotiable' => ['label' => 'NOT NEGOTIABLE', 'sample' => 'NOT NEGOTIABLE', 'top' => 50, 'left' => 75, 'width' => 145, 'height' => 22, 'font_size' => 12, 'enabled' => false],
                'double_cross' => ['label' => 'Double Cross', 'sample' => '//', 'top' => 15, 'left' => 25, 'width' => 25, 'height' => 35, 'font_size' => 22, 'enabled' => false],
                'strike_bearer' => ['label' => 'Strike Bearer', 'sample' => 'BEARER', 'top' => 96, 'left' => 470, 'width' => 70, 'height' => 22, 'font_size' => 12, 'enabled' => false],
            ],
            'options' => [
                'amount_words_2' => false,
                'amount_words_3' => false,
                'strike_bearer' => false,
                'stamp' => false,
                'signature' => false,
                'double_cross' => false,
                'account_payee_only' => false,
                'not_negotiable' => false,
            ],
        ];
    }
}

<?php
namespace Modules\Membership\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Services\MemberLedgerSummaryService;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Membership\Entities\MembershipBusinessType;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\MembershipSetting;
use Modules\Membership\Entities\MembershipCardSetting;
use Modules\Membership\Entities\MembershipSignature;
use Modules\Membership\Entities\MembershipType;
use Modules\Membership\Entities\MembershipStatus;
use Modules\Membership\Entities\MembershipMemberRenewal;
use App\Business;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;
use Throwable;

class MembershipController extends Controller
{
    protected $businessUtil;
    protected $memberLedgerSummaryService;
    protected $membershipMemberColumns = null;

    private function buildMemberQrPayload(MembershipMember $member): string
    {
        return implode('|', array_filter([
            'member_code:' . ($member->member_number ?? ''),
            'member_name:' . ($member->member_name ?? ''),
            'mobile_no:' . ($member->default_mobile_number ?? ''),
            'joined_date:' . (!empty($member->date_joined) ? $member->date_joined : optional($member->created_at)->format('Y-m-d')),
            'region:' . ($member->region ?? ''),
            'date_issued:' . (optional($member->created_at)->format('Y-m-d') ?? now()->format('Y-m-d')),
        ], function ($value) {
            return $value !== null && $value !== '';
        }));
    }

    private function generateMemberQrCode(MembershipMember $member, string $qrCodePath): void
    {
        $fullPath = public_path('uploads/' . $qrCodePath);

        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        $writer = new PngWriter();
        $qrCode = QrCode::create($this->buildMemberQrPayload($member))
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium())
            ->setSize(220)
            ->setMargin(6)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());

        $result = $writer->write($qrCode);
        $result->saveToFile($fullPath);
    }

    private function tryGenerateMemberQrCode(MembershipMember $member, string $qrCodePath): void
    {
        try {
            $this->generateMemberQrCode($member, $qrCodePath);
            $member->update(['qr_code_path' => $qrCodePath]);
        } catch (Throwable $e) {
            Log::warning('MembershipController QR generation failed', [
                'member_id' => $member->id ?? null,
                'member_number' => $member->member_number ?? null,
                'qr_code_path' => $qrCodePath,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function membershipBusinessTypesHaveActiveColumn(): bool
    {
        return Schema::hasColumn('membership_business_types', 'is_active');
    }

    private function selectableMembershipBusinessTypesQuery($business_id)
    {
        return MembershipBusinessType::where(function ($q) use ($business_id) {
                $q->where('business_id', $business_id)
                    ->orWhere('business_id', 0);
            })
            ->when($this->membershipBusinessTypesHaveActiveColumn(), function ($query) {
                $query->where('is_active', 1);
            });
    }

    private function membershipBusinessTypeIsSelectable($business_id, $businessTypeId): bool
    {
        return $this->selectableMembershipBusinessTypesQuery($business_id)
            ->where('id', $businessTypeId)
            ->exists();
    }

    public function __construct(
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil,
        ProductUtil $productUtil,
        ContactUtil $contactUtil,
        MemberLedgerSummaryService $memberLedgerSummaryService
    ) {

        $this->commonUtil      = $commonUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->businessUtil    = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil     = $productUtil;
        $this->contactUtil     = $contactUtil;
        $this->memberLedgerSummaryService = $memberLedgerSummaryService;
    }

    public function index()
    {

    }

    protected function calculateRenewalDate(?string $dateJoined, ?string $renewalPeriod, $renewalCycles): ?string
    {
        if (empty($dateJoined) || empty($renewalPeriod) || empty($renewalCycles)) {
            return null;
        }

        $cycles = (int) $renewalCycles;
        if ($cycles < 1) {
            return null;
        }

        $date = \Carbon\Carbon::parse($dateJoined);

        switch ($renewalPeriod) {
            case 'days':
                $date->addDays($cycles);
                break;
            case 'weeks':
                $date->addWeeks($cycles);
                break;
            case 'months':
                $date->addMonths($cycles);
                break;
            case 'years':
                $date->addYears($cycles);
                break;
            default:
                return null;
        }

        return $date->format('Y-m-d');
    }

    protected function getMembershipMemberColumns(): array
    {
        if ($this->membershipMemberColumns !== null) {
            return $this->membershipMemberColumns;
        }

        return $this->membershipMemberColumns = Schema::getColumnListing('membership_members');
    }

    protected function filterMembershipMemberData(array $data): array
    {
        $availableColumns = array_flip($this->getMembershipMemberColumns());

        return array_intersect_key($data, $availableColumns);
    }

    protected function getRenewalBaseDate(MembershipMember $member): ?string
    {
        return $member->renewal_date ?: $member->date_joined;
    }

    protected function parseMembershipFilterDate(?string $value): ?\Carbon\Carbon
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        $formats = ['d-m-Y', 'd/m/Y', 'j M Y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {
                // Try the next known format.
            }
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getMembershipActivities(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $business_users = User::where('business_id', $business_id)->pluck('id')->toArray();

            // Query activities for membership-related models (only edit and delete)
            $activity = Activity::whereIn('causer_id', $business_users)
                ->where(function ($query) {
                    $query->where('subject_type', 'Modules\Membership\Entities\MembershipMember')
                        ->orWhere('log_name', 'membership_member');
                })
                ->whereIn('description', ['updated', 'deleted', 'update', 'delete']);

            // Filter by user
            if (!empty(request()->user) && request()->user != 'All') {
                $activity->where('causer_id', request()->user);
            }

            // Filter by type (updated/deleted)
            if (!empty(request()->type) && request()->type != 'All') {
                $activity->where('description', request()->type);
            }

            // Filter by date range
            if (!empty(request()->startDate) && !empty(request()->endDate)) {
                $activity->whereDate('created_at', '>=', request()->startDate);
                $activity->whereDate('created_at', '<=', request()->endDate);
            }

            $datatable = DataTables::of($activity)
                ->editColumn('created_at', '{{ @format_datetime($created_at) }}')
                ->removeColumn('id')
                ->editColumn('causer_id', function ($row) {
                    $user = User::find($row->causer_id);
                    return $user ? $user->username : 'Unknown';
                })
                ->editColumn('description', function ($row) {
                    // Format the description to show proper activity type
                    $description = $row->description;
                    if ($description == 'updated' || $description == 'update') {
                        return 'Updated';
                    } elseif ($description == 'deleted' || $description == 'delete') {
                        return 'Deleted';
                    }
                    return ucfirst($description);
                })
                ->addColumn('member_info', function ($row) {
                    $member = MembershipMember::find($row->subject_id);
                    if ($member) {
                        return $member->member_name . ' (' . $member->member_number . ')';
                    }
                    // For deleted records, try to get from properties
                    $attributes = json_decode($row->properties, true);
                    $old = $attributes['attributes'] ?? $attributes['old'] ?? [];
                    if (!empty($old['member_name'])) {
                        return $old['member_name'] . ' (Deleted)';
                    }
                    return 'Member #' . $row->subject_id;
                })
                ->addColumn('description_details', function ($row) {
                    $html = "";
                    
                    // Try to decode properties
                    $properties = $row->properties;
                    if (empty($properties)) {
                        // If no properties, show basic info based on description
                        if ($row->description == 'updated' || $row->description == 'update') {
                            return "Member information was updated.";
                        } elseif ($row->description == 'deleted' || $row->description == 'delete') {
                            return "Member was deleted.";
                        }
                        return "No details available";
                    }
                    
                    if (is_string($properties)) {
                        $attributes = json_decode($properties, true);
                        // If JSON decode failed, try as is
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $attributes = $properties;
                        }
                    } else {
                        $attributes = $properties;
                    }
                    
                    // Handle different property structures
                    if (is_array($attributes)) {
                        $new = $attributes['attributes'] ?? $attributes['new'] ?? [];
                        $old = $attributes['old'] ?? [];
                    } else {
                        $new = [];
                        $old = [];
                    }

                    if ($row->description == 'updated' || $row->description == 'update') {
                        // Collect all changed fields
                        $changedFields = [];
                        $detailedChanges = [];
                        
                        // Priority order for important fields
                        $priorityFields = [
                            'member_name' => 'Member Name',
                            'member_number' => 'Member Number',
                            'default_mobile_number' => 'Mobile Number',
                            'membership_status_id' => 'Membership Status',
                            'membership_type_id' => 'Membership Type',
                            'membership_business_type_id' => 'Business Type',
                            'no_of_shares' => 'Number of Shares',
                            'total_share_value' => 'Share Value',
                            'member_address' => 'Address',
                            'date_joined' => 'Date Joined',
                            'nic_no' => 'NIC Number',
                            'gender' => 'Gender',
                            'date_of_birth' => 'Date of Birth',
                            'title' => 'Title',
                            'region' => 'Region',
                            'membership_code' => 'Membership Code',
                            'other_mobile_numbers' => 'Other Mobile Numbers',
                        ];
                        
                        // Get all unique keys from both old and new
                        $allKeys = array_unique(array_merge(
                            is_array($new) ? array_keys($new) : [],
                            is_array($old) ? array_keys($old) : []
                        ));
                        
                        foreach ($allKeys as $key) {
                            // Skip system fields
                            if (in_array($key, ['created_at', 'updated_at', 'id', 'business_id', 'created_by', 'qr_code_path'])) {
                                continue;
                            }
                            
                            $oldValue = $old[$key] ?? null;
                            $newValue = $new[$key] ?? null;
                            
                            // Normalize values for comparison
                            // Handle null/empty comparisons - treat null, empty string, and '0' consistently
                            $oldNormalized = ($oldValue === null || $oldValue === '') ? '' : $oldValue;
                            $newNormalized = ($newValue === null || $newValue === '') ? '' : $newValue;
                            
                            // Compare values (handle type differences and nulls)
                            $oldStr = trim((string)$oldNormalized);
                            $newStr = trim((string)$newNormalized);
                            
                            // Also check numeric comparisons for numeric fields
                            $isNumericField = in_array($key, ['no_of_shares', 'total_share_value', 'membership_business_type_id', 'membership_type_id', 'membership_status_id', 'membership_setting_id']);
                            
                            $hasChanged = false;
                            if ($isNumericField) {
                                // For numeric fields, compare as numbers
                                $oldNum = ($oldValue === null || $oldValue === '') ? null : (is_numeric($oldValue) ? (float)$oldValue : null);
                                $newNum = ($newValue === null || $newValue === '') ? null : (is_numeric($newValue) ? (float)$newValue : null);
                                $hasChanged = ($oldNum !== $newNum);
                            } else {
                                // For string fields, compare as strings (case-insensitive for some fields)
                                if (in_array($key, ['member_name', 'member_address', 'title', 'gender'])) {
                                    $hasChanged = (strtolower($oldStr) !== strtolower($newStr));
                                } else {
                                    $hasChanged = ($oldStr !== $newStr);
                                }
                            }
                            
                            // Also check if one exists and the other doesn't
                            if (!$hasChanged) {
                                $oldExists = ($oldValue !== null && $oldValue !== '');
                                $newExists = ($newValue !== null && $newValue !== '');
                                $hasChanged = ($oldExists !== $newExists);
                            }
                            
                            if ($hasChanged) {
                                $fieldName = $this->formatFieldName($key);
                                $oldDisplay = $this->formatFieldValue($key, $oldValue);
                                $newDisplay = $this->formatFieldValue($key, $newValue);
                                
                                // Store for summary
                                $changedFields[$key] = $fieldName;
                                
                                // Store detailed change
                                $detailedChanges[] = [
                                    'field' => $fieldName,
                                    'old' => $oldDisplay,
                                    'new' => $newDisplay
                                ];
                            }
                        }
                        
                        // Create dynamic summary message
                        if (!empty($changedFields)) {
                            // Sort by priority
                            $priorityOrder = array_keys($priorityFields);
                            $sortedFields = [];
                            $otherFields = [];
                            
                            foreach ($priorityOrder as $fieldKey) {
                                if (isset($changedFields[$fieldKey])) {
                                    $sortedFields[$fieldKey] = $changedFields[$fieldKey];
                                }
                            }
                            
                            // Add any remaining fields
                            foreach ($changedFields as $key => $name) {
                                if (!isset($sortedFields[$key])) {
                                    $otherFields[$key] = $name;
                                }
                            }
                            
                            // Build summary message (show up to 3-4 main fields)
                            $summaryFields = array_slice($sortedFields, 0, 4, true);
                            $fieldNames = array_values($summaryFields);
                            $totalChanges = count($changedFields);
                            
                            // Convert field names to "change" format (lowercase, simpler names)
                            $changeLabels = [];
                            foreach ($fieldNames as $fieldName) {
                                // Convert to simpler, lowercase format
                                $changeLabel = strtolower($fieldName);
                                // Remove "Member " prefix if present
                                $changeLabel = str_replace('member ', '', $changeLabel);
                                // Simplify common terms
                                $changeLabel = str_replace('number', 'no.', $changeLabel);
                                $changeLabel = str_replace('mobile no.', 'mobile', $changeLabel);
                                $changeLabel = str_replace('membership status', 'status', $changeLabel);
                                $changeLabel = str_replace('membership type', 'type', $changeLabel);
                                $changeLabel = str_replace('business type', 'business', $changeLabel);
                                $changeLabel = str_replace('number of shares', 'shares', $changeLabel);
                                $changeLabel = str_replace('share value', 'share value', $changeLabel);
                                $changeLabel = str_replace('date joined', 'join date', $changeLabel);
                                $changeLabel = str_replace('date of birth', 'birth date', $changeLabel);
                                $changeLabel = str_replace('nic number', 'NIC', $changeLabel);
                                $changeLabel = str_replace('other mobile numbers', 'other mobiles', $changeLabel);
                                
                                $changeLabels[] = $changeLabel . " change";
                            }
                            
                            if (count($summaryFields) == 1) {
                                // Single field changed
                                $html = "<strong>" . $changeLabels[0] . "</strong>";
                            } elseif (count($summaryFields) == 2) {
                                // Two fields changed
                                $html = "<strong>" . implode(" and ", $changeLabels) . "</strong>";
                            } elseif (count($summaryFields) == 3) {
                                // Three fields changed
                                $lastChange = array_pop($changeLabels);
                                $html = "<strong>" . implode(", ", $changeLabels) . ", and " . $lastChange . "</strong>";
                            } else {
                                // Multiple fields changed
                                $lastChange = array_pop($changeLabels);
                                $html = "<strong>" . implode(", ", $changeLabels) . ", and " . $lastChange;
                                if ($totalChanges > count($summaryFields)) {
                                    $html .= " (and " . ($totalChanges - count($summaryFields)) . " more)";
                                }
                                $html .= "</strong>";
                            }
                            
                            // Add detailed changes below summary (collapsible or always visible)
                            if (count($detailedChanges) > 0) {
                                $html .= "<br><div style='margin-top: 5px; font-size: 0.9em;'>";
                                foreach ($detailedChanges as $change) {
                                    $html .= "<span style='color:#666;'>{$change['field']}:</span> ";
                                    $html .= "<span style='color:#d9534f;text-decoration:line-through;'>{$change['old']}</span> ";
                                    $html .= "→ <span style='color:#5cb85c;'>{$change['new']}</span><br>";
                                }
                                $html .= "</div>";
                            }
                        } else {
                            // No specific changes detected, but it's an update
                            // Try to identify what fields were updated based on available data
                            $detectedChanges = [];
                            
                            if (!empty($new) && is_array($new)) {
                                foreach ($new as $key => $value) {
                                    if (!in_array($key, ['created_at', 'updated_at', 'id', 'business_id', 'created_by', 'qr_code_path']) && isset($priorityFields[$key])) {
                                        $fieldName = strtolower($priorityFields[$key]);
                                        // Simplify field names
                                        $fieldName = str_replace('member ', '', $fieldName);
                                        $fieldName = str_replace('number', 'no.', $fieldName);
                                        $fieldName = str_replace('mobile no.', 'mobile', $fieldName);
                                        $fieldName = str_replace('membership status', 'status', $fieldName);
                                        $fieldName = str_replace('membership type', 'type', $fieldName);
                                        $fieldName = str_replace('business type', 'business', $fieldName);
                                        $fieldName = str_replace('number of shares', 'shares', $fieldName);
                                        $fieldName = str_replace('date joined', 'join date', $fieldName);
                                        $fieldName = str_replace('date of birth', 'birth date', $fieldName);
                                        $fieldName = str_replace('nic number', 'NIC', $fieldName);
                                        
                                        $detectedChanges[] = $fieldName . " change";
                                        
                                        // Limit to 3 most important changes
                                        if (count($detectedChanges) >= 3) {
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if (!empty($detectedChanges)) {
                                if (count($detectedChanges) == 1) {
                                    $html = "<strong>" . $detectedChanges[0] . "</strong>";
                                } elseif (count($detectedChanges) == 2) {
                                    $html = "<strong>" . implode(" and ", $detectedChanges) . "</strong>";
                                } else {
                                    $lastChange = array_pop($detectedChanges);
                                    $html = "<strong>" . implode(", ", $detectedChanges) . ", and " . $lastChange . "</strong>";
                                }
                            } else {
                                // Last resort - show generic message
                                $html = "<strong>Member information change</strong>";
                            }
                        }
                    } elseif ($row->description == 'deleted' || $row->description == 'delete') {
                        // Show deleted record details
                        $deletedData = !empty($new) ? $new : (!empty($old) ? $old : []);
                        if (!empty($deletedData) && is_array($deletedData)) {
                            $html .= "<strong>Deleted Member Details:</strong><br>";
                            if (!empty($deletedData['member_name'])) {
                                $html .= "Name: " . htmlspecialchars($deletedData['member_name']) . "<br>";
                            }
                            if (!empty($deletedData['member_number'])) {
                                $html .= "Member Number: " . htmlspecialchars($deletedData['member_number']) . "<br>";
                            }
                            if (!empty($deletedData['default_mobile_number'])) {
                                $html .= "Mobile: " . htmlspecialchars($deletedData['default_mobile_number']) . "<br>";
                            }
                            if (!empty($deletedData['region'])) {
                                $html .= "Region: " . htmlspecialchars($deletedData['region']) . "<br>";
                            }
                        } else {
                            // Handle legacy format
                            if (is_string($properties)) {
                                $decodedProperties = json_decode($properties);
                                if (is_array($decodedProperties) && !empty($decodedProperties[0])) {
                                    $html .= nl2br(htmlspecialchars($decodedProperties[0]));
                                } else {
                                    $html = "Member was deleted.";
                                }
                            } else {
                                $html = "Member was deleted.";
                            }
                        }
                    }

                    return $html ?: 'No details available';
                });

            return $datatable->rawColumns(['description_details', 'member_info'])
                ->make(true);
        }

        // Get users for filter dropdown
        $users = User::where('business_id', $business_id)->pluck('username', 'id');

        // Activity types (only edit and delete)
        $type = collect(['updated' => 'Updated', 'deleted' => 'Deleted']);

        return view('membership::partials.membership_activities')
            ->with(compact('users', 'type'));
    }

    private function formatFieldName($key)
    {
        $fieldNames = [
            'member_name' => 'Member Name',
            'member_number' => 'Member Number',
            'membership_code' => 'Membership Code',
            'title' => 'Title',
            'member_address' => 'Address',
            'date_joined' => 'Date Joined',
            'nic_no' => 'NIC Number',
            'date_of_birth' => 'Date of Birth',
            'gender' => 'Gender',
            'membership_business_type_id' => 'Business Type',
            'membership_type_id' => 'Membership Type',
            'membership_status_id' => 'Membership Status',
            'no_of_shares' => 'Number of Shares',
            'total_share_value' => 'Total Share Value',
            'default_mobile_number' => 'Mobile Number',
            'other_mobile_numbers' => 'Other Mobile Numbers',
            'region' => 'Region',
            'membership_setting_id' => 'Membership Setting',
            'contact_id' => 'Contact',
        ];

        return $fieldNames[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    private function formatFieldValue($key, $value)
    {
        if ($value === null || $value === '') {
            return '<em>Empty</em>';
        }

        // Handle foreign key lookups
        if ($key === 'membership_business_type_id' && $value) {
            $type = MembershipBusinessType::find($value);
            return $type ? $type->business_type : $value;
        }
        if ($key === 'membership_type_id' && $value) {
            $type = MembershipType::find($value);
            return $type ? $type->type_name : $value;
        }
        if ($key === 'membership_status_id' && $value) {
            $status = MembershipStatus::find($value);
            return $status ? $status->status_name : $value;
        }
        if ($key === 'membership_setting_id' && $value) {
            $setting = MembershipSetting::find($value);
            return $setting ? $setting->region : $value;
        }

        return htmlspecialchars($value);
    }

    private function buildNextMemberNumber(MembershipSetting $setting, bool $incrementSequence = true): string
    {
        $padding = strlen((string) $setting->starting_number);
        $padding = $padding > 0 ? $padding : 1;

        $prefix = $setting->prefix ?? '';
        $nextNumber = max((int) ($setting->next_sequence ?? 0), (int) ($setting->starting_number ?? 1), 1);

        do {
            $numberPadded = str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
            $memberNumber = $prefix . $numberPadded;
            $exists = MembershipMember::where('member_number', $memberNumber)->exists();

            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        if ($incrementSequence) {
            $setting->next_sequence = $nextNumber + 1;
            $setting->save();
        }

        return $memberNumber;
    }

    private function generateMemberNumber($business_id, $membership_setting_id)
    {
        $setting = MembershipSetting::where('id', $membership_setting_id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        return $this->buildNextMemberNumber($setting, true);
    }

  public function getMembers(Request $request)
{
    $business_id = request()->session()->get('user.business_id');

      if ($request->ajax()) {
          $nextRenewalDate   = $request->get('next_renewal_date');
          $filterMembershipTypeId = $request->get('filter_membership_type_id');
          $filterMembershipStatusId = $request->get('filter_membership_status_id');
          $hasMemberNameOtherColumn = Schema::hasColumn('membership_members', 'member_name_other');
          $hasDateJoinedColumn = Schema::hasColumn('membership_members', 'date_joined');
          $hasDateOfBirthColumn = Schema::hasColumn('membership_members', 'date_of_birth');
          $hasRenewalDateColumn = Schema::hasColumn('membership_members', 'renewal_date');

        // Correlated subquery: only runs for the rows on the current page,
        // avoiding a full-table GROUP BY scan before pagination
        $latestPointBalance = \DB::raw('(
            SELECT COALESCE(MAX(mp2.point_balance), 0)
            FROM membership_points mp2
            WHERE mp2.member_id = membership_members.id
              AND mp2.business_id = ' . (int) $business_id . '
        ) as point_balance');

        $selectColumns = [
            'membership_members.id',
            'membership_members.member_number',
            'membership_members.member_name',
            $hasMemberNameOtherColumn
                ? 'membership_members.member_name_other'
                : \DB::raw('NULL as member_name_other'),
            'membership_members.membership_business_type_id',
            'membership_members.membership_type_id',
            'membership_members.membership_status_id',
            'membership_members.default_mobile_number',
            'membership_members.region',
            'membership_members.created_at',
            $hasDateJoinedColumn
                ? 'membership_members.date_joined'
                : \DB::raw('NULL as date_joined'),
            $hasDateOfBirthColumn
                ? 'membership_members.date_of_birth'
                : \DB::raw('NULL as date_of_birth'),
        ];
        if ($hasRenewalDateColumn) {
            $selectColumns[] = 'membership_members.renewal_date';
        }
        $selectColumns[] = $latestPointBalance;

        $members = MembershipMember::where('membership_members.business_id', $business_id)
            ->with([
                'businessType:id,business_type',
                'membershipType:id,type_name',
                'membershipStatus:id,status_name',
            ])
            ->select($selectColumns);

          if ($filterMembershipTypeId !== null && $filterMembershipTypeId !== '') {
              $typeId = (int) $filterMembershipTypeId;
              if ($typeId > 0 && MembershipType::where('id', $typeId)
                  ->where(function ($q) use ($business_id) {
                      $q->where('business_id', $business_id)->orWhere('business_id', 0);
                  })
                  ->exists()
              ) {
                  $members->where('membership_members.membership_type_id', $typeId);
              }
          }

          if ($filterMembershipStatusId !== null && $filterMembershipStatusId !== '') {
              $statusId = (int) $filterMembershipStatusId;
              if ($statusId > 0 && MembershipStatus::where('id', $statusId)
                  ->where(function ($q) use ($business_id) {
                      $q->where('business_id', $business_id)->orWhere('business_id', 0);
                  })
                  ->exists()
              ) {
                  $members->where('membership_members.membership_status_id', $statusId);
              }
          }

          if ($hasRenewalDateColumn && $nextRenewalDate && trim($nextRenewalDate) !== '') {
              try {
                  $separator = null;

                  if (strpos($nextRenewalDate, ' ~ ') !== false) {
                      $separator = ' ~ ';
                  } elseif (strpos($nextRenewalDate, ' - ') !== false) {
                      $separator = ' - ';
                  }

                  if ($separator !== null) {
                      $dates = explode($separator, $nextRenewalDate, 2);
                      if (count($dates) === 2) {
                          $startDate = $this->parseMembershipFilterDate($dates[0]);
                          $endDate = $this->parseMembershipFilterDate($dates[1]);

                          if ($startDate && $endDate) {
                              $members->whereBetween('membership_members.renewal_date', [
                                  $startDate->toDateString(),
                                  $endDate->toDateString(),
                              ]);
                          }
                      }
                  } else {
                      $parsedDate = $this->parseMembershipFilterDate($nextRenewalDate);
                      if ($parsedDate) {
                          $members->whereDate('membership_members.renewal_date', $parsedDate->toDateString());
                      }
                  }
              } catch (\Exception $e) {
                  // Invalid date format, ignore filter
              }
          }

        $canEdit = auth()->user()->can('edit_member');

        return DataTables::of($members)
            ->addColumn('date_time', function ($row) {
                return $row->created_at ? $row->created_at->format('j M Y H:i') : '-';
            })
            ->addColumn('business_type_name', function ($row) {
                return $row->businessType ? $row->businessType->business_type : '-';
            })
            ->editColumn('date_joined', function ($row) {
                return $row->date_joined ? \Carbon\Carbon::parse($row->date_joined)->format('j M Y') : '-';
            })
            ->editColumn('date_of_birth', function ($row) {
                return $row->date_of_birth ? \Carbon\Carbon::parse($row->date_of_birth)->format('j M Y') : '-';
            })
            ->addColumn('point_balance_formatted', function ($row) {
                return number_format($row->point_balance, 2);
            })
            ->addColumn('action', function ($row) use ($canEdit) {
                if (! $canEdit) {
                    return '<span class="text-muted">—</span>';
                }

                $renewalDateLabel = $row->renewal_date
                    ? \Carbon\Carbon::parse($row->renewal_date)->format('j M Y')
                    : '-';

                $actionHtml = '<div class="btn-group">';
                $actionHtml .= '<button type="button" class="btn btn-primary btn-xs dropdown-toggle" data-toggle="dropdown">';
                $actionHtml .= __('messages.action') . ' <span class="caret"></span>';
                $actionHtml .= '</button>';
                $actionHtml .= '<ul class="dropdown-menu">';
                $actionHtml .= '<li>
                    <a href="#" class="btn-modal"
                       data-href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'viewMember'], [$row->id]) . '"
                       data-container=".member_modal">
                       <i class="fa fa-eye"></i> ' . __('messages.view') . '
                    </a>
                </li>';
                $actionHtml .= '<li>
                        <a href="#" class="btn-modal"
                           data-href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'editMember'], [$row->id]) . '"
                           data-container=".member_modal">
                           <i class="fa fa-edit"></i> ' . __('messages.edit') . '
                        </a>
                    </li>';
                $actionHtml .= '<li>
                        <a href="#" class="member_delete"
                           data-href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'destroyMember'], [$row->id]) . '">
                           <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                        </a>
                    </li>';

                $actionHtml .= '<li>
                    <a href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'viewMemberLedger'], [$row->id]) . '">
                       <i class="fa fa-book"></i> ' . __('membership::lang.member_ledger') . '
                    </a>
                </li>';

                $actionHtml .= '</ul>';
                $actionHtml .= '<button type="button"
                    class="btn btn-info btn-xs print_card_btn"
                    data-href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'printCard'], [$row->id]) . '">
                    <i class="fa fa-print"></i> ' . __('membership::lang.print_card') . '
                </button>';
                $actionHtml .= '<button type="button" class="btn btn-warning btn-xs renew_member_btn" style="margin-left: 4px;" data-href="' . action([\Modules\Membership\Http\Controllers\MembershipController::class, 'showRenewalModal'], [$row->id]) . '">Renewal: ' . e($renewalDateLabel) . '</button>';
                $actionHtml .= '</div>';

                return $actionHtml;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

        $membershipTypes = MembershipType::where(function ($q) use ($business_id) {
            $q->where('business_id', $business_id)->orWhere('business_id', 0);
        })
            ->select('id', 'type_name')
            ->orderBy('type_name')
            ->get()
            ->pluck('type_name', 'id');

        $membershipStatuses = MembershipStatus::where(function ($q) use ($business_id) {
            $q->where('business_id', $business_id)->orWhere('business_id', 0);
        })
            ->select('id', 'status_name')
            ->orderBy('status_name')
            ->get()
            ->pluck('status_name', 'id');

        return view('membership::partials.members', compact('membershipTypes', 'membershipStatuses'));
}

    public function createMember()
    {

        $business_id = request()->session()->get('user.business_id');

        $businessTypes = $this->selectableMembershipBusinessTypesQuery($business_id)
            ->whereIn('id', function($query) use ($business_id) {
                $query->selectRaw('MAX(id)')
                    ->from('membership_business_types')
                    ->where(function($q) use ($business_id) {
                        $q->where('business_id', $business_id)
                          ->orWhere('business_id', 0);
                    })
                    ->when($this->membershipBusinessTypesHaveActiveColumn(), function ($q) {
                        $q->where('is_active', 1);
                    })
                    ->groupBy('business_type');
            })
            ->orderByRaw("business_id = 0 DESC")
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            })
            ->pluck('business_type', 'id');

        $regions = MembershipSetting::where('business_id', $business_id)
            ->select('id', 'region')
            ->get()
            ->pluck('region', 'id');

        $membershipTypes = MembershipType::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->select('id', 'type_name')
            ->orderBy('type_name')
            ->get()
            ->pluck('type_name', 'id');

        $membershipStatuses = MembershipStatus::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->select('id', 'status_name')
            ->orderBy('status_name')
            ->get()
            ->pluck('status_name', 'id');

        \Log::info("MembershipController createMember: Found " . $regions->count() . " regions for business_id: " . $business_id);

        return view('membership::partials.member_create', compact('businessTypes', 'regions', 'membershipTypes', 'membershipStatuses'));
    }

    public function viewMember($id)
    {

        $business_id = request()->session()->get('user.business_id');

        $member = MembershipMember::where('id', $id)
            ->where('business_id', $business_id)
            ->with(['businessType', 'membershipSetting', 'membershipType', 'membershipStatus', 'createdBy'])
            ->firstOrFail();

        return view('membership::partials.member_view', compact('member'));
    }

    public function showRenewalModal($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $member = MembershipMember::where('id', $id)
            ->where('business_id', $business_id)
            ->with(['renewals.renewedBy:id,username,first_name,last_name'])
            ->firstOrFail();

        $renewals = $member->renewals->sortByDesc('created_at')->values();
        $renewalBaseDate = $this->getRenewalBaseDate($member);

        return view('membership::partials.member_renewal', compact('member', 'renewals', 'renewalBaseDate'));
    }

    public function storeRenewal(Request $request, $id)
    {
        if (!auth()->user()->can('edit_member')) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.unauthorized_action'),
            ]);
        }

        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'renewal_period' => 'required|in:days,weeks,months,years',
            'renewal_cycles' => 'required|integer|min:1',
            'renewal_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $member = MembershipMember::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $baseDate = $this->getRenewalBaseDate($member);
            $nextRenewalDate = $this->calculateRenewalDate(
                $baseDate,
                $request->renewal_period,
                $request->renewal_cycles
            );

            if (empty($nextRenewalDate)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            MembershipMemberRenewal::create([
                'business_id' => $business_id,
                'membership_member_id' => $member->id,
                'renewal_period' => $request->renewal_period,
                'renewal_cycles' => $request->renewal_cycles,
                'next_renewal_date' => $nextRenewalDate,
                'renewal_amount' => $request->renewal_amount,
                'renewed_by' => auth()->id(),
            ]);

            $member->update([
                'renewal_period' => $request->renewal_period,
                'renewal_cycles' => $request->renewal_cycles,
                'renewal_date' => $nextRenewalDate,
                'registration_renewal_amount' => $request->renewal_amount,
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully'),
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function editMember($id)
    {
        // Check permission
        if (!auth()->user()->can('edit_member')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $member = MembershipMember::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $businessTypes = $this->selectableMembershipBusinessTypesQuery($business_id)
            ->pluck('business_type', 'id');

        $regions = MembershipSetting::where('business_id', $business_id)
            ->select('id', 'region')
            ->get()
            ->pluck('region', 'id');

        $membershipTypes = MembershipType::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->select('id', 'type_name')
            ->orderBy('type_name')
            ->get()
            ->pluck('type_name', 'id');

        $membershipStatuses = MembershipStatus::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->select('id', 'status_name')
            ->orderBy('status_name')
            ->get()
            ->pluck('status_name', 'id');

        return view('membership::partials.member_edit', compact('member', 'businessTypes', 'regions', 'membershipTypes', 'membershipStatuses'));
    }

    public function updateMember(Request $request, $id)
    {
        if ($request->ajax()) {
            // Check permission
            if (!auth()->user()->can('edit_member')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.unauthorized_action'),
                ]);
            }

            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'member_name'                 => 'required|string|max:255',
                'member_name_other'          => 'nullable|string|max:255',
                'membership_business_type_id' => 'required|exists:membership_business_types,id',
                'default_mobile_number'       => 'required|string|max:20',
                'other_mobile_numbers'        => 'nullable|string',
                'title'                       => 'nullable|string|max:20',
                'member_address'             => 'nullable|string',
                'date_joined'                 => 'nullable|date',
                'nic_no'                      => 'nullable|string|max:50',
                'date_of_birth'               => 'nullable|date',
                'gender'                      => 'nullable|in:Male,Female',
                'membership_code'             => 'nullable|string|max:100',
                'membership_type_id'          => 'nullable|exists:membership_types,id',
                'no_of_shares'                => 'nullable|integer|min:0',
                'total_share_value'           => 'nullable|numeric|min:0',
                'membership_status_id'        => 'nullable|exists:membership_statuses,id',
                'renewal_period'              => 'nullable|in:days,weeks,months,years',
                'renewal_cycles'              => 'nullable|required_with:renewal_period|integer|min:1',
                'renewal_date'                => 'nullable|date',
                'registration_renewal_amount' => 'nullable|numeric|min:0',
            ]);

            if (! $this->membershipBusinessTypeIsSelectable($business_id, $request->membership_business_type_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('membership::lang.disabled_business_type_not_selectable'),
                ]);
            }

            try {
                $member = MembershipMember::where('id', $id)
                    ->where('business_id', $business_id)
                    ->firstOrFail();

                // Store old values for activity log
                $oldValues = $member->toArray();

                // Parse dates (accepts "23 Jan 2026" format)
                $date_joined = !empty($request->date_joined) ? \Carbon\Carbon::parse($request->date_joined)->format('Y-m-d') : null;
                $date_of_birth = !empty($request->date_of_birth) ? \Carbon\Carbon::parse($request->date_of_birth)->format('Y-m-d') : null;
                $renewal_date = $this->calculateRenewalDate(
                    $date_joined,
                    $request->renewal_period,
                    $request->renewal_cycles
                );

                $newData = [
                    'member_name'                 => $request->member_name,
                    'member_name_other'          => $request->member_name_other,
                    'title'                       => $request->title,
                    'member_address'             => $request->member_address,
                    'date_joined'                 => $date_joined,
                    'nic_no'                      => $request->nic_no,
                    'date_of_birth'               => $date_of_birth,
                    'gender'                      => $request->gender,
                    'membership_business_type_id' => $request->membership_business_type_id,
                    'membership_type_id'          => $request->membership_type_id,
                    'no_of_shares'                => $request->no_of_shares ?? 0,
                    'total_share_value'           => $request->total_share_value ?? 0,
                    'membership_status_id'        => $request->membership_status_id,
                    'renewal_period'              => $request->renewal_period,
                    'renewal_cycles'              => $request->renewal_period ? $request->renewal_cycles : null,
                    'renewal_date'                => $renewal_date,
                    'registration_renewal_amount' => $request->registration_renewal_amount,
                    'default_mobile_number'       => $request->default_mobile_number,
                    'other_mobile_numbers'        => $request->other_mobile_numbers,
                ];

                $newData = $this->filterMembershipMemberData($newData);

                $shouldRegenerateQr = $member->member_name !== ($newData['member_name'] ?? $member->member_name)
                    || $member->default_mobile_number !== ($newData['default_mobile_number'] ?? $member->default_mobile_number)
                    || (string) $member->date_joined !== (string) ($newData['date_joined'] ?? $member->date_joined);

                $member->update($newData);

                // Update the associated Contact record
                if (!empty($member->contact_id)) {
                    $contactUpdate = [];
                    $associatedContact = Contact::find($member->contact_id);
                    if ($associatedContact) {
                        if ($associatedContact->name !== $request->member_name) {
                            $contactUpdate['name'] = $request->member_name;
                        }
                        if ($associatedContact->mobile !== $request->default_mobile_number) {
                            $contactUpdate['mobile'] = $request->default_mobile_number;
                        }
                        if ($associatedContact->crm_source !== 'membership') {
                            $contactUpdate['crm_source'] = 'membership';
                        }
                        if (!empty($contactUpdate)) {
                            $associatedContact->update($contactUpdate);
                        }
                    }
                }

                // Log activity manually using direct Activity model (same pattern as Contact module)
                $freshMember = $member->fresh();
                $activity = new Activity();
                $activity->log_name = 'membership_member';
                $activity->description = 'updated';
                $activity->subject_type = 'Modules\Membership\Entities\MembershipMember';
                $activity->subject_id = $member->id;
                $activity->causer_type = 'App\User';
                $activity->causer_id = auth()->user()->id;
                $activity->properties = json_encode([
                    'old' => $oldValues,
                    'attributes' => $freshMember->toArray()
                ]);
                $activity->created_at = now();
                $activity->updated_at = now();
                $activity->save();

                // Regenerate QR Code with all member details
                if ($shouldRegenerateQr && !empty($member->qr_code_path)) {
                    $this->tryGenerateMemberQrCode($member, $member->qr_code_path);
                }

                $output = [
                    'success' => true,
                    'msg'     => __('membership::lang.member_updated_successfully'),
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            return response()->json($output);
        }
    }

    public function destroyMember($id)
    {
        // Check permission
        if (!auth()->user()->can('edit_member')) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.unauthorized_action'),
            ]);
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $member = MembershipMember::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Store member data for activity log before deletion
            $memberData = $member->toArray();

            // Delete QR code file
            if ($member->qr_code_path && file_exists(public_path('uploads/' . $member->qr_code_path))) {
                unlink(public_path('uploads/' . $member->qr_code_path));
            }

            // Log activity before deletion using direct Activity model (same pattern as Contact module)
            $activity = new Activity();
            $activity->log_name = 'membership_member';
            $activity->description = 'deleted';
            $activity->subject_type = 'Modules\Membership\Entities\MembershipMember';
            $activity->subject_id = $member->id;
            $activity->causer_type = 'App\User';
            $activity->causer_id = auth()->user()->id;
            $activity->properties = json_encode([
                'old' => $memberData,
                'attributes' => $memberData
            ]);
            $activity->created_at = now();
            $activity->updated_at = now();
            $activity->save();

            $member->delete();

            $output = [
                'success' => true,
                'msg'     => __('membership::lang.member_deleted_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    public function storeMember(Request $request)
    {
        if ($request->ajax()) {
            
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'membership_setting_id'       => 'required|exists:membership_settings,id',
                'member_name'                 => 'required|string|max:255',
                'member_name_other'          => 'nullable|string|max:255',
                'membership_business_type_id' => 'required|exists:membership_business_types,id',
                'default_mobile_number'       => 'required|string|max:20',
                'other_mobile_numbers'        => 'nullable|string',
                'title'                       => 'nullable|string|max:20',
                'member_address'             => 'nullable|string',
                'date_joined'                 => 'nullable|date',
                'nic_no'                      => 'nullable|string|max:50',
                'date_of_birth'               => 'nullable|date',
                'gender'                      => 'nullable|in:Male,Female',
                'membership_code'             => 'nullable|string|max:100',
                'membership_type_id'          => 'nullable|exists:membership_types,id',
                'no_of_shares'                => 'nullable|integer|min:0',
                'total_share_value'           => 'nullable|numeric|min:0',
                'membership_status_id'        => 'nullable|exists:membership_statuses,id',
                'renewal_period'              => 'nullable|in:days,weeks,months,years',
                'renewal_cycles'              => 'nullable|required_with:renewal_period|integer|min:1',
                'renewal_date'                => 'nullable|date',
                'registration_renewal_amount' => 'nullable|numeric|min:0',
            ]);

            if (! $this->membershipBusinessTypeIsSelectable($business_id, $request->membership_business_type_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('membership::lang.disabled_business_type_not_selectable'),
                ]);
            }

            try {
                $setting = MembershipSetting::where('id', $request->membership_setting_id)
                    ->where('business_id', $business_id)
                    ->first();

                if (! $setting) {
                    return response()->json([
                        'success' => false,
                        'msg'     => __('membership::lang.invalid_region_selected'),
                    ]);
                }

                $memberNumber = $this->generateMemberNumber($business_id, $request->membership_setting_id);

                $contact = Contact::firstOrCreate(
                    [
                        'mobile'      => $request->default_mobile_number,
                        'business_id' => $business_id,
                    ],
                    [
                        'type'                  => 'customer',
                        'name'                  => $request->member_name,
                        'is_default'            => 0,
                        'active'                => 1,
                        'contact_status'        => 'active',
                        'opening_balance'       => 0,
                        'total_rp'              => 0,
                        'total_rp_used'         => 0,
                        'total_rp_expired'      => 0,
                        'created_by'            => auth()->id(),
                        'notification_contacts' => '',
                        'crm_source'            => 'membership',
                    ]
                );

                // Update contact name if it was an existing contact or ensure crm_source is membership
                if ($contact->name !== $request->member_name || $contact->crm_source !== 'membership') {
                    $contact->update([
                        'name'       => $request->member_name,
                        'active'     => 1,
                        'crm_source' => 'membership',
                    ]);
                }

                // Parse dates (accepts "23 Jan 2026" format)
                $date_joined = !empty($request->date_joined) ? \Carbon\Carbon::parse($request->date_joined)->format('Y-m-d') : null;
                $date_of_birth = !empty($request->date_of_birth) ? \Carbon\Carbon::parse($request->date_of_birth)->format('Y-m-d') : null;
                $renewal_date = $this->calculateRenewalDate(
                    $date_joined,
                    $request->renewal_period,
                    $request->renewal_cycles
                );

                $memberData = [
                    'business_id'                 => $business_id,
                    'membership_setting_id'       => $request->membership_setting_id,
                    'region'                      => $setting->region,
                    'contact_id'                  => $contact->id,
                    'member_number'               => $memberNumber,
                    'membership_code'             => $request->membership_code ?? $memberNumber,
                    'member_name'                 => $request->member_name,
                    'member_name_other'          => $request->member_name_other,
                    'title'                       => $request->title,
                    'member_address'             => $request->member_address,
                    'date_joined'                 => $date_joined,
                    'nic_no'                      => $request->nic_no,
                    'date_of_birth'               => $date_of_birth,
                    'gender'                      => $request->gender,
                    'membership_business_type_id' => $request->membership_business_type_id,
                    'membership_type_id'          => $request->membership_type_id,
                    'no_of_shares'                => $request->no_of_shares ?? 0,
                    'total_share_value'           => $request->total_share_value ?? 0,
                    'membership_status_id'        => $request->membership_status_id,
                    'renewal_period'              => $request->renewal_period,
                    'renewal_cycles'              => $request->renewal_period ? $request->renewal_cycles : null,
                    'renewal_date'                => $renewal_date,
                    'registration_renewal_amount' => $request->registration_renewal_amount,
                    'default_mobile_number'       => $request->default_mobile_number,
                    'other_mobile_numbers'        => $request->other_mobile_numbers,
                    'created_by'                  => auth()->id(),
                ];

                $member = MembershipMember::create($this->filterMembershipMemberData($memberData));

                $qrCodePath = 'qrcodes/' . $memberNumber . '.png';
                $this->tryGenerateMemberQrCode($member, $qrCodePath);

                // Card is auto-generated when member is created (can be printed via printCard method)

                $output = [
                    'success' => true,
                    'msg'     => __('membership::lang.member_created_successfully'),
                    'redirect' => action([\Modules\Membership\Http\Controllers\MembershipController::class, 'getMembers']),
                ];
            } catch (\Exception $e) {
                \Log::emergency("MembershipController storeMember - File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
                \Log::emergency("MembershipController storeMember - Stack trace: " . $e->getTraceAsString());
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            return response()->json($output);
        }
    }

    /**
     * Print membership card
     */
    public function printCard($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $member = MembershipMember::where('id', $id)
            ->where('business_id', $business_id)
            ->with(['businessType', 'createdBy'])
            ->firstOrFail();

        $business = Business::where('id', $business_id)->first();
        
        // Get latest card settings
        $cardSetting = MembershipCardSetting::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get latest active signature (only this one is used for Add/Print cards)
        $signature = MembershipSignature::where('business_id', $business_id)
            ->where('is_active', 1)
            ->first();

        // Get membership setting for region
        $membershipSetting = MembershipSetting::where('id', $member->membership_setting_id)
            ->first();

        return view('membership::partials.membership_card', compact(
            'member',
            'business',
            'cardSetting',
            'signature',
            'membershipSetting'
        ));
    }

    public function getNextMemberNumber(Request $request)
    {
        $business_id           = request()->session()->get('user.business_id');
        $membership_setting_id = $request->input('membership_setting_id');

        if (! $membership_setting_id) {
            return response()->json(['member_number' => '']);
        }

        $setting = MembershipSetting::where('id', $membership_setting_id)
            ->where('business_id', $business_id)
            ->first();

        if (! $setting) {
            return response()->json(['member_number' => '']);
        }

        $memberNumber = $this->buildNextMemberNumber($setting, false);

        return response()->json(['member_number' => $memberNumber]);
    }

    public function viewMemberLedger($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $member      = MembershipMember::where('membership_members.id', $id)
            ->where('membership_members.business_id', $business_id)
            ->firstOrFail();
        $contact             = null;
        $transaction_amounts = [];
        if (! empty($member->contact_id)) {
            $contact             = Contact::where('contacts.id', $member->contact_id)->first();
            $transaction_amounts = ContactLedger::where('contact_id', $contact->id)->distinct('amount')->pluck('amount');
        }
        return view('membership::partials.member_ledger', compact('member', 'transaction_amounts', 'contact'));
    }
    public function getMemberLedger($id)
    {
        Log::info('getMemberLedger request id: ' . $id);
        $business_id         = request()->session()->get('user.business_id');
        $start_date          = request()->start_date;
        $end_date            = request()->end_date;
        $transaction_amounts = request()->transaction_amount;
        $transaction_type    = request()->transaction_type;

        $member = MembershipMember::where('membership_members.id', $id)
            ->where('membership_members.business_id', $business_id)
            ->firstOrFail();
        $contact = Contact::where('contacts.id', $member->contact_id)->first();

        if (! $contact) {
            Log::error('No contact found for member ID: ' . $id);
            return response()->json([
                'html'    => '<p>No contact found for this member.</p>',
                'amounts' => [],
            ]);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);
        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        // Calculate account summary using the new service
        $accountSummary = $this->memberLedgerSummaryService->calculateAccountSummary(
            $id,
            $business_id,
            $start_date,
            $end_date
        );

        // Calculate initial point balance before start_date
        $initialPointBalance = 0;
        $lastPointBeforeStart = \Modules\Membership\Entities\MembershipPoint::where('business_id', $business_id)
            ->where('member_id', $member->id)
            ->whereDate('date', '<', $start_date)
            ->orderBy('date', 'desc')
            ->orderBy('form_number', 'desc')
            ->value('point_balance');
        
        if ($lastPointBeforeStart !== null) {
            $initialPointBalance = $lastPointBeforeStart;
        }

        $processTransactions = function ($transactions) {
            return $transactions;
        };
        
        // Use calculated values from the service
        $ledger_details['beginning_balance'] = $accountSummary['opening_balance'];
        $ledger_details['initial_point_balance'] = $initialPointBalance;
        $ledger_details['total_sales'] = $accountSummary['total_sales'];
        $ledger_details['total_paid'] = $accountSummary['total_paid'];
        $ledger_details['total_earned_points'] = $accountSummary['total_points_earned'];
        $ledger_details['total_redeemed_points'] = $accountSummary['total_points_redeemed'];
        $ledger_details['final_point_balance'] = $accountSummary['current_points_balance'];
        
        $ledger_transactions = $this->contactUtil->getCustomerLedger($member->contact_id, $business_id, $start_date, $end_date)
            ->sortBy([
                ['date', 'asc'],
                ['created_at', 'asc'],
            ]);
        $ledger_transactions = $processTransactions($ledger_transactions);
        $amts                = array_unique($ledger_transactions->pluck('amount')->toArray());

        if (request()->input('action') == 'pdf') {
            Log::info('Generating PDF for customer ledger');
            $for_pdf = true;
            $html    = view('membership::partials.ledger_detail')
                ->with(compact('ledger_details', 'contact', 'for_pdf', 'ledger_transactions', 'business_details', 'location_details', 'start_date', 'end_date', 'transaction_amounts', 'transaction_type', 'member', 'accountSummary'))
                ->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        }

        if (request()->input('action') == 'print') {
            Log::info('Printing customer ledger');
            $for_pdf = true;
            return view('membership::partials.ledger_detail')
                ->with(compact('ledger_details', 'contact', 'for_pdf', 'ledger_transactions', 'business_details', 'location_details', 'start_date', 'end_date', 'transaction_amounts', 'transaction_type', 'member', 'accountSummary'))
                ->render();
        }

        return response()->json([
            'html'    => view('membership::partials.ledger_detail')
                ->with(compact('ledger_details', 'contact', 'ledger_transactions', 'business_details', 'location_details', 'start_date', 'end_date', 'transaction_amounts', 'transaction_type', 'member', 'accountSummary'))
                ->render(),
            'amounts' => $amts,
        ]);
    }

    public function searchMembershipTypes(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $search = $request->get('search', '');

            $types = MembershipType::where(function($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                          ->orWhere('business_id', 0);
                })
                ->where('type_name', 'like', '%' . $search . '%')
                ->select('id', 'type_name')
                ->orderBy('type_name')
                ->limit(20)
                ->get();

            return response()->json($types);
        }
    }

    public function storeMembershipType(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'type_name' => 'required|string|max:255',
            ]);

            try {
                $type = MembershipType::create([
                    'business_id' => $business_id,
                    'type_name' => $request->type_name,
                    'created_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'id' => $type->id,
                    'type_name' => $type->type_name,
                    'msg' => __('membership::lang.membership_type_created_successfully'),
                ], 200);
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ], 500);
            }
        }
    }

    public function searchMembershipStatuses(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $search = $request->get('search', '');

            $statuses = MembershipStatus::where(function($query) use ($business_id) {
                    $query->where('business_id', $business_id)
                          ->orWhere('business_id', 0);
                })
                ->where('status_name', 'like', '%' . $search . '%')
                ->select('id', 'status_name')
                ->orderBy('status_name')
                ->limit(20)
                ->get();

            return response()->json($statuses);
        }
    }

    public function storeMembershipStatus(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'status_name' => 'required|string|max:255',
            ]);

            try {
                $status = MembershipStatus::create([
                    'business_id' => $business_id,
                    'status_name' => $request->status_name,
                    'created_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'id' => $status->id,
                    'status_name' => $status->status_name,
                    'msg' => __('membership::lang.membership_status_created_successfully'),
                ], 200);
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ], 500);
            }
        }
    }

    /**
     * Search members for dropdown (used in dividends form)
     */
    public function searchMembers(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $search = $request->get('search', '') ?: $request->get('term', '');

        $query = MembershipMember::where('business_id', $business_id);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('member_name', 'like', '%' . $search . '%')
                  ->orWhere('member_number', 'like', '%' . $search . '%')
                  ->orWhere('membership_code', 'like', '%' . $search . '%');
            });
        }
        
        $members = $query->limit(50)
            ->get(['id', 'member_name', 'member_number', 'membership_code']);

        return response()->json([
            'members' => $members->map(function($member) {
                return [
                    'id' => $member->id,
                    'member_name' => $member->member_name,
                    'member_number' => $member->member_number ?? $member->membership_code,
                ];
            })
        ]);
    }
}

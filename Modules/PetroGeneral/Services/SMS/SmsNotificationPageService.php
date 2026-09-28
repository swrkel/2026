<?php

namespace Modules\PetroGeneral\Services\SMS;

use Modules\PetroGeneral\Entities\PetroNotificationTemplate;
use Modules\PetroGeneral\Entities\PetroWhatsAppTemplate;

class SmsNotificationPageService
{
    /**
     * Build all data required by the combined Petro SMS Notifications page.
     *
     * The combined page renders SMS and WhatsApp template forms directly.  The
     * legacy template controllers still remain available for their historical
     * resource URLs, but this page must receive its own template data instead of
     * including a full-page Blade view that expects an undefined $notifications
     * variable.
     */
    public function getIndexData(): array
    {
        $businessId = (int) request()->session()->get('user.business_id');
        $activeTab = request()->get('tab', 'sms_templates');

        if (! in_array($activeTab, ['sms_templates', 'whatsapp_templates'], true)) {
            $activeTab = 'sms_templates';
        }

        return [
            'active_tab' => $activeTab,
            'sms_notifications' => $this->hydrateTemplates(
                PetroNotificationTemplate::notifications(),
                PetroNotificationTemplate::class,
                $businessId
            ),
            'whatsapp_notifications' => $this->hydrateTemplates(
                PetroWhatsAppTemplate::notifications(),
                PetroWhatsAppTemplate::class,
                $businessId
            ),
        ];
    }

    /**
     * Merge the default template definitions with this business's saved values.
     */
    private function hydrateTemplates(array $templates, string $modelClass, int $businessId): array
    {
        foreach ($templates as $key => $template) {
            $saved = $modelClass::getTemplate($businessId, $key);

            $templates[$key]['sms_body'] = $saved['sms_body'] ?? '';
            $templates[$key]['auto_send_sms'] = ! empty($saved['auto_send_sms']) ? 1 : 0;
            $templates[$key]['template_for'] = $saved['template_for'] ?? $key;
            $templates[$key]['phone_nos'] = $saved['phone_nos'] ?? '';
        }

        return $templates;
    }
}

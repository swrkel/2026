<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewMessageTemplate;
class LeadsNewMessagingService {
    public function renderTemplate(LeadsNewMessageTemplate $template, array $data=[]): array {
        $subject=$this->replace($template->subject ?? '', $data);
        $body=$this->replace($template->body ?? '', $data);
        return compact('subject','body');
    }
    protected function replace(string $text, array $data): string { foreach($data as $key=>$value){ $text=str_replace('{{'.$key.'}}', (string)$value, $text); } return $text; }
}

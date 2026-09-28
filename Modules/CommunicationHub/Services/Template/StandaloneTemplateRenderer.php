<?php

namespace Modules\CommunicationHub\Services\Template;

class StandaloneTemplateRenderer
{
    public function render(?string $content, array $data = []): string
    {
        $content = (string) $content;
        foreach ($data as $key => $value) {
            $content = str_replace(['{' . $key . '}', '{{' . $key . '}}'], (string) $value, $content);
        }
        return $content;
    }
}

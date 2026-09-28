<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawDocumentTemplate,LawClient,LawMatter};
class TemplateService
{
    public function render(LawDocumentTemplate $template,?LawClient $client=null,?LawMatter $matter=null): string
    {
        $vars=[
            '{{today}}'=>now()->toDateString(),
            '{{client.name}}'=>$client->name??'', '{{client.no}}'=>$client->client_no??'',
            '{{client.address}}'=>$client ? trim(($client->address_line_1??'').' '.($client->address_line_2??'').' '.($client->city??'')) : '',
            '{{matter.no}}'=>$matter->matter_no??'', '{{matter.title}}'=>$matter->title??'', '{{matter.case_no}}'=>$matter->case_no??'',
            '{{matter.court}}'=>$matter && $matter->court ? $matter->court->name : '',
        ];
        $body=(string)$template->body_html;
        $body=preg_replace('/<br\s*\/?\s*>/i',"\n",$body);
        $body=preg_replace('/<\/p\s*>/i',"\n\n",$body);
        $body=html_entity_decode(strip_tags($body),ENT_QUOTES,'UTF-8');
        return nl2br(e(strtr($body,$vars)));
    }
}

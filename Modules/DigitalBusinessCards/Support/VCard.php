<?php

namespace Modules\DigitalBusinessCards\Support;

use Modules\DigitalBusinessCards\Models\Card;

/**
 * Builds an RFC 6350 (vCard 3.0) payload. 3.0 is used deliberately: it is the
 * version iOS Contacts and Android import without complaint.
 */
class VCard
{
    public function __construct(protected Card $card)
    {
    }

    public static function for(Card $card): self
    {
        return new self($card);
    }

    public function filename(): string
    {
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $this->card->fullName());

        return trim($name, '-').'.vcf';
    }

    public function render(): string
    {
        $c     = $this->card;
        $lines = ['BEGIN:VCARD', 'VERSION:3.0'];

        $lines[] = 'N:'.$this->esc($c->last_name).';'.$this->esc($c->first_name).';;;';
        $lines[] = 'FN:'.$this->esc($c->fullName());

        if ($c->company) {
            $lines[] = 'ORG:'.$this->esc($c->company).($c->department ? ';'.$this->esc($c->department) : '');
        }

        if ($c->job_title) {
            $lines[] = 'TITLE:'.$this->esc($c->job_title);
        }

        if ($c->email) {
            $lines[] = 'EMAIL;type=INTERNET;type=WORK:'.$this->esc($c->email);
        }

        if ($c->phone) {
            $lines[] = 'TEL;type=CELL;type=VOICE:'.$this->esc($c->phone);
        }

        if ($c->phone_alt) {
            $lines[] = 'TEL;type=WORK;type=VOICE:'.$this->esc($c->phone_alt);
        }

        if ($c->website) {
            $lines[] = 'URL:'.$this->esc($c->website);
        }

        if ($c->addressLines()) {
            $lines[] = 'ADR;type=WORK:;;'
                .$this->esc((string) $c->address_line).';'
                .$this->esc((string) $c->city).';'
                .$this->esc((string) $c->region).';'
                .$this->esc((string) $c->postal_code).';'
                .$this->esc((string) $c->country);
        }

        if ($c->bio) {
            $lines[] = 'NOTE:'.$this->esc($c->bio);
        }

        foreach ($c->normalisedLinks() as $link) {
            $lines[] = 'URL;type='.$this->esc($link['label']).':'.$this->esc($link['url']);
        }

        if ($photo = $this->photoPayload()) {
            $lines[] = $photo;
        }

        // Round-trip anchor: re-scanning the card always finds the live page.
        $lines[] = 'URL;type=Digital card:'.$this->esc($c->publicUrl());
        $lines[] = 'REV:'.$c->updated_at?->utc()->format('Ymd\THis\Z');
        $lines[] = 'END:VCARD';

        return implode("\r\n", array_filter($lines))."\r\n";
    }

    /** Embeds the avatar so the contact photo survives offline import. */
    protected function photoPayload(): ?string
    {
        if (! $this->card->photo_path) {
            return null;
        }

        $disk = app('filesystem')->disk(config('digital-business-cards.media.disk', 'public'));

        if (! $disk->exists($this->card->photo_path)) {
            return null;
        }

        $bytes = $disk->get($this->card->photo_path);

        // Contacts apps choke on very large embedded photos; link instead.
        if (strlen($bytes) > 512 * 1024) {
            return 'PHOTO;VALUE=URI:'.$this->esc((string) $this->card->photoUrl());
        }

        $type = str_contains(strtolower($this->card->photo_path), '.png') ? 'PNG' : 'JPEG';

        return $this->fold('PHOTO;ENCODING=b;TYPE='.$type.':'.base64_encode($bytes));
    }

    /** vCard lines must not exceed 75 octets; continuations start with a space. */
    protected function fold(string $line): string
    {
        return trim(chunk_split($line, 73, "\r\n "));
    }

    protected function esc(?string $value): string
    {
        return str_replace(
            ["\\", ';', ',', "\r\n", "\n"],
            ['\\\\', '\;', '\,', '\n', '\n'],
            (string) $value
        );
    }
}

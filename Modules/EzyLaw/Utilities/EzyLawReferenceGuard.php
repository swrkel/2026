<?php

namespace Modules\EzyLaw\Utilities;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\EzyLaw\Entities\{
    LawClient,
    LawCourt,
    LawDocument,
    LawInvoice,
    LawMatter,
    LawPracticeArea
};

/**
 * Resolves EzyLaw-owned foreign references through business-scoped models.
 *
 * EzyLaw models carry the ezylaw_business global scope.  Using this utility
 * before persisting relation ids prevents a crafted request from storing an
 * id that belongs to another business in the same tenant database.
 */
class EzyLawReferenceGuard
{
    public static function client($id): ?LawClient
    {
        return self::findOptional(LawClient::class, $id);
    }

    public static function matter($id): ?LawMatter
    {
        return self::findOptional(LawMatter::class, $id);
    }

    public static function court($id): ?LawCourt
    {
        return self::findOptional(LawCourt::class, $id);
    }

    public static function practiceArea($id): ?LawPracticeArea
    {
        return self::findOptional(LawPracticeArea::class, $id);
    }

    public static function invoice($id): ?LawInvoice
    {
        return self::findOptional(LawInvoice::class, $id);
    }

    public static function document($id): ?LawDocument
    {
        return self::findOptional(LawDocument::class, $id);
    }

    public static function matterForClient($matterId, $clientId): ?LawMatter
    {
        if (self::isEmpty($matterId)) {
            return null;
        }

        $client = self::client($clientId);
        if (!$client) {
            throw (new ModelNotFoundException())->setModel(LawClient::class, [(int) $clientId]);
        }

        return LawMatter::whereKey((int) $matterId)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    public static function assertDocumentClient(LawDocument $document, $clientId): LawClient
    {
        $client = self::client($clientId);
        if (!$client) {
            throw (new ModelNotFoundException())->setModel(LawClient::class, [(int) $clientId]);
        }

        if (!empty($document->client_id)) {
            if ((int) $document->client_id !== (int) $client->id) {
                throw (new ModelNotFoundException())->setModel(LawDocument::class, [$document->id]);
            }
            return $client;
        }

        if (!empty($document->matter_id)) {
            LawMatter::whereKey((int) $document->matter_id)
                ->where('client_id', $client->id)
                ->firstOrFail();
            return $client;
        }

        // An unlinked document must not be exposed to a client portal/e-sign
        // recipient merely by submitting a client id in the request.
        throw (new ModelNotFoundException())->setModel(LawDocument::class, [$document->id]);
    }

    private static function findOptional(string $model, $id)
    {
        if (self::isEmpty($id)) {
            return null;
        }

        return $model::findOrFail((int) $id);
    }

    private static function isEmpty($value): bool
    {
        return $value === null || $value === '';
    }
}

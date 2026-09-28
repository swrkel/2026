<?php

namespace Modules\Distribution\Entities\Core;

/**
 * Distribution-owned compatibility model for App\Contact.
 * Keeps Distribution code referencing module-local classes while preserving existing table/relations.
 */
class Contact extends \App\Contact
{
}

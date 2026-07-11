<?php

namespace App\Enums;

/**
 * Standard CRUD operation types. Handlers may accept additional custom types;
 * these cover the common cases and keep type strings out of handler code.
 */
enum SyncOperationType: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}

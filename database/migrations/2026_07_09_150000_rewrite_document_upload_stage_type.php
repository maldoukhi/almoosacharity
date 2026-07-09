<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The dedicated 'document_upload' stage type has been removed: document
 * collection is now expressed by a plain 'approval' stage with
 * documents_required = true (that mechanism already existed). Rewrite any
 * historical stage persisted with type='document_upload' to preserve its
 * intent — a document-collecting approval stage — rather than silently
 * losing the requirement.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('approval_flow_stages')
            ->where('type', 'document_upload')
            ->update([
                'type' => 'approval',
                'documents_required' => true,
            ]);
    }

    public function down(): void
    {
        // Irreversible: the 'document_upload' type no longer exists, and a
        // document-collecting approval stage is a strict superset of the old
        // behaviour, so there is nothing meaningful to restore.
    }
};

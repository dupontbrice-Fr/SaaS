<?php
class Audit {
    public static function log(
        string $action,
        string $entityType,
        string $entityName,
        string $fieldName = null,
        string $oldValue = null,
        string $newValue = null
    ): void {
        if (!Auth::check()) return;

        Database::execute(
            "INSERT INTO audit_logs (org_id, user_email, action, entity_type, entity_name, field_name, old_value, new_value)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                Auth::orgId(),
                Auth::userEmail(),
                $action,
                $entityType,
                $entityName,
                $fieldName,
                $oldValue,
                $newValue,
            ]
        );
    }

    public static function logProductHistory(
        int $productId,
        string $fieldName,
        string $oldValue = null,
        string $newValue = null
    ): void {
        if (!Auth::check()) return;

        Database::execute(
            "INSERT INTO product_history (product_id, org_id, user_email, field_name, old_value, new_value)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $productId,
                Auth::orgId(),
                Auth::userEmail(),
                $fieldName,
                $oldValue,
                $newValue,
            ]
        );
    }
}

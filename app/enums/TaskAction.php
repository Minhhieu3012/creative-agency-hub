<?php
namespace App\Enums;

class TaskAction {
    const CREATE = 'create';
    const ASSIGN = 'assign';
    const REASSIGN = 'reassign';
    const STATUS_CHANGE = 'status_change';
    const UPLOAD = 'upload';
    const COMMENT = 'comment';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const DOWNLOAD = 'download';

    public static function all() {
        return [
            self::CREATE,
            self::ASSIGN,
            self::REASSIGN,
            self::STATUS_CHANGE,
            self::UPLOAD,
            self::COMMENT,
            self::UPDATE,
            self::DELETE,
            self::DOWNLOAD
        ];
    }
}
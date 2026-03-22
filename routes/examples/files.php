<?php

class File {
    public static function all($userId, $filters = [], $columns = [])
    {
        $allowedColumns = ['id', 'created_at', 'description', 'mime_type', 'modify_time', 'orig_name', 'size', 'threads'];
        if (empty($columns)) {
            $columns = $allowedColumns;
        } else {
            foreach ($columns as $col) {
                if (!in_array($col, $allowedColumns)) {
                    // remove invalid column
                    $columns = array_diff($columns, [$col]);
                }
            }
        }

        if (empty($columns)) {
            return [];
        }

        $query = "SELECT " . implode(',', $columns) . " FROM files_view WHERE user = ?";
        $params = [$userId];

        if (!empty($filters['threads'])) {
            $ids = self::getPositionsIdByThreadName($userId, $filters['threads']);
            if (count($ids) === 0) {
                return [];
            }
            $query .= " AND id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
            $params = array_merge($params, $ids);
        }

        if (!empty($filters['threads_id'])) {
            $ids = self::getPositionsIdByThreadId($userId, $filters['threads_id']);
            if (count($ids) === 0) {
                return [];
            }
            $query .= " AND id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
            $params = array_merge($params, $ids);
        }

        if (!empty($filters['orig_name'])) {
            $query .= " AND orig_name LIKE ?";
            $params[] = '%' . $filters['orig_name'] . '%';
        }

        if (isset($filters['mime_type'])) {
            $query .= " AND mime_type = ?";
            $params[] = $filters['mime_type'];
        }

        if (isset($filters['description'])) {
            $query .= " AND description LIKE ?";
            $params[] = '%' . $filters['description'] . '%';
        }

        if (!empty($filters['sizeMin'])) {
            $query .= " AND size >= ?";
            $params[] = $filters['sizeMin'];
        }

        if (!empty($filters['sizeMax'])) {
            $query .= " AND size <= ?";
            $params[] = $filters['sizeMax'];
        }

        if (!empty($filters['dateFrom'])) {
            $query .= " AND date(created_at) >= ?";
            $params[] = $filters['dateFrom'];
        }

        if (!empty($filters['dateTo'])) {
            $query .= " AND date(created_at) <= ?";
            $params[] = $filters['dateTo'];
        }

        return db()->query($query)->bind(...$params)->fetchAll();
    }

    public static function getPositionsIdByThreadId($userId, $threadId)
    {
        if (!empty($threadId)) {
            $positionIds = db()->query("SELECT DISTINCT file FROM files_threads WHERE user = ? AND thread = ?")
                ->bind($userId, $threadId)
                ->fetchAll();

            return array_column($positionIds, 'file');
        }
        return [];
    }

    public static function getPositionsIdByThreadName($userId, $threadName)
    {
        if (!empty($threadName)) {
            $threadIds = Thread::all($userId, ['title' => $threadName], ['id']);
            if (count($threadIds) === 0) {
                return [];
            }
            $threadIds = array_column($threadIds, 'id');
            $positionIds = db()->query("SELECT DISTINCT file FROM files_threads WHERE user = ? AND thread IN (" . implode(',', array_fill(0, count($threadIds), '?')) . ")")
                ->bind($userId, ...$threadIds)
                ->fetchAll();

            return array_column($positionIds, 'file');
        }
        return [];
    }

    public static function find($userId, $id) {
        return db()->query("SELECT id,created_at,description,mime_type,modify_time,orig_name,size,threads FROM files_view WHERE id = ? and user = ?")->bind($id, $userId)->fetch();
    }

    public static function getDownloadData($userId, $id) {
        return db()->query("SELECT curr_name,orig_name FROM files WHERE id = ? and user = ?")->bind($id, $userId)->fetch();
    }

    public static function create($userId, $formData) {
        $db = db()->query("INSERT INTO files (user, curr_name, orig_name, size, mime_type, description, modify_time) VALUES (?, ?, ?, ?, ?, ?, ?)")->bind($userId, $formData['curr_name'], $formData['orig_name'], $formData['size'], $formData['mime_type'], $formData['description'], $formData['modify_time']);
        if ($db->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }
    
    public static function update($userId, $id, $formData) {
        return db()->update('files', $formData, "id = ? AND user = ?", [$id, $userId], ['curr_name', 'orig_name', 'size', 'mime_type', 'description']);
    }

    public static function delete($userId, $id) {
        return db()->delete("files", "id = ? AND user = ?", [$id, $userId]);
    }

    public static function linkThread($userId, $id, $threadId) {
        if (Thread::find($userId, $threadId) === false) {
            return false;
        }
        return db()->query("INSERT INTO files_threads (user, file, thread) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE thread = ?")
            ->bind($userId, $id, $threadId, $threadId)
            ->execute();
    }

    public static function removeLinkThread($userId, $id, $threadId) {
        return db()->query("DELETE FROM files_threads WHERE user = ? AND file = ? AND thread = ?")
            ->bind($userId, $id, $threadId)
            ->execute();
    }

    public static function getCurrentUserFilesSize($userId) {
        $result = db()->query("SELECT SUM(size) as total FROM files WHERE user = ?")->bind($userId)->fetch();
        return $result['total'] ?? 0;
    }
}

function getUserQuota($userId) {
    return 2 ?? null; // in MB, null = unlimited
}

app()->group('/files', function() {
    app()->get('/', function() {
        rejectUnlogged();

        $formData = request()->validate([
            'orig_name' => 'optional|required',
            'mime_type' => 'optional|required',
            'description' => 'optional|required',
            'sizeMin' => 'optional|number',
            'sizeMax' => 'optional|number',
            'dateFrom' => 'optional|date',
            'dateTo' => 'optional|date',
            'threads' => 'optional|required',
            'threads_id' => 'optional|array_number_by_caret',
            // 'page' => 'number',
            // 'limit' => 'number',
        ], false);

        $userId = Auth::id();

        $files = File::all($userId, $formData);

        $maxSpace = getUserQuota($userId); // in MB, null = unlimited
        $usedSpace = File::getCurrentUserFilesSize($userId); // in bytes

        response()->json(['data' => $files, 'quota' => ['maxMb' => $maxSpace, 'usedBytes' => $usedSpace]]);
    });

    app()->get('/(\d+)', function($id) {
        rejectUnlogged();

        $userId = Auth::id();

        $file = File::find($userId, $id);

        if (!$file) {
            response()->json(['info' => 'FILE_NOT_FOUND'], 404)->exit();
        }

        response()->json(['data' => $file]);
    });

    app()->get('/download/(\d+)', function($id) {
        rejectUnlogged();

        $userId = Auth::id();

        $file = File::getDownloadData($userId, $id);

        if (!$file) {
            response()->json(['info' => 'FILE_NOT_FOUND'], 404)->exit();
        }

        if(readUploadedFile($file['curr_name'], $file['orig_name'], $_GET['download'] ?? false)) exit;
        response()->json(['info' => 'ERROR_OCCURED'], 500)->exit();
        // response()->json(['data' => $file]);
    });

    app()->post('/upload', function() {
        rejectUnlogged();

        $userId = Auth::id();

        $formData = request()->validate([
            'description' => 'optional|required',
            'modify_time' => 'optional|datetime',
            'threads' => 'optional|array_number_by_caret',
        ]);

        // response()->json($_FILES)->exit();
        // response()->json(request()->files())->exit();
        // response()->json($formData)->exit();

        $filesData = [
            ['name' => 'file', 'multiple' => false, 'prefix' => $userId, 'maxSize' => 1024 * 1024 * 1024], // 1024 MB = 1 GB
        ];
        $files = parseRequestFiles($filesData);

        if (count($files) === 0) {
            response()->json(['info' => 'ERROR_OCCURED'], 500)->exit();
        }

        // check if quota exceeded
        $maxSpace = getUserQuota($userId); // in MB, null = unlimited
        if ($maxSpace !== null) {
            $usedSpace = File::getCurrentUserFilesSize($userId); // in bytes
            if (($usedSpace + $files[0]['server_size']) > ($maxSpace * 1024 * 1024)) {
                deleteRequestFiles($files);
                response()->json(['info' => 'QUOTA_EXCEEDED'], 400)->exit();
            }
        }

        // response()->json($files)->exit();

        $formData = array_merge($formData, [
            'curr_name' => $files[0]['newName'],
            'orig_name' => $files[0]['origName'],
            'size' => $files[0]['server_size'],
            'mime_type' => $files[0]['server_type'],
        ]);

        // replace \ / : * ? " < > | with _ in orig_name
        $formData['orig_name'] = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $formData['orig_name']);
        
        if(moveRequestFiles($files)) {
            // File move success
            $id = File::create($userId, $formData);

            if($id !== false) {
                if(!empty($formData['threads'])) {
                    $threadIds = explode('^', $formData['threads']);
                    foreach($threadIds as $threadId) {
                        File::linkThread($userId, $id, $threadId);
                    }
                }

                // File move success
                response()->json(['info' => 'FILE_CREATED', 'id' => $id], 201)->exit();
            }
        }

        // File move failed - delete record from DB
        // File::delete($userId, $id);

        // File move failed - delete temp files
        deleteRequestFiles($files);
        response()->json(['info' => 'FILE_UPLOAD_FAILED'], 500)->exit();

    });

    app()->post('/(\d+)', function($id) {
        rejectUnlogged();

        $formData = request()->validate([
            'orig_name' => 'filename',
            'description' => 'optional|required|max:1024',
        ]);

        // replace \ / : * ? " < > | with _ in orig_name
        if(isset($formData['orig_name'])) {
            $formData['orig_name'] = preg_replace('/[\\\\\/:\*\?"<>\|]/', '_', $formData['orig_name']);
        }

        $userId = Auth::id();

        File::update($userId, $id, $formData);

        response()->json(['info' => 'FILE_UPDATED', 'id' => $id]);
    });

    app()->delete('/(\d+)', function($id) {
        rejectUnlogged();

        $userId = Auth::id();

        $file = File::getDownloadData($userId, $id);
        if(!$file) {
            response()->json(['info' => 'FILE_NOT_FOUND'], 404)->exit();
        }

        if(@deleteUploadedFile($file['curr_name'])) {
            // File delete success
            File::delete($userId, $id);
            response()->json(['info' => 'FILE_DELETED', 'id' => $id])->exit();
        }

        response()->json(['info' => 'ERROR_OCCURED'], 500)->exit();
    });

    app()->post('/(\d+)/link/thread/(\d+)', function($id, $threadId) {
        rejectUnlogged();

        $userId = Auth::id();

        if(File::linkThread($userId, $id, $threadId))
            response()->json(['info' => 'THREAD_LINKED', 'id' => $id, 'threadId' => $threadId]);
        else
            response()->json(['info' => 'THREAD_LINK_FAILED'], 500);
    });

    app()->delete('/(\d+)/link/thread/(\d+)', function($id, $threadId) {
        rejectUnlogged();

        $userId = Auth::id();

        if(File::removeLinkThread($userId, $id, $threadId))
            response()->json(['info' => 'THREAD_UNLINKED', 'id' => $id, 'threadId' => $threadId]);
        else
            response()->json(['info' => 'THREAD_UNLINK_FAILED'], 500);
    });

    app()->get('/quota', function() {
        rejectUnlogged();

        $userId = Auth::id();

        $maxSpace = getUserQuota($userId); // in MB, null = unlimited
        $usedSpace = File::getCurrentUserFilesSize($userId); // in bytes

        response()->json(['data' => ['maxMb' => $maxSpace, 'usedBytes' => $usedSpace]]);
    });
});
<?php

class Thread {
    public static function all($userId, $filters = [], $columns = []) {
        $allowedColumns = ['id', 'active', 'color', 'title', 'created_at'];
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

        $query = "SELECT " . implode(',', $columns) . " FROM threads WHERE user = ?";
        $params = [$userId];

        if (!empty($filters['title'])) {
            $query .= " AND title LIKE ?";
            $params[] = '%' . $filters['title'] . '%';
        }

        if (isset($filters['state'])) {
            $query .= " AND active = ?";
            $params[] = $filters['state'] ? 1 : 0;
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


    public static function pickList($userId) {
        return self::all($userId, [], ['id', 'active', 'color', 'title']);
    }

    public static function find($userId, $id) {
        return db()->query("SELECT id,active,color,title,created_at FROM threads WHERE id = ? and user = ?")->bind($id, $userId)->fetch();
    }

    public static function create($userId, $formData) {
        $db = db()->query("INSERT INTO threads (user, title, color, active) VALUES (?, ?, ?, ?)")->bind($userId, $formData['title'], $formData['color'], ($formData['active'] == 'true' || $formData['active'] == 1 ? 1 : 0));
        if ($db->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function update($userId, $id, $formData) {
        return db()->update('threads', $formData, "id = ? AND user = ?", [$id, $userId], ['title', 'color', 'active']);
    }

    public static function delete($userId, $id) {
        return db()->delete("threads", "id = ? AND user = ?", [$id, $userId]);
    }
}

app()->group('/threads', function() {
    app()->get('/', function() {
        rejectUnlogged();

        $formData = request()->validate([
            'title' => 'optional|required',
            'state' => 'optional|boolean',
            'dateFrom' => 'optional|date',
            'dateTo' => 'optional|date',
            // 'page' => 'number',
            // 'limit' => 'number',
        ], false);

        $userId = Auth::id();
        
        $threads = Thread::all($userId, $formData);

        response()->json(['data' => $threads]);
    });
    app()->get('/pickList', function() {
        rejectUnlogged();

        $userId = Auth::id();

        $threads = Thread::pickList($userId);

        response()->json(['data' => $threads]);
    });

    app()->get('/(\d+)', function($id) {
        rejectUnlogged();

        $userId = Auth::id();

        $thread = Thread::find($userId, $id);

        response()->json(['data' => $thread]);
    });

    app()->post('/new', function() {
        rejectUnlogged();

        $formData = request()->validate([
            'title' => 'any|max:255',
            'color' => 'optional|hexcolor',
            'active' => 'boolean',
        ]);

        if($formData['color']) {
            $formData['color'] = ltrim($formData['color'], '#');
        }

        $userId = Auth::id();

        $threadId = Thread::create($userId, $formData);

        if(!$threadId) {
            response()->json(['info' => 'THREAD_CREATION_FAILED'], 500);
            return;
        }
        response()->json(['info' => 'THREAD_CREATED', 'id' => $threadId], 201);
    });

    app()->post('/(\d+)', function($id) {
        rejectUnlogged();

        $formData = request()->validate([
            'title' => 'any|max:255',
            'color' => 'optional|hexcolor',
            'active' => 'boolean',
        ]);

        if($formData['color']) {
            $formData['color'] = ltrim($formData['color'], '#');
        }

        $userId = Auth::id();

        Thread::update($userId, $id, $formData);

        response()->json(['info' => 'THREAD_UPDATED', 'id' => $id]);
    });

    app()->delete('/(\d+)', function($id) {
        rejectUnlogged();

        $userId = Auth::id();

        if(Thread::delete($userId, $id))
            response()->json(['info' => 'THREAD_DELETED', 'id' => $id]);
        else
            response()->json(['info' => 'ERROR_OCCURED'], 500);
    });
});
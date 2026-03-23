<?php

class Auth
{
    public static function check()
    {
        return isset($_SESSION['user']);
    }

    public static function logout()
    {
        $_SESSION['user'] = null;
    }

    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function login($username, $password)
    {
        $result = db()->query("SELECT id,username,email,email_verified,password_hash FROM users WHERE username = ? LIMIT 1")->bind($username)->fetch();

        if (empty($result)) {
            return null;
        }

        if (!password_verify($password, $result['password_hash'])) {
            return false;
        }

        if ($result['email_verified'] != null) {
            return 0; // Konto nie zostało potwierdzone
        }

        unset($result['email_verified']); // Usuń token aktywacji z danych użytkownika
        unset($result['password_hash']); // Usuń hash hasła z danych użytkownika
        $_SESSION['user'] = $result;

        return true;
    }

    public static function register($username, $password, $email)
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $mailHash = bin2hex(random_bytes(16));

        try {
            db()->query("INSERT INTO users (username, password_hash, email, email_verified) VALUES (?, ?, ?, ?)")
                ->bind($username, $hashedPassword, $email, $mailHash)
                ->execute();
            return $mailHash;
        } catch (PDOException $e) {
            // Obsługa błędu
            // response()->json($e)->exit();
            return false;
        }
    }

    public static function passwordChangeGenHash($userId, $newPassword)
    {
        // check if there is already a non-used hash for this user
        $existing = db()->query("SELECT id FROM users_mails WHERE user = ? AND used = 0 AND expiry_at > NOW() LIMIT 1")
            ->bind($userId)
            ->fetch();

        if (!empty($existing)) {
            return null; // There is already a pending password change request
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $mailHash = bin2hex(random_bytes(16));
        $expiryAt = date('Y-m-d H:i:s', strtotime('+6 hour'));

        try {
            db()->query("INSERT INTO users_mails (user,mail_hash,new_password_hash,expiry_at) VALUES (?, ?, ?, ?)")
                ->bind($userId, $mailHash, $hashedPassword, $expiryAt)
                ->execute();
            return true;
        } catch (PDOException $e) {
            // Obsługa błędu
            // response()->json($e)->exit();
            return false;
        }
    }

    public static function confirmAccountHash($mailHash)
    {
        try {
            $result = db()->update(
                'users',
                ['email_verified' => null],
                'email_verified = ?',
                $mailHash
            );

            return $result === 1;
        } catch (PDOException $e) {
            // Obsługa błędu
            // response()->json($e)->exit();
            return false;
        }
    }

    public static function confirmChangePassswordHash($mailHash)
    {
        try {
            $result = db()->query("SELECT id,user,new_password_hash,expiry_at FROM users_mails WHERE mail_hash = ? AND used = 0 LIMIT 1")
                ->bind($mailHash)
                ->fetch();

            if (empty($result)) {
                return false;
            }

            if (strtotime($result['expiry_at']) < time()) {
                return false;
            }

            db()->update('users', ['password_hash' => $result['new_password_hash']], 'id = ?', [$result['user']]);

            db()->update('users_mails', ['used' => 1], 'id = ?', [$result['id']]);

            return true;
        } catch (PDOException $e) {
            // Obsługa błędu
            // response()->json($e)->exit();
            return false;
        }
    }

    public static function usernameExists($username)
    {
        $result = db()->query("SELECT id FROM users WHERE username = ? LIMIT 1")
            ->bind($username)
            ->fetch();
        return !empty($result);
    }

    public static function emailExists($email)
    {
        $result = db()->query("SELECT id FROM users WHERE email = ? LIMIT 1")
            ->bind($email)
            ->fetch();
        return !empty($result);
    }

    public static function getIdByUsername($username)
    {
        $result = db()->query("SELECT id FROM users WHERE username = ? LIMIT 1")
            ->bind($username)
            ->fetch();
        return $result['id'] ?? null;
    }

    public static function getIdByEmail($email)
    {
        $result = db()->query("SELECT id FROM users WHERE email = ? LIMIT 1")
            ->bind($email)
            ->fetch();
        return $result['id'] ?? null;
    }
}

app()->group('/auth', function () {

    app()->get('/me', function () {
        if (Auth::check()) {
            response()->json(['logged_in' => true, 'user' => Auth::user()]);
        } else {
            response()->json(['logged_in' => false]);
        }
    });

    app()->get('/logout', function () {
        Auth::logout();
        response()->json(['info' => 'LOGGED_OUT_SUCCESSFULLY']);
    });

    app()->post('/register', function () {
        if (Auth::check()) {
            response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
            return;
        }

        $data = request()->validate([
            'username' => 'any',
            'password' => 'any|min:6',
            'passwordConfirm' => 'any|min:6',
            'email' => 'email'
        ]);

        if (!$data['username'] || !$data['password'] || !$data['email']) {
            response()->json(['info' => 'USERNAME_PASSWORD_EMAIL_REQUIRED'], 400);
            return;
        }

        if ($data['password'] !== $data['passwordConfirm']) {
            response()->json(['info' => 'PASSWORDS_DO_NOT_MATCH'], 400)->exit();
            return;
        }

        if (Auth::usernameExists($data['username'])) {
            response()->json(['info' => 'USERNAME_ALREADY_TAKEN'], 400);
            return;
        }

        if (Auth::emailExists($data['email'])) {
            response()->json(['info' => 'EMAIL_ALREADY_TAKEN'], 400);
            return;
        }

        $mailHash = Auth::register($data['username'], $data['password'], $data['email']);
        if ($mailHash === false) {
            response()->json(['info' => 'ERROR_OCCURED'], 400);
            return;
        }

        // send email with confirmation link
        // $confirmLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/confirm/account/$mailHash/";
        $confirmLink = CONFIRM_EMAIL_URL . "account/$mailHash/";
        $subject = "Account Confirmation";
        $message = "To confirm your account, please click the following link:\n\n$confirmLink\n\nIf you did not register, please ignore this email.";
        $headers = "From: " . EMAIL_FROM;

        mail($data['email'], $subject, $message, $headers);

        response()->json(['info' => 'USER_CREATED_SUCCESSFULLY']);
    });

    app()->post('/login', function () {
        if (Auth::check()) {
            response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
            return;
        }

        $username = request()->post('username', null);
        $password = request()->post('password', null);

        if (!$username || !$password) {
            response()->json(['fields' => ['username' => 'FIELD_REQUIRED', 'password' => 'FIELD_REQUIRED']], 400);
            return;
        }

        $response = Auth::login($username, $password);

        if ($response === null || $response === false) {
            response()->json(['info' => 'INVALID_USERNAME_OR_PASSWORD'], 401);
            return;
        } else if ($response === 0) {
            response()->json(['info' => 'ACCOUNT_NOT_CONFIRMED'], 403);
            return;
        }

        response()->json(['info' => 'LOGGED_IN_SUCCESSFULLY', 'user' => Auth::user()]);
    });

    app()->post('/change-password', function () {
        if (Auth::check()) {
            response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
            return;
        }

        $email = request()->post('email', null);
        $password = request()->post('password', null);
        $passwordConfirm = request()->post('passwordConfirm', null);

        if (!$email || !$password) {
            response()->json(['fields' => ['email' => 'FIELD_REQUIRED', 'password' => 'FIELD_REQUIRED']], 400);
            return;
        }

        if ($password !== $passwordConfirm) {
            response()->json(['info' => 'PASSWORDS_DO_NOT_MATCH'], 400)->exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response()->json(['fields' => ['email' => 'FIELD_INVALID']], 400)->exit();
        }

        $userId = Auth::getIdByEmail($email);
        if (!$userId) {
            response()->json(['info' => 'EMAIL_NOT_FOUND'], 404)->exit();
        }

        $response = Auth::passwordChangeGenHash($userId, $password);

        if ($response !== false) {
            if ($response === true) {
                // Send email with the token
                $mailHash = db()->query("SELECT mail_hash FROM users_mails WHERE user = ? AND used = 0 ORDER BY created_at DESC LIMIT 1")
                    ->bind($userId)
                    ->fetch()['mail_hash'] ?? null;

                if ($mailHash) {
                    // $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/confirm/change-password/$mailHash/";
                    $resetLink = CONFIRM_EMAIL_URL . "change-password/$mailHash/";

                    $subject = "Password Reset Request";
                    $message = "To reset your password, please click the following link:\n\n$resetLink\n\nIf you did not request a password reset, please ignore this email.";
                    $headers = "From: " . EMAIL_FROM;

                    // Use mail() function or any mail library to send the email
                    mail($email, $subject, $message, $headers);
                }
            }

            // Always return the same response to prevent email enumeration
            response()->json(['info' => 'PASSWORD_RESET_EMAIL_SENT']);
        } else {
            response()->json(['info' => 'ERROR_OCCURED'], 500);
        }
    });

    app()->get('/confirm/account/{token}', function ($token) {
        // if (Auth::check()) {
        //     response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
        //     return;
        // }

        if (!$token) {
            response()->json(['info' => 'FIELD_REQUIRED'], 400);
            return;
        }

        if (Auth::confirmAccountHash($token)) {
            response()->json(['info' => 'ACCOUNT_CONFIRMED_SUCCESSFULLY']);
        } else {
            response()->json(['info' => 'INVALID_TOKEN'], 400);
        }
    });

    app()->get('/confirm/change-password/{token}', function ($token) {
        // if (Auth::check()) {
        //     response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
        //     return;
        // }

        if (!$token) {
            response()->json(['info' => 'FIELD_REQUIRED'], 400);
            return;
        }

        if (Auth::confirmChangePassswordHash($token)) {
            response()->json(['info' => 'PASSWORD_CHANGED_SUCCESSFULLY']);
        } else {
            response()->json(['info' => 'INVALID_TOKEN'], 400);
        }
    });

    app()->post('/loginAsGuest', function () {
        if (Auth::check()) {
            response()->json(['info' => 'ALREADY_LOGGED_IN'], 400);
            return;
        }

        $data = request()->validate([
            'name' => 'any',
        ]);

        if (!$data['name']) {
            response()->json(['info' => 'USERNAME_REQUIRED'], 400);
            return;
        }

        $guestUsername = 'Guest_' . bin2hex(random_bytes(4)) . '_' . $data['name'];
        $sess = [
            'id' => null,
            'username' => $guestUsername,
            'email' => null,
        ];
        $_SESSION['user'] = $sess;
        response()->json(['info' => 'GUEST_LOGIN_SUCCESSFUL', 'user' => Auth::user()]);
    });
});

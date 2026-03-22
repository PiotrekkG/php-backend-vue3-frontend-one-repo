<?php

function parseRequestFiles($fileFormData)
{
    $returnFiles = [];
    $files = request()->files();

    function parseFile($fieldName, $file, $prefix, &$returnFiles, $maxSize = null)
    {
        if (!is_uploaded_file($file['tmp_name']))
            return false;

        if ($maxSize !== null && $file['size'] > $maxSize)
            return false;

        $newName = uniqid($prefix == '' ? '' : "{$prefix}_");
        $origName = $file['name'];

        $returnFiles[] = [
            'fieldName' => $fieldName,
            'newName' => $newName,
            'origName' => $origName,
            'tmpPath' => $file['tmp_name'],
            'currentPath' => $file['tmp_name'],
            'client_size' => $file['size'],
            'client_type' => $file['type'],
            'exists' => file_exists($file['tmp_name']),
            'server_size' => filesize($file['tmp_name']),
            'server_type' => mime_content_type($file['tmp_name']),
            // 'server_size' => filesize($newPath),
            // 'server_type' => mime_content_type($newPath),
            'moved' => false,
            'deleted' => false,
        ];
    }

    foreach ($fileFormData as $fieldValue) {
        if (gettype($fieldValue) == 'string') {
            $fieldValue = ['name' => $fieldValue, 'multiple' => false];
        }
        if(!($fieldValue['multiple'] ?? true) && key_exists("{$fieldValue['name']}", $files)) {
            $file = $files["{$fieldValue['name']}"];
            parseFile($fieldValue['name'], $file, key_exists("prefix", $fieldValue) ? $fieldValue['prefix'] : '', $returnFiles, key_exists('maxSize', $fieldValue) ? $fieldValue['maxSize'] : null);
        } else {
            for ($i = 0; key_exists("{$fieldValue['name']}_{$i}", $files); $i++) {
                $file = $files["{$fieldValue['name']}_{$i}"];
                parseFile($fieldValue['name'], $file, key_exists("prefix", $fieldValue) ? $fieldValue['prefix'] : '', $returnFiles, key_exists('maxSize', $fieldValue) ? $fieldValue['maxSize'] : null);

                if (!$fieldValue['multiple'])
                    break;
            }
        }
    }

    return $returnFiles;
}

function deleteRequestFiles(&$parsedFiles)
{
    $allSuccess = true;
    foreach ($parsedFiles as &$file) {
        if (!deleteRequestFile($file)) $allSuccess = false;
    }
    return $allSuccess;
}

function deleteRequestFile(&$file)
{
    if ($file['moved'])
        if (unlink($file['currentPath']))
            return $file['deleted'] = true;
    return false;
}

function moveRequestFiles(&$parsedFiles)
{
    $allSuccess = true;
    foreach ($parsedFiles as &$file) {
        if ($file['moved']) continue;
        if (!moveRequestFile($file)) $allSuccess = false;
    }
    return $allSuccess;
}

function getPath($filename)
{
    $uploadParameters = [
        'TYPE' => 'local',
        'UPLOADS_PATH' => __DIR__ . '/../uploads/',
        'DOMAIN' => null,
        'USERNAME' => null,
        'PASSWORD' => null,
    ];
    // $uploadParameters = getParametersValues('FILE_STORAGE_ACCESS', ['TYPE', 'UPLOADS_PATH', 'DOMAIN', 'USERNAME', 'PASSWORD']);

    if ($uploadParameters['TYPE'] === 'local') {
        $path = $uploadParameters['UPLOADS_PATH'];
        if ($path !== null && is_dir($path) && is_readable($path)) {
            return $path . '/' . $filename;
        }
        return __DIR__ . '/../../uploads/' . $filename;
    } elseif ($uploadParameters['TYPE'] === 'samba') {
        if ($uploadParameters['UPLOADS_PATH'] === null || $uploadParameters['DOMAIN'] === null || $uploadParameters['USERNAME'] === null || $uploadParameters['PASSWORD'] === null) {
            response()->exit(['message' => 'File storage access not configured']);
        }

        $smbPath = sambaCredentials(
            $uploadParameters['UPLOADS_PATH'] . $filename,
            $uploadParameters['DOMAIN'] ? $uploadParameters['DOMAIN'] : null,
            $uploadParameters['USERNAME'] ? $uploadParameters['USERNAME'] : null,
            $uploadParameters['PASSWORD'] ? $uploadParameters['PASSWORD'] : null
        );

        return $smbPath;
    } else {
        response()->exit(['message' => 'File storage access not configured']);
    }
}

function sambaCredentials($path, $domain = null, $user = null, $password = null)
{
    $credentials = '';
    if ($user) {
        if ($domain)
            $credentials = "$domain;$user";
        else
            $credentials = $user;

        if ($password)
            $credentials = "$credentials:$password";

        $credentials .= '@';
    }

    $directory = "smb://{$credentials}{$path}";

    return $directory;
}

function moveRequestFile(&$file)
{
    if ($file['moved']) return false;

    $smbPath = getPath($file['newName']);

    $currentPath = $smbPath;
    if (move_uploaded_file($file['tmpPath'], $smbPath) !== false && file_exists($currentPath)) {
        $file['moved'] = true;
        $file['currentPath'] = $currentPath;
        $file['exists'] = file_exists($file['currentPath']);
        return true;

        // if(file_put_contents($smbPath, file_get_contents($currentPath))){
        //     $file['currentPath'] = $smbPath;
        //     $file['moved'] = true;
        //     return true;
        // }
        // return false;
    }
    return false;
}

function deleteUploadedFile($uniq_filename)
{
    try {
        $smbPath = getPath($uniq_filename);

        if (@file_exists($smbPath)) {
            return unlink($smbPath);
        }
    } catch (\Throwable $th) {
    }

    return false;
}

function readUploadedFile($uniq_filename, $orig_filename = null, $download = false)
{
    try {
        $smbPath = getPath($uniq_filename);
        $orig_filename = $orig_filename !== null ? $orig_filename : @basename($smbPath);

        if (!@file_exists($smbPath))
            return false;

        if ($download) header('Content-Disposition: attachment; filename="' . $orig_filename . '"');
        else header('Content-Disposition: filename="' . $orig_filename . '"');
        header('Content-Type: ' . @mime_content_type($smbPath) . '');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . @filesize($smbPath));
        readfile($smbPath);
        return true;
    } catch (\Throwable $th) {
    }

    return false;
}

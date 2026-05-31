<?php
/**
 * Helpers for candidate profile pictures (default placeholder vs uploaded).
 */

function candidateProfilePictureRelativePath(?string $stored): ?string
{
    if ($stored === null || trim($stored) === '') {
        return null;
    }
    return ltrim($stored, '/');
}

function candidateProfilePictureUploadDir(): string
{
    return dirname(__DIR__, 2) . '/public/uploads/candidates';
}

function deleteCandidateProfilePictureFile(?string $stored): void
{
    $relative = candidateProfilePictureRelativePath($stored);
    if (!$relative) {
        return;
    }

    $full = dirname(__DIR__, 2) . '/public/' . $relative;
    if (is_file($full)) {
        @unlink($full);
    }
}

/**
 * @return array{success:bool,message?:string,path?:string}
 */
function saveCandidateProfilePictureUpload(PDO $db, int $userId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No photo uploaded or upload failed.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Photo must be 2 MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $map   = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($map[$mime])) {
        return ['success' => false, 'message' => 'Please upload a JPG, PNG, or WebP image.'];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'message' => 'Invalid image file.'];
    }

    $stmt = $db->prepare('SELECT id, profilePicture FROM candidateinfo WHERE userID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['success' => false, 'message' => 'Candidate profile not found.'];
    }

    $candId   = (int) $row['id'];
    $ext      = $map[$mime];
    $uploadDir = candidateProfilePictureUploadDir();

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['success' => false, 'message' => 'Could not create upload folder.'];
    }

    deleteCandidateProfilePictureFile($row['profilePicture'] ?? null);

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExt) {
        $old = $uploadDir . '/candidate_' . $candId . '.' . $oldExt;
        if (is_file($old)) {
            @unlink($old);
        }
    }

    $filename     = 'candidate_' . $candId . '.' . $ext;
    $fullPath     = $uploadDir . '/' . $filename;
    $relativePath = 'uploads/candidates/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['success' => false, 'message' => 'Failed to save profile photo.'];
    }

    $stmt = $db->prepare('UPDATE candidateinfo SET profilePicture = ? WHERE id = ?');
    $stmt->execute([$relativePath, $candId]);

    return ['success' => true, 'path' => $relativePath];
}

function removeCandidateProfilePicture(PDO $db, int $userId): bool
{
    $stmt = $db->prepare('SELECT id, profilePicture FROM candidateinfo WHERE userID = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return false;
    }

    deleteCandidateProfilePictureFile($row['profilePicture'] ?? null);

    $stmt = $db->prepare('UPDATE candidateinfo SET profilePicture = NULL WHERE id = ?');
    $stmt->execute([$row['id']]);

    return true;
}

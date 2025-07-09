<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class PhotoController {
    function getAvatarPath(): ?string
    {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadsDir = __DIR__ . '/../../assets/uploads/';
        $filename =  uniqid() . '_' . basename($_FILES['avatar']['name']);
        $destination = $uploadsDir . $filename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new RuntimeException('Save failed');
        }

        $this->validatePhotoExtension($destination);

        return '/uploads/' . $filename;
    }

    function updateAvatar(User $user): void
    {
        if (isset($data['remove_avatar']) && $data['remove_avatar'] === '1') {
            if ($user->getAvatarPath()) {
                $oldAvatarPath = __DIR__ . '/../../assets' . $user->getAvatarPath();
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
            $user->setAvatarPath("");
            unset($data['remove_avatar']);
            return;
        }

        $newAvatarPath = self::getAvatarPath();

        if ($newAvatarPath) {
            if ($user->getAvatarPath()) {
                $oldAvatarPath = __DIR__ . '/../../assets' . $user->getAvatarPath();
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
            $user->setAvatarPath($newAvatarPath);
        }
    }

    private function validatePhotoExtension(string $url): void
    {
        $mime_extension = mime_content_type($url);

        if ($mime_extension !== "image/png" and $mime_extension !== "image/jpeg" and $mime_extension !== "image/gif") {
            throw new InvalidArgumentException('Wrong file extension!' . $mime_extension);
        }
    }
}
<?php

namespace App\Services\Media;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaFileService
{
    public function storeUploadedFile(UploadedFile $file, ?User $uploadedBy = null, array $options = []): MediaFile
    {
        $disk = $options['disk'] ?? 'public';
        $mediaType = $options['media_type'] ?? $this->detectMediaType($file->getMimeType(), $file->extension());
        $directory = $options['directory'] ?? sprintf('media/%s/%s', $mediaType, now()->format('Y/m'));
        $collection = $options['collection'] ?? null;

        $extension = Str::lower($file->guessExtension() ?: $file->extension() ?: 'bin');
        $filename = Str::uuid()->toString().'.'.$extension;
        $storedPath = $file->storeAs($directory, $filename, $disk);
        $absolutePath = Storage::disk($disk)->path($storedPath);

        $width = null;
        $height = null;
        if (str_starts_with((string) $file->getMimeType(), 'image/') && is_file($absolutePath)) {
            $size = @getimagesize($absolutePath);
            if (is_array($size)) {
                $width = $size[0] ?? null;
                $height = $size[1] ?? null;
            }
        }

        return MediaFile::query()->create([
            'uploaded_by' => $uploadedBy?->id,
            'disk' => $disk,
            'directory' => trim($directory, '/'),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size_bytes' => $file->getSize() ?? 0,
            'media_type' => $mediaType,
            'collection' => $collection,
            'width' => $width,
            'height' => $height,
            'checksum' => is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null,
            'visibility' => $options['visibility'] ?? 'public',
            'status' => $options['status'] ?? 'active',
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    public function replaceUserProfileImage(User $user, UploadedFile $file, ?User $uploadedBy = null): MediaFile
    {
        $newMedia = $this->storeUploadedFile($file, $uploadedBy ?? $user, [
            'media_type' => 'image',
            'collection' => 'profile_images',
            'directory' => sprintf('media/images/users/%d', $user->id),
        ]);

        $oldMedia = $user->profileImage;

        $user->forceFill(['profile_image_id' => $newMedia->id])->save();

        if ($oldMedia && $oldMedia->id !== $newMedia->id) {
            $this->archiveMediaFile($oldMedia);
        }

        return $newMedia;
    }

    public function deleteMediaFile(MediaFile $mediaFile, bool $deletePhysicalFile = true): void
    {
        if ($deletePhysicalFile && Storage::disk($mediaFile->disk)->exists($mediaFile->path)) {
            Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        }

        $mediaFile->update(['status' => 'deleted']);
        $mediaFile->delete();
    }

    public function archiveMediaFile(MediaFile $mediaFile): void
    {
        $mediaFile->update(['status' => 'archived']);
    }

    private function detectMediaType(?string $mimeType, ?string $extension): string
    {
        $mimeType = Str::lower((string) $mimeType);
        $extension = Str::lower((string) $extension);

        if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wav', 'm4a', 'ogg'], true)) {
            return 'audio';
        }

        if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'webm'], true)) {
            return 'video';
        }

        if (in_array($extension, ['pdf', 'txt', 'doc', 'docx'], true)) {
            return 'document';
        }

        return 'file';
    }
}

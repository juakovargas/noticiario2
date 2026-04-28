<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaFileController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'media_type' => ['nullable', 'string', 'max:50'],
            'collection' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'uploaded_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $mediaFiles = MediaFile::query()
            ->with('uploadedBy:id,name')
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('original_name', 'like', "%{$search}%")
                        ->orWhere('filename', 'like', "%{$search}%")
                        ->orWhere('path', 'like', "%{$search}%");
                });
            })
            ->when($filters['media_type'] ?? null, fn ($query, $value) => $query->where('media_type', $value))
            ->when($filters['collection'] ?? null, fn ($query, $value) => $query->where('collection', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['uploaded_by'] ?? null, fn ($query, $value) => $query->where('uploaded_by', $value))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MediaFile $mediaFile) => [
                'id' => $mediaFile->id,
                'original_name' => $mediaFile->original_name,
                'filename' => $mediaFile->filename,
                'url' => $mediaFile->public_url,
                'mime_type' => $mediaFile->mime_type,
                'media_type' => $mediaFile->media_type,
                'size_bytes' => $mediaFile->size_bytes,
                'human_size' => $mediaFile->humanSize(),
                'collection' => $mediaFile->collection,
                'status' => $mediaFile->status,
                'uploaded_by' => $mediaFile->uploadedBy?->name,
                'uploaded_by_id' => $mediaFile->uploaded_by,
                'created_at' => $mediaFile->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/MediaFiles/Index', [
            'mediaFiles' => $mediaFiles,
            'filters' => $filters,
            'mediaTypes' => MediaFile::query()->select('media_type')->distinct()->orderBy('media_type')->pluck('media_type')->values(),
            'collections' => MediaFile::query()->whereNotNull('collection')->select('collection')->distinct()->orderBy('collection')->pluck('collection')->values(),
            'statuses' => MediaFile::query()->select('status')->distinct()->orderBy('status')->pluck('status')->values(),
            'uploaders' => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function show(MediaFile $mediaFile): Response
    {
        $mediaFile->load('uploadedBy:id,name,email');

        return Inertia::render('Admin/MediaFiles/Show', [
            'mediaFile' => [
                'id' => $mediaFile->id,
                'original_name' => $mediaFile->original_name,
                'filename' => $mediaFile->filename,
                'url' => $mediaFile->public_url,
                'disk' => $mediaFile->disk,
                'directory' => $mediaFile->directory,
                'path' => $mediaFile->path,
                'public_url' => $mediaFile->public_url,
                'file_exists' => filled($mediaFile->path) && filled($mediaFile->disk)
                    ? Storage::disk($mediaFile->disk)->exists($mediaFile->path)
                    : false,
                'storage_link_required' => $mediaFile->disk === 'public',
                'mime_type' => $mediaFile->mime_type,
                'extension' => $mediaFile->extension,
                'size_bytes' => $mediaFile->size_bytes,
                'human_size' => $mediaFile->humanSize(),
                'media_type' => $mediaFile->media_type,
                'collection' => $mediaFile->collection,
                'visibility' => $mediaFile->visibility,
                'status' => $mediaFile->status,
                'title' => $mediaFile->title,
                'alt_text' => $mediaFile->alt_text,
                'caption' => $mediaFile->caption,
                'description' => $mediaFile->description,
                'width' => $mediaFile->width,
                'height' => $mediaFile->height,
                'duration_seconds' => $mediaFile->duration_seconds,
                'checksum' => $mediaFile->checksum,
                'metadata' => $mediaFile->metadata,
                'uploaded_by' => $mediaFile->uploadedBy ? [
                    'id' => $mediaFile->uploadedBy->id,
                    'name' => $mediaFile->uploadedBy->name,
                    'email' => $mediaFile->uploadedBy->email,
                ] : null,
                'created_at' => $mediaFile->created_at?->toDateTimeString(),
                'updated_at' => $mediaFile->updated_at?->toDateTimeString(),
            ],
        ]);
    }

    public function update(Request $request, MediaFile $mediaFile): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,archived,deleted'],
        ]);

        $mediaFile->update($data);

        return to_route('admin.media-files.show', $mediaFile)->with('success', 'Media file updated successfully');
    }
}

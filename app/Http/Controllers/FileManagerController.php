<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ZipArchive;

class FileManagerController extends Controller
{
    public function index(Request $request)
    {
        $relativePath = $request->query('path', '');
        $search = $request->query('search', '');
        $sortBy = $request->query('sort', 'name');
        $sortOrder = $request->query('order', 'asc');

        $userRoot = auth()->id();
        $fullPath = $userRoot . '/' . ltrim($relativePath, '/');

        if (str_contains($relativePath, '..')) {
            abort(403);
        }

        $disk = Storage::disk('user-files');

        if (!$disk->exists($userRoot)) {
            $disk->makeDirectory($userRoot);
        }

        $directories = collect($disk->directories($fullPath))->map(function ($dir) use ($disk) {
            return [
                'name' => basename($dir),
                'type' => 'folder',
                'path' => $dir,
                'size' => null,
                'lastModified' => $disk->lastModified($dir),
            ];
        });

        $files = collect($disk->files($fullPath))->map(function ($file) use ($disk) {
            return [
                'name' => basename($file),
                'type' => 'file',
                'path' => $file,
                'size' => $disk->size($file),
                'lastModified' => $disk->lastModified($file),
            ];
        });

        $items = $directories->concat($files);

        if (!empty($search)) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'], $search) !== false;
            });
        }

        $items = $items->sortBy(function ($item) use ($sortBy) {
            switch ($sortBy) {
                case 'size':
                    return $item['size'] ?? 0;
                case 'date':
                    return $item['lastModified'];
                case 'name':
                default:
                    return strtolower($item['name']);
            }
        }, SORT_REGULAR, $sortOrder === 'desc');

        $items = $items->values();

        $breadcrumbs = $this->buildBreadcrumb($relativePath);

        return Inertia::render('FileManager/Index', [
            'currentPath' => $relativePath ?: '/',
            'items' => $items,
            'breadcrumbs' => $breadcrumbs,
            'search' => $search,
            'sort' => $sortBy,
            'order' => $sortOrder,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $relativePath = $request->input('path', '');
        $userRoot = auth()->id();
        $targetDir = $userRoot . '/' . ltrim($relativePath, '/');

        $file = $request->file('file');
        $file->storeAs($targetDir, $file->getClientOriginalName(), 'user-files');

        return back()->with('success', 'File berhasil diupload');
    }

    public function update(Request $request, string $encodedPath)
    {
        $request->validate([
            'new_name' => 'required|string|max:255',
        ]);

        $path = base64_decode($encodedPath);
        $this->authorizePath($path);

        $disk = Storage::disk('user-files');
        $dirname = dirname($path);
        $newPath = $dirname . '/' . $request->new_name;

        if ($disk->exists($newPath)) {
            return back()->withErrors(['new_name' => 'Nama sudah ada']);
        }

        $disk->move($path, $newPath);
        return back()->with('success', 'Berhasil diubah');
    }

    public function destroy(string $encodedPath)
    {
        $path = base64_decode($encodedPath);
        $this->authorizePath($path);

        $disk = Storage::disk('user-files');
        if ($disk->exists($path)) {
            if ($disk->directoryExists($path)) {
                $disk->deleteDirectory($path);
            } else {
                $disk->delete($path);
            }
        }

        return back()->with('success', 'Berhasil dihapus');
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $relativePath = $request->input('path', '');
        $userRoot = auth()->id();
        $targetDir = $userRoot . '/' . ltrim($relativePath, '/');
        $newFolder = $targetDir . '/' . $request->name;

        $disk = Storage::disk('user-files');
        if ($disk->exists($newFolder)) {
            return back()->withErrors(['name' => 'Folder sudah ada']);
        }

        $disk->makeDirectory($newFolder);
        return back()->with('success', 'Folder berhasil dibuat');
    }

    public function download(string $encodedPath)
    {
        $path = base64_decode($encodedPath);
        $this->authorizePath($path);

        $disk = Storage::disk('user-files');
        if (!$disk->exists($path) || $disk->directoryExists($path)) {
            abort(404);
        }

        return $disk->download($path);
    }

    public function preview(string $encodedPath)
    {
        $path = base64_decode($encodedPath);
        $this->authorizePath($path);

        $disk = Storage::disk('user-files');
        if (!$disk->exists($path) || $disk->directoryExists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path);
        $allowedImages = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($mime, $allowedImages)) {
            return response()->json([
                'type' => 'image',
                'url' => route('files.preview', $encodedPath),
            ]);
        }

        $allowedText = ['text/plain', 'text/html', 'text/css', 'text/javascript', 'application/json', 'application/xml', 'text/x-php', 'text/x-python', 'text/x-sh', 'application/x-yaml'];
        if (in_array($mime, $allowedText) || str_starts_with($mime, 'text/')) {
            $content = $disk->get($path);
            return response()->json([
                'type' => 'text',
                'content' => $content,
                'language' => $this->guessLanguage($path),
            ]);
        }

        abort(415, 'Preview tidak didukung untuk tipe file ini.');
    }

    private function guessLanguage(string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $map = [
            'js' => 'javascript',
            'ts' => 'typescript',
            'php' => 'php',
            'py' => 'python',
            'rb' => 'ruby',
            'java' => 'java',
            'html' => 'html',
            'css' => 'css',
            'json' => 'json',
            'xml' => 'xml',
            'yaml' => 'yaml',
            'yml' => 'yaml',
            'sh' => 'bash',
            'md' => 'markdown',
        ];
        return $map[$ext] ?? 'plaintext';
    }

    public function batchDelete(Request $request)
    {
        $paths = $request->input('paths', []);
        if (empty($paths)) {
            return back()->withErrors(['paths' => 'Tidak ada item yang dipilih']);
        }

        $disk = Storage::disk('user-files');
        foreach ($paths as $encodedPath) {
            $path = base64_decode($encodedPath);
            $this->authorizePath($path);
            if ($disk->exists($path)) {
                if ($disk->directoryExists($path)) {
                    $disk->deleteDirectory($path);
                } else {
                    $disk->delete($path);
                }
            }
        }

        return back()->with('success', count($paths) . ' item berhasil dihapus');
    }

    public function batchDownload(Request $request)
    {
        $paths = $request->input('paths', []);
        if (empty($paths)) {
            return back()->withErrors(['paths' => 'Tidak ada item yang dipilih']);
        }

        $disk = Storage::disk('user-files');
        $zip = new ZipArchive;
        $zipName = 'download-' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat file zip');
        }

        foreach ($paths as $encodedPath) {
            $path = base64_decode($encodedPath);
            $this->authorizePath($path);
            if (!$disk->exists($path)) continue;

            if ($disk->directoryExists($path)) {
                $files = $disk->allFiles($path);
                foreach ($files as $file) {
                    $relativePath = substr($file, strlen(auth()->id() . '/'));
                    $zip->addFromString($relativePath, $disk->get($file));
                }
            } else {
                $relativePath = substr($path, strlen(auth()->id() . '/'));
                $zip->addFromString($relativePath, $disk->get($path));
            }
        }

        $zip->close();
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function authorizePath(string $path): void
    {
        $userRoot = auth()->id();
        if (!str_starts_with($path, $userRoot . '/') && $path !== $userRoot) {
            abort(403);
        }
    }

    private function buildBreadcrumb(string $relativePath): array
    {
        if (empty($relativePath)) {
            return [['label' => 'Root', 'path' => '']];
        }

        $parts = explode('/', trim($relativePath, '/'));
        $breadcrumbs = [['label' => 'Root', 'path' => '']];
        $current = '';
        foreach ($parts as $part) {
            $current .= '/' . $part;
            $breadcrumbs[] = [
                'label' => $part,
                'path' => ltrim($current, '/'),
            ];
        }
        return $breadcrumbs;
    }
}

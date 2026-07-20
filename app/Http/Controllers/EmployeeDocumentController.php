<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeDocumentController extends Controller
{
    /**
     * Upload a document for an employee.
     */
    public function upload(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'collection' => 'required|in:documents,certificates,identification',
        ]);

        $file = $request->file('document');
        $media = $employee->addMedia($file)
            ->toMediaCollection($request->collection);

        Log::info('Document uploaded', [
            'employee_id' => $employee->id,
            'media_id' => $media->id,
            'collection' => $request->collection,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
            ],
        ]);
    }

    /**
     * Download a document.
     */
    public function download(Employee $employee, $mediaId)
    {
        $this->authorize('view', $employee);

        $media = $employee->media()->findOrFail($mediaId);

        Log::info('Document downloaded', [
            'employee_id' => $employee->id,
            'media_id' => $media->id,
            'user_id' => auth()->id(),
        ]);

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Delete a document.
     */
    public function delete(Employee $employee, $mediaId)
    {
        $this->authorize('update', $employee);

        $media = $employee->media()->findOrFail($mediaId);
        $media->delete();

        Log::info('Document deleted', [
            'employee_id' => $employee->id,
            'media_id' => $mediaId,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    /**
     * List documents for an employee.
     */
    public function list(Employee $employee)
    {
        $this->authorize('view', $employee);

        $documents = $employee->media()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'collection' => $media->collection_name,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'created_at' => $media->created_at->toDateTimeString(),
            ]);

        return view('employees.documents', [
            'employee' => $employee,
            'documents' => $documents,
        ]);
    }
}

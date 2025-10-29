<?php

namespace Modules\Bar\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OneSignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Bar\Models\Bar;

class BarController extends Controller
{
    protected $oneSignalService;

    public function __construct(OneSignalService $oneSignalService)
    {
        $this->oneSignalService = $oneSignalService;
    }

    public function index(Request $request)
    {
        $query = Bar::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        $page = $request->get('page', 1);
        $bars = $query->paginate(null, ['*'], 'page', $page)->withPath($request->url());

        return $this->respondOk($bars, 'Bars retrieved successfully');
    }

    public function show($id)
    {
        $bar = Bar::find($id);

        if (!$bar) {
            return $this->respondNotFound(null, 'Bar not found');
        }

        return $this->respondOk($bar);
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'link'  => 'nullable|string|url'
        ])->validate();

        if ($request->file('image')) {
            $file = $request->file('image');
            $data['image'] = $this->uploadToSpaces(
                $file, 
                'bars', 
                '', 
                'Bar_' . time() . '.' . $file->getClientOriginalExtension()
            );
        } else {
            $data['image'] = null;
        }

        $bar = Bar::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image' => $data['image'],
            'link'  => $validated['link'] ?? null,
        ]);       

        $title = "📢 Announcement";
        $body  = "{$validated['title']} is now available. Don't miss it!";
        
        $this->oneSignalService->sendNotificationToAll($title, $body, null,null);
        return $this->respondCreated($bar);
    }

    public function update(Request $request, $id)
    {
        $bar = Bar::find($id);
        if (!$bar) {
            return $this->respondNotFound(null, 'Bar not found');
        }

        $validated = Validator::make($request->all(), [
            'title'  => 'nullable|string',
            'description'  => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            'link'  => 'nullable|string|url'
        ])->validate();

        if ($request->file('image')) {
            if ($bar->image) {
                $this->deleteFromSpaces($bar->image);
            }

            $file = $request->file('image');
            $validated['image'] = $this->uploadToSpaces(
                $file, 
                'bars', 
               '',
                'Bar_' . time() . '.' . $file->getClientOriginalExtension()
            );
        }

        $bar->update($validated);

        return $this->respondOk($bar);
    }

    public function destroy($id)
    {
        $bar = Bar::find($id);
        if (!$bar) {
            return $this->respondNotFound(null, 'Bar not found');
        }
        
        if ($bar->image) {
            $this->deleteFromSpaces($bar->image);
        }
        $bar->delete();
        return $this->respondOk(null, 'Bar deleted successfully.');
    }

    public function showArchiveRecords(Request $request)
    {
        $query = Bar::onlyTrashed();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('body', 'like', "%{$search}%");
        }

        $bars = $query->paginate(10);

       return $this->respondOk($bars,'Archived bars retrieved successfully');
    }

    public function restoreArchiveRecords(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return $this->respondNotFound(null, 'No IDs provided');
        }
        Bar::restoreArchive($ids);
        return $this->respondOk(null,'Records restored successfully');
    }
}

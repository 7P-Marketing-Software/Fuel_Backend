<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DraftController extends Controller
{
    public function index(Request $request)
    {
        $query = Draft::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        $drafts = $query->paginate();
        return $this->respondOk($drafts);
    }



    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['course', 'module', 'folder', 'bundle', 'task', 'live', 'video'])],
            'data' => ['required', 'array'],
        ]);

        $draft = Draft::create([
            'type' => $request->type,
            'data' => $request->data,
        ]);
        return $this->respondCreated($draft);
    }

    public function show($id)
    {
        $draft = Draft::find($id);

        if (!$draft) {
            return $this->respondNotFound(null, 'Draft not found');
        }

        return $this->respondOk($draft);
    }

    public function update(Request $request, $id)
    {
        $draft = Draft::find($id);

        if (!$draft) {
            return $this->respondNotFound(null, 'Draft not found');
        }
        $request->validate([
            'type' => ['required', 'string', Rule::in(['course', 'module', 'folder', 'bundle', 'task', 'live', 'video'])],
            'data' => ['nullable', 'array'],
        ]);

        $draft->update($request->only(['type', 'data']));
        $draft->save();
        return $this->respondOk($draft);
    }

    public function destroy($id)
    {
        $draft = Draft::find($id);

        if (!$draft) {
            return $this->respondNotFound(null, 'Draft not found');
        }

        $draft->delete();

        return $this->respondOk(null, 'Draft deleted successfully');
    }
}

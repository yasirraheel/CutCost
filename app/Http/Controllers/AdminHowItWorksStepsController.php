<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\HowItWorksStep;
use Illuminate\Http\Request;

class AdminHowItWorksStepsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $steps = HowItWorksStep::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.how-it-works.index', compact('steps'))->render(), 'How it works', '/en/admin/how-it-works');
    }

    public function create()
    {
        $step = new HowItWorksStep(['is_active' => true]);

        return $this->adminShell(view('admin.how-it-works.create', compact('step'))->render(), 'New step', '/en/admin/how-it-works');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        HowItWorksStep::create($this->attributes($request, $validated));

        return redirect()->route('admin.how-it-works.index')->with('status', 'Step has been created.');
    }

    public function edit(HowItWorksStep $step)
    {
        return $this->adminShell(view('admin.how-it-works.edit', compact('step'))->render(), 'Edit step', '/en/admin/how-it-works');
    }

    public function update(Request $request, HowItWorksStep $step)
    {
        $validated = $request->validate($this->rules());

        $step->fill($this->attributes($request, $validated));
        $step->save();

        return redirect()->route('admin.how-it-works.edit', $step)->with('status', 'Step has been updated.');
    }

    public function destroy(HowItWorksStep $step)
    {
        $step->delete();

        return redirect()->route('admin.how-it-works.index')->with('status', 'Step has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'icon_svg' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'title' => $validated['title'],
            'text' => $validated['text'] ?? null,
            'icon_svg' => $validated['icon_svg'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

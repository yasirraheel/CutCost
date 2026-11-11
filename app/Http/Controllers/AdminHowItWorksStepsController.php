<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\HowItWorksStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $step = new HowItWorksStep(['is_active' => true, 'audience' => 'share']);
        $audiences = $this->audiences();

        return $this->adminShell(view('admin.how-it-works.create', compact('step', 'audiences'))->render(), 'New step', '/en/admin/how-it-works');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $step = new HowItWorksStep($this->attributes($request, $validated));
        if ($request->hasFile('mockup')) {
            $step->mockup_image_path = $request->file('mockup')->store('how-it-works', 'public');
        }
        $step->save();

        return redirect()->route('admin.how-it-works.index')->with('status', 'Step has been created.');
    }

    public function edit(HowItWorksStep $step)
    {
        $audiences = $this->audiences();

        return $this->adminShell(view('admin.how-it-works.edit', compact('step', 'audiences'))->render(), 'Edit step', '/en/admin/how-it-works');
    }

    public function update(Request $request, HowItWorksStep $step)
    {
        $validated = $request->validate($this->rules($step));

        $oldMockupPath = $step->mockup_image_path;
        $step->fill($this->attributes($request, $validated));
        if ($request->hasFile('mockup')) {
            if ($oldMockupPath) {
                Storage::disk('public')->delete($oldMockupPath);
            }
            $step->mockup_image_path = $request->file('mockup')->store('how-it-works', 'public');
        }
        $step->save();

        return redirect()->route('admin.how-it-works.edit', $step)->with('status', 'Step has been updated.');
    }

    public function destroy(HowItWorksStep $step)
    {
        if ($step->mockup_image_path) {
            Storage::disk('public')->delete($step->mockup_image_path);
        }
        $step->delete();

        return redirect()->route('admin.how-it-works.index')->with('status', 'Step has been deleted.');
    }

    /** Audiences the app defines for a step, value => label. */
    protected function audiences(): array
    {
        return [
            'share' => 'I want to share',
            'seat' => 'I want a seat',
        ];
    }

    /** @return array<string, mixed> */
    protected function rules(?HowItWorksStep $step = null): array
    {
        // Dropdown options are the app-defined audiences, plus the
        // step's own stored value when it is no longer in the set, so
        // an old row can still be saved without silently resetting it.
        $audiences = array_keys($this->audiences());
        if ($step !== null && filled($step->audience)) {
            $audiences[] = $step->audience;
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string'],
            'icon_svg' => ['nullable', 'string'],
            'audience' => ['nullable', 'string', Rule::in($audiences)],
            'mockup' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
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
            'audience' => $validated['audience'] ?? 'share',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

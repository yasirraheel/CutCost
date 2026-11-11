<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminTestimonialsController extends Controller
{
    use RendersAdminShell;

    public function index()
    {
        $testimonials = Testimonial::orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.testimonials.index', compact('testimonials'))->render(), 'Testimonials', '/en/admin/testimonials');
    }

    public function create()
    {
        $testimonial = new Testimonial(['is_active' => true]);

        return $this->adminShell(view('admin.testimonials.create', compact('testimonial'))->render(), 'New testimonial', '/en/admin/testimonials');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $testimonial = new Testimonial($this->attributes($request, $validated));
        if ($request->hasFile('avatar')) {
            $testimonial->avatar_path = $request->file('avatar')->store('testimonials', 'public');
        }
        $testimonial->save();

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial has been created.');
    }

    public function edit(Testimonial $testimonial)
    {
        return $this->adminShell(view('admin.testimonials.edit', compact('testimonial'))->render(), 'Edit testimonial', '/en/admin/testimonials');
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar_path) {
                Storage::disk('public')->delete($testimonial->avatar_path);
            }
            $testimonial->avatar_path = $request->file('avatar')->store('testimonials', 'public');
        }
        $testimonial->fill($this->attributes($request, $validated));
        $testimonial->save();

        return redirect()->route('admin.testimonials.edit', $testimonial)->with('status', 'Testimonial has been updated.');
    }

    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->avatar_path) {
            Storage::disk('public')->delete($testimonial->avatar_path);
        }
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'role_text' => ['nullable', 'string', 'max:255'],
            'quote' => ['required', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'author_name' => $validated['author_name'],
            'role_text' => $validated['role_text'] ?? null,
            'quote' => $validated['quote'],
            'rating' => $validated['rating'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersAdminShell;
use App\Models\Brand;
use App\Models\Currency;
use App\Models\Template;
use Illuminate\Http\Request;

class AdminTemplatesController extends Controller
{
    use RendersAdminShell;

    /**
     * Frequency values are the same set the subscription wizard uses
     * (CreateSubscriptionController::FREQUENCY_LABELS): 1 weekly, 2 monthly,
     * 3 quarterly, 4 yearly. The labels are the wizard's own period labels.
     *
     * @var array<int, string>
     */
    private const FREQUENCY_OPTIONS = [1 => '/week', 2 => '/month', 3 => '/quarter', 4 => '/year'];

    /**
     * Credentials type values are the ones the subscription wizard handles
     * (additional step and publish validation): 0 none, 1 login and password,
     * 2 email and password, 3 invite link, 4 invite link and postal address,
     * 6 email and password for invitation only subscriptions. The labels
     * describe what the wizard asks the owner for at each type.
     *
     * @var array<int, string>
     */
    private const CREDENTIAL_TYPE_OPTIONS = [
        0 => 'No credentials needed',
        1 => 'Login and password',
        2 => 'Email and password',
        3 => 'Invite link',
        4 => 'Invite link and postal address',
        6 => 'Email and password (invitation only)',
    ];

    /**
     * Cost sharing values are the only ones the wizard price math branches
     * on (eachPays): 1 splits across all seats, 2 splits between the owner
     * and the subscribers, 3 has the subscribers pay the full price.
     *
     * @var array<int, string>
     */
    private const COST_SHARING_OPTIONS = [
        1 => 'Split across all seats',
        2 => 'Split between owner and subscribers',
        3 => 'Subscribers pay the full price',
    ];

    public function index()
    {
        $templates = Template::with('brand')->orderBy('sort_order')->latest()->paginate(20);

        return $this->adminShell(view('admin.templates.index', compact('templates'))->render(), 'Templates', '/en/admin/templates');
    }

    public function create()
    {
        $template = new Template(['currency' => Currency::defaultCode(), 'sort_order' => 0, 'is_active' => true]);
        $brands = Brand::orderBy('name')->get();
        $currencies = Currency::ordered()->get();
        $credentialTypes = self::CREDENTIAL_TYPE_OPTIONS;
        $frequencies = self::FREQUENCY_OPTIONS;
        $costSharingOptions = self::COST_SHARING_OPTIONS;

        return $this->adminShell(view('admin.templates.create', compact('template', 'brands', 'currencies', 'credentialTypes', 'frequencies', 'costSharingOptions'))->render(), 'New template', '/en/admin/templates');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $template = new Template($this->attributes($request, $validated));
        $template->save();

        return redirect()->route('admin.templates.index')->with('status', 'Template has been created.');
    }

    public function edit(Template $template)
    {
        $brands = Brand::orderBy('name')->get();
        $currencies = Currency::ordered()->get();
        $credentialTypes = self::CREDENTIAL_TYPE_OPTIONS;
        $frequencies = self::FREQUENCY_OPTIONS;
        $costSharingOptions = self::COST_SHARING_OPTIONS;

        return $this->adminShell(view('admin.templates.edit', compact('template', 'brands', 'currencies', 'credentialTypes', 'frequencies', 'costSharingOptions'))->render(), 'Edit template', '/en/admin/templates');
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate($this->rules());

        $template->fill($this->attributes($request, $validated));
        $template->save();

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template has been updated.');
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()->route('admin.templates.index')->with('status', 'Template has been deleted.');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'seats' => ['nullable', 'integer', 'min:0'],
            'shareable_seats' => ['nullable', 'integer', 'min:0'],
            'available_seats' => ['nullable', 'integer', 'min:0'],
            'price_with_fees' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'credentials_type' => ['nullable', 'integer', 'in:0,1,2,3,4,5,6'],
            'frequency' => ['nullable', 'integer', 'in:1,2,3,4'],
            'cost_sharing' => ['nullable', 'integer', 'in:1,2,3'],
            'description' => ['nullable', 'string'],
            'description_seo' => ['nullable', 'string'],
            'title_seo' => ['nullable', 'string', 'max:255'],
            'helper_create' => ['nullable', 'string'],
            'helper_join' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $validated @return array<string, mixed> */
    protected function attributes(Request $request, array $validated): array
    {
        return [
            'brand_id' => $validated['brand_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'seats' => $validated['seats'] ?? null,
            'shareable_seats' => $validated['shareable_seats'] ?? null,
            'available_seats' => $validated['available_seats'] ?? null,
            'price_with_fees' => $validated['price_with_fees'],
            'retail_price' => $validated['retail_price'] ?? null,
            'currency' => strtoupper($validated['currency']),
            'credentials_type' => $validated['credentials_type'] ?? 0,
            'frequency' => $validated['frequency'] ?? 2,
            'cost_sharing' => $validated['cost_sharing'] ?? 1,
            'description' => $validated['description'] ?? null,
            'description_seo' => $validated['description_seo'] ?? null,
            'title_seo' => $validated['title_seo'] ?? null,
            'helper_create' => $validated['helper_create'] ?? null,
            'helper_join' => $validated['helper_join'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}

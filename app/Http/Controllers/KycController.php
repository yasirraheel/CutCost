<?php

namespace App\Http\Controllers;

use App\Models\KycSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function show(): \Symfony\Component\HttpFoundation\Response
    {
        return app(CapturedPageController::class)->identityPage();
    }

    public function submit(Request $request)
    {
        $existing = $request->user()->kycSubmission;

        if ($existing && $existing->status === KycSubmission::STATUS_APPROVED) {
            return back()->withErrors(['kyc' => 'Your identity is already approved.']);
        }

        if ($existing && $existing->status === KycSubmission::STATUS_UNDER_REVIEW) {
            return back()->withErrors(['kyc' => 'Your identity documents are already under review.']);
        }

        $validated = $request->validate([
            'cnic_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'cnic_back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'cnic_front.required' => 'Please upload the front of your CNIC.',
            'cnic_back.required' => 'Please upload the back of your CNIC.',
            'cnic_front.max' => 'The CNIC front file must be 5 MB or smaller.',
            'cnic_back.max' => 'The CNIC back file must be 5 MB or smaller.',
        ]);

        if ($existing) {
            Storage::disk('local')->delete([$existing->front_path, $existing->back_path]);
        }

        $directory = 'kyc/'.$request->user()->id;
        $frontPath = $validated['cnic_front']->store($directory, 'local');
        $backPath = $validated['cnic_back']->store($directory, 'local');

        $request->user()->kycSubmission()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'front_path' => $frontPath,
                'back_path' => $backPath,
                'status' => KycSubmission::STATUS_UNDER_REVIEW,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'submitted_at' => now(),
            ],
        );

        return redirect('/en/my-account/identity');
    }

    public function adminIndex()
    {
        $submissions = KycSubmission::with(['user', 'reviewer'])
            ->latest('submitted_at')
            ->get();

        return view('admin.kyc', compact('submissions'));
    }

    public function approve(Request $request, KycSubmission $submission)
    {
        $submission->forceFill([
            'status' => KycSubmission::STATUS_APPROVED,
            'admin_note' => null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Identity approved successfully.');
    }

    public function reject(Request $request, KycSubmission $submission)
    {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:1000'],
        ]);

        $submission->forceFill([
            'status' => KycSubmission::STATUS_REJECTED,
            'admin_note' => $validated['admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Identity marked as rejected.');
    }

    public function download(KycSubmission $submission, string $side)
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);

        $path = $side === 'front' ? $submission->front_path : $submission->back_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}

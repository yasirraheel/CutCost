<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CutCost Admin - KYC review</title>
    <link href="/css/app.css?id=d5e0988" rel="stylesheet">
    <style>
        body { background: #f4f6fb; color: #112e45; }
        .admin-shell { min-height: 100vh; }
        .admin-header { background: linear-gradient(110deg, #0d5360, #123d68 58%, #0b4a9a); padding: 28px 0 42px; }
        .admin-logo { width: 223px; height: 40px; }
        .admin-card { background: #fff; border-radius: 14px; box-shadow: 0 10px 30px rgba(17, 46, 69, .08); margin-top: -22px; padding: 28px; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { border-bottom: 1px solid #e5eaf1; padding: 14px 10px; text-align: left; vertical-align: top; }
        .admin-table th { color: #5f7184; font-size: 13px; text-transform: uppercase; }
        .status { border-radius: 999px; display: inline-block; font-size: 12px; font-weight: 700; padding: 5px 10px; }
        .status-under_review { background: #fff1c2; color: #795900; }
        .status-approved { background: #d5f8ea; color: #08794f; }
        .status-rejected { background: #ffe0e0; color: #a32929; }
        .status-not_submitted { background: #edf1f5; color: #586b7c; }
        .admin-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .admin-actions form { margin: 0; }
        .admin-actions input { border: 1px solid #dbe3ed; border-radius: 6px; padding: 7px; width: 180px; }
        .admin-actions button, .admin-actions a { border: 0; border-radius: 7px; cursor: pointer; display: inline-block; font-size: 13px; font-weight: 700; padding: 8px 12px; text-decoration: none; }
        .approve { background: #00e497; color: #073d32; }
        .reject { background: #ffe0e0; color: #a32929; }
        .download { background: #e8f0ff; color: #016df4; }
        @media (max-width: 800px) { .admin-card { overflow-x: auto; padding: 16px; } .admin-table { min-width: 760px; } }
    </style>
</head>
<body>
<div class="admin-shell">
    <header class="admin-header">
        <div class="container-fluid" style="max-width: 1200px; margin: 0 auto;">
            <img class="admin-logo" src="/img/cutcost-logo-white.svg" alt="CutCost">
            <h1 style="color:#fff; margin:26px 0 0;">KYC review</h1>
        </div>
    </header>
    <main class="container-fluid" style="max-width: 1200px; margin: 0 auto;">
        <section class="admin-card">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            <p>Review submitted CNIC documents and decide whether each user is approved.</p>
            <table class="admin-table">
                <thead>
                <tr><th>User</th><th>Status</th><th>Submitted</th><th>Documents and decision</th></tr>
                </thead>
                <tbody>
                @forelse ($submissions as $submission)
                    <tr>
                        <td><strong>{{ $submission->user->name }}</strong><br>{{ $submission->user->email }}</td>
                        <td><span class="status status-{{ $submission->status }}">{{ str_replace('_', ' ', ucfirst($submission->status)) }}</span><br>{{ $submission->admin_note }}</td>
                        <td>{{ optional($submission->submitted_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="admin-actions">
                                <a class="download" href="{{ route('admin.kyc.download', [$submission, 'front']) }}">Front</a>
                                <a class="download" href="{{ route('admin.kyc.download', [$submission, 'back']) }}">Back</a>
                                @if ($submission->status !== 'approved')
                                    <form method="POST" action="{{ route('admin.kyc.approve', $submission) }}">@csrf<button class="approve" type="submit">Approve</button></form>
                                @endif
                                @if ($submission->status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.kyc.reject', $submission) }}">
                                        @csrf
                                        <input type="text" name="admin_note" placeholder="Reason if rejecting" required>
                                        <button class="reject" type="submit">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No KYC submissions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>

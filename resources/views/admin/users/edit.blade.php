<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CutCost Admin - Edit user</title>
    <link href="/css/app.css?id=d5e0988" rel="stylesheet">
</head>
<body>
<main class="container-fluid dashboard-container">
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-12 col-lg-8">
                <h1 class="dashboard-header-title">Admin panel · <span class="text-green">Edit user</span></h1>
            </div>
            <div class="col-12 col-lg-4 text-lg-right">
                <a class="btn-sharesub green" href="{{ route('admin.users') }}">Back to users</a>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="card-sharesub p-4">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-12 col-md-6 form-group">
                    <label for="first_name">First name</label>
                    <input class="input-sharesub" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="last_name">Last name</label>
                    <input class="input-sharesub" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="username">Username</label>
                    <input class="input-sharesub" id="username" name="username" value="{{ old('username', $user->username) }}">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="email">Email</label>
                    <input class="input-sharesub" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="role">Role</label>
                    <select class="input-sharesub" id="role" name="role" required>
                        <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="birthdate">Birthdate</label>
                    <input class="input-sharesub" id="birthdate" name="birthdate" type="date" value="{{ old('birthdate', optional($user->birthdate)->format('Y-m-d')) }}">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="password">New password <span class="text-muted">(optional)</span></label>
                    <input class="input-sharesub" id="password" name="password" type="password" minlength="8" autocomplete="new-password">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="language">Language</label>
                    <input class="input-sharesub" id="language" name="language" value="{{ old('language', $user->language ?: 'en') }}" required>
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="phone">Phone</label>
                    <input class="input-sharesub" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="intl_phone">International phone</label>
                    <input class="input-sharesub" id="intl_phone" name="intl_phone" value="{{ old('intl_phone', $user->intl_phone) }}">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="country">Country</label>
                    <input class="input-sharesub" id="country" name="country" maxlength="2" value="{{ old('country', $user->country) }}">
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="referral_code">Referral code</label>
                    <input class="input-sharesub" id="referral_code" name="referral_code" value="{{ old('referral_code', $user->referral_code) }}">
                </div>
                <div class="col-12 form-group">
                    <label for="address1">Address</label>
                    <input class="input-sharesub" id="address1" name="address1" value="{{ old('address1', $user->address1) }}">
                </div>
                <div class="col-12 form-group">
                    <label for="address2">Address line 2</label>
                    <input class="input-sharesub" id="address2" name="address2" value="{{ old('address2', $user->address2) }}">
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="city">City</label>
                    <input class="input-sharesub" id="city" name="city" value="{{ old('city', $user->city) }}">
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="state">State</label>
                    <input class="input-sharesub" id="state" name="state" value="{{ old('state', $user->state) }}">
                </div>
                <div class="col-12 col-md-4 form-group">
                    <label for="zip">Postal code</label>
                    <input class="input-sharesub" id="zip" name="zip" value="{{ old('zip', $user->zip) }}">
                </div>
            </div>

            <div class="form-group mt-3">
                <div class="filter-toggle justify-content-start">
                    <div class="filter-toggle-wrapper">
                        <input type="checkbox" id="payout_low_amount" name="payout_low_amount" value="1" class="checkbox-sharesub" @checked(old('payout_low_amount', $user->payout_low_amount))>
                        <label for="payout_low_amount" class="label-sharesub">Toggle</label>
                    </div>
                    <div class="filter-toggle-text">Allow transfers below the free threshold</div>
                </div>
            </div>
            <div class="form-group">
                <div class="filter-toggle justify-content-start">
                    <div class="filter-toggle-wrapper">
                        <input type="checkbox" id="payout_convert_currency" name="payout_convert_currency" value="1" class="checkbox-sharesub" @checked(old('payout_convert_currency', $user->payout_convert_currency))>
                        <label for="payout_convert_currency" class="label-sharesub">Toggle</label>
                    </div>
                    <div class="filter-toggle-text">Automatically convert transfer currency</div>
                </div>
            </div>
            <button type="submit" class="btn-sharesub bleu mx-auto my-2 d-block">Save user</button>
        </form>
    </section>
</main>
</body>
</html>
